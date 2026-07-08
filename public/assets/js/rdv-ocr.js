/**
 * RDV — Pipeline de OCR com fallback automático (Strategy Pattern).
 *
 * Ordem de tentativas:
 *   1) Tesseract.js (100% client-side, sem custo/chave) — 2 variantes de
 *      pré-processamento de imagem, usa a de maior confiança.
 *   2) Fallback no servidor: OCR.space -> OpenAI Vision (opcionais, conforme
 *      variáveis de ambiente configuradas em .env — ver RdvController::processarOcr).
 *
 * Só reporta falha ao usuário quando TODOS os mecanismos falharem.
 */
(function (global) {
  'use strict';

  // ===========================================================================
  // Pré-processamento de imagem (Canvas 2D)
  // ===========================================================================
  const ImagePreprocessor = {
    async fileToCanvas(file) {
      let bitmap;
      try {
        bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
      } catch (e) {
        bitmap = await this._loadImageFallback(file);
      }
      const canvas = document.createElement('canvas');
      canvas.width = bitmap.width;
      canvas.height = bitmap.height;
      canvas.getContext('2d').drawImage(bitmap, 0, 0);
      return canvas;
    },

    _loadImageFallback(file) {
      return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = url;
      });
    },

    copyCanvas(canvas) {
      const out = document.createElement('canvas');
      out.width = canvas.width;
      out.height = canvas.height;
      out.getContext('2d').drawImage(canvas, 0, 0);
      return out;
    },

    // Redimensiona para uma resolução equivalente a ~300 DPI para recibos comuns.
    resize(canvas, targetLongSide = 1900) {
      const longSide = Math.max(canvas.width, canvas.height);
      if (longSide >= targetLongSide || longSide === 0) return canvas;
      const scale = targetLongSide / longSide;
      const out = document.createElement('canvas');
      out.width = Math.round(canvas.width * scale);
      out.height = Math.round(canvas.height * scale);
      out.getContext('2d').drawImage(canvas, 0, 0, out.width, out.height);
      return out;
    },

    grayscale(canvas) {
      const ctx = canvas.getContext('2d');
      const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const d = img.data;
      for (let i = 0; i < d.length; i += 4) {
        const g = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
        d[i] = d[i + 1] = d[i + 2] = g;
      }
      ctx.putImageData(img, 0, 0);
      return canvas;
    },

    // Remoção de ruído — box blur separável (2 passes 1D, mais rápido que 2D).
    denoise(canvas) {
      const ctx = canvas.getContext('2d');
      const w = canvas.width, h = canvas.height;
      const img = ctx.getImageData(0, 0, w, h);
      const src = img.data;
      const tmp = new Float32Array(w * h);
      for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
          let sum = 0, count = 0;
          for (let k = -1; k <= 1; k++) {
            const xx = x + k;
            if (xx < 0 || xx >= w) continue;
            sum += src[(y * w + xx) * 4]; count++;
          }
          tmp[y * w + x] = sum / count;
        }
      }
      for (let x = 0; x < w; x++) {
        for (let y = 0; y < h; y++) {
          let sum = 0, count = 0;
          for (let k = -1; k <= 1; k++) {
            const yy = y + k;
            if (yy < 0 || yy >= h) continue;
            sum += tmp[yy * w + x]; count++;
          }
          const v = sum / count;
          const o = (y * w + x) * 4;
          src[o] = src[o + 1] = src[o + 2] = v;
        }
      }
      ctx.putImageData(img, 0, 0);
      return canvas;
    },

    // Contraste automático — alongamento linear min-max.
    autoContrast(canvas) {
      const ctx = canvas.getContext('2d');
      const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const d = img.data;
      let min = 255, max = 0;
      for (let i = 0; i < d.length; i += 4) {
        if (d[i] < min) min = d[i];
        if (d[i] > max) max = d[i];
      }
      const range = (max - min) || 1;
      for (let i = 0; i < d.length; i += 4) {
        const v = ((d[i] - min) / range) * 255;
        d[i] = d[i + 1] = d[i + 2] = v;
      }
      ctx.putImageData(img, 0, 0);
      return canvas;
    },

    // Equalização de histograma (distinta do contraste automático linear).
    equalize(canvas) {
      const ctx = canvas.getContext('2d');
      const w = canvas.width, h = canvas.height;
      const img = ctx.getImageData(0, 0, w, h);
      const d = img.data;
      const hist = new Array(256).fill(0);
      for (let i = 0; i < d.length; i += 4) hist[d[i] | 0]++;
      const total = w * h;
      const cdf = new Array(256).fill(0);
      let cum = 0;
      for (let i = 0; i < 256; i++) { cum += hist[i]; cdf[i] = cum; }
      let cdfMin = 0;
      for (let i = 0; i < 256; i++) { if (cdf[i] > 0) { cdfMin = cdf[i]; break; } }
      const map = new Array(256);
      const denom = (total - cdfMin) || 1;
      for (let i = 0; i < 256; i++) map[i] = Math.round(((cdf[i] - cdfMin) / denom) * 255);
      for (let i = 0; i < d.length; i += 4) {
        const v = map[d[i] | 0];
        d[i] = d[i + 1] = d[i + 2] = v;
      }
      ctx.putImageData(img, 0, 0);
      return canvas;
    },

    // Nitidez — convolução 3x3.
    sharpen(canvas) {
      const ctx = canvas.getContext('2d');
      const w = canvas.width, h = canvas.height;
      const src = ctx.getImageData(0, 0, w, h);
      const dst = ctx.createImageData(w, h);
      const kernel = [0, -1, 0, -1, 5, -1, 0, -1, 0];
      for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
          if (y === 0 || y === h - 1 || x === 0 || x === w - 1) {
            const o = (y * w + x) * 4;
            dst.data[o] = dst.data[o + 1] = dst.data[o + 2] = src.data[o];
            dst.data[o + 3] = 255;
            continue;
          }
          let sum = 0, k = 0;
          for (let ky = -1; ky <= 1; ky++) {
            for (let kx = -1; kx <= 1; kx++) {
              const idx = ((y + ky) * w + (x + kx)) * 4;
              sum += src.data[idx] * kernel[k++];
            }
          }
          const o = (y * w + x) * 4;
          const v = Math.min(255, Math.max(0, sum));
          dst.data[o] = dst.data[o + 1] = dst.data[o + 2] = v;
          dst.data[o + 3] = 255;
        }
      }
      ctx.putImageData(dst, 0, 0);
      return canvas;
    },

    // Threshold adaptativo (média local via imagem integral — mais robusto que
    // um threshold global fixo em recibos com sombra/iluminação irregular).
    adaptiveThreshold(canvas, blockSize = 25, c = 10) {
      const ctx = canvas.getContext('2d');
      const w = canvas.width, h = canvas.height;
      const img = ctx.getImageData(0, 0, w, h);
      const d = img.data;
      const integral = new Float64Array((w + 1) * (h + 1));
      for (let y = 0; y < h; y++) {
        let rowSum = 0;
        for (let x = 0; x < w; x++) {
          rowSum += d[(y * w + x) * 4];
          integral[(y + 1) * (w + 1) + (x + 1)] = integral[y * (w + 1) + (x + 1)] + rowSum;
        }
      }
      const half = Math.floor(blockSize / 2);
      const areaSum = (x0, y0, x1, y1) => {
        x0 = Math.max(0, x0); y0 = Math.max(0, y0);
        x1 = Math.min(w - 1, x1); y1 = Math.min(h - 1, y1);
        return integral[(y1 + 1) * (w + 1) + (x1 + 1)] - integral[y0 * (w + 1) + (x1 + 1)]
             - integral[(y1 + 1) * (w + 1) + x0] + integral[y0 * (w + 1) + x0];
      };
      for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
          const x0 = x - half, x1 = x + half, y0 = y - half, y1 = y + half;
          const areaW = (Math.min(w - 1, x1) - Math.max(0, x0) + 1) * (Math.min(h - 1, y1) - Math.max(0, y0) + 1);
          const mean = areaSum(x0, y0, x1, y1) / areaW;
          const o = (y * w + x) * 4;
          const v = d[o] > (mean - c) ? 255 : 0;
          d[o] = d[o + 1] = d[o + 2] = v;
        }
      }
      ctx.putImageData(img, 0, 0);
      return canvas;
    },

    // Corte de bordas — remove margens uniformes ao redor do conteúdo do recibo.
    autoCrop(canvas) {
      const ctx = canvas.getContext('2d');
      const w = canvas.width, h = canvas.height;
      const img = ctx.getImageData(0, 0, w, h).data;
      const isContentRow = (y) => {
        let count = 0;
        for (let x = 0; x < w; x += 2) { if (img[(y * w + x) * 4] < 250) count++; }
        return count > w * 0.01;
      };
      const isContentCol = (x) => {
        let count = 0;
        for (let y = 0; y < h; y += 2) { if (img[(y * w + x) * 4] < 250) count++; }
        return count > h * 0.01;
      };
      let top = 0, bottom = h - 1, left = 0, right = w - 1;
      while (top < bottom && !isContentRow(top)) top++;
      while (bottom > top && !isContentRow(bottom)) bottom--;
      while (left < right && !isContentCol(left)) left++;
      while (right > left && !isContentCol(right)) right--;
      const cw = right - left, ch = bottom - top;
      if (cw < w * 0.5 || ch < h * 0.5) return canvas; // corte suspeito — mantém original
      const out = document.createElement('canvas');
      out.width = cw; out.height = ch;
      out.getContext('2d').drawImage(canvas, left, top, cw, ch, 0, 0, cw, ch);
      return out;
    },

    // Variante 1: preprocessamento moderado (preserva tons de cinza).
    async variant1(base) {
      let c = this.copyCanvas(base);
      c = this.grayscale(c);
      c = this.denoise(c);
      c = this.autoContrast(c);
      c = this.sharpen(c);
      c = this.autoCrop(c);
      return c;
    },

    // Variante 2: binarização agressiva (recibos com sombra/baixo contraste).
    async variant2(base) {
      let c = this.copyCanvas(base);
      c = this.grayscale(c);
      c = this.denoise(c);
      c = this.equalize(c);
      c = this.adaptiveThreshold(c);
      c = this.autoCrop(c);
      return c;
    },
  };

  // ===========================================================================
  // PDF -> imagens (via pdf.js)
  // ===========================================================================
  const PdfHelper = {
    async renderPages(file, ctx) {
      if (!global.pdfjsLib) throw new Error('pdf.js não carregado.');
      const buf = await file.arrayBuffer();
      const pdf = await global.pdfjsLib.getDocument({ data: buf }).promise;
      const canvases = [];
      for (let i = 1; i <= pdf.numPages; i++) {
        ctx.onProgress && ctx.onProgress(`Convertendo página ${i}/${pdf.numPages} do PDF em imagem...`);
        const page = await pdf.getPage(i);
        const viewport = page.getViewport({ scale: 2.2 });
        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        canvases.push(canvas);
      }
      return canvases;
    },
  };

  // ===========================================================================
  // Extração inteligente de campos a partir do texto reconhecido
  // ===========================================================================
  const FieldExtractor = {
    extract(texto) {
      const campos = {
        data: null, hora: null, valor: null, fornecedor: null, cnpj: null, cpf: null,
        cidade: null, forma_pagamento: null, categoria_sugerida: null,
        numero_documento: null, chave_nfce: null, bandeira_cartao: null,
      };
      if (!texto) return campos;
      let m;

      if ((m = texto.match(/(\d{2})[\/\-.](\d{2})[\/\-.](\d{2,4})/))) {
        const ano = m[3].length === 2 ? '20' + m[3] : m[3];
        const dt = new Date(`${ano}-${m[2]}-${m[1]}`);
        if (!isNaN(dt.getTime())) campos.data = `${ano}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`;
      }
      if ((m = texto.match(/(\d{1,2}):(\d{2})(?::\d{2})?/))) {
        campos.hora = `${m[1].padStart(2, '0')}:${m[2]}`;
      }
      if ((m = texto.match(/total[^\d]{0,20}(\d{1,3}(?:\.\d{3})*,\d{2})/i))) {
        campos.valor = parseFloat(m[1].replace(/\./g, '').replace(',', '.'));
      } else if ((m = texto.match(/R\$\s*(\d{1,3}(?:\.\d{3})*,\d{2})/))) {
        campos.valor = parseFloat(m[1].replace(/\./g, '').replace(',', '.'));
      }
      if ((m = texto.match(/\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}/))) {
        campos.cnpj = m[0].replace(/\D/g, '');
      }
      try {
        if ((m = texto.match(/(?<!\d)\d{3}\.?\d{3}\.?\d{3}-?\d{2}(?!\d)/))) {
          const cpf = m[0].replace(/\D/g, '');
          if (cpf !== campos.cnpj) campos.cpf = cpf;
        }
      } catch (e) { /* lookbehind não suportado — ignora CPF */ }
      if ((m = texto.match(/(?:\d[\s.]?){44}/))) {
        const chave = m[0].replace(/\D/g, '');
        if (chave.length === 44) campos.chave_nfce = chave;
      }

      // Casamento por palavra inteira (\b) — evita falsos positivos como
      // "ELO" dentro de "BELO Horizonte" ao usar apenas .includes().
      const matchPalavra = (txt, palavra) => new RegExp('\\b' + palavra.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'u').test(txt);

      const textoUp = texto.toUpperCase();
      const formaMap = {
        PIX: 'PIX', DINHEIRO: 'Dinheiro', 'CRÉDITO': 'Cartão Crédito', CREDITO: 'Cartão Crédito',
        'DÉBITO': 'Cartão Débito', DEBITO: 'Cartão Débito', 'TRANSFERÊNCIA': 'Transferência',
        TRANSFERENCIA: 'Transferência', BOLETO: 'Boleto',
      };
      for (const k in formaMap) { if (matchPalavra(textoUp, k)) { campos.forma_pagamento = formaMap[k]; break; } }

      for (const bandeira of ['VISA', 'MASTERCARD', 'ELO', 'AMEX', 'HIPERCARD', 'DINERS']) {
        if (matchPalavra(textoUp, bandeira)) { campos.bandeira_cartao = bandeira.charAt(0) + bandeira.slice(1).toLowerCase(); break; }
      }

      const catMap = {
        HOTEL: 'Hospedagem', POUSADA: 'Hospedagem', HOSTEL: 'Hospedagem', UBER: 'Uber', '99': '99',
        CABIFY: 'Uber', IFOOD: 'Alimentação', RESTAURANTE: 'Alimentação', LANCHONETE: 'Alimentação',
        PADARIA: 'Alimentação', PIZZARIA: 'Alimentação', CHURRASCARIA: 'Alimentação', POSTO: 'Combustível',
        SHELL: 'Combustível', PETROBRAS: 'Combustível', IPIRANGA: 'Combustível', PEDAGIO: 'Pedágio',
        'PEDÁGIO': 'Pedágio', AUTOPASS: 'Pedágio', AZUL: 'Passagem Aérea', LATAM: 'Passagem Aérea',
        GOL: 'Passagem Aérea', AVIANCA: 'Passagem Aérea', PARK: 'Estacionamento', ESTACIONAMENTO: 'Estacionamento',
        FARMACIA: 'Farmácia', 'FARMÁCIA': 'Farmácia', DROGARIA: 'Farmácia', LAVANDERIA: 'Lavanderia',
        TAXI: 'Táxi', 'TÁXI': 'Táxi',
      };
      for (const k in catMap) { if (matchPalavra(textoUp, k)) { campos.categoria_sugerida = catMap[k]; break; } }

      if ((m = texto.match(/n[ºo°.]?\s*(?:documento|cupom|nf-?e)?\s*[:\-]?\s*(\d{3,12})/i))) {
        campos.numero_documento = m[1];
      }

      for (const linhaRaw of texto.split(/\r\n|\r|\n/)) {
        const linha = linhaRaw.trim();
        if (linha.length >= 4 && /[A-Za-zÀ-ú]{3,}/.test(linha) && !/^\d+$/.test(linha)) {
          campos.fornecedor = linha.substring(0, 100);
          break;
        }
      }

      if ((m = texto.match(/([A-ZÀ-Ú][a-zà-ú]+(?:\s[A-ZÀ-Ú][a-zà-ú]+)*)\s*[\/\-]\s*([A-Z]{2})\b/))) {
        campos.cidade = m[1].trim();
      }

      return campos;
    },

    isSuficiente(campos, confianca) {
      return !!((campos.valor || campos.data) && confianca >= 35);
    },
  };

  // ===========================================================================
  // Strategy: TesseractProvider (client-side, sem custo/chave)
  // ===========================================================================
  class TesseractProvider {
    constructor() {
      this.name = 'Tesseract.js';
      this._worker = null;
    }

    async _getWorker() {
      if (this._worker) return this._worker;
      if (!global.Tesseract) throw new Error('Tesseract.js não carregado.');
      this._worker = await global.Tesseract.createWorker('por');
      return this._worker;
    }

    async _recognizeCanvas(canvas) {
      const worker = await this._getWorker();
      const { data } = await worker.recognize(canvas);
      return { texto: data.text || '', confianca: data.confidence || 0 };
    }

    async recognizeFile(file, ctx) {
      const t0 = performance.now();
      const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
      let baseCanvases = [];

      if (isPdf) {
        baseCanvases = await PdfHelper.renderPages(file, ctx);
      } else {
        ctx.onProgress('Carregando e orientando imagem...');
        const raw = await ImagePreprocessor.fileToCanvas(file);
        baseCanvases = [ImagePreprocessor.resize(raw)];
      }

      const textos = [];
      let confSum = 0;
      let debugImagens = null;

      for (let i = 0; i < baseCanvases.length; i++) {
        const base = baseCanvases[i];
        const label = baseCanvases.length > 1 ? ` (página ${i + 1}/${baseCanvases.length})` : '';

        ctx.onProgress(`Pré-processando imagem${label}...`);
        const v1 = await ImagePreprocessor.variant1(base);

        ctx.onProgress(`Executando OCR — Tesseract.js${label}...`);
        let { texto, confianca } = await this._recognizeCanvas(v1);
        let processedCanvas = v1;

        if (confianca < 55) {
          ctx.onProgress(`Confiança baixa (${confianca.toFixed(0)}%) — tentando pré-processamento alternativo${label}...`);
          const v2 = await ImagePreprocessor.variant2(base);
          const r2 = await this._recognizeCanvas(v2);
          if (r2.confianca > confianca) {
            texto = r2.texto; confianca = r2.confianca; processedCanvas = v2;
          }
        }

        textos.push(texto);
        confSum += confianca;

        if (i === 0) {
          debugImagens = {
            original: base.toDataURL('image/jpeg', 0.8),
            processada: processedCanvas.toDataURL('image/jpeg', 0.8),
          };
        }
      }

      const tempo = performance.now() - t0;
      return {
        engine: this.name,
        texto: textos.join('\n\n'),
        confianca: Math.round((confSum / (baseCanvases.length || 1)) * 10) / 10,
        tempo_ms: Math.round(tempo),
        debugImagens,
      };
    }
  }

  // ===========================================================================
  // Strategy: fallback no servidor (OCR.space -> OpenAI Vision, se configurados)
  // ===========================================================================
  const ServerFallbackProvider = {
    async run({ viagemId, arquivoPath, tentativasCliente, pularEngines, ocrCliente }) {
      const fd = new FormData();
      fd.append('arquivo_path', arquivoPath);
      fd.append('tentativas_cliente', JSON.stringify(tentativasCliente || []));
      if (pularEngines) {
        fd.append('pular_engines', '1');
        fd.append('ocr_cliente', JSON.stringify(ocrCliente || {}));
      }
      const resp = await fetch(`/rdv/viagens/${viagemId}/ocr`, { method: 'POST', body: fd });
      return resp.json();
    },
  };

  // ===========================================================================
  // OCRManager — orquestra o pipeline completo com fallback automático
  // ===========================================================================
  const tesseractProvider = new TesseractProvider(); // singleton — mantém worker "quente"

  const RdvOcr = {
    async run(file, { viagemId, onProgress } = {}) {
      const progress = (msg) => { onProgress && onProgress(msg); };
      const tentativas = [];
      let arquivoPath = null;

      // 1) Upload do comprovante original — necessário para anexar à despesa
      //    independentemente do resultado do OCR.
      progress('Enviando comprovante...');
      try {
        const fd = new FormData();
        fd.append('arquivo', file);
        const resp = await fetch(`/rdv/viagens/${viagemId}/upload-comprovante`, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) throw new Error(data.error || 'Falha ao enviar arquivo.');
        arquivoPath = data.arquivo;
      } catch (e) {
        return { success: false, arquivo: null, fields: null, meta: null, tentativas, erro: 'Erro ao enviar arquivo: ' + e.message };
      }

      // 2) Tesseract.js (client-side)
      try {
        const r = await tesseractProvider.recognizeFile(file, { onProgress: progress });
        const campos = FieldExtractor.extract(r.texto);
        const suficiente = FieldExtractor.isSuficiente(campos, r.confianca);
        tentativas.push({
          engine: r.engine, sucesso: suficiente, confianca: r.confianca, tempo_ms: r.tempo_ms,
          erro: suficiente ? null : 'Confiança/campos insuficientes.', origem: 'cliente',
        });

        if (suficiente) {
          progress('Extraindo dados...');
          const meta = { engine: r.engine, confianca: r.confianca, tempo_ms: r.tempo_ms };
          this._logFireAndForget(viagemId, arquivoPath, tentativas, campos);
          progress('Preenchendo formulário...');
          return { success: true, arquivo: arquivoPath, fields: campos, meta, tentativas, texto: r.texto, debugImagens: r.debugImagens };
        }
      } catch (e) {
        tentativas.push({ engine: 'Tesseract.js', sucesso: false, confianca: 0, tempo_ms: 0, erro: e.message, origem: 'cliente' });
      }

      // 3) Fallback no servidor: OCR.space -> OpenAI Vision (conforme configurado)
      progress('Tentando mecanismos adicionais no servidor...');
      try {
        const data = await ServerFallbackProvider.run({ viagemId, arquivoPath, tentativasCliente: tentativas });
        const tentativasServidor = (data.tentativas || []).filter(t => t.origem === 'servidor');
        const todasTentativas = tentativas.concat(tentativasServidor);

        if (data.success && data.ocr && !data.ocr.erro) {
          progress('Extraindo dados...');
          const meta = { engine: data.ocr.engine || 'servidor', confianca: data.ocr.confianca ?? null, tempo_ms: data.ocr.tempo_ms ?? null };
          progress('Preenchendo formulário...');
          return { success: true, arquivo: data.arquivo || arquivoPath, fields: data.ocr, meta, tentativas: todasTentativas, texto: data.ocr.texto || null };
        }

        return { success: false, arquivo: data.arquivo || arquivoPath, fields: null, meta: null, tentativas: todasTentativas, erro: (data.ocr && data.ocr.erro) || 'Todos os mecanismos de OCR falharam.' };
      } catch (e) {
        return { success: false, arquivo: arquivoPath, fields: null, meta: null, tentativas, erro: 'Erro de comunicação com o servidor: ' + e.message };
      }
    },

    // Registra a tentativa client-side mesmo quando o servidor não precisa
    // rodar seus próprios motores (Tesseract já teve sucesso) — não bloqueia a UI.
    _logFireAndForget(viagemId, arquivoPath, tentativas, campos) {
      try {
        const fd = new FormData();
        fd.append('arquivo_path', arquivoPath);
        fd.append('tentativas_cliente', JSON.stringify(tentativas));
        fd.append('pular_engines', '1');
        fd.append('ocr_cliente', JSON.stringify(campos));
        fetch(`/rdv/viagens/${viagemId}/ocr`, { method: 'POST', body: fd }).catch(() => {});
      } catch (e) { /* melhor esforço — não bloqueia o fluxo principal */ }
    },
  };

  global.RdvOcr = RdvOcr;
  global.RdvOcrInternals = { ImagePreprocessor, FieldExtractor, PdfHelper };
})(window);

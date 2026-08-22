<?php

namespace App\Services;

use App\Core\Logger;
use App\Models\Integracao;

class VoxelPacsService
{
    private const PROVIDER = 'voxel_pacs';
    private const DEFAULT_BASE_URL = 'https://server.voxelpacs.com.br';
    private const TIMEOUT_SECONDS = 15;

    private string $baseUrl;
    private string $integrationCode;
    private string $secret;
    private Logger $logger;

    private function __construct(string $baseUrl, string $integrationCode, string $secret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->integrationCode = $integrationCode;
        $this->secret = $secret;
        $this->logger = new Logger();
    }

    public static function forUser(int $usuarioId): self
    {
        $integracao = new Integracao();
        $row = $integracao->findByNomeAndUsuarioId(self::PROVIDER, $usuarioId);
        if (!$row || ($row->status ?? 'inativo') !== 'ativo') {
            throw new \RuntimeException('A integração ImagiFlow / VOXEL PACS não está ativa.');
        }

        $config = $integracao->getDecodedConfig($row);
        $baseUrl = trim((string) ($config['base_url'] ?? self::DEFAULT_BASE_URL));
        $code = trim((string) ($config['integration_code'] ?? ''));
        $secretEnc = (string) ($config['secret_enc'] ?? '');
        if ($code === '' || $secretEnc === '') {
            throw new \RuntimeException('Configure o código e o segredo da integração VOXEL PACS.');
        }
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \RuntimeException('A URL do VOXEL PACS deve utilizar HTTPS.');
        }

        try {
            $secret = (new CryptoService())->decryptString($secretEnc);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Não foi possível ler o segredo criptografado do VOXEL PACS. Configure-o novamente.');
        }
        if ($secret === '') {
            throw new \RuntimeException('O segredo do VOXEL PACS está vazio.');
        }

        return new self($baseUrl, $code, $secret);
    }

    /** @return array<string,mixed> */
    public function consultarMedico(string $crm, string $nome = ''): array
    {
        return $this->request('/api/integracoes/imagiflow/v1/medicos/consultar', [
            'crm' => preg_replace('/\D+/', '', $crm) ?: '',
            'nome' => trim($nome),
        ]);
    }

    /** @return array{request_id:string,itens:array<int,array<string,mixed>>,total:int,total_paginas:int} */
    public function listarEstudos(string $inicio, string $fim, string $medicoCrm = '', string $unidade = ''): array
    {
        $this->validatePeriod($inicio, $fim);
        $page = 1;
        $pages = 1;
        $items = [];
        $requestId = '';

        while ($page <= $pages) {
            $payload = [
                'periodo_inicio' => $inicio,
                'periodo_fim' => $fim,
                'pagina' => $page,
                'por_pagina' => 100,
            ];
            if (($crm = preg_replace('/\D+/', '', $medicoCrm) ?: '') !== '') {
                $payload['medico_crm'] = $crm;
            }
            if (trim($unidade) !== '') {
                $payload['unidade'] = trim($unidade);
            }

            $response = $this->request('/api/integracoes/imagiflow/v1/apuracao/estudos', $payload);
            $requestId = (string) ($response['request_id'] ?? $requestId);
            $data = $response['data'] ?? [];
            if (!is_array($data)) {
                throw new \RuntimeException('Resposta inválida do VOXEL PACS.');
            }
            $pageItems = $data['itens'] ?? [];
            if (!is_array($pageItems)) {
                throw new \RuntimeException('Lista de estudos inválida no VOXEL PACS.');
            }
            foreach ($pageItems as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }
            $pages = max(1, (int) ($data['total_paginas'] ?? 1));
            $page++;
            if ($page > 1000) {
                throw new \RuntimeException('A consulta VOXEL excedeu o limite seguro de páginas.');
            }
        }

        return [
            'request_id' => $requestId,
            'itens' => $items,
            'total' => count($items),
            'total_paginas' => $pages,
        ];
    }

    /** @return array<string,mixed> */
    private function request(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('Não foi possível preparar a consulta VOXEL PACS.');
        }
        $timestamp = (string) time();
        $requestId = bin2hex(random_bytes(16));
        $canonical = "POST\n{$path}\n{$timestamp}\n" . hash('sha256', $body);
        $signature = hash_hmac('sha256', $canonical, $this->secret);

        [$raw, $status, $curlError] = $this->sendRequest($path, $body, [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Imagiflow-Code: ' . $this->integrationCode,
            'X-Imagiflow-Timestamp: ' . $timestamp,
            'X-Imagiflow-Signature: ' . $signature,
            'X-Request-Id: ' . $requestId,
        ]);

        if ($raw === false || $curlError !== '') {
            $this->logger->warning('Falha de comunicação com VOXEL PACS', [
                'request_id' => $requestId,
                'endpoint' => $path,
                'error' => $curlError,
            ]);
            throw new \RuntimeException('Não foi possível comunicar com o VOXEL PACS.');
        }
        $response = json_decode((string) $raw, true);
        if (!is_array($response) || ($response['ok'] ?? false) !== true || $status !== 200) {
            $this->logger->warning('Resposta não autorizada ou inválida do VOXEL PACS', [
                'request_id' => $requestId,
                'endpoint' => $path,
                'http_status' => $status,
            ]);
            throw new \RuntimeException($status === 401
                ? 'O VOXEL PACS recusou a credencial da integração.'
                : 'O VOXEL PACS retornou uma resposta inválida.');
        }
        return $response;
    }

    /** @return array{0:string|false,1:int,2:string} */
    private function sendRequest(string $path, string $body, array $headers): array
    {
        $url = $this->baseUrl . $path;
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                return [false, 0, 'Falha ao inicializar cURL.'];
            }
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $raw = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return [$raw, $status, $error];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => self::TIMEOUT_SECONDS,
                'ignore_errors' => true,
                'protocol_version' => 1.1,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $errorData = $raw === false ? error_get_last() : null;
        $status = 0;
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
        return [$raw, $status, (string) ($errorData['message'] ?? '')];
    }

    private function validatePeriod(string $inicio, string $fim): void
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $fim);
        if (!$start || !$end || $start->format('Y-m-d') !== $inicio || $end->format('Y-m-d') !== $fim) {
            throw new \RuntimeException('Informe o período no formato YYYY-MM-DD.');
        }
        if ($end < $start || $end->diff($start)->days > 92) {
            throw new \RuntimeException('O período VOXEL deve ter no máximo 93 dias.');
        }
    }
}

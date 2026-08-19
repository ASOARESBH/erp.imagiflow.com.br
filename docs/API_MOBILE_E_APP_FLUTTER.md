# API Mobile e Aplicativo Flutter

## Decisões de implementação

A camada móvel será adicionada sem modificar os fluxos web existentes. As rotas serão registradas em `routes/api.php` sob o prefixo `/api/mobile/v1` e utilizarão apenas `GET` e `POST`, pois o roteador MVC atual não expõe verbos `PUT` e `DELETE`. Operações de atualização serão, portanto, realizadas por `POST /recurso/{id}`. Todas as respostas respeitarão o envelope `success`, `data`, `message` e `errors`.

O primeiro acesso continuará usando o domínio ou subdomínio do tenant. As rotas públicas `tenant/ping`, `login`, `2fa/verify`, `2fa/resend` e `forgot-password` dependerão da resolução normal por `HTTP_HOST`. Nas rotas autenticadas, o middleware móvel validará um token opaco e verificará que o tenant resolvido pelo host coincide com o tenant registrado no token. Essa dupla validação preserva o isolamento atual e não permite que um token de uma empresa seja usado em outra.

| Componente | Decisão adotada |
|---|---|
| Autenticação | Token opaco gerado com `random_bytes(32)`, persistindo exclusivamente o hash SHA-256. |
| Expiração | Trinta dias, com atualização de `last_used_at` a cada uso válido. |
| 2FA | Reutilização de `TwoFactorService`; o sistema vigente utiliza código de quatro dígitos e expiração de cinco minutos. |
| Tenant | Domínio continua sendo fonte de verdade; token e host devem coincidir nas rotas autenticadas. |
| RBAC | `Auth::can()` continua sendo a única verificação de permissão. O middleware materializa o mesmo contrato de sessão esperado pelo ERP. |
| Escritas | Controllers móveis validam dados no servidor, usam os Models existentes e registram auditoria com `origem: app_mobile`. |
| Banco | Apenas novas tabelas e campos; migration manual, compatível com MySQL 5.7, sem remoção ou renomeação. |
| Geolocalização | Captura pontual por evento, sem rastreamento em segundo plano, e com envio opcional para não bloquear o fluxo de negócio. |

## Endpoints iniciais

| Área | Endpoints |
|---|---|
| Fundação | `GET /tenant/ping`, `POST /login`, `POST /2fa/verify`, `POST /2fa/resend`, `POST /logout`, `GET /perfil/me` |
| Dispositivos | `GET /dispositivos`, `POST /dispositivos/{id}/revogar`, `POST /dispositivos/push-token` |
| Cadastros | `GET/POST /clientes`, `GET/POST /clientes/{id}`, `GET/POST /fornecedores`, `GET/POST /fornecedores/{id}` |
| Consulta | `GET /financeiro/contas-pagar`, `GET /financeiro/contas-receber`, `GET /financeiro/resumo`, `GET /contratos`, `GET /contratos/{id}`, `GET /apuracao/{tipo}` |
| CRM | `GET/POST /crm/leads`, `GET/POST /crm/oportunidades`, `GET /crm/funil`, `GET/POST /crm/propostas`, `POST /crm/interacoes` |
| Campo | `GET/POST /manutencao/ordens`, `GET/POST /manutencao/ordens/{id}`, `GET/POST /rdv/viagens`, `POST /rdv/viagens/{id}/despesas`, `POST /localizacoes` |
| Suporte | `GET /dashboard/resumo`, `GET /notificacoes`, `POST /notificacoes/{id}/lida`, `GET /busca` |

## Aplicativo Flutter

O aplicativo ficará em `mobile/imagiflow_mobile/`, isolado do backend PHP e organizado em camadas `core` e `features`. O tema reproduz a identidade confirmada do login web: fonte Inter, azul primário `#00529B`, azul escuro `#002D54`, bordas de 8 px e cartões com raio de 12 px. O fluxo de login usa seleção de empresa, credenciais, 2FA de quatro dígitos, token guardado em armazenamento seguro e opção de biometria após uma autenticação válida.

Os recursos sensíveis do dispositivo serão solicitados somente no contexto de uso. A localização será opcional e vinculada à criação de cliente, à interação de CRM e ao check-in de RDV. Câmera e galeria serão usadas para anexos e comprovantes. A fila offline do RDV manterá uma ação local até que a conectividade seja restabelecida.

## Critérios técnicos de aceite

A entrega inclui migrations, Models, middleware, Controllers, rotas, permissões, mapa web de localizações, estrutura Flutter, integração HTTP, telas de autenticação, dashboard, módulos de uso diário e testes estáticos/unitários onde o ambiente permitir. A publicação em lojas e a configuração de Firebase/FCM exigem credenciais e contas externas que não estão presentes no repositório; o código será preparado para recebê-las por ambiente, sem conter segredos versionados.

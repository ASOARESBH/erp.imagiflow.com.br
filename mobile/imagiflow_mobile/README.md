# ImagiFlow Mobile

Este diretório contém o cliente **Flutter** do ERP ImagiFlow para Android e iOS. O aplicativo foi estruturado para usar uma URL própria por empresa, autenticação por token Bearer, armazenamento seguro, segundo fator, módulos condicionados por permissão e geolocalização apenas em eventos de campo autorizados.

## Preparação das plataformas nativas

O ambiente de desenvolvimento deve ter o SDK Flutter estável instalado. A partir deste diretório, execute o comando abaixo uma única vez para gerar os diretórios nativos que não são versionados nesta entrega:

```bash
flutter create --platforms=android,ios .
flutter pub get
```

Depois da geração, acrescente as permissões abaixo aos manifests nativos. A solicitação é pontual: o aplicativo não inicia rastreamento contínuo e não solicita localização em segundo plano.

| Plataforma | Arquivo | Configuração necessária |
|---|---|---|
| Android | `android/app/src/main/AndroidManifest.xml` | `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION`, `CAMERA` e `READ_MEDIA_IMAGES` quando aplicável. |
| iOS | `ios/Runner/Info.plist` | `NSLocationWhenInUseUsageDescription`, `NSCameraUsageDescription` e `NSPhotoLibraryUsageDescription`, com textos claros de finalidade. |

## Execução e qualidade

Configure um emulador ou dispositivo, valide as dependências e inicie o aplicativo.

```bash
flutter analyze
flutter test
flutter run
```

A base da empresa é informada pelo usuário, normalizada para HTTPS e validada no endpoint `GET /api/mobile/v1/tenant/ping` antes de qualquer credencial ser enviada. O token de sessão fica somente em `flutter_secure_storage`; ele não é gravado no banco local nem em preferências comuns.

## Módulos entregues

| Área | Integração móvel |
|---|---|
| Autenticação | Tenant por URL, login, recuperação de senha, 2FA, logout e biometria local opcional. |
| Início | KPIs, ações rápidas e módulos filtrados por permissões retornadas pela API. |
| Cadastros | Listagens pesquisáveis de clientes e fornecedores. |
| Financeiro | Listagens de contas a pagar e receber, além de resumo do dashboard. |
| Comercial | Contratos, apurações, leads, oportunidades, propostas e funil. |
| Campo | Ordens de serviço, RDV, despesas e ponto de localização explícito. |
| Perfil | Dados de sessão, biometria, dispositivos, senha, logout e troca de empresa. |

## Contrato da API

Consulte [`../../docs/API_MOBILE_E_APP_FLUTTER.md`](../../docs/API_MOBILE_E_APP_FLUTTER.md) para a referência de endpoints, envelopes JSON e regras de segurança. O aplicativo trata respostas `401`, `403`, `422` e falhas de conectividade de forma explícita, sem interpretar mensagens HTML como sucesso.

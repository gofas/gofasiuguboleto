# Gofas iugu Boleto

Módulo de gateway de pagamento para WHMCS que integra geração e baixa de boletos bancários via API iugu. Desenvolvido pela Gofas Software.

## Funcionalidades

- Geração de boletos via API iugu
- Verificação e baixa automática de faturas pagas
- Suporte a vencimento customizado

## Requisitos

- WHMCS 7.x ou superior
- PHP 8.x
- Conta iugu com API habilitada
- API Token iugu

## Instalação

1. Copiar `modules/gateways/` para o `modules/gateways/` do WHMCS
2. Ativar em **Configurações > Formas de Pagamento**
3. Informar API Token

## Changelog

Ver [changelog.md](changelog.md).

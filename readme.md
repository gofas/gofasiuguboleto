# Módulo iugu Boleto para WHMCS

[![versão](https://img.shields.io/github/v/release/gofas/gofasiuguboleto?label=vers%C3%A3o&color=005071&style=flat-square)](https://github.com/gofas/gofasiuguboleto/releases/latest)
[![downloads](https://img.shields.io/github/downloads/gofas/gofasiuguboleto/total?label=downloads&color=005071&style=flat-square)](https://github.com/gofas/gofasiuguboleto/releases/latest)
[![licença](https://img.shields.io/badge/licen%C3%A7a-propriet%C3%A1ria-005071?style=flat-square)](https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/)
[![suporte](https://img.shields.io/badge/suporte-f%C3%B3rum%20gratuito-ff8700?style=flat-square)](https://gofas.net/foruns/)

Gera, consulta e dá baixa automática em boletos bancários via API da iugu, integrado ao checkout nativo do WHMCS. Desenvolvido pela Gofas Software, é 100% gratuito e de código aberto.

## Sumário

- [Download](#download)
- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Informações importantes](#informações-importantes)
- [Suporte](#suporte)
- [Licença](#licença)

## Download

**[Baixar a versão mais recente](https://github.com/gofas/gofasiuguboleto/releases/latest/download/gofasiuguboleto.zip)**

O download é contabilizado no site pelo contador de instalações do módulo.

## Funcionalidades

- **Boleto registrado** gerado via API iugu, integrado ao checkout nativo do WHMCS
- **Verificação periódica de status** configurável: horário de execução e quantidade de faturas verificadas por requisição
- **Baixa automática** das faturas quando o boleto é pago
- **Valor mínimo** da fatura para permitir pagamento via boleto
- **Dias até o vencimento** configuráveis
- **Mensagem personalizada** exibida na fatura
- **Redirecionamento para o boleto** ao acessar a fatura (opcional)
- **Suporte a produção e a testes (sandbox)**
- **Logs de diagnóstico** configuráveis
- **Aviso de atualização** e verificação de versão na própria tela de configuração do módulo

## Requisitos

- WHMCS >= 7.9
- PHP >= 8.1
- Conta iugu com API habilitada (token de produção e de testes)

## Instalação

1. Baixe o arquivo pelo link de download e descompacte. Será criada a pasta `gofasiuguboleto`.
2. Copie a pasta `modules` de dentro de `gofasiuguboleto` para a raiz da instalação do WHMCS, mesclando com as pastas existentes.
3. Ative o módulo em `Opções > Pagamentos > Portais para Pagamentos > aba All Payment Gateways`, clicando em "Gofas iugu - Boleto".
4. Informe os tokens da API.

## Configuração

### Opções do módulo

<img src="https://raw.githubusercontent.com/gofas/gofasiuguboleto/master/docs/img/tela-configuracoes-modulo-1.3.0.png" alt="Tela de configuracoes do modulo" width="640">

- **API token produção**: token de produção da sua conta iugu.
- **API token teste**: token de testes da sua conta iugu.
- **Sandbox**: alterna entre o ambiente de testes e produção.
- **Salvar Logs**: grava informações de diagnóstico em `Utilitários > Logs > Log de Módulo`.
- **Valor mínimo**: valor mínimo da fatura para permitir pagamento via boleto.
- **Dias até o vencimento**: prazo do boleto gerado.
- **Mensagem na fatura**: texto exibido na fatura, acima do botão do boleto.
- **Redirecionar para o Boleto**: redireciona o cliente direto ao boleto ao acessar a fatura.
- **Horário da verificação**: horário em que o módulo verifica o status dos boletos.
- **Verificações por requisição**: número máximo de faturas consultadas por vez.
- **Enviar estatísticas de uso (opcional)**: controla o envio identificado das estatísticas de confirmação de pagamento. Desmarcado, as confirmações continuam sendo contabilizadas de forma anônima.

## Informações importantes

- A tarifa do boleto é paga separadamente à iugu, conforme o plano da sua conta.
- Sempre faça backup antes de mudar algo no seu sistema.

## Suporte

Fórum de suporte gratuito: https://gofas.net/foruns/

## Licença

Software proprietário da Gofas Software. O código é público apenas para transparência e consulta; isso não concede licença de uso, modificação ou redistribuição. É vedado modificar, redistribuir, sublicenciar ou realizar engenharia reversa sem autorização prévia por escrito. Veja [LICENSE](LICENSE) e o contrato completo em https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/.

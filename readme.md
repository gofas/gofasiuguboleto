# Módulo iugu Boleto para WHMCS

[![versão](https://img.shields.io/github/v/release/gofas/gofasiuguboleto?label=vers%C3%A3o&color=005071&style=flat-square)](https://github.com/gofas/gofasiuguboleto/releases/latest)
[![downloads](https://img.shields.io/endpoint?url=https%3A%2F%2Fgofas.net%2Fwp-json%2Fgofas%2Fv1%2Fbadge%2Fgofasiuguboleto&style=flat-square)](https://github.com/gofas/gofasiuguboleto/releases/latest)
[![abrir issue](https://img.shields.io/badge/suporte-abrir%20issue-ff8700?style=flat-square)](https://gofas.net/?p=11234/#new-post)

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

[Abrir issue](https://gofas.net/?p=11234/#new-post) no fórum do módulo.

## Licença

O código deste módulo é público para transparência e auditoria. Isso não transfere a titularidade nem concede licença livre de uso: o software é de propriedade da Gofas Software, protegido pela Lei 9.609/98 e pelos tratados de direitos autorais.

Trechos do [contrato de licença de uso](https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/) que se aplicam diretamente a este repositório:

- **Não redistribuir**: é proibido o aluguel, o arrendamento, o empréstimo, a cessão e o licenciamento do software a terceiros, total ou parcial, assim como o fornecimento de serviços de hospedagem comercial do software (Cláusula 10ª, §3º).
- **Não modificar**: é vedado qualquer procedimento que implique engenharia reversa, descompilação, desmontagem, tradução, adaptação ou modificação do software, bem como qualquer alteração não autorizada de suas funcionalidades (Cláusula 10ª, §2º).
- **Módulo alterado perde o suporte**: a Gofas não se responsabiliza por defeitos decorrentes de alteração do software, de operação por pessoas não autorizadas ou da integração com softwares de terceiros (Cláusula 10ª, §7º). O suporte é uma cortesia e não é garantido pela licença (Cláusula 7ª, §1º).

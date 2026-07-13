# Módulo iugu Boleto para WHMCS

Gera, consulta e dá baixa automática em boletos bancários via API da iugu, integrado ao checkout nativo do WHMCS. Desenvolvido pela Gofas Software, é 100% gratuito e de código aberto.

## Download

Baixe a versão mais recente:

https://github.com/gofas/gofasiuguboleto/releases/latest/download/gofasiuguboleto.zip

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

<img src="https://raw.githubusercontent.com/gofas/gofasiuguboleto/master/docs/img/tela-configuracoes-modulo.png" alt="Tela de configuracoes do modulo" width="640">

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

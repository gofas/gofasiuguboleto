<?php
/**
 * Módulo iugu Boleto para WHMCS
 * @copyright	2023 Gofas Software
 * @see			https://gofas.net/?p=14942
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14687
 * @version		1.0.0
 */

if( !defined('WHMCS')){ die(''); }
use WHMCS\Database\Capsule;
function gofasiuguboleto_MetaData(){
    return array(
        'DisplayName' => 'Gofas iugu - Boleto',
        'APIVersion' => '1.1',
    );
}
function gofasiuguboleto_config(){
	if(stripos($_SERVER['REQUEST_URI'], '/configgateways.php')!==false){
		$module_version	= '1.0.0';
		$module_page	= '14942';
		require_once __DIR__.'/functions.php';
		$verify_install = gib_verify_install();
		$whmcs_url = gib_whmcs_url();
		$check_updates = gib_verify_module_updates($module_page,$whmcs_url['admin_url'],$module_version);		
		$opt_num = 1;
		$renderize = array(
			'FriendlyName' => array(
				'Type' => 'System',
				'Value' => 'Gofas iugu - Boleto',
			),
			'separator_1' => array(
				'Description' => '
				<div class="gib_separator" style="padding: 1px 15px 9px;">
					<div style="float: right; padding: 0px;">
					'.gib_decrypt($check_updates['check']).'
					</div>
					<div style="margin-left: 10px;">
						<h4 style="padding-top: 5px;">Módulo Gofas iugu - Boleto para WHMCS v'.$module_version.'</h4>
						'.$check_updates['message'].'
						<p><a style="text-decoration:underline;" target="_blank" href="https://gofas.net/?p=14942#configuration">Documentação do módulo</a> | <a style="text-decoration:underline;" target="_blank" href="https://dev.iugu.com/reference/metadados/">Documentação da API iugu</a></p>
					</div>
				</div>',
			),
			/*'account_id' => array(
				'FriendlyName' => $opt_num++.'- ID da conta iugu<span class="gib_required">*</span>',
				'Type' => 'text',
				'Size' => '50',
				'Default' => '',
				'Description' => '<a target="_blank" style="text-decoration:underline;" href="https://alia.iugu.com/settings/account/general_information">Obter ID da conta</a> - <a target="_blank" style="text-decoration:underline;" href="https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2023/03/iugu_id_da_conta.png">veja onde encontrar</a>.',
			),*/
			'api_token' => array(
				'FriendlyName' => $opt_num++.'- API token produção<span class="gib_required">*</span>',
				'Type' => 'password',
				'Size' => '50',
				'Default' => '',
				'Description' => '<a target="_blank" style="text-decoration:underline;" href="https://alia.iugu.com/settings/account/api_integration">Obter API token</a>',
			),
			'sandbox_api_token' => array(
				'FriendlyName' => $opt_num++.'- API token teste<span class="gib_required">*</span>',
				'Type' => 'password',
				'Size' => '50',
				'Default' => '',
				'Description' => '<a target="_blank" style="text-decoration:underline;" href="https://alia.iugu.com/settings/account/api_integration">Obter API token</a>',
			),
			'separator_3_1' => array(
				'Description' => '<span><a target="_blank" style="text-decoration:underline;" href="https://dev.iugu.com/reference/autentica%C3%A7%C3%A3o#criando-suas-chaves-de-api-api-tokens-via-painel">Veja aqui como criar suas chaves de API (API Tokens) via painel iugu</a></span>',
			),
			// Sandbox
			'sandbox' => array(
				'FriendlyName' => $opt_num++.'- <i>Sandbox</i>',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Ative essa opção para gerar cobranças em modo de teste.',
			),
			// Log
			'log' => array(
				'FriendlyName' => $opt_num++.'- Salvar Logs',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Salva informações de diagnóstico em <a target="_blank" style="text-decoration: underline;" href="'.$whmcs_url['admin_url'].'/systemmodulelog.php">Utilitários > Logs > Log de Módulo</a>. Para funcionar, antes é necessário ativar o debug de módulo clicando em "Ativar Log de Debug". <a target="_blank" style="text-decoration: underline;" href="'.$whmcs_url['admin_url'].'/systemmodulelog.php">VER LOG</a>.',
			),
			// minimum amount
			'minimunamount' => array(
				'FriendlyName' => $opt_num++.'- Valor mínimo',
				'Type' => 'text',
				'Size' => '10',
				'Default' => '5',
				'Description' => 'Insira o valor total mínimo da fatura para permitir pagamento via Boleto. Formato: Decimal, separado por ponto. Não deve ser menor que o valor da tarifa aplicada à sua conta iugu.',
			),
			// Dias + vencimento
			'diasparavencimento' => array(
        	    'FriendlyName'      => $opt_num++.'- Dias até o vencimento',
        	    'Type'              => 'text',
				'Size'				=> '10',
				'Default' 			=> '2',
        	    'Description'       => 'Dias entre a data de emissão e a data do vencimento do boleto quando gerado no dia do vencimento ou após o vencimento da fatura. Boleto gerado antes do vencimento da fatura é emitido com a mesma data de vencimento da fatura. Mínimo 1 máximo 30.',
        	),
			// Top billet button message 
			'message' => array(
				'FriendlyName' => $opt_num++.'- Mensagem na fatura',
				'Type' => 'text',
				'Size' => '50',
				'Default' => 'Boleto gerado com sucesso.<br>Acesse o link ou copie a linha digitável.<br>',
				'Description' => 'Texto exibido na fatura acima do botão "Vizualizar Boleto"',
			),
			// Redirecionar para o link do boleto
			'redirecttobillet' => array(
				'FriendlyName' => $opt_num++.'- Redirecionar para o Boleto',
				'Type' => 'yesno',
				'Description' => 'Redireciona o cliente diretamente para o URL do boleto ao acessar a fatura.',
			),
		);
		$footer = array('footer' => array(
				'Description' => '<div class="ggp_section">
				<p>&copy; '.date('Y').' <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net">Gofas.net</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net/?p=14641#changelog">'.$module_version.'</a> | <a  style="text-decoration:underline;"target="_blank" title="↗ Documentação" href="https://gofas.net/?p=14641">Documentação</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Fórum de Suporte" href="https://gofas.net/foruns/">Suporte</a>.</p>
				<p style="font-size: 11px;">
				Ao utilizar esse módulo você concorda com nosso <a style="text-decoration:underline;" target="_blank" title="↗ Contrato de licença de uso de software" href="https://gofas.net/?p=9340">contrato de licença de uso de software</a>.
				</p>
				'.$check_updates['message'].'
				</div>',
			),
		);
	}
	return array_merge($renderize,$footer);
}
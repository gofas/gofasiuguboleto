<?php
/**
 * Módulo iugu Boleto para WHMCS
 * @copyright	2023 Gofas Software
 * @see			https://gofas.net/?p=14942
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14687
 * @version		1.0.0
 */
use WHMCS\Database\Capsule;

add_hook("AfterCronJob",1,"gib_check_status_updates");
add_hook("EmailPreSend",1,"gib_qrcode_mergetags");
add_hook("EmailTplMergeFields",1,"gib_qrcode_mergetags_fields");
//add_hook("PreAutomationTask",1,"gib_check_status_updates");
//add_hook("PreCronJob",1,"gib_check_status_updates");
if(!function_exists('gib_qrcode_mergetags_fields')){
    function gib_qrcode_mergetags_fields($vars){
        $gib_merge_fields = array();
	    $gib_merge_fields['gib_pdf']		= 'iugu Boleto: URL do boleto em PDF';
		$gib_merge_fields['gib_bankLine']	= 'iugu Boleto: Linha digitável do boleto para copiar';
        return $gib_merge_fields;
    }
}
if(!function_exists('gib_qrcode_mergetags')){
    function gib_qrcode_mergetags($vars){
        $params = getGatewayVariables('gofasiuguboleto');
	    if(
			$vars['messagename'] === 'Invoice Created' ||
			$vars['messagename'] === 'Invoice Payment Reminder' ||
			$vars['messagename'] === 'First Invoice Overdue Notice' ||
			$vars['messagename'] === 'Second Invoice Overdue Notice' ||
			$vars['messagename'] === 'Third Invoice Overdue Notice'
		){
			$gib_merge_fields	= array();
			$invoice			= localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), (int)(int)gib_setup_admin()['id']);
			if( $invoice['total'] > '0.00' and $invoice['paymentmethod'] === 'gofasiuguboleto'){
				// Saved Billets
				$boleto_saved = array();
				foreach( Capsule::table('gofasiuguboleto') -> where('invoice_id', '=', $vars['relid'])->get(['pdf','bankLine']) as $key => $value ){
					$boletos_for_invoice[$key] = json_decode(json_encode($value), true);
				}
				$boleto_saved = $boletos_for_invoice['0']; // Array
				// Merge Fields
				if (!array_key_exists('gib_pdf', $vars['mergefields'])) {
					$gib_merge_fields['gib_pdf'] = $boleto_saved['pdf'];
				}
				if (!array_key_exists('gib_bankLine', $vars['mergefields'])) {
					$gib_merge_fields['gib_bankLine'] = $boleto_saved['bankLine'];
				}
			}
    	}
		if($params['log']){
			logModuleCall('gofasiuguboleto','email_boleto',$vars,'',$invoice);
		}
		return $gib_merge_fields;
    }
}

if(!function_exists('gib_check_status_updates')){
function gib_check_status_updates($vars){
	require_once __DIR__.'/../../modules/gateways/gofasiuguboleto/includes/functions.php';
	$params = getGatewayVariables('gofasiuguboleto');
	$params_api = gib_api_connect();
	// Get Billets
	try {
		// Add Payment to Invoices
		$log = array();
		$boleto = array();
		$invoices = array();
		// Unpaid invoices IDs
		foreach( Capsule::table('tblinvoices') -> where( 'status', '=', 'Unpaid' ) -> where('paymentmethod','=','gofasiuguboleto')->get( array('id','total','userid')) as $tblinvoices){
			foreach( Capsule::table('gofasiuguboleto') -> where( 'invoice_id', '=', $tblinvoices->id )-> get( array( 'charge_id' ) ) as $local_boleto ) {
				$boleto = gib_charge_verify($local_boleto->charge_id);
				$boletos[$local_boleto->charge_id] = $boleto;
				if((int)$boleto['result_code'] !== 200){
					$error	.= 'Erro ao verificar Boleto: ' . json_encode($boleto);
				}
				if($boleto['result']['status'] === 'paid') {
					$invoices[$tblinvoices->id] = [
						'invoice_id'=>$tblinvoices->id,
						'trans_id'=>$local_boleto->charge_id,
						'transaction_id'=>$local_boleto->charge_id,
						'total'=>$tblinvoices->total,
						'user_id'=>$tblinvoices->userid,
						'paid_amount'=>(float)number_format(($boleto['result']['total_paid_cents']/100), 2,'.',''),
						'fee'=>(float)number_format(($boleto['result']['taxes_paid_cents']/100), 2,'.','')
					];
				}
			} // End Foreach
		} // End Foreach
		// Add Payments
		if (!empty($invoices)) {
			foreach ($invoices as $key => $value) {
				$log['invoice_value'][$value['invoice_id']] = $value;
				$log['invoice_id'][$value['invoice_id']] = $value['invoice_id'];
				if ( (float)$value['paid_amount'] > (float)$value['total'] ) {
					$update_invoice = localAPI('updateinvoice', array( 'invoiceid' => $value['invoice_id'], 'newitemdescription' => array('Acréscimos calculados na emissão do Boleto'),'newitemamount' => array((float)($value['paid_amount'] - $value['total']))), (int)gib_setup_admin()['id'] );
				}
				// - Billet amount is less than the invoice amount
				if ( (float)$value['paid_amount'] < (float)$value['total'] ) {
					$update_invoice = localAPI('updateinvoice', array( 'invoiceid' => $value['invoice_id'], 'newitemdescription' => array('Descontos calculados na emissão do Boleto'),'newitemamount' => array((float)($value['paid_amount'] - $value['total']))), (int)gib_setup_admin()['id'] );
				}
				$add_trans = localAPI( 'addtransaction' ,
					[
						'userid'=>$value['user_id'],
						'invoiceid'=>$value['invoice_id'],
						'description'=>'Boleto pago - baixa dada via cron job',
						'amountin'=>$value['paid_amount'],
						'fees'=>$value['fee'],
						'paymentmethod'=>'gofasiuguboleto',
						'transid'=>'gib-'.$value['trans_id'].'-'.$params_api['api_mode'],
					],
					(int)gib_setup_admin()['id']
				);
				$update_invoice_log[$value['invoice_id']]=$update_invoice;
				$add_trans_log[$value['invoice_id']]=$add_trans;
			}
		}
	}
	catch (Exception $e) {
		$error	.= 'Erro ao listar boletos pagos: ' . $e->getMessage();
		$log['error'] = $error;
	}
	$log['boletos'] = $boletos;
	$log['invoices'] = $invoices;
	$log['update_invoice'] = $update_invoice;
	$log['add_trans'] = $add_trans;
	if($params['log']){
		logModuleCall('gofasiuguboleto','AfterCronJob',array('module_version'=>gib_version(),'params'=>$params),'', array($log) );
		//echo '<pre>',print_r($log),'</pre>';
	}
	return;
}}
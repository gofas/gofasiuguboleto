<?php
/**
 * Módulo iugu Boleto para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14942
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14687
 * @version		0.1.0
 */

use WHMCS\Database\Capsule;
//require __DIR__.'/includes/cron.php';
//require __DIR__.'/includes/hooks.php';
require_once __DIR__.'/includes/config.php';
function gofasiuguboleto_link($params){
	if(stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') !== false ){
		require __DIR__.'/includes/functions.php';
		$log['params'] = $params;
		if($params['amount'] >= $params['minimunamount']){	
			foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gibwhmcsurl') -> get( array( 'value','created_at') ) as $gibwhmcsurl_ ){
				$gibwhmcsurl					= $gibwhmcsurl_->value;
			}
			$result .= '<script>
			function copy_tooltip() {
				var copyText = document.getElementById("qrcodeforcopy");
				copyText.select();
				copyText.setSelectionRange(0, 99999);
				navigator.clipboard.writeText(copyText.value);
				var tooltip = document.getElementById("copy_tooltip");
				tooltip.innerHTML = "Copiado!"; //"Copied: " + copyText.value;
			  }
			  function outFunc() {
				var tooltip = document.getElementById("copy_tooltip");
				//tooltip.innerHTML = "Copiar linha digitável";
				setTimeout(function(){ tooltip.innerHTML = "Copiar linha digitável"; }, 1000);
			  }
			</script>';
			$result .= '<input type="hidden" id="system_url" value="'.$gibwhmcsurl.'">';
			$result .= '<input type="hidden" id="invoice_id" value="'.$params['invoiceid'].'">';
			$params_api = gib_api_connect();
			$customer = gib_customer($params['clientdetails']['id']);
			$log['customer'] = $customer;
			$saved_boleto = gib_get_local_qrc($params['invoiceid']);
			
			$saved_boleto_amount = (int)$saved_boleto['amount']; // 4898
			$invoice_int_amount = (int)preg_replace("/[^0-9]/", "", $params['amount']); // 4898
			$saved_boleto_float_amount = (float)number_format(($saved_boleto['amount']/100), 2,'.',''); // 48.98

			$log['saved_boleto_amount'] = $saved_boleto_amount;
			$log['invoice_int_amount'] = $invoice_int_amount;
			$log['saved_boleto_float_amount'] = $saved_boleto_float_amount;
			
			$log['saved_boleto'] = $saved_boleto;

			$GetInvoiceResults			= localAPI('getinvoice',array('invoiceid'=>$params['invoiceid'] ), (int)$params['admin'] );
			$datediff = gib_datediff($GetInvoiceResults['duedate'],$params['diasparavencimento']);
			$log['datediff'] = $datediff;
			
			$now_int = (int)date('Ymd');
			$billet_duedate_int = (int)preg_replace("/[^0-9]/", "", $saved_boleto['duedate']);
			
			if($saved_boleto['pdf'] and $saved_boleto_amount === $invoice_int_amount and $billet_duedate_int >= $now_int ){
				$charge_verify = gib_charge_verify($saved_boleto['charge_id']);
				$log['charge_verify'] = $charge_verify;
				if((string)$charge_verify['result']['status'] === (string)'paid'){
					$add_trans = gib_add_trans($params['clientdetails']['id'], $params['invoiceid'], (float)number_format( $charge_verify['result']['total_paid_cents']/100,  2, '.', ''), (float)number_format( $charge_verify['result']['taxes_paid_cents']/100,  2, '.', ''), 'gib-'.$saved_boleto['charge_id'].'-'.$params_api['api_mode'], 'Boleto pago - baixa dada ao acessar a fatura');
					header_remove();
					header("Location: ".$gibwhmcsurl.'/viewinvoice.php?id='.$params['invoiceid'],true,303);
					exit;
				}

				$result .= $params['message'];
				$result .= '<a target="_blank" class="btn btn-default" style=" float: left;font-size: 14px;" href="'.$saved_boleto['pdf'].'">Visualizar o Boleto</a>';
				$result .= '<input value="'.$saved_boleto['bankLine'].'" id="qrcodeforcopy" style="width: 0px;height: 0px;font-size: 0px;padding: 0px;display:none;">';
				$result .= '<button style="position: relative;font-size: 14px; display: inline-block;float: right"  id="copy_tooltip" class="btn btn-default" onclick="copy_tooltip()" onmouseout="outFunc()">Copiar linha digitável</button>';
				$log['saved_boleto'] = $saved_boleto;
				if($error){
					$result = '<b style="color:red;">Erro: '.$error.'</b>';
				}
				if($params['log']){
					foreach( Capsule::table('tblconfiguration') -> where('setting','=','gib_version') -> get(['value']) as $gib_version_ ){
						$gib_version			= $gib_version_->value;
					}
					logModuleCall('gofasiuguboleto','gofasiuguboleto_link',array('module_version'=>$gib_version,'postfields'=>$postfields),'', $log );
					//echo '<pre style="height:250px;">',$url,'<br>',print_r($log),'</pre>';
				}
				if(!$error and $params['redirecttobillet'] and stripos($_SERVER['REQUEST_URI'], 'viewinvoice') ){
					header_remove();
					header("Location: ".$saved_boleto['pdf'],true,303);
					exit;
				}
				else {
					return $result;
				}
			}
			if(!$saved_boleto['pdf'] || !$saved_boleto['bankLine'] || $saved_boleto_amount !== $invoice_int_amount || $billet_duedate_int < $now_int){
				$line_items = array();
				foreach( $GetInvoiceResults['items']['item'] as $Value){
					//$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');
					$line_items[]	= [
						'description'=>substr( $Value['description'],  0, 80),
						'quantity'=>1,
						'price_cents'=> (int)preg_replace("/[^0-9]/", "", $Value['amount']),
					];
				}
				$postfields = [
					'charge'=> [
						'order_id'=> $params['invoiceid'].time(),
						'method'=>'bank_slip',
						'restrict_payment_method'=>true,
						'email'=>$customer['email'],
						'bank_slip_extra_days'=>(int)$datediff['datediff'],
						'items'=>$line_items,
						'payer' => [
							'name'=> $customer['name'],
							'cpf_cnpj'=> $customer['document'],
							'email'=>$customer['email'],
							'address'=> [
								'zip_code'=> $customer['postcode'],
								'street'=> $customer['address'],
								'number'=> $customer['number'],
								'complement'=> $customer['complement'],
								'district'=> $customer['neighborhood'],
								'city'=> $customer['city'],
								'state'=> $customer['state']
							],
						],
					],
				];
				$boleto_ = gib_charge($postfields);
				if((int)$boleto_['result_code'] !== (int)200){
					//$error .= $boleto_['result_code'].': ';
					foreach($boleto_['result']['errors'] as $key=>$value){
						$error .= $key.' '.implode(", ",$value);
					}
					
				}
				$log['postfields_json'] = json_encode($postfields['charge']);
				$log['boleto_'] = $boleto_;
				if((int)$boleto_['result']['success']===1){
				
					if(!$saved_boleto['pdf'] || !$saved_boleto['bankLine']){
						$save_qrc = gib_save_qrc(
							[
								'invoice_id'=>$params['invoiceid'],
								'charge_id'=>$boleto_['result']['invoice_id'],
								'amount'=>$invoice_int_amount,
								'duedate'=>(string)$datediff['duedate'],
								'pdf'=>$boleto_['result']['pdf'],
								'bankLine'=>$boleto_['result']['identification'],
								'api_mode'=>$params_api['api_mode'],
							]
						);
						if($save_qrc !== 'success'){
							$error .= $save_qrc;
						}
					}
					if($saved_boleto['pdf']){
						$update_qrc = gib_update_qrc(
							[
								'invoice_id'=>$params['invoiceid'],
								'charge_id'=>$boleto_['result']['invoice_id'],
								'amount'=>$invoice_int_amount,
								'duedate'=>(string)$datediff['duedate'],
								'pdf'=>$boleto_['result']['pdf'],
								'bankLine'=>$boleto_['result']['identification'],
								'api_mode'=>$params_api['api_mode'],
							]
						);
						//$update_qrc = gib_update_qrc($update_qrc);
						if($update_qrc !== 'success'){
							$error .= $update_qrc;
						}
					}
					$result .= $params['message'];
					$result .= '<a target="_blank" class="btn btn-default" style=" float: left;font-size: 14px;" href="'.$boleto_['result']['pdf'].'">Visualizar o Boleto</a>';
					$result .= '<input value="'.$boleto_['result']['identification'].'" id="qrcodeforcopy" style="width: 0px;height: 0px;font-size: 0px;padding: 0px;display:none;">';
					$result .= '<button style="position: relative;font-size: 14px; display: inline-block;float: right"  id="copy_tooltip" class="btn btn-default" onclick="copy_tooltip()" onmouseout="outFunc()">Copiar linha digitável</button>';
				}
			}
			if($error){
		    	$result = '<b style="color:red;">Erro: '.$error.'</b>';
			}
			if($params['log']){
				foreach( Capsule::table('tblconfiguration') -> where('setting','=','gib_version') -> get(['value']) as $gib_version_ ){
					$gib_version			= $gib_version_->value;
				}
				logModuleCall('gofasiuguboleto','gofasiuguboleto_link',array('module_version'=>$gib_version,'postfields'=>$postfields),'', $log );
				//echo '<pre style="height:250px;">',$url,'<br>',print_r($log),'</pre>';
			}
			if(!$error and $params['redirecttobillet'] and stripos($_SERVER['REQUEST_URI'], 'viewinvoice') ){
				header_remove();
				header("Location: ".$boleto_['result']['pdf'],true,303);
				exit;
			}
			else {
				return $result;
			}
		}
		elseif( $params['amount'] < $params['minimunamount']){
			$error .= 'O valor mínimo para utilizar esse método de pagamento é '.number_format( $params['minimunamount'] ,  2, ',', '.').'.';
			return $error;
		}
	}
}
<?php
/**
 * Módulo iugu Boleto para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14942
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14687
 * @version		1.0.0
 */
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';
if(!defined("WHMCS")){die();}
use WHMCS\Database\Capsule;
use WHMCS\Aplication;
if(!function_exists('gib_api_connect')){
	function gib_api_connect(){
		$params = getGatewayVariables('gofasiuguboleto');
		if($params['sandbox']){
			$params_api = [
				'api_mode' => 'sandbox',
				'account_id' => $params['account_id'],
				'api_token' => $params['sandbox_api_token'],
				'charge_url' => 'https://api.iugu.com/v1',
			];
		}
		if(!$params['sandbox']){
			$params_api = [
				'api_mode' => 'live',
				'account_id' => $params['account_id'],					// $params_api['api_mode']
				'api_token' => $params['api_token'],							// $params_api['galax_id']
				'charge_url' => 'https://api.iugu.com/v1',					// $params_api['charge_url']												// $params_api['sandbox']
			];
		}
		return $params_api;
	}
}
if( !function_exists('gib_charge') ){
	function gib_charge($postfields){
		$params_api = gib_api_connect();
    	$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/charge',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER => array(
		    	'Authorization: Basic '.base64_encode((string)$params_api['api_token'].':'),
				'Content-Type: application/json',
				'Accept: application/json',
		  	),
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($postfields['charge']),
		));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$result_code,'result'=>$result];
	}
}
if( !function_exists('gib_charge_verify') ){
	function gib_charge_verify($charge_id){
		$params_api = gib_api_connect();
		$curl = curl_init();
		//$access_token_ = gib_get_token();
		//$access_token = $access_token_['result']['access_token'];

		curl_setopt_array($curl, array(
			CURLOPT_URL => $params_api['charge_url'].'/invoices/'.$charge_id,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
		    	'Authorization: Basic '.base64_encode((string)$params_api['api_token'].':'),
				'Content-Type: application/json',
				'Accept: application/json',
		  	),
		));
		$result = json_decode(curl_exec($curl),true);
		$result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['result_code'=>$result_code,'result'=>$result];
	}
}
if( !function_exists('gib_get_string_between') ){
	function gib_get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);   
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}
}
if( !function_exists('gib_add_trans') ){
	function gib_add_trans( $user_id, $invoice_id, $amount, $fee, $charge_id, $description ){
		$params = getGatewayVariables('gofasiuguboleto');
 		$addtransvalues['userid'] = $user_id;
 		$addtransvalues['invoiceid'] = $invoice_id;
 		$addtransvalues['description'] = $description;
 		$addtransvalues['amountin'] = $amount;
 		$addtransvalues['fees'] = $fee;
 		$addtransvalues['paymentmethod'] = 'gofasiuguboleto';
 		$addtransvalues['transid'] = $charge_id;
 		$addtransvalues['date'] = date('d/m/Y');
		$addtransresults = localAPI( "addtransaction", $addtransvalues, (int)gib_setup_admin()['id']);
		$delete_qrc = Capsule::table('gofasiuguboleto')->where('invoice_id', '=',$invoice_id)->delete();
		$gib_update_stats = gib_update_stats();
		
		if( $addtransresults['result'] === 'success'){
			return array('values'=>$addtransvalues, 'result'=>$addtransresults);
		}
		elseif($addtransresults['result'] !== 'success'){
			$error = '<b>Não foi possível gravar a transação.</b>';
			return array('error'=>$error, 'values'=>$addtransvalues, 'result'=>$addtransresults,'update_stats'=>$gib_update_stats);
		}
	}
}

if(!function_exists('gib_customer') ){
	function gib_customer($client_id){
		//Determine custom fields id
		$params = getGatewayVariables('gofasiuguboleto');
		$client = localAPI('GetClientsDetails',array( 'clientid' => $client_id, 'stats' => false, ), (int)gib_setup_admin()['id']);
		foreach( Capsule::table('tblcustomfields')->where('type','=','client')->get() as $customfield ){
			$customfield_id = $customfield->id;
			$customfield_name = strtolower($customfield->fieldname);
			// cpf
			if(strpos($customfield_name, 'cpf') !== false and strpos($customfield_name,'cnpj') === false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}	
			// cnpj
			if(strpos($customfield_name, 'cnpj') !== false and strpos($customfield_name,'cpf') === false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// cpf + cnpj
			if( strpos( $customfield_name, 'cpf') !== false and strpos( $customfield_name, 'cnpj') !== false ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// Inscrição Estadual
			if( strpos( $customfield_name, 'inscrição estadual') !== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$ie = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// Complemento Custom Field
			if( strpos( $customfield_name, 'complemento') !== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$complement = $customfieldvalue->value;
				}
			}
			// Número Custom Field
			if( strpos( $customfield_name, 'numero')!== false ||  strpos( $customfield_name, 'número')!== false ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$number = $customfieldvalue->value;
				}
				if(!$number){
					$number = preg_replace('/[^0-9]/', '', $client['address1']);
				}
			}
			else {
				$number = preg_replace('/[^0-9]/', '', $client['address1']);
			}
			// Emitir Custom Field
			if( strpos( $customfield_name, 'emitir nfe')!== false || strpos( $customfield_name, 'emitir nfse')!== false || strpos( $customfield_name, 'emitir nfs-e')!== false || strpos( $customfield_name, 'emitir nf-e')!== false){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$issue_nfe = $customfieldvalue->value;
				}
				if(!$issue_nfe){
					$issue_nfe = false;
				}
			}
			// nascimento
			if( strpos( $customfield_name, 'nascimento') ){
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client_id) -> get( array( 'value') ) as $customfieldvalue ){
					$birt_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$birthday_pre			= preg_replace('/[^\da-z]/i', '', $birt_customfield_value);
					if(strlen($birthday_pre) === 8){
						$birth_ = $birthday_pre;
					}
					elseif( strlen($birthday_pre) === 7 ){
						$birth_ = '0'.$birthday_pre;
					}
					$birth_Y					= substr($birth_, -4);
					$birth_m					= substr($birth_, 2, -4);
					$birth_d					= substr($birth_, 0, -6);
					$birthday_us = $birth_Y.'-'.$birth_m.'-'.$birth_d; // 2021-02-20
					$birthday_br = $birth_d.'/'.$birth_m.'/'.$birth_Y; // 20/02/2021
					$birthday_raw = $customfieldvalue->value;
				}
			}
			foreach(Capsule::table('tblcustomfieldsvalues')->where('fieldid','=',$customfield_id)->where('relid','=',$client_id)->get(array('value')) as $customfieldvalue ){
				$custom_fields[$customfield_name] = $customfieldvalue->value;
			}
		}
		//
		// Cliente possui CPF e CNPJ
		// CPF com 1 nº a menos, adiciona 0 antes do documento
		if( strlen( $cpf_customfield_value ) === 10 ){
			$cpf = '0'.$cpf_customfield_value;
		}
		// CPF com 11 dígitos
		elseif( strlen( $cpf_customfield_value ) === 11){
			$cpf = $cpf_customfield_value;
		}
		// CNPJ no campo de CPF com um dígito a menos
		elseif( strlen( $cpf_customfield_value ) === 13 ){
			$cpf = false; 
			$cnpj = '0'.$cpf_customfield_value;
		}
		// CNPJ no campo de CPF
		elseif( strlen( $cpf_customfield_value ) === 14 ){
			$cpf 				= false;
			$cnpj				= $cpf_customfield_value;
		}
		// cadastro não possui CPF
		elseif( !$cpf_customfield_value || strlen( $cpf_customfield_value ) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen( $cpf_customfield_value ) !== 13 || strlen($cpf_customfield_value) !== 14 ){	
			$cpf = false;
		}
		// CNPJ com 1 nº a menos, adiciona 0 antes do documento
		if( strlen($cnpj_customfield_value) === 13 ){
			$cnpj = '0'.$cnpj_customfield_value;
		}
		// CNPJ com nº de dígitos correto
		elseif( strlen($cnpj_customfield_value) === 14 ){
			$cnpj = $cnpj_customfield_value;
		}
		// Cliente não possui CNPJ
		elseif( !$cnpj_customfield_value and strlen( $cnpj_customfield_value ) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen( $cpf_customfield_value ) !== 13 and strlen( $cpf_customfield_value ) !== 14  ){
			$cnpj = false;
		}

		if( ( $cpf and $cnpj ) or ( !$cpf and $cnpj ) ){
			if( $client['companyname'] ){
				$name	= $client['companyname'];
			}
			elseif( !$client['companyname'] ){
				$name	= $client['firstname'].' '.$client['lastname'];
			}
			$doc_type	= 'J';
			$document	= $cnpj;
		}
		elseif( $cpf and !$cnpj ){
			$name	= $client['firstname'].' '.$client['lastname'];
			$doc_type	= 'F';
			$document	= $cpf;
		}
		/// Formated Array
		$customer=[
			'id'=>$client_id,
			'email'=>$client['email'],
			'name'=>$name,
			'names'=>['firstname'=>$client['firstname'],'lastname'=>$client['lastname'],'companyname'=>$client['companyname']],
			'address'=>str_replace(',','',preg_replace('/[0-9]+/i','',$client['address1'],1)),
			'number'=>$number,
			'neighborhood'=>$client['address2'],
			'complement'=>$complement,
			'city'=>$client['city'],
			'state'=>$client['state'],
			'postcode'=>preg_replace("/[^\da-z]/i", "",$client['postcode']),
			'phone'=>preg_replace('/[^\da-z]/i', '', $client['phonenumber']),
			'doc_type'=>$doc_type,
			'document'=>$document,
			'ie'=>$ie,
			'issue_nfe'=>$issue_nfe,
			'birthday'=>['raw'=>$birthday_raw,'br'=>$birthday_br,'us'=>$birthday_us],
			'custom_fields'=>$custom_fields,
		];
		return $customer;
	}
}
if( !function_exists('gib_save_qrc') ){
	function gib_save_qrc($qr_code){
		$data = array(
			'invoice_id'=>$qr_code['invoice_id'],
			'charge_id'=>$qr_code['charge_id'],
			'amount'=>$qr_code['amount'],
			'duedate'=>$qr_code['duedate'],
			'pdf'=>$qr_code['pdf'],
			'bankLine'=>$qr_code['bankLine'],
			'api_mode'=>$qr_code['api_mode'],
			'created_at'=>date("Y-m-d H:i:s"),
			'updated_at'=>date("Y-m-d H:i:s"),
		);
	try {
		$save_qrc = Capsule::table('gofasiuguboleto')->insert($data);
		return 'success';
	}
	catch (\Exception $e){
		return $e->getMessage();
	}
}}
if(!function_exists('gib_update_qrc') ){
	function gib_update_qrc($data){
		$params = getGatewayVariables('gofasiuguboleto');
		$local_qrc = gib_get_local_qrc($data['invoice_id']);
		$data['created_at'] = $local_qrc['created_at'];
		$data['updated_at']= date("Y-m-d H:i:s");
		
	try {
		$update_qrc = Capsule::table('gofasiuguboleto')->where('invoice_id', '=',$data['invoice_id'])->update($data);
		if($params['log']){
			logModuleCall('gofasiuguboleto','gib_update_qrc',array('data'=>$data),'post',array('update_qrc' => $update_qrc),'replaceVars');
		}
		return 'success';
	}
	catch (\Exception $e){
		if($params['log']){
			logModuleCall('gofasiuguboleto','gib_update_qrc',array('data'=>$data),'post',array('update_qrc' => $update_qrc),'replaceVars');
		}
		return $e->getMessage();
	}
}}
if( !function_exists('gib_get_local_qrc') ){
	function gib_get_local_qrc($invoice_id){
		$params_api = gib_api_connect();
		foreach( Capsule::table('gofasiuguboleto')->where('invoice_id','=', $invoice_id)->where('api_mode','=',$params_api['api_mode'])->get() as $key => $value ){
			$qrc_for_invoice[$key] = json_decode(json_encode($value), true);
		}
		return $qrc_for_invoice['0'];
	}
}
if( !function_exists('gib_verify_install') ){
	function gib_verify_install(){
		if( !Capsule::schema()->hasTable('gofasiuguboleto') ){
			try {
				Capsule::schema()->create('gofasiuguboleto', function($table){
					$table->string('invoice_id');
					$table->string('charge_id');
					$table->string('amount');
					$table->string('duedate');
					$table->text('pdf');
					$table->string('bankLine');
					$table->string('api_mode');
					$table->string('created_at');
					$table->string('updated_at');
				});
			}
			catch (\Exception $e){
				$error .= "Não foi possível criar a tabela do módulo no banco de dados: {$e->getMessage()}";
			}
		}
		if(!$error){
			return array('sucess'=>1);
		}
		elseif($error){
			return array('error'=>$error);
		}
	}
}
// Admin functions
if( !function_exists('gib_whmcs_url') ){
	function gib_whmcs_url(){
		$self = App::self();
		$whmcs_admin_path = gib_get_protected_property($self, 'customadminpath');
		$whmcs_url = App::getSystemUrl();
		$admin_url = $whmcs_url.$whmcs_admin_path;
		return ['url'=>$whmcs_url,'admin_url'=>$admin_url,'admin_path'=>$whmcs_admin_path];
	}
}
if( !function_exists('gib_get_embed') ){
	function gib_get_embed($page_id,$referer,$module_version){
		$query = 'https://gofas.net/cliente/gofas/updates/?embed='.$page_id.'&referer='.$referer.'&version='.$module_version;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_URL, $query);
		$embed = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['embed'=>$embed,'http_code'=>$http_status];
	}
}
if(!function_exists('gib_encrypt')){
	function gib_encrypt($q) {
	    $encryptionMethod = "AES-256-CBC";
		$secretHash = "535ba9979bc6c7ff151f2136cd13b0f9";
	    return openssl_encrypt($q, $encryptionMethod, $secretHash);
	}
}
if(!function_exists('gib_decrypt')){
	function gib_decrypt($q){
		$encryptionMethod = "AES-256-CBC";
		$secretHash = "535ba9979bc6c7ff151f2136cd13b0f9";
	    return openssl_decrypt($q, $encryptionMethod, $secretHash);
	}
}
if(!function_exists('gib_get_version') ){
	function gib_get_version($page_id,$referer,$module_version){
		//$currentUser = new \WHMCS\Authentication\CurrentUser;
		$current_admin = gib_current_admin();
		//$admin_ = json_decode(json_encode($currentUser->admin()),true);
		//$admin = ['email'=>$admin_['email'],'firstname'=>$admin_['firstname'],'lastname'=>$admin_['lastname']];
		$query = '?software_id='.$page_id.'&install_url='.$referer.'&current_version='.$module_version.'&installer_email='.$current_admin['email'].'&installer_firstname='.$current_admin['firstname'].'&installer_lastname='.$current_admin['lastname'].'&action=verify'.gib_sysinfo();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_URL, 'https://gofas.net/br/updates/stats.php'.$query);
		$available_version_ = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['version'=>$available_version_,'http_code'=>$http_status];
	}
}
if(!function_exists('gib_update_stats') ){
	function gib_update_stats(){
		$params = getGatewayVariables('gofasiuguboleto');
		if($params['sandbox']){
			return;
		}
		$params_api = gib_api_connect();
		$whmcs_url = gib_whmcs_url();
		$setup_admin = gib_setup_admin();
		
		$query = '?software_id=14942&install_url='.$whmcs_url['admin_url'].'&installer_email='.$setup_admin['email'].'&installer_firstname='.$setup_admin['firstname'].'&installer_lastname='.$setup_admin['lastname'].'&action=charge';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_URL, 'https://gofas.net/br/updates/stats.php'.$query);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return ['query'=>$query,'response'=>$response,'http_code'=>$http_status];
	}
}

if(!function_exists('gib_sysinfo')){
	function gib_sysinfo(){
		foreach( Capsule::table('tblconfiguration')
		->where('setting','=','Version')
		->get(['value']) as $data1 ){
			$Version = $data1->value;
		}
		foreach( Capsule::table('tblconfiguration')
		->where('setting','=','CronPHPVersion')
		->get(['value']) as $data1 ){
			$PHPVersion = $data1->value;
		}
		return '&whmcs_version='.$Version.'&php_version='.$PHPVersion;
	}
}
if(!function_exists('gib_verify_module_updates')){
	function gib_verify_module_updates($page_id,$referer,$module_version){
		foreach( Capsule::table('tblconfiguration')->where('setting','=','gib_version')->get(['value','created_at','updated_at']) as $version_ ){
			$version		= json_decode($version_->value, true);
			$local_version	= $version['local_version'];
			$last_version	= $version['last_version'];
			$embed			= $version['check'];
			$created_at		= $version_->created_at;
			$updated_at		= $version_->updated_at;
			//$available_version	= (int)preg_replace("/[^0-9]/","",$version['last_version']);
		}
		///// Get
		if(!$version){
			$get_version = gib_get_version($page_id,$referer,$module_version);
			$get_embed	 = gib_get_embed($page_id,$referer,$module_version);
			
			if((int)$get_version['http_code'] !== 200){
				$error .= $get_version['http_code'].' '.$get_version['version'];
			}
			else{
				$available_version = $get_version['version'];
			}
		}
		if($version and strtotime($updated_at) < strtotime("-1 day")){
			$get_version = gib_get_version($page_id,$referer,$module_version);
			$get_embed	 = gib_get_embed($page_id,$referer,$module_version);
			if((int)$get_version['http_code'] !== 200){
				$error .= $get_version['http_code'].' '.$get_version['version'];
			}
			else{
				$available_version = $get_version['version'];
			}
		}
		if($version and (string)$module_version !== (string)$local_version){
			$get_version = gib_get_version($page_id,$referer,$module_version);
			$get_embed	 = gib_get_embed($page_id,$referer,$module_version);
			if((int)$get_version['http_code'] !== 200){
				$error .= $get_version['http_code'].' '.$get_version['version'];
			}
			else{
				$available_version = $get_version['version'];
			}
		}
		if($version and strtotime($updated_at) > strtotime("-1 day")){
			$available_version = $last_version;
		}
		// insert
		if(!$version and $get_version['version'] and $get_embed['embed']){
			$local_version = $module_version;
			$last_version = $get_version['version'];
			$embed		  = gib_encrypt($get_embed['embed']);
			$created_at		= date("Y-m-d H:i:s");
			$updated_at		= date("Y-m-d H:i:s");

			try { Capsule::table('tblconfiguration')->insert(array(
				'setting' => 'gib_version',
				'value' => json_encode([
					'local_version'=>$module_version,
					'last_version'=>$get_version['version'],
					'check'=>gib_encrypt($get_embed['embed']),
					'admin'=>gib_current_admin(),
				]),
				'created_at' => $created_at,
				'updated_at' => $updated_at
			));
			}
			catch (\Exception $e){
				$error .= $e->getMessage();
			}
		}
		// update
		if($version and $get_version['version'] and $get_embed['embed'] and strtotime($updated_at) < strtotime("-1 day") and (
			$available_version !== $module_version ||
			$local_version !== $module_version ||
			$last_version !== $available_version
		)){
			try {
				Capsule::table('tblconfiguration')->where('setting','gib_version')->update([
					'value' => json_encode([
						'local_version'=>$module_version,
						'last_version'=>$available_version,
						'check'=>gib_encrypt($get_embed['embed']),
						'admin'=>gib_current_admin(),
					]),
					'created_at' =>  $created_at,
					'updated_at' => date("Y-m-d H:i:s")]
				);
			}
			catch (\Exception $e){
				$error .= $e->getMessage();
			}
		}
		// update
		if($version and $get_version['version'] and $get_embed['embed'] and (string)$local_version !== (string)$module_version){
			try {
				Capsule::table('tblconfiguration')->where('setting','gib_version')->update([
					'value' => json_encode([
						'local_version'=>$module_version,
						'last_version'=>$available_version,
						'check'=>gib_encrypt($get_embed['embed']),
						'admin'=>gib_current_admin(),
					]),
					'created_at' =>  $created_at,
					'updated_at' => date("Y-m-d H:i:s")]
				);
			}
			catch (\Exception $e){
				$error .= $e->getMessage();
			}
		}
		$module_version_int = (int)preg_replace("/[^0-9]/", "", $module_version);
		$available_version_int = (int)preg_replace("/[^0-9]/", "", $available_version);
		if( $available_version_int === $module_version_int ){
			$message = '<p style="color: green"><i class="fas fa-check-square"></i> Você está executando a versão mais recente do módulo.</p>';
		}
		if( $available_version_int > $module_version_int ){
			$message = '<p style="font-size: 14px; color: red;"><i class="fas fa-exclamation-triangle"></i> Atualização disponível, verifique a <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p='.$page_id.'" target="_blank">versão '.$available_version.'</a>';
		}
		if( $available_version_int < $module_version_int ){
			$message = '<p style="font-size: 14px; color: orange;"><i class="fas fa-exclamation-triangle"></i> Você está executando uma versão Beta desse módulo.<br>Baixar versão estável: <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p='.$page_id.'" target="_blank">v'.$available_version.'</a>';
		}
		return [
			'version'=>$version,
			'get_version'=>$get_version,
			'message' => $message,
			'check'=> $embed,
			'error' => $error,
		];
	}
}
if(!function_exists('gib_version')){
	function gib_version($opt=1){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gib_version') -> get( array( 'value','created_at') ) as $gib_version_ ){
			$gib_version				= $gib_version_->value;
			$gib_version_created_at	= $gib_version_->created_at;
		}
		if($opt=1){ // local_version string
			$version = json_decode($gib_version, true);
			return $version['local_version'];
		}
		if($opt=2){ // local_version integer
			$version = json_decode($gib_version, true);
			return (int)preg_replace("/[^0-9]/", "", $version['local_version']);
		}
		if($opt=3){ // full
			return$gib_version;
		}
	}
}
if(!function_exists('gib_current_admin')){
	function gib_current_admin(){
		$currentUser = new \WHMCS\Authentication\CurrentUser;
		$admin = json_decode(json_encode($currentUser->admin()),true);
		return $admin;
	}
}
if(!function_exists('gib_setup_admin')){
	function gib_setup_admin(){
	foreach( Capsule::table('tblconfiguration')->where('setting','=','gib_version')->get(['value']) as $version_ ){
		$version		= json_decode($version_->value, true);
		$admin			= $version['admin'];
	}
	return $admin;
}}
if(!function_exists('gib_tblticketdepartments')){
	function gib_tblticketdepartments(){
		$tblticketdepartments[] = '';
		foreach( Capsule::table('tblticketdepartments') -> get() as $tblticketdepartments_ ){
			$tblticketdepartments_id			= $tblticketdepartments_->id;
			$tblticketdepartments_name			= $tblticketdepartments_->name;
			$tblticketdepartments[]				= $tblticketdepartments_id.' - '.$tblticketdepartments_name;
		}
		return $tblticketdepartments;
	}
}
if(!function_exists('gib_line_items')){
	function gib_line_items($invoice_id){
		$invoice			= localAPI('getinvoice',array('invoiceid'=>$invoice_id),(int)gib_setup_admin()['id']);
		// Itens de Linha - Serviços/produtos relacionados à fatura
		$invoice_items_item	= $invoice['items']['item'];
		$line_items = array();
		foreach( $invoice_items_item as $Value){
			$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.');	
		}
		return substr( implode("\n",$line_items),  0, 400);
	}
}
if(!function_exists('gib_datediff')){
	function gib_datediff($invoice_duedate,$diasparavencimento=1){
		if( $diasparavencimento and $diasparavencimento > 0 and $diasparavencimento > 1){
			$diasParaVencimento = '+'.$diasparavencimento.' days';
		}
		if( $diasparavencimento == '0'){
			$diasParaVencimento = '+1 day';
		}
		if( $diasparavencimento == '1'){
			$diasParaVencimento = '+1 day';
		}
		if( $diasparavencimento > '30'){
			$diasParaVencimento = '+30 days';
		}
		if( !$diasparavencimento ){
			$diasParaVencimento = '+1 day';
		}
		if( $invoice_duedate > date('Y-m-d') ){
			$billet_duedate = $invoice_duedate;
		}
		if( $invoice_duedate === date('Y-m-d') ){
			$billet_duedate = date('Y-m-d', strtotime($diasParaVencimento));
		}
		if( $invoice_duedate < date('Y-m-d') ){
			$billet_duedate = date('Y-m-d', strtotime( $diasParaVencimento )); // Se fatura já venceu, data de vencimento do boleto = Hoje + X dia(s)
		}
		$now = (int)date('Ymd');
		$due_date = (int)preg_replace("/[^0-9]/", "", $billet_duedate);
		$datediff = $due_date-$now;
		return ['datediff'=>$datediff,'duedate'=>$billet_duedate];
	}
}
if(!function_exists('gib_get_protected_property')){
	function gib_get_protected_property($object, $property){
	    $reflectedClass = new \ReflectionClass($object);
	    $reflection = $reflectedClass->getProperty($property);
	    $reflection->setAccessible(true);
	    return $reflection->getValue($object);
	}
}
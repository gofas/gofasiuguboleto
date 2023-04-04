<?php
/**
 * Módulo iugu Boleto para WHMCS
 * @copyright	2023 Gofas Software
 * @see			https://gofas.net/?p=14942
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14687
 * @version		1.2.0
 */
use WHMCS\Aplication;
$self=App::self();
if(!function_exists('gib_get_protected_property')){
	function gib_get_protected_property($object, $property){
	    $reflectedClass = new \ReflectionClass($object);
	    $reflection = $reflectedClass->getProperty($property);
	    $reflection->setAccessible(true);
	    return $reflection->getValue($object);
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
$root_dir = '/'.gib_get_string_between(gib_get_protected_property(gib_get_protected_property(gib_get_protected_property(gib_get_protected_property($self,'clientTemplate'),'config'),'configFile'),'path'),'/','/templates/');
require_once $root_dir.'/modules/gateways/gofasiuguboleto.php';
<?php
//Archivo que contiene la configuración del modulo de webpay para codeigniter
$config['webpay'] = array(
	'error_reporting'		=> true,
	'log_level'				=> 'ALL',
	'trasaction_type'		=> 'TR_NORMAL_WS',
	'commerce_code'			=> '597032311245',
	'service_id'			=> '597032311245',
	'cert_file_name'		=> '597032311245.crt',	
	'cert_private_key'		=> '597032311245.key',	
	'cert_server_cert'		=> 'serverTBK.crt',	
	'commerce_email'		=> 'contacto@iguales.cl',
	'service_endpoint'		=> 'https://webpay3g.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl',
	'path_return_url'		=> 'uri_retorno',
	'path_fin_url'			=> 'uri_fin'
);
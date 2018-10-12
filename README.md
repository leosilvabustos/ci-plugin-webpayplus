
# plugin-webpay-ci
Librería de integración con webpay plus

### Instalación
Para implementar se deben agregar los siguientes archivos en las siguientes ubicaciones

```+
       ├── Project Folder-   
	   │   ├── applications
       │   │  ├── config
       │   │  │   ├── webpay.php 		#Archivo de configuración de la librería 
       │   │  ├── libraries
       │   │  │   ├── ws-webpay
	   │   │  │   │   ├── cert			#Carpeta de almacenamiento de los certificados
	   │   │  │   │   ├── logs			#Carpeta de almacenamiento de los logs
		   
```
### Configuración
Se debe configurar el archivo *projects/applications/config/**webpay.php***
```php
<?php
//Archivo que contiene la configuración del modulo de webpay para codeigniter
$config['webpay'] = array(
	'error_reporting'		=> true,					# Reporte de errores
	'log_level'				=> 'ALL',					# Nivel del log (debug, info, error, all)
	'trasaction_type'		=> 'TR_NORMAL_WS',			# Tipo de transacción
	'commerce_code'			=> '597032311245',			# Código del comercio
	'service_id'			=> '597032311245',			# Código del serviio
	'cert_file_name'		=> '597032311245.crt',		# Certificado publico del comercio
	'cert_private_key'		=> '597032311245.key',		# Certificado privado del comercio
	'cert_server_cert'		=> 'serverTBK.crt',			# Certificado público transbank
	'commerce_email'		=> 'contacto@business.cl',	# Contacto del comercio
	'service_endpoint'		=> 'https://webpay3g.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl', # Endpoint de transbank
	'path_return_url'		=> 'uri_retorno',			# Url expuesta para el retorno del inicio de la transacción
	'path_fin_url'			=> 'uri_fin'				# Url expuesta para el fin de la transacción
);
```
### Implementación
#### Instancia
```php
	<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
		class Controller extends CI_Constroller
			
			protected $wsWebpay;
			
			public function __construct(){
				require_once APPPATH . 'libraries/ws-webpay/WsWebpay.php';	
				$this->wsWebpay = new WsWebpay();
			}
```
### InitTransaction
Método que inicia la transacción

```php
	<?php
	...
		public function payment(){
			//Cógido de validación y negocio
			WsWebpay::debug('Se inicia correctamente la transaccion con webpay, orden actualizada a estado 2.');
			$result = $this->wsWebpay->initTransaction($orderId, $monto);
			WsWebpay::debug("result: " . WsWebpay::dumpObject($result));
			// Si el pago se realiza correctamente
			if(is_object($result) && !empty($result)) {
				//Payment register
			} else {
				//Log error, redirect url error
			}
		}
```
## API
### Debug
Permite escribir en los archivos de logs
```php
	<?php
	...
		WsWebpay::debug('Cualquier cosa');
```
### DumpObject
Permite serializar un objeto, util para registrar los logs de la operación
```php
	<?php
	...
		WsWebpay::debug('Orden: ' . WsWebpay::dumpObject($orden));
```
<?php
//error_reporting( E_ALL );
if (!defined('WSWEBPAY_ROOT')) {
    define('WSWEBPAY_ROOT', dirname(__FILE__) . '/');
}

require_once WSWEBPAY_ROOT . "soap-validation.php";
require_once WSWEBPAY_ROOT . "WebpayStub.php";

class WsWebpay {
	
	public static $ESTADO_INICIADA		= 'INICIADA';
	public static $ESTADO_CONFIRMADA	= 'CONFIRMADA';
	public static $ESTADO_ERROR			= 'ERROR';
	public static $ESTADO_EXITO			= 'EXITO';
	public static $ESTADO_ANULADA		= 'ANULADA';
	public static $ESTADO_ERROR_DUPLICADA= 'DUPLICADA';
	
	private $CI;	
	private $_client;
	private static $_CONFIG;
	private static $TIPOS_DE_VENTA;
	private static $RETURN_URL;
	private static $FINAL_URL;
	private static $LOG_LEVEL;
	
	
	static function init(){	
		self::$TIPOS_DE_VENTA		= array(
			'VD'	=> array('tipo_de_pago' => 'Débito','tipo_de_cuotas'=>'Venta débito'),
			'VN'    => array('tipo_de_pago' => 'Crédito','tipo_de_cuotas'=>'Sin cuotas'),
			'VC'    => array('tipo_de_pago' => 'Crédito','tipo_de_cuotas'=>'Cuotas normales'),
			'SI'    => array('tipo_de_pago' => 'Crédito','tipo_de_cuotas'=>'Sin interés'),
			'S2'    => array('tipo_de_pago' => 'Crédito','tipo_de_cuotas'=>'Sin interés'),
			'NC'    => array('tipo_de_pago' => 'Crédito','tipo_de_cuotas'=>'Sin interés')
		);
		self::$RETURN_URL = self::$_CONFIG['path_return_url'];
		self::$FINAL_URL = self::$_CONFIG['path_fin_url'];
		self::$LOG_LEVEL = self::$_CONFIG['log_level'];
	}
	
	function __construct(){
		
		$this->CI =& get_instance();
		$webpay_config = $this->CI->config->item('webpay');
		self::$_CONFIG = $webpay_config;		
		$this->_client = new WebpayStub($webpay_config['service_endpoint']);
		self::init();
		
	}
	
	public function initTransaction( $idorder, $amount) {
		
		$wsInitTransactionInput = new wsInitTransactionInput();
		$wsTransactionDetail = new wsTransactionDetail();
		/*Variables de tipo string*/
		$wsInitTransactionInput->wSTransactionType = self::getTransactionType();
		$wsInitTransactionInput->commerceId = self::getCommerceCode();
		$wsInitTransactionInput->buyOrder = $idorder;
		$wsInitTransactionInput->sessionId = null;
		$wsInitTransactionInput->returnURL = base_url() . self::$RETURN_URL;
		$wsInitTransactionInput->finalURL = base_url() . self::$FINAL_URL;
		$wsTransactionDetail->commerceCode = self::getCommerceCode();
		$wsTransactionDetail->buyOrder = $idorder;
		$wsTransactionDetail->amount = $amount;
		$wsInitTransactionInput->transactionDetails = $wsTransactionDetail;
		
		self::debug('wsInitTransactionInput - Request : ' . self::dumpObject($wsInitTransactionInput));
		
		$initTransactionResponse = $this->_client->initTransaction(
			array("wsInitTransactionInput" => $wsInitTransactionInput)
		);
				
		$xmlResponse = $this->_client->soapClient->__getLastResponse();
		$soapValidation = new SoapValidation($xmlResponse, self::getServerCert());
		$validationResult = $soapValidation->getValidationResult();
		
		if ($validationResult) {
			return $wsInitTransactionOutput = $initTransactionResponse->return;
		}
		
		return false;
	}
	
	public function getTransactionResult($token) {
		$getTransactionResult = new getTransactionResult();
		$getTransactionResult->tokenInput = $token;
		self::debug('Request - getTrasactionResult : ' . self::dumpObject($getTransactionResult));
		$getTransactionResultResponse = $this->_client->getTransactionResult($getTransactionResult);
		self::debug('getTransactionResult - Response: ' . self::dumpObject($getTransactionResult));
		$res = $getTransactionResultResponse->return;
		self::debug('getTransactionResult - Response RES: ' . self::dumpObject($res));
		
		$xmlResponse = $this->_client->soapClient->__getLastResponse();
		$soapValidation = new SoapValidation($xmlResponse, self::getServerCert());
		$validationResult = $soapValidation->getValidationResult();
		
		if ($validationResult) {
			return $res;
		}
		
		return false;
	}
	
	public function acknowledgeTransaction($token) {

		$acknowledgeTransaction = new acknowledgeTransaction();
		$acknowledgeTransaction->tokenInput = $token;
		
		self::debug('Request - acknowledgeTransaction : ' . self::dumpObject($acknowledgeTransaction));
		
		$acknowledgeTransactionResponse = $this->_client->acknowledgeTransaction($acknowledgeTransaction);
		
		self::debug('Response - acknowledgeTransaction : ' . self::dumpObject($acknowledgeTransactionResponse));
		
		$xmlResponse = $this->_client->soapClient->__getLastResponse();
		$soapValidation = new SoapValidation($xmlResponse, self::getServerCert());
		
		$validationResult = $soapValidation->getValidationResult();
		
		if($validationResult) {
			return true;
		}
		return false;
	}
	
	public static function getErrorCodeMessage($code = null) {		
		switch ($code) {
			case 0: $message='Transacción aprobada';break;
			case -1: $message='Rechazo de transacción';break;
			case -2: $message='Transacción debe reintentarse';break;
			case -3: $message='Error en transacción';break;
			case -4: $message='Rechazo de transacción';break;
			case -5: $message='Rechazo por error de tasa';break;
			case -6: $message='Excede cupo máximo mensual';break;
			case -7: $message='Excede límite diario por transacción';break;
			case -8: $message='Rubro no autorizado';break;
			case -100: $message='Rechazo por inscripción de PatPass by Webpay';break;
			default: $message= "Error en el procesamiento"; break;			
		}
		return $message;
	}
	
	public static function getTipoCuota($code_tipo_venta){
		$tipoCuota = null;
		if(array_key_exists($code_tipo_venta, self::$TIPOS_DE_VENTA)) {
			$tipoCuota = self::$TIPOS_DE_VENTA[$code_tipo_venta]['tipo_de_cuotas'];
		}
		return $tipoCuota;
	}
	
	public static function getTipoVenta($code_tipo_venta) {
		$tipoVenta = null;
		if(array_key_exists($code_tipo_venta, self::$TIPOS_DE_VENTA)) {
			$tipoVenta = self::$TIPOS_DE_VENTA[$code_tipo_venta]['tipo_de_pago'];
		}
		return $tipoVenta;
	}
	
	public static function debug($message=""){
		self::log('debug', $message);
	}
	
	public static function error($message){
		self::log('error', $message);
	}
	
	public static function log($level, $message=""){
		$level = strtoupper($level);
		if(($level === "DEBUG" && self::$LOG_LEVEL === "ALL")  ||
			$level === "ERROR"){
			$file		= WSWEBPAY_ROOT . "logs/webpay-logs-" . date('Y-m-d') . ".log";
			file_put_contents($file, "\n" . $level . " [".date('Y-m-d H:i:s')."]: " .$message, FILE_APPEND);			
		}
	}
	
	public static function getTransactionType(){
		return self::$_CONFIG['trasaction_type'];
	}
	
	public static function getCommerceCode(){
		return self::$_CONFIG['commerce_code'];
	}
	
	public static function getServiceId(){
		return self::$_CONFIG['service_id'];
	}
	
	public static function getCommerceEmail(){
		return self::$_CONFIG['commerce_email'];		
	}
	
	public static function getCertFile() {
		return WSWEBPAY_ROOT .'cert/' .  self::$_CONFIG['cert_file_name'];
	}
	
	public static function getPrivateKey() {
		return WSWEBPAY_ROOT .'cert/' .  self::$_CONFIG['cert_private_key'];
	}
	
	public static function getServerCert() {
		return WSWEBPAY_ROOT . 'cert/' . self::$_CONFIG['cert_server_cert'];
	}
	
	public static function dumpObject($object, $t = 0) {
		$output = "";
		if($object === null) {
			$output = "(null)";
		} else if(is_array($object) || is_object($object)) {
			if(empty($object)) {
				$output = is_array($object)?"array()":"object()";
			} else {
				$i = 0;
				$tbs = $t;
				$output = self::tabular($tbs). (is_array($object)?"array(":"object(");				
				foreach($object as $k => $v) {
					if(is_object($v) || is_array($v)){
						$tbs++;
						$output .= "\n" .self::tabular($tbs)."$k\t: \n" . self::dumpObject($v, $tbs);
					} else {
						$output .= "\n" .self::tabular($tbs)."$k\t: $v";
					}
					$output .= "";
					$i=$i+1;
				}
				$output .= "\n)";
			}
		} else if(is_int($object) || is_integer($object) || is_long($object)) {
			$output = "(number) $object";
		} else if(is_string($object)){
			$output = "(string) $object";
		}
		return $output;
	}
	private static function tabular($index = 0) {
		$tabs = "";
		for($i = 0; $i < $index; $i++){
			$tabs .="\t";
		}
		return $tabs;
	}
}
<?php
require_once WSWEBPAY_ROOT . 'xmlseclibs.php';
require_once WSWEBPAY_ROOT . 'soap-wsse.php';

class WebpaySoap extends SoapClient {
	
	function __doRequest($request, $location, $saction, $version) {
		$doc = new DOMDocument('1.0');
		$doc->loadXML($request);
		
		$objWSSE = new WSSESoap($doc);
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1,array('type' => 'private'));
		$objKey->loadKey(WsWebpay::getPrivateKey(), TRUE);
		
		$options = array("insertBefore" => TRUE);
		$objWSSE->signSoapDoc($objKey, $options);
		$objWSSE->addIssuerSerial(WsWebpay::getCertFile());
		
		$objKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
		$objKey->generateSessionKey();
		$retVal = parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);
		$doc = new DOMDocument();
		$doc->loadXML($retVal);
		return $doc->saveXML();
	}
	
}
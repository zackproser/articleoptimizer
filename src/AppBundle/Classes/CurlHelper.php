<?php 

namespace AppBundle\Classes; 

use 
	Symfony\Component\HttpFoundation\Request, 
	Symfony\Component\HttpFoundation\Response
; 

/**
 * @author Zack Proser
 *
 * Handles common curl operations 
 */
class CurlHelper {

	protected $timeout;

	protected $logger; 

	public function __construct($timeout, $logger)
	{
		$this->timeout = $timeout; 

		$this->logger = $logger;
	}

	public function get($url, $headers = array(), $options = array())
	{
		if (!isset($url)) {
			$this->logger->error('Bad url passed to curl get: ' . $url); 
			throw new \Exception('Curl get requires a valid URL'); 
		}

		$ch = curl_init(); 
		$this->applyDefaultCurlSettings($ch, $url, null, $headers, $options); 

		return $this->executeCurlRequest($ch);
	}

	public function post($url, $data, $headers = array(), $options = array())
	{	
		if (!isset($url)) {
			$this->logger->error('Bad url passed to curl post: ' . $url); 
			throw new \Exception('Curl post requires a valid URL');
		}

		$ch = curl_init(); 
		$this->applyDefaultCurlSettings($ch, $url, $data, $headers, $options);

		curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? array()); 

       return $this->executeCurlRequest($ch);
	}

	private function applyDefaultCurlSettings(&$ch, $url, $data = null, $headers = array(), $options = null)
	{   
        curl_setopt($ch, CURLOPT_HEADER, $options['CURLOPT_HEADER'] ?? false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers ?? array());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $options['CURLOPT_RETURNTRANSFER'] ?? true);
        curl_setopt($ch, CURLOPT_USERAGENT, $options['CURLOPT_USERAGENT'] ?? 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
        curl_setopt($ch, CURLOPT_URL, $url ?? $options['CURLOPT_URL']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['CURLOPT_FOLLOWLOCATION'] ?? true); 
    	if (isset($options['CURLOPT_USERPWD'])) {
    		curl_setopt($ch, CURLOPT_USERPWD, $options['CURLOPT_USERPWD']); 
    	}
    	if (isset($options['CURLOPT_HTTPAUTH'])) {
    		curl_setopt($ch, CURLOPT_HTTPAUTH, $options['CURLOPT_HTTPAUTH']); 
    	}
	}

 	private function executeCurlRequest(&$ch)
 	{
 		$curlReqResult = curl_exec($ch); 
        $curlReqHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch); 

        $response = new Response(); 
        $response->setStatusCode($curlReqHttpCode); 
        $response->setContent($curlReqResult);

        return $response;
 	}
}

?>
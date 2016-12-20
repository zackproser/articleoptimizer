<?php 

namespace AppBundle\Classes; 

use 
	Symfony\Bundle\FrameworkBundle\Controller\Controller, 
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response
; 

/** 
 * @author  Zack Proser 
 *
 * Makes requests to the Bitly API for link shortening 
 */
class Bitly extends Controller {

	//We'll use the app's hostname, as configured in parameters.yml, to build our final URL 
	//to a given article when sending requests to Bitly 
	private $hostname; 

	//The access token that identifies the associated Bitly account
	private $token; 

	//The curl helper service
	private $curl; 

	private $logger; 

	public function __construct(String $hostname, String $token, $curl, $logger)
	{
		if (!isset($hostname) || !isset($token)) {
			throw new \Exception('Bitly class requires parameters: hostname, token during instantiation.');
		}

		$this->hostname = $hostname; 

		$this->token = $token; 

		$this->curl = $curl; 

		$this->logger = $logger;
	}

	/**
	 * Accepts a relative URL, builds it into a fully qualified URL, then shortens that link via Bitly
	 * 
	 * @param  string $url - The relative URL to the report that should be fully qualified and shortened
	 * @return string $shortUrl - The fully qualified and shortened URL
	 */
	public function shortenUrl(Request $request)
	{
		//Build the query parameters
		$queryParams = array(
			'access_token' => $this->token, 
			'longUrl' => $request->get('url'),
			'format' => 'json'
		); 

		//Build the URL to make the Bitly API request
		$bitlyTarget = 'https://api-ssl.bitly.com/v3/shorten?' . http_build_query($queryParams); 
		
		//Remove default urlencoding that http_build_query creates, as Bitly will choke on it
		$bitlyTarget = urldecode($bitlyTarget);

		$bitlyResponse = $this->curl->get($bitlyTarget);

		return $bitlyResponse; 
	}
}
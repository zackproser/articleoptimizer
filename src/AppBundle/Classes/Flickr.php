<?php 

namespace AppBundle\Classes;

use 
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response
;

/**
 * @author Zack Proser
 *
 * Makes requests to the Flickr API for photos based on supplied keywords
 */
class Flickr extends Controller {
	
    protected $container; 

	private $apiKey; 

	private $logger; 

    //Curl helper service
    private $curl;

    private $baseFlickrUrl;

	public function __construct($container, String $apiKey, $curl, $logger)
	{
        $this->container = $container;

		$this->apiKey = $apiKey; 

        $this->curl = $curl;

		$this->logger = $logger; 

        $this->baseFlickrUrl = 'http://flickr.com/services/rest/?'; 

	}

	/**
     * Obtains copyright-free images from the Flickr API 
     *  
     * @param  Request $request Symfony request 
     * @return Response $response Symfony response 
     */
    public function searchAction(Request $request)
    {
    	//Pull the query off the search
        $postString = $request->getContent(); 
    
        //Pull the keywords leading string off
        $postString = str_replace('keywords=', '', $postString); 

        $postString = urldecode($postString); 

        //Form an array of search terms to loop through
        $searchTerms = explode(',', $postString); 

        //Initialize array that will hold photos returned by Flickr
        $photos = array();

        foreach($searchTerms as $query) {
            //Build arguments array
            $arguments = array(
                'method' => 'flickr.photos.search', 
                'api_key' => $this->apiKey, 
                'tags' => urlencode($query),
                'text' => urlencode($query), 
                'per_page' => 50,
                'content_type' => 1, //Photos only - not screenshots which tend to be lower quality
                'format' => 'php_serial' 
            ); 

            //Build full Flickr API request URL
            $searchUrl = $this->baseFlickrUrl.http_build_query($arguments); 

            //Make Flickr API request via curl helper
            $flickrResponse = $this->curl->get($searchUrl);
            
            if ($flickrResponse->getStatusCode() === 200) {
                $data = unserialize($flickrResponse->getContent());
                if (isset($data['photos']['photo'])) {
                    array_push($photos, $data['photos']['photo']);
                }
            }
        }
        //Return rendered html containing the images 
        //flickr-images.html.twig creates correct URLs from 
        //data returned by the above Flickr API calls
        return $this->render('snippets/flickr-images.html.twig', array(
           'photos' => isset($data['photos']['photo'])  ? $data['photos']['photo'] : null
        )); 
    }
}


?>
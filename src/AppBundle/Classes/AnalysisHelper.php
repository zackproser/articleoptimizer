<?php 

namespace AppBundle\Classes;

use AppBundle\Entity\Article; 
use Symfony\Component\Finder\Finder;

/**
 * Contains helper methods used in analyzing an artticle 
 */
class AnalysisHelper {

	protected $logger; 

	protected $container; 

	public function __construct($logger, $container) {

		$this->logger = $logger; 

		$this->container = $container; 
	}

	/**
	 * Sanitize and return the article body 
	 * 
	 * @param  String $articleText The text of the article
	 * @return String $articleText The santitized text of the article
	 */
	public function sanitizeArticleBody($articleText) 
	{
		//Convert all html entities to their applicable characters - e.g.
		$articleText = html_entity_decode($articleText);

		//Clean submitted article body of any HTML or PHP tags
		$articleText = strip_tags($articleText); 

		//Drop all non-alphanumeric characters, but preserve single spaces
		$articleText = preg_replace('/[^A-Za-z0-9\-\s]/', '', $articleText);

		//Replace all hyphens with spaces
		$articleText = preg_replace('/\-/', ' ', $articleText); 	

		return $articleText;	
	}

	/**
	 * Parses the Alchemy API response and stores the data in the analysis array 
	 *
	 * Modifes the analysis array in-place
	 * 
	 * @param  Array $alchemyResponse The response from Alchemy API after processing the article
	 * @param  Array &$analysis       The current analysis 
	 */
	public function parseAlchemyResponse($alchemyResponse, &$analysis)
	{
		//Decode Alchemy's response JSON into an associative array 
		try {
			$alchemyResponse = json_decode($alchemyResponse, true); 
		} catch (\Exception $e) {
			//TODO: handle this
			$this->logger->error('Error decoding Alchemy API response JSON: ' . $alchemyResponse); 
		}
		
		$analysis['flickrKeywords'] = array();

		//The properties of a typical Alchemy API response that we are interested in 
		$props = array(
			'keywords', 
			'concepts', 
			'entities', 
			'taxonomy', 
			'category'
		); 
		//Loop through and inspect each of these properties in the Alchemy API response
		//and store them in analysis if they are set  
		if (isset($alchemyResponse['status']) && $alchemyResponse['status'] === 'OK') {

			foreach($props as $prop) {
				//Initialize success/failure key in the analysis array for each property  
				$analysis[$prop .'Succeeded'] = false;

				if (isset($alchemyResponse[$prop]) && count($alchemyResponse[$prop]) > 0) {
					//var_dump($alchemyResponse[$prop]);
					//If the given property is set and has at least one entry, save it to analysis
					$analysis[$prop] = $alchemyResponse[$prop]; 
					//Skim a sampling of all properties that aren't taxonomy to build
					//a diverse and accurate sampling of topics for searching via Flickr
					if ($prop != 'taxonomy' && isset($analysis[$prop][0]['text'])) {
						$analysis['flickrKeywords'][] = strtolower($analysis[$prop][0]['text']);
					}
					$analysis[$prop . 'Succeeded'] = true; 
				}
			}

			//Parse the Alchemy taxonomy information to extract a generic article category
			//if possible. Otherwise, fall back to a generic category name 'General Interest'
			$this->parseAlchemyTaxonomy($analysis); 

		} else {
			$this->logger->error('Received Alchemy API failure: ' . $alchemyResponse['status'] ?? ''); 
		}
	}

	/**
	 * Examines Alchemy API assigned taxonomy information
	 *
	 * Extracts the most descriptive root taxonomy and formats it so that 
	 * it is appropriate for displaying to the end user 
	 *
	 * Falls back to generic "General Interest" should processsing the taxonomy fail
	 *
	 * Modifies the analysis array in-place
	 * 
	 * @param  Array &$analysis The associative array representing all analysis completed so far
	 */
	private function parseAlchemyTaxonomy(&$analysis) 
	{	
		if (isset($analysis['taxonomy']) && count($analysis['taxonomy']) > 0 && isset($analysis['taxonomy'][0]['label'])) {
			$categories = explode('/', $analysis['taxonomy'][0]['label']); 
			$analysis['category'] = (isset($categories[1]) && gettype($categories[1] === 'string')) ? ucwords($categories[1]) : 'General Interest';
			$analysis['categorySucceeded'] = true;
		}	
	}

	/**
	 * Maps the Alchemy-provided taxonomy to a 
	 * category badge directory. 
	 *
	 * Category badges are 'consolation' badges 
	 * specific to the content categroy of a given 
	 * article which was not totally optimized
	 *
	 * Totally optimized articles receive a 
	 * completely different type of badge
	 * 
	 * @param  String $category The Alchemy-provided 
	 * @return String $directory The name of the directory from which a badge should be selected
	 */
	public function mapCategoryToBadge($category)
	{
		switch($category) {
			case 'Art And Entertainment':
			case 'Education': 
				return 'arts-entertainment'; 
			case 'Health And Fitness':
				return 'health';
			case 'Hobbies And Interests': 
			case 'Automotive':
			case 'Home And Garden':
				return 'recreation'; 
			case 'Business': 
			case 'Careers':
				return 'business';
			case 'Family And Parenting':
			case 'Law, Govt And Politics': 
			case 'Style And Fashion':
				return 'culture-politics'; 
			case 'Health And Fitness': 
			case 'Food And Drink': 
				return 'health';
			case 'General Interest':
			default: 
				return 'unknown'; 
		}
	}

	/**
	 * Generates a unique path for a given report 
	 * 
	 * @return String - the relative path where this report will be saved
	 */
	public function generateReportUrl()
	{
		$today = date('M-d-o-g-i-s'); 
		$random_token = md5(uniqid(rand(), true)); 
		return 'saved-reports/'.$today.'-'.$random_token.'.html'; 
	}

	/**
	 * Subscribe a user to the configured mailchimp list associated with this app
	 * 
	 * @param String $emailAddress The email address to subscribe
	 */
	public function subscribe($emailAddress) {

		//Mailchimp's base API url - N.B: you must include the correct <dc> datacenter for your account
		//e.g: us7 or us2
		$mailchimpAPIRoot = $this->container->getParameter('mailchimp_api_root');
		$listId = $this->container->getParameter('mailchimp_list_id'); 
		//Mailchimp expects user email addresses to be hashed with the md5 algorithm
		$hashedEmailAddress = md5($emailAddress); 

		//Build URL for making the subscription request to Mailchimp
		$subscribeUserUrl = sprintf("%slists/%s/members", $mailchimpAPIRoot, $listId);

		$payload = array(); 

		$payload['email_address'] = $emailAddress;
		//Tell mailchimp we want them to send a confirmation email to the user's address
		//This will ultimately increase list quality and reduce fraud and abuse
		$payload['status'] = 'pending'; 

		$headers = array(); 

		//Mailchimp expects you to supply your API key via HTTP Basic Auth
		//@See: http://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api
		$options = array(
			'CURLOPT_USERPWD' => 'articleoptimizer:' . $this->container->getParameter('mailchimp_api_key'),
			'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC
		);

		return $this->container->get('curl')->post($subscribeUserUrl, json_encode($payload), $headers, $options); 
	}
}

?>
<?php 

namespace AppBundle\Classes;

use AppBundle\Entity\Article; 
use Symfony\Component\Finder\Finder;
/**
 * Contains methods for processing the content of an AppBundle\Entity\Article;
 */
class Analyzer {

	//API key for making requests to Alchemy API - set in parameters.yml
	private $alchemyAPIKey; 

	//Monologger 
	private $logger; 

	//Curl request helper library 
	private $curl; 

	//The Symfony service container
	protected $container;

	//Service containing analysis helper functionality
	protected $analysisHelper; 

	//The web/img directory that contains the optimized badges
	protected $optimizedBadgeDirectory; 

	//The web/img directory that contains category-specific badges
	protected $categoryBadgeDirectory; 

	public function __construct($alchemyApiKey, $curl, $logger, $container) {

		$this->alchemyAPIKey = $alchemyApiKey; 

		//Set up the logger
		$this->logger = $logger; 

		//Curl helper service
		$this->curl = $curl; 

		//Store a reference to the service container
		$this->container = $container; 

		//Analysis Helper is a class that contains helper functions used by this Analyzer
		$this->analysisHelper = $this->container->get('analysis_helper'); 

		//Set the names of the optimized and category badge directories
		$this->optimizedBadgeDirectory = $container->getParameter('optimal_badge_directory');

		$this->categoryBadgeDirectory = $container->getParameter('category_badge_directory');
	}

	/**
	 * Perform analysis functions on article text 
	 *
	 * As well as pass text to Alchemy API for linguistic analysis
	 *
	 * Builds up an associative array containing all analaysis information 
	 *
	 * To be rendered on the report 
	 * 
	 * @param  Article $article the body of the article that should be analyzed
	 * @return Analysis array   the associative array containing all analysis information
	 */
	public function analyze(Article $article)
	{	
		//Main array that will contain all analysis data
		$analysis = array(
			'success' => false, 
			'wordCount' => 0
		); 

		//Options to pass to Alchemy API: drop unnecessary metadata about decision sources to speed up processing
		$options = array(
			//'linkedData' => 1 
		); 

		//Pull the article body off the article object
		$articleText = $article->getBody(); 

		//Store the text in the analysis for later rendering on the report next to the badge
		$analysis['articleBody'] = $article->getBody();

		if (isset($articleText) && is_string($articleText) && strlen($articleText) > 1) {
			$analysis['wordCount'] = str_word_count($articleText);
			$analysis['success'] = true;
		} else {
			$analysis['success'] = false;
			$this->logger->info(sprintf('Invalid article of length: %s after sanitization', strlen($articleText))); 
		}

		//Sanitize the article text 
		$articleText = $this->analysisHelper->sanitizeArticleBody($articleText); 

		//Prepare and execute request to Alchemy API 
		$alchemyResponse = $this->alchemizeText($articleText, $analysis); 

		//Validates and gathers required data from an Alchemy response
		//Formats response data neatly so it can be sanely accessed by twig  
		$this->analysisHelper->parseAlchemyResponse($alchemyResponse, $analysis); 	

		//Make determinations on how often the key phrases were repeated throughout
		$this->analyzePhraseDensity($analysis);

		//Select the badge image that will be used for this report
		$this->selectReportBadge($analysis);

		//Generate advertisements 
		$this->fetchAdvertisements($analysis); 

		return $analysis; 
	}

	/**
	 * Prepares and executes request to Alchemy API for further processing
	 * 
	 * @param  String $articleText The sanitized article body to send to Alchemy
	 */
	public function alchemizeText(String $articleText, &$analysis)
	{
		//Form the target URL to call, passing our Alchemy API key as a query string parameter
		$targetUrl = $this->container->getParameter('alchemy_api_base_url') . "/text/TextGetCombinedData?outputMode=json&apikey=" . $this->alchemyAPIKey;

		//Use the alchemy parameters if they are set, otherwise sensible defaults
		$data = sprintf("maxRetrieve=%d&sentiment=%d&text=%s", 
			$this->container->getParameter('alchemy_max_entities_retrieve') ?? 10, 
			$this->container->getParameter('alchemy_include_sentiment') ?? 1,
			urlencode($articleText)
		); 

		//In order to be able to POST a body to Alchemy API, this header must be set exactly as so: 
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded'
		); 

		//Use curl service to POST request to Alchemy API 
		$alchemyResponse = $this->curl->post($targetUrl, $data, $headers); 

		//If we received a 200 from Alchemy, we have a successful analysis - otherwise we don't
		if (200 === $alchemyResponse->getStatusCode()) {
			return $alchemyResponse->getContent();
		} else {
			$analysis['success'] = false;
			$this->logger->error(sprintf("Error retrieving results from Alchemy API: %s  and status code: %d", $alchemyResponse->getContent(), $alchemyResponse->getStatusCode()));
			return; 
		}
	}

	/**
	 * Fetches advertisement blocks defined in parameters.yml
	 * for rendering in the report
	 * 
	 * @param  Array &$analysis The current analysis 
	 */
	private function fetchAdvertisements(&$analysis)
	{	
		$analysis['adsEnabled'] = $this->container->getParameter('ads_enabled'); 
		if ($analysis['adsEnabled'] === true) {
			$analysis['ads'] = $this->container->getParameter('advertisements');
		}
	}

	/**
	 * Makes determinations on the frequency with which key phrases are repeated
	 * which are used to make recommendations on the report page
	 * 
	 * Modifies the analysis array in place
	 * 
	 * @param  Array &$analysis The current analysis
	 */
	private function analyzePhraseDensity(&$analysis)
	{
		if (isset($analysis['entitiesSucceeded']) && $analysis['entitiesSucceeded'] === true) {
			//Initialize density array
			$density = array(
				'stuffing' => array(),
				'good' => array(), 
				'low' => array()
			); 
			foreach($analysis['entities'] as $entity) {
				$count = (int)$entity['count']; 
				$entity = $entity['text'];
				$p = round((($count / $analysis['wordCount']) * 100), 2); 
				switch(true) {
					case ($p >= 0.20 && $p <= 1.50): 
						$density['good'][$entity] = $p; 
						break; 
					case ($p < 0.20):
						$density['low'][$entity] = $p;
						break; 
					case ($p > 1.50):
						$density['stuffing'][$entity] = $p;
						break;
					default: 
						$density['good'][$entity] = $p;
						break;
				}
			}
			$analysis['phraseDensitySucceeded'] = false; 
			foreach($density as $level => $children) {
				if (is_array($level) && count($level) > 0) {
					$analysis['phraseDensitySucceeded'] = true;
				}
			}
			if ($analysis['phraseDensitySucceeded'] = true) {
				$analysis['phraseDensity'] = $density;
			}
		}
	}

	/**
	 * Chooses a report badge based on the state of the analysis
	 *
	 * If the article is long enough and has at least one keyword 
	 * in the "good" range as determined by the analyzePhraseDensity
	 * method, it receives an optimized badge
	 *
	 * Otherwise it receives a badge based on its category
	 * 
	 * @param  Array &$analysis The current analysis
	 */
	private function selectReportBadge(&$analysis)
	{
		//If the article is greater than 151 words, that's one positive check toward its quality
		$wordCountCheck = ($analysis['wordCount'] >= 151) ? true : false; 
		$keywordsCheck = (isset($analysis['phraseDensity']['good']) && count($analysis['phraseDensity']['good']) >= 1) ? true : false;
		
		$analysis['wordCountCheck'] = $wordCountCheck; 
		$analysis['keywordsCheck'] = $keywordsCheck;

		if (true === $wordCountCheck && true === $keywordsCheck) {

			//We have a high quality article - render an optimized badge
			$analysis['badge'] = $this->getBadgeUrl('optimized', null);
		} else {
			//Use a category badge instead
			$badgeCategory = isset($analysis['category']) ? $this->analysisHelper->mapCategoryToBadge($analysis['category']) : 'unknown';
			$analysis['badge'] = $this->getBadgeUrl('category', $badgeCategory); 
		}
	}

	/**
	 * Builds the system path to the /web directory of this Symfony project 
	 * 
	 * @return string path - the system path to the /web directory
	 */
	private function getWebDirectory()
	{
		return dirname($this->container->get('kernel')->getRootDir()) . '/web'; 
	}

	/**
	 * Obtain an optimized or a category badge URL, depending on the mode passed to this method
	 *
	 * @var string mode - either 'optimized' or 'category' for specifying the type of badge that should be found
	 * 
	 * @return string url - the relative URL to the badge that was selected for this report. Used by report.html.twig to display the badge
	 */
	//private function getBadgeUrl($mode, string $category = null)
	private function getBadgeUrl($mode, $category = null)
	{
		$badges = array(); 

		//If an optimized badge is being requested, choose one at random from its directory
		//Category badges requring dropping down into the specific category directory before selecting a badge
		$pathRoot = '/img/badges/' . ($mode === 'optimized' ? $this->optimizedBadgeDirectory : $this->categoryBadgeDirectory . '/' . $category) . '/'; 

		//Instantiate Symfony file finder object
		$finder = new Finder(); 
		//Only pick up jpegs, (or .jpg), .png, .gif or .gifv files
		$finder->files()->name('/(:?.*\.jpe?g$|.*\.png$|.*\.gifv?$)/')->in($this->getWebDirectory() . $pathRoot);

		foreach ($finder as $file) {
			//Build the full path to the badge image and store it in the array of possible badges
			array_push($badges, ($pathRoot . $file->getRelativePathname()));
		}

		//Choose one of the optimized badges at random
		return $badges[array_rand($badges)];
	}
}

?>
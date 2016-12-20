<?php 

namespace AppBundle\Twig; 

class AppExtension extends \Twig_Extension {

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('ribbon', array($this, 'generateRibbon'), array('is_safe' => array('html'))), 
			new \Twig_SimpleFunction('legend', array($this ,'generateLegend'), array('is_safe' => array('html'))), 
			new \Twig_SimpleFunction('advertisement', array($this, 'generateAdBlock'), array('is_safe' => array('html')))
		); 
	}

	/**
	 * Generates an advertisement block of two ads split into two equal columns
	 *
	 * Ads have the following format: 
	 *
	 * array('
	 * 	'cta' => 'Buy this wonderful product please', 
	 * 	'img_path' => 'ads/elegantthemes/300x250.gif', 
	 * 	'url' => 'https://www.elegantthemes.com/myaffiliatecode'
	 * '); 
	 *
	 *  cta is the "Call to Action" - the text that will appear with the ad
	 * 
	 *  img_path is the relative path from web/img/ to the image asset / banner
	 *  
	 *  url is the final url that people who click the ad should be sent to
	 *  this should contain any affiliate tracking information necessary to credit 
	 *  your account with conversions that it generates
	 * 
	 * @param  Array $ad1  The associative array representing advertisement 1
	 * @param  Array $ad2  The associative array representing advertisement 2 
	 */
	public function generateAdBlock($ad1, $ad2)
	{

		return sprintf("<div class=\"jumbotron report-backing\">
				<div class=\"row\">
					<div class=\"col-md-6 col-sm-6 col-xs-12\">
						<p>%s</p>
					</div>
					<div class=\"col-md-6 col-sm-6 col-xs-12\">
						<p>%s</p>
					</div>
				</div>
				<div class=\"row\">
					<div class=\"col-md-6 col-sm-6 col-xs-12\">
						<a href=\"%s\">
							<img class=\"img-responsive ad\" src=\"/img/%s\" />
						</a>
					</div>
					<div class=\"col-md-6 col-sm-6 col-xs-12\">
						<a href=\"%s\">
							<img class=\"img-responsive ad\" src=\"/img/%s\" />
						</a>
					</div>
				</div>
			</div>", $ad1['cta'], $ad2['cta'], $ad1['url'], $ad1['img_path'], $ad2['url'], $ad2['img_path']); 
	}

	/**
	 * Generate a section legend mapping to the bootstrap default classes: 
	 * 
	 * e.g:
	 * $keys = array('positive', 'neutral', 'negative')
	 *
	 * will produce the following legend: 
	 * 
	 * positive = success, neutral = info, negative = danger
	 * 
	 * @param  Array $keys The keys that map to each 
	 * @return  String The html of the legend
	 */
	public function generateLegend($title, $keys)
	{
		if (!isset($title) || !gettype($title) === "string") {
			throw new \Exception('generateLegend expects a valid string for a title');
		}
		if (count($keys) < 3) {
			throw new \Exception('generateLegend expects an array containing 3 strings to map to bootstrap classes.'); 
		}
		return sprintf("
			<label>%s</label>
				<div class=\"well\">
					<span class=\"label label-success\">%s</span>
					<span class=\"label label-primary\">%s</span>
					<span class=\"label label-danger\">%s</span>
				</div>
		",$title, $keys[0], $keys[1], $keys[2]);
	}

	/**
	 * Generates a properly-formatted 'bootstrap ribbon' as used on the report page 
	 * 
	 * @param  String $title The title that will appear on top of the ribbon
	 * @return String $html  The final html of the ribbon to be rendered on the report
	 */
	public function generateRibbon($title, $type = 'body') 
	{	
		//Most ribbons are 'body' types, which ensures they are padded correctly. 
		//If the second parameter passed to this function is anything but body, it's probably the first ribbon
		//which means that we should remove the 'body' class from the ribbon
		if ($type != 'body') {
			$type = null;
		}
		return sprintf("<div class=\"row ribbon-row\">
		<button type=\"button\" class=\"btn btn-primary ribbon %s noselect\"><h1>%s</h1></button>
	</div>", $type, $title); 
	}

	public function getName()
	{
		return 'app_extension';
	}
}

?> 
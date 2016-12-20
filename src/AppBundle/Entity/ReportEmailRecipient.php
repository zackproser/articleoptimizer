<?php 

namespace AppBundle\Entity; 

use
	Symfony\Component\Validator\Mapping\ClassMetaData,
	Symfony\Component\Validator\Constraints\NotBlank, 
	Symfony\Component\Validator\Constraints\Type, 
	Symfony\Component\Validator\Constraints\Length, 
	Symfony\Component\Validator\Constraints\Email
; 

class ReportEmailRecipient {

	protected $reportUrl;

	protected $address; 

	/**
	 * The validation method that is run when a report email request is submitted
	 */
	public static function loadValidatorMetadata(ClassMetaData $metadata)
	{
		$metadata->addPropertyConstraint('address', new NotBlank(array(
			'message' => 'You must provide a valid email address')
		)); 

		$metadata->addPropertyConstraint('address', new Email(array(
			'strict' => false, 
			'message' => 'Please provide a valid email address', 
			'checkMX' => true, 
			'checkHost' => true
			)
		)); 
	}

	/**
	 * Return the recipient email address
	 * 
	 * @return String The recipient email address
	 */
	public function getAddress()
	{
		return $this->address; 
	}

	/**
	 * Set the recipient email address
	 * 
	 * @param String $address The recipient email address
	 */
	public function setAddress($address)
	{
		$this->address = $address;
	}

	/**
	 * Return the final url of the article report 
	 * 
	 * @return String The final url of the article report
	 */
	public function getReportUrl()
	{
		return $this->reportUrl; 
	}

	/**
	 * Set the final url of the article report 
	 * 
	 * @param String The final url of the article report 
	 */
	public function setReportUrl($url)
	{
		$this->reportUrl = $url; 
	}
}

?>
<?php 

namespace AppBundle\Entity; 

use 
	Symfony\Component\Validator\Mapping\ClassMetaData,
	Symfony\Component\Validator\Constraints\NotBlank, 
	Symfony\Component\Validator\Constraints\Type, 
	Symfony\Component\Validator\Constraints\Length
; 

/**
 * The article being submitted for analysis 
 */
class Article 
{	
	//The article's content
	protected $body; 

	//The article's creation Datetime
	protected $createdDate; 

	/**
	 * Validation method that determines if the article is correctly formed
	 */
	public static function loadValidatorMetadata(ClassMetadata $metadata)
	{
		$metadata->addPropertyConstraint('body', new NotBlank(
			array('message' => 'You must submit the full text of your article.')
			)
		); 

		$metadata->addPropertyConstraint('body', new Length(array(
			'min' => 150,
			'minMessage' => 'Your article must be at least 150 characters long.', 			
			))
		); 
	}

	/**
	 * Return the body content of the article 
	 * 
	 * @return String The article content 
	 */
	public function getBody()
	{
		return $this->body; 
	}

	/**
	 * Set the body content of the article 
	 * 
	 * @param String $body The article content
	 */
	public function setBody($body)
	{
		$this->body = $body; 
	}

	/**
	 * Return the Datetime the article was created 
	 * 
	 * @return Datetime The article's creation time
	 */
	public function getCreatedDate()
	{
		return $this->createdDate; 
	}

	/**
	 * Sets the Datetime the article was created
	 * 
	 * @param Datetime $createdDate The article's creation time
	 */
	public function setCreatedDate($createdDate)
	{
		$this->createdDate = $createdDate; 
	}

}

?>
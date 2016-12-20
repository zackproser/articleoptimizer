<?php 

namespace AppBundle\Entity; 

use
	Symfony\Component\Validator\Mapping\ClassMetaData,
	Symfony\Component\Validator\Constraints\NotBlank, 
	Symfony\Component\Validator\Constraints\Type, 
	Symfony\Component\Validator\Constraints\Length, 
	Symfony\Component\Validator\Constraints\Email
; 

class ContactRequest 
{
	protected $body;
	protected $createdDate; 
	protected $requestor; 

	/**
	 * The validation method that is run when the contact request is submitted
	 */
	public static function loadValidatorMetadata(ClassMetadata $metadata)
	{
		$metadata->addPropertyConstraint('body', 
			new NotBlank(array(
				'message' => 'You must have something more to say!'
				)
			)
		); 

		$metadata->addPropertyConstraint('body', 
			new Length(array(
				'min' => 10, 
				'max' => 5000,
				'minMessage' => 'Your message must be at least 10 characters long', 
				'maxMessage' => 'Your message cannot be longer than {{ limit }} characters'
				)
			)
		);

		$metadata->addPropertyConstraint('requestor', new Email(array(
			'strict' => false, 
		    'message' => 'Please enter a valid email address',
		    'checkMX' => true, 
		    'checkHost' => true 
			)
		)); 
	}

	public function getBody()
	{
		return $this->body; 
	}

	public function setBody($body)
	{
		$this->body = $body; 
	}

	public function getCreatedDate()
	{
		return $this->createdDate; 
	}

	public function setCreatedDate($createdDate)
	{
		$this->createdDate = $createdDate; 
	}

	public function getRequestor()
	{
		return $this->requestor; 
	}

	public function setRequestor($requestor)
	{
		$this->requestor = $requestor; 
	}
} 

?>
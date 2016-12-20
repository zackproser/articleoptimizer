<?php 

namespace AppBundle\Entity; 

use
	Symfony\Component\Validator\Mapping\ClassMetaData,
	Symfony\Component\Validator\Constraints\NotBlank, 
	Symfony\Component\Validator\Constraints\Type, 
	Symfony\Component\Validator\Constraints\Email
; 	

class Subscriber 
{
	protected $address;

	/**
	 * The validation method that is run when the contact request is submitted
	 */
	public static function loadValidatorMetadata(ClassMetadata $metadata)
	{
		$metadata->addPropertyConstraint('address', 
			new NotBlank(array(
				'message' => 'Please enter your email address.'
				)
			)
		); 

		$metadata->addPropertyConstraint('address', new Email(array(
			'strict' => false, 
		    'message' => 'Please enter a valid email address',
		    'checkMX' => true, 
		    'checkHost' => true 
			)
		)); 
	}

	public function getAddress()
	{
		return $this->address; 
	}

	public function setAddress($address)
	{
		$this->address = $address; 
	}
} 

?>
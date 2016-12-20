<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    
    }

    public function testIndexFormsPresent()
    {
    	$client = static::createClient(); 

    	$crawler = $client->request('GET', '/'); 

    	//Ensure the article, subscribe and contact forms are present
    	$this->assertEquals(3, $crawler->filter('form')->count());
    }

    public function testIndexTabsPresent()
    {
    	$client = static::createClient(); 

    	$crawler = $client->request('GET', '/'); 

    	//Ensure the optimize, how it works and contact panel nav tabs are present
    	$this->assertEquals(3, $crawler->filter('.nav-tabs li a')->count()); 
    }

    //Tests that the article form provides an error when
    //supplied content is too short
    public function testArticleFormSubmitShortContent()
    {
    	$client = static::createClient(); 

    	$crawler = $client->request('GET', '/'); 

    	$articleForm = $this->getArticleForm($crawler);

    	$articleForm['form[body]'] = 'This is deliberately too short'; 

    	$crawler = $client->submit($articleForm);

    	$this->assertContains('Your article must be at least 150 characters long.', $client->getResponse()->getContent());  
    }

    //Test that submitting a simple article works and 
    //results in a redirect response to the written report
    public function testArticleFormSubmitArticle()
    {
    	$client = static::createClient(); 

    	$crawler = $client->request('GET', '/'); 

    	$articleForm = $this->getArticleForm($crawler); 

    	$articleForm['form[body]'] = 'What is Symfony? Symfony is a set of PHP Components, a Web Application framework, a Philosophy, and a Community — all working together in harmony. The leading PHP framework to create websites and web applications. Built on top of the Symfony Components. A set of decoupled and reusable components on which the best PHP applications are built, such as Drupal, phpBB, and eZ Publish. A huge community of Symfony fans committed to take PHP to the next level.';

    	$crawler = $client->submit($articleForm); 

    	$this->assertContains('Redirecting to saved-reports', $client->getResponse()->getContent());
    }

    //Test that submitting empty feedback results in an error
    public function testContactFormSubmitEmptyContent()
    {
    	$client = static::createClient(); 

    	$crawler = $client->request('GET', '/');

    	$contactForm = $this->getContactForm($crawler); 

    	$contactForm['form[body]'] = '';

    	$crawler = $client->submit($contactForm); 

    	$this->assertContains('You must have something more to say!', $client->getResponse()->getContent()); 
    }

    private function getArticleForm($crawler)
    {
    	return $crawler->filter("form[name='articleForm']")->form(); 
    }

    private function getContactForm($crawler)
    {
    	return $crawler->filter("form[name='contactForm']")->form(); 
    }
}

<?php

namespace AppBundle\Controller;

use 
    AppBundle\Entity\Article,
    AppBundle\Entity\ContactRequest,
    AppBundle\Entity\ReportEmailRecipient,
    AppBundle\Entity\Subscriber,
    AppBundle\Classes\Analyzer,
    AppBundle\Classes\AnalysisHelper,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Cookie,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\TextType, 
    Symfony\Component\Form\Extension\Core\Type\DateType, 
    Symfony\Component\Form\Extension\Core\Type\SubmitType, 
    Symfony\Component\Filesystem\Filesystem,
    Symfony\Component\Filesystem\Filesystem\Exception\IOExceptionInterface
;

class DefaultController extends Controller
{
    /**
     * Render the homepage 
     * 
     * @param  Request $request - the incoming request
     * @return Response $response - the Symfony response
     */
    public function indexAction(Request $request)
    {      
        //Get the user's cookie status
        $userStatus = $this->getUserCookieStatus($request);   
        //Build and return response, including all the app-level configuration params
        //That will be used to render owner-specific data in meta tags, title, etc.
        $response = $this->render('default/index.html.twig', array(
            'returningUser' => $userStatus, 
            'appTitle' => $this->getParameter('app_title'), 
            'appAuthor' => $this->getParameter('app_author')
        )); 

        return $response;
    }

    /**
     * Render the form that accepts articles 
     * 
     * @param  Request $request - the Symfony request 
     * @return Response $response - the Symfony response
     */
    public function articleFormAction(Request $request)
    {
        //Create a new article entity and its accompanying form    
        $article = new Article(); 
        //Set the createdDate to the time of the request
        $article->setCreatedDate(new \DateTime()); 

        $articleForm = $this->createFormBuilder($article)
            ->setAction($this->generateUrl('article_process'))
            ->add('body', TextareaType::class, array(
                'label' => ' ',
                'attr' => array('class' => 'form-control article-form',
                'placeholder' => 'Step 1: Paste Your Article Here'
                    )
                )
            )
            ->add('submit', SubmitType::class, array(
                'label' => 'Step 2: Click Here to Optimize!', 
                'attr' => array(
                    'class' => 'submit-button article-submit btn btn-success'
                    )
                )
            )
            ->getForm();

        $articleForm->handleRequest($request);

        if ($articleForm->isSubmitted() && $articleForm->isValid()) {
            
            //Load Analyzer service
            $analyzer = $this->get('analyzer');  

            //Obtain analysis of the article
            $analysis = $analyzer->analyze($article); 
            
            //Analysis helper builds the final destination path for the report
            $reportDestination = $this->container->get('analysis_helper')->generateReportUrl(); 

            //First, render the report and write to the server for later use
            $renderedReport = $this->renderView('default/report.html.twig', array(
                'analysis' => $analysis,
                'appTitle' => $this->getParameter('app_title'), 
                'appAuthor' => $this->getParameter('app_author') 
                )
            ); 
            
            //Instantiate new Symfony filesystem component
            $fs = new Filesystem(); 

            try {
                //Write the rendered repport to the file for later access
                $fs->dumpFile($reportDestination, $renderedReport); 
            
            } catch (\Exception $e) {
                $this->container->get('logger')->error('Error persisting generated report to filesystem: ' . $e); 
            }

            //Create a new redirect response to the newly written report 
            $response = new RedirectResponse($reportDestination, 301);
            //Set the returning user cookie in the response so that user
            //will not be required to pass sign-up flow again
            $this->setUserCookie($response); 

            //Redirect request to the written report
            return $response; 

        }

        //Render the article form
        return $this->render('forms/article.html.twig', array(
                'articleForm' => $articleForm->createView()
            )
        ); 
    }

    /**
     * Render the contact request form and process its submission
     *
     * Sends a contact request email using the configured mailer on 
     *
     * Submission of a valid contact request form 
     * 
     * @param  Request $request The request
     * @return Response $response The response
     */
    public function contactFormAction(Request $request)
    {
        //Build the contact us form 
        $contact = new ContactRequest(); 

        $contactForm = $this->createFormBuilder($contact)
            ->setAction($this->generateUrl('contact_process'))
            ->add('body', TextareaType::class, array(
                'label' => ' ', 
                'attr' => array('class' => 'form-control contact-form', 
                'placeholder' => 'What\'s on your mind?'))
            )
            ->add('requestor', TextType::class, array(
                'label' => ' ', 
                'attr' => array('class' => 'form-control contact-email', 
                'placeholder' => 'Optionally, add your email address here if you want a response'))
            ) 
            ->add('submitContact', SubmitType::class, array(
                'label' => 'Submit Feedback', 
                'attr' => array(
                    'class' => 'submit-button btn btn-success')))
            ->getForm(); 

        $contactForm->handleRequest($request); 

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {

            //Set the createdDate to the time of the valid submission
            $contact->setCreatedDate(new \DateTime());

            //Get the Requestor off the contact record - if it exists
            $requestorField = $contact->getRequestor(); 
            $requestor = ($requestorField ?? "anonymous@article-optimize.com");

            $message = $contact->getBody(); 

            //Prepare the contact request email 
            $contactEmail = \Swift_Message::newInstance()
                ->setSubject($this->getParameter('contact_email_subject'))
                ->setFrom($requestor)
                ->setTo($this->getParameter('contact_email_delivery_address'))
                ->setBody(
                    $this->renderView(
                        'emails/contact.html.twig',
                        array('requestor' => $requestor, 
                              'message' => $message
                        ), 
                        'text/html'
                    )
                )
            ; 
            //Send the contact request email 
            $this->get('mailer')->send($contactEmail);

            //Render the contact success page with customized message
            return $this->render('default/contact-success.html.twig', array(
                   'contact_success_response' => $this->getParameter('contact_success_response')
                )
            );
        } 

        //Don't return full form view for ajax requests
        if (!$request->isXmlHttpRequest()) {
            return $this->render('forms/contact.html.twig', array(
                'contactForm' => $contactForm->createView(),  
            ));
        } else {

            $response = new Response(); 
            $response->setStatusCode(400);
            $response->setContent($this->getFormErrorsAsString($contactForm)); 
            return $response;
        }
    }

    /**
     * Sends a report via email to a recipient specified by a user
     * 
     * @param  Request Symfony request
     * @return Response Symfony response
     */
    public function emailReportAction(Request $request)
    {
        $recipient = new ReportEmailRecipient(); 

        $recipient->setReportUrl($request->headers->get('referer')); 

        $recipientForm = $this->createFormBuilder($recipient, array(), array())
            ->setAction($this->generateUrl('email_report_process'))
            ->add('address', TextType::class, array(
                'label' => ' ', 
                'attr' => array('class' => 'form-control report-email', 
                'placeholder' => 'Enter a valid email address to send this report to'))
            ) 
            ->add('sendReport', SubmitType::class, array(
                'label' => 'Send Report', 
                'attr' => array(
                    'class' => 'submit-button report-send-button btn btn-success'))
            )
            ->getForm(); 

        $recipientForm->handleRequest($request);    

        if ($recipientForm->isSubmitted() && $recipientForm->isValid()) {

            //Prepare the report-sending email 
            $recipientEmail = \Swift_Message::newInstance()
                ->setSubject($this->getParameter('report_email_subject'))
                ->setFrom('report@article-optimize.com')
                ->setTo($recipient->getAddress())
                ->setBody(
                    $this->renderView(
                        'emails/report.html.twig',
                        array('reportUrl' => $request->get('uri')) 
                    ), 
                    'text/html' 
                )
            ; 
            //Send the contact request email 
            $this->get('mailer')->send($recipientEmail);

            //Render the contact success page with customized message
            return $this->render('forms/email-report-success.html.twig', array(
                    'successMessage' => $this->getParameter('report_email_success_message')
                )
            );
        }

        return $this->render('forms/email-report.html.twig', array(
                'emailReportForm' => $recipientForm->createView(),  
            )
        );         
    }

    /**
     * Handles user subscriptions to email list via signup modal on index page
     * 
     * @param  Request $request Symfony request
     */
    public function subscribeAction(Request $request)
    {
        $subscriber = new Subscriber();

        $subscriberForm = $this->createFormBuilder($subscriber)
        ->setAction($this->generateUrl('subscribe_process'))
        ->add('address', TextType::class, array(
            'label' => ' ', 
            'attr' => array('class' => 'form-control report-email', 
            'placeholder' => 'Enter a valid email address to subscribe'))
        ) 
        ->getForm();

        $subscriberForm->handleRequest($request);

        if ($subscriberForm->isSubmitted() && $subscriberForm->isValid()) {

            $mailchimpResponse = $this->get('analysis_helper')->subscribe($subscriber->getAddress());

            $mailchimpStatusCode = $mailchimpResponse->getStatusCode(); 

            if (200 === $mailchimpStatusCode) {
                $message = 'Success! You\'ve been subscribed. Please check your email to confirm.'; 
            } else {
                $message = 'Sorry, there was an issue signing you up. Please try again later.';
                $this->container->get('logger')->error(sprintf("Error subscribing user to Mailchimp list: %s in %s %s %s", $mailchimpResponse->getContent(), __CLASS__, __FUNCTION__, __LINE__));
            }

            $response = new Response();
            $response->setStatusCode($mailchimpStatusCode); 
            $response->setContent($message); 
            return $response; 
        }  


        //Don't return full form view for ajax requests
        if (!$request->isXmlHttpRequest()) {
             return $this->render('forms/subscriber.html.twig', array(
                'subscriberForm' => $subscriberForm->createView(),  
            )); 
        } else {

            $response = new Response(); 
            $response->setStatusCode(400);
            $response->setContent($this->getFormErrorsAsString($subscriberForm)); 
            return $response;
        }
    }

    /**
     * Returns all form errors for supplied form in a user-friendly string
     * 
     * @param  Object $form A Symfony form instance
     * @return String $errors  A user-legible string of concatenated form errors
     */
    private function getFormErrorsAsString(&$form)
    {
        //Parse the form errors and return them as a usable string
        $errString = "";
        foreach($form as $fieldName => $formField) {
            $fieldErrors = $formField->getErrors();
            $userFriendlyError =  stripslashes(trim($fieldErrors));
            $userFriendlyError = str_replace('ERROR:', '', $userFriendlyError);
            $userFriendlyError = str_replace(',', '', $userFriendlyError);
            $errString .= $userFriendlyError;
        } 
        return $errString;
    }

    /**
     * Determine if the user is a repeat visitor or not 
     *
     * Returning users will not be challenged with the email submission form
     *
     * New users will be given the email submission form before they can optimize an article
     *
     * @param  Object - Symfony request
     * @return Boolean - true if the user is returning, and false if they are new 
     */
    private function getUserCookieStatus(Request $request)
    {
        $cookie = $request->cookies->get($this->getParameter('cookie_name'));
        
        return (isset($cookie) && $cookie === $this->getParameter('cookie_value')) ? true : false; 
    }

    /** 
     * Sets the application's cookie and value as specified in parameters.yml
     *
     * The presence of this cookie, set to the correct value, will mark the user as recognized and returning
     * in the future, so that client code allows them to bypass the sign-up flow
     *
     * The response will be passed by reference, so it will be modified in-place
     * 
     * @param  Object - the Symfony response
     */
    private function setUserCookie(Response &$response)
    {   
        //Set a cookie of specified name with the specified value. Requests for the index page are checked for this cookie 
        $response->headers->setCookie(new Cookie($this->getParameter('cookie_name'), $this->getParameter('cookie_value')));
    }

}

$(document).ready(function(){

    var controls = {}; 
 
    controls.articleSubmitButton = $('.article-submit');
    controls.loadingAnimation = $('#loading-animation');
    controls.returningUser = $('span.hidden.stat').data('status') === "r" ? true : false;
    controls.articleErrorAlert = $('#article-alert');
    controls.articleForm = $("form[name='articleForm']");
    controls.articleInput = $(controls.articleForm).find('textarea');
    controls.contactForm = $("form[name='contactForm']");
    controls.contactSuccessAlert = $('#contact').find('.alert.alert-success');
    controls.contactErrorAlert = $('#contact').find('.alert.alert-danger');
    controls.contactSubmitButton = $('#form_submitContact');
    controls.emailSignupModal = $('#email-signup-modal');
    controls.subscriberForm = $('#subscriberForm');
    controls.subscribeSuccessAlert = $(controls.emailSignupModal).find('.alert.alert-success');
    controls.subscriberErrorAlert = $(controls.emailSignupModal).find('.alert.alert-danger');
    controls.subscribeDeclineButton = $('#subscribe-decline'); 
    controls.subscribeAcceptButton = $('#subscribe-accept');
    controls.navTabs = $('.nav-tabs a');
    controls.alerts = $('.alert.alert-danger');

    //Initialize the Optimizer client with jQuery controls
    Client.init(controls);  
    
}); 


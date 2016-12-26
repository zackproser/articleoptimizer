var Client = (function(options) {

    var ctrl = {};

    function init(controls) {
        ctrl = controls;

        $(ctrl.errorAlert).hide();

        //Process contact submissions
        $(ctrl.contactSubmitButton).on('click', processContactSubmission);

        //Handle email subscription signup
        $(ctrl.subscribeAcceptButton).on('click', processSubscriptionSubmission);

        //On email subscription declination, proceed with article processing
        $(ctrl.subscribeDeclineButton).on('click', processArticle);

        //On article submission, validate the content before processing
        $(ctrl.articleSubmitButton).on('click', validateSubmission);

        //Wire up nav tab click handler
        $(ctrl.navTabs).on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }

    function showEmailInput() {
        $(ctrl.emailSignupModal).removeClass('hidden').modal('show');
    }

    function processArticle() {
        //Hide all modals
        $.each($('.modal'), function(i, modal) {
            $(modal).modal('hide');
        });
        ctrl.returningUser = true;
        resetErrors();
        loading();
        $(ctrl.articleForm).submit();
    }

    function validateSubmission(e) {
        if ($(ctrl.articleInput).val().length === 0) {
            showArticleError('Please paste your entire article into the box.');
            e.preventDefault();
            return;

        } else if ($(ctrl.articleInput).val().length < 150) {
            showArticleError('Articles of at least 200 words work best');
            e.preventDefault();
            return;
        }

        if (ctrl.returningUser) {
            processArticle();
        } else {
            e.preventDefault();
            showEmailInput();
        }
    };

    function loading() {
        $(ctrl.loadingAnimation).removeClass('hidden').show();
    }

    function processContactSubmission(e) {

        e.preventDefault();

        function resetContactPanel() {
            setTimeout(function() {
                $(ctrl.contactSuccessAlert).html('').hide();
                $(ctrl.contactErrorAlert).html('').hide();
                $(ctrl.contactForm).find('textarea').val('');
                $(ctrl.contactForm).find('input').val('');
            }, 4000);
        }

        function showSuccessAlert(msg) {
            resetErrors();
            $(ctrl.contactSuccessAlert).html(msg).removeClass('hidden').show();
            resetContactPanel();
        }

        function showErrorAlert(msg) {
            if (msg.trim() === "") {
                msg = "Sorry, there was an issue sending your feedback. Please try again later."
            }
            $(ctrl.contactErrorAlert).html(msg).removeClass('hidden').show();
        }

        var values = {};

        $.each($(ctrl.contactForm).serializeArray(), function(i, field) {
            values[field.name] = field.value;
        });

        var requestOptions = {
            url: '/contact',
            method: 'POST',
            data: values,
            success: function(data) {
                showSuccessAlert(data);
            },
            error: function(error) {
                console.dir(error);
                showErrorAlert(error.responseText);
            }
        }

        $.ajax(requestOptions);
    }

    function processSubscriptionSubmission(e) {

        e.preventDefault();

        var values = {};

        $.each($(ctrl.subscriberForm).serializeArray(), function(i, field) {
            values[field.name] = field.value;
        });

        function showSuccessAlert(msg) {
            $(ctrl.subscribeSuccessAlert).html(msg).removeClass('hidden').show();
        }

        function showErrorAlert(msg) {
            $(ctrl.subscriberErrorAlert).html(msg).removeClass('hidden').show();
        }

        var requestOptions = {
            url: '/subscribe',
            method: 'POST',
            data: values,
            success: function(data) {
                resetErrors();
                showSuccessAlert(data);
                setTimeout(processArticle, 1200);
            },
            error: function(error) {
                showErrorAlert(error.responseText);
                return;
            }
        };

        $.ajax(requestOptions);
    };

    function resetErrors() {
        $.each(ctrl.alerts, function(i, e) {
            $(e).hide();
        });
    };

    function showArticleError(msg) {
        $(ctrl.articleErrorAlert).html(msg).removeClass('hidden').show();
    }

    return {
        init: init
    }
})();
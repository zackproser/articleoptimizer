var ReportClient = (function(options) {

  var ctrl = {};

  function init(controls) {
    ctrl = controls;

    //Instantiate new Clipboard.js object on the Copy Link button
    var clipboard = new Clipboard('#copy-link-button'); 
    //"Click to Copy" overlay for images
    var imageClipboard = new Clipboard('.img-thumbnail', {
      text: function(trigger) {
        return $(trigger).data('clipboard-text'); 
      }
    });

    imageClipboard.on('success', function(e){
      var button = $(e.trigger).find('.overlay button'); 
      $(button).html('<i class="fa fa-check"></i> Copied!');
      setTimeout(function(){
        $(button).html('<i class="fa fa-copy"></i> Click to Copy');
      }, 900);
    });

    //On copy bitly url success, display visual confirmation
    clipboard.on('success', function(e) {
      $('#copy-link-button').tooltip('show'); 
      setTimeout(function(){
        $('#copy-link-button').tooltip('hide');
      }, 1200); 
    }); 

    //On image hover, display the copy to clipboard overlay
    $(ctrl.imageThumbnail).on('mouseenter', function(){
      $(this).find('.overlay').fadeIn(400); 
    }).on('mouseleave', function(){
      $(this).find('.overlay').stop().fadeOut(100);
    });  

    //Bind email share button to show report emailing modal
    $(ctrl.emailReportButton).on('click', function(){
      $(ctrl.emailReportModal).modal('show'); 
    });

    //Set Facebook share button to current report url
    $(ctrl.facebookShareButton).attr('href', $(ctrl.facebookShareButton).attr('href') + window.location.href); 

    //Process requests to email the report to someone
    $(ctrl.sendReportButton).on('click', function(e){
      e.preventDefault(); 

      function showSuccessAlert(msg) {
        $(ctrl.sendReportSuccessAlert).html(msg).removeClass('hidden').show(); 
        resetSendReportModal();
      }

      function showErrorAlert(msg) {
        $(ctrl.sendReportErrorAlert).html(msg).removeClass('hidden').show();
        resetSendReportModal();
      }

      function resetSendReportModal() {
        setTimeout(function(){
          $(ctrl.sendReportSuccessAlert).html('').hide(); 
          $(ctrl.sendReportErrorAlert).html('').hide();
        }, 1200); 
      }

      var values = {}; 
      $.each( $(ctrl.sendReportForm).serializeArray(), function(i, field) {
        values[field.name] = field.value;
      }); 

      values.uri = window.location.href;

      var requestOptions = {
        url: '/email-report', 
        method: 'POST', 
        data: values, 
        success: function(data) {
          showSuccessAlert(data); 
          setTimeout(function(){
            ctrl.emailReportModal.modal('hide');
          }, 1200);
        }, 
        error: function(error) {
          showErrorAlert(error); 
        }
      }
      $.ajax(requestOptions);
    });
  }

  function showShortlinkPending() {
    $(ctrl.copyLinkButton).html('<i class="fa fa-spinner fa-spin"></i> Linking..');
  }

  function updateBitlyUrl(url) {
    $(ctrl.copyLinkButton).html('Copy Link'); 
    $('#report-link-holder').val(url);
  }

  function renderBitlyError(error) {
    $(ctrl.copyLinkButton).html('Copy Link');
    $(ctrl.bitlyErrorAlert).html('Sorry, there was an issue obtaining a shortlink.')
    .removeClass('hidden')
    .show();
  }

  function getBitlyLink() {

    showShortlinkPending(); 

    var url = window.location.href; 
    var requestOptions = {
      method: 'POST', 
      url: '/bitly-shorten', 
      data: {
        url: url
      }, 
      success: function(data) {
        if (data && typeof data === "string") {
          var response = JSON.parse(data); 
        }
        if (response.status_code && response.status_code === 200 && response.data.url) {
          updateBitlyUrl(response.data.url); 
          addBitlyLinkToTweetPrefill(response.data.url);
        } else {
          renderBitlyError();
        }
      }, 
      error: function(error) {
       this.renderBitlyError();
      }
    }; 
    $.ajax(requestOptions); 
  };

  //Add the bitly link to the pre-formatted tweet
  function addBitlyLinkToTweetPrefill(url) {
    if (typeof url === "string" && url != null) {
        var 
          originalHref = $(ctrl.tweetButton).attr('href'),
          newHref = originalHref += ' ' + url
        ; 
      $(ctrl.tweetButton).attr('href', newHref); 
    }
  }

  getBitlyLink(); 

  getFlickrImages(); 

  return {
    init: init
  }

})();


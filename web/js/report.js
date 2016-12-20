$(document).ready(function() {

  var controls = {}; 
 
  controls.copyLinkButton = $('#copy-link-button');
  controls.emailReportButton = $('#email-report-button');
  controls.emailReportModal = $('#email-report-modal');
  controls.sendReportButton = $('#form_sendReport');
  controls.sendReportForm = $('form');
  controls.sendReportSuccessAlert = $(controls.emailReportModal).find('.alert.alert-success');
  controls.sendReportErrorAlert = $(controls.emailReportModal).find('.alert.alert-danger');
  controls.facebookShareButton = $('#facebook-share-button');
  controls.imageThumbnail = $('.img-thumbnail');
  controls.bitlyErrorAlert = $('#bitly-error');
  controls.tweetButton = $('#tweet-button');

  ReportClient.init(controls);

}); 


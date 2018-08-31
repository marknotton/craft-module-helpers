////////////////////////////////////////////////////////////////////////////////
// Craft CMS Installer
////////////////////////////////////////////////////////////////////////////////

document.addEventListener("DOMContentLoaded", function(event) {
  $('#account-username').val('yello');
  $("#site-language").val('en');
  if (typeof project !== 'undefined' && project) {
    $("#site-name").val(project);
  }
  if (typeof email !== 'undefined' && email) {
    $('#account-email').val(email);
  }
});

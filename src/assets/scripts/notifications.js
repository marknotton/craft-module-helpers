////////////////////////////////////////////////////////////////////////////////
// notification Messages
////////////////////////////////////////////////////////////////////////////////

// Success Notice --------------------------------------------------------------

function setNotice(message) {
  var notice = '<div class="notification notice">'+message+'</div>';
  $('#notifications').append(notice);
  setTimeout(function(){ $('.notification.notice:last').fadeOut(500) }, 3000);
}

// Error Notice ----------------------------------------------------------------

function setError(message) {
  var notice = '<div class="notification error">'+message+'</div>';
  $('#notifications').append(notice);
  setTimeout(function(){ $('.notification.error:last').fadeOut(500) }, 3000);
}

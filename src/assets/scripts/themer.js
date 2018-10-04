////////////////////////////////////////////////////////////////////////////////
// Craft CMS Themeing
////////////////////////////////////////////////////////////////////////////////

// Add site name to sidebar ----------------------------------------------------

if (typeof site !== 'undefined') {
  $('#system-name h2').text(site.name);
}

// Replace 'Share' button text -------------------------------------------------

var shareButton = $('.btn.sharebtn');

if ( shareButton.length ) {
  shareButton.text('View Page');
}

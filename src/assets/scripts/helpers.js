////////////////////////////////////////////////////////////////////////////////
// Craft CMS Helpers
////////////////////////////////////////////////////////////////////////////////

// If BrowserSync is detected, we assume there is a port number present the the
// URL. This method will hijack links and inserts the port number into every
// href so that all links within the CMS are navigable.

document.addEventListener("DOMContentLoaded", function(){
  if ( typeof ___browserSync___ !== 'undefined' ) {
    document.addEventListener('click', function(event) {
      if (event.target.tagName.toLowerCase() == 'a') {
        event.target.port = ___browserSync___.options.port || 3000;
      }
    }, false);
  }
})

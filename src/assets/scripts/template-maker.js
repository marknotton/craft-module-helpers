////////////////////////////////////////////////////////////////////////////////
// Template Maker
////////////////////////////////////////////////////////////////////////////////

// && typeof initFLD !== 'undefined'

if ( typeof templateMakerForm !== 'undefined' ) {

  // Append form to page
  $('form#main-form').after(templateMakerForm);

  // Move filename input to aesthetically better position
  $('#filename-field').appendTo("#location-field > .input");

  $('form#template-maker').on('submit', function(event) {
    event.preventDefault();

    var entryTypeID = parseInt(window.location.pathname.split("/").pop(), 10);

    if ( entryTypeID ) {

      fetch('/template-maker', {
        mode    : 'cors',
        method  : 'POST',
        headers : new Headers({
          'Content-Type'     : 'application/json',
          'Accept'           : 'application/json',
          'X-Requested-With' : 'fetch'
        }),
        body: JSON.stringify({id:entryTypeID}),
        credentials: 'same-origin',
      })
      .then(
        function(response) {
          response.json().then(function(data) {
            if (response.ok && !data.error) {
              console.log(data);
              setNotice('Template Created');
              return data
            } else {
              setError('Failed to created Template');
              return Promise.reject({status: response.status, data})
            }
          });
        }
      )
      .catch(function(err) {
        if ( error ) {
          console.error('Error:', error.data.message)
        } else {
          console.error('Unknown error')
        }
      });

    } else {

      console.warn('Entry Type ID was not found in the URL');

    }

  });

}

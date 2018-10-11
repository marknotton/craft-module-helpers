////////////////////////////////////////////////////////////////////////////////
// Template Maker
////////////////////////////////////////////////////////////////////////////////

// && typeof initFLD !== 'undefined'

if ( typeof templateMaker !== 'undefined' && 'fetch' in window) {

  // Append form to page
  $('form#main-form').after(templateMaker);

  // Move filename input to aesthetically better position
  $('#template-field').appendTo("#path-field > .input");

  var templateMakerForm = $('form#template-maker');
  var overwriteCheckbox = templateMakerForm.find('#overwrite');

  templateMakerForm.on('submit', function(event) {

    event.preventDefault();

    if (overwriteCheckbox.is(":checked")) {
      if (!confirm("You are about to overwrite an existing file. Are you sure you want to do this? This can't be undone.")) {
        console.log('Do nothing!');
        return false;
      }
    }

    templateMakerForm.addClass('loading');

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
              templateMakerForm.removeClass('loading');
              return data
            } else {
              templateMakerForm.addClass('error');
              setError('Failed to created Template');
              setTimeout(function(){ templateMakerForm.removeClass('error loading') }, 1000);
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

      setError('Entry Type not found');
      console.warn('Entry Type ID was not found in the URL');

    }

  });

  overwriteCheckbox.on('change', function(event) {
    event.preventDefault();
    console.log('TOGGLE');
  })

}

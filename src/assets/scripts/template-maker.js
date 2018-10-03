////////////////////////////////////////////////////////////////////////////////
// Template Maker
////////////////////////////////////////////////////////////////////////////////

if ($('#crumbs nav li a[href$=entrytypes]').length && 'fetch' in window) {

  $('header#header input.btn.submit').before(
    "<button class='btn create-template'>Generate Template File</button>"
  );

  $('button.create-template').on('click', function(event) {
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
              return data
            } else {
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

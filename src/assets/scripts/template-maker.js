////////////////////////////////////////////////////////////////////////////////
// Template Maker
////////////////////////////////////////////////////////////////////////////////

// && typeof initFLD !== 'undefined'

// (function() {

  if ( typeof templateMaker !== 'undefined' && 'fetch' in window) {

    // Append form to page
    $('form#main-form').after(templateMaker);

    // Move filename input to aesthetically better position
    $('#template-field').appendTo("#path-field > .input");

    var templatemaker = { form : $('form#template-maker') };
    var timer = null;
    var filePathAndName = '';

    templatemaker['path']      = templatemaker.form.find('input#path');
    templatemaker['template']  = templatemaker.form.find('input#template');
    templatemaker['overwrite'] = templatemaker.form.find('#overwrite');

    $('#template-field').attr('data-timestamp', timestamp);


    function fileExistance() {
      clearTimeout(timer);

      // templatemaker.path.val(templatemaker.path.val().replace(/[^a-zA-Z0-9-_/]/g, ''));
      if (templatemaker.template.val().length == 0) {
        timer = setTimeout(function(){ templatemaker.template.val(defaultTemplate); }, 1000);
      } else {
        templatemaker.template.val(templatemaker.template.val().replace(/[^a-zA-Z0-9-_]/g, ''));
      }

      templatemaker.path.first().val(templatemaker.path.first().val().replace(/\/\/+/g, '/').replace(/^\/+/g, '').replace(/[^a-zA-Z0-9-_/]/g, ''));

      filePathAndName = (templatemaker.path.val().replace(/\/$/, "")+ '/' +templatemaker.template.val() + '.twig').replace(/^\//, '');
      if (existingFiles.includes(filePathAndName)) {
        templatemaker.form.addClass('exists');
      } else {
        templatemaker.form.removeClass('exists');
      }
      $('warning-message > p em').text(filePathAndName);
      console.log(existingFiles.includes(filePathAndName) ? 'exists' : 'doesnt exists');
    }

    $.merge(templatemaker.path, templatemaker.template).on('input', fileExistance);
    fileExistance();

    templatemaker.form.on('submit', function(event) {

      event.preventDefault();

      if (templatemaker.overwrite.is(":checked")) {
        if (!confirm("You are about to overwrite: "+filePathAndName+".\n Are you sure you want to do this? This can not be undone.")) {
          setError('Template file was not created');
          return false;
        }
      }

      templatemaker.form.addClass('loading');

      if ( sectionId ) {

        fetch('/template-maker', {
          mode    : 'cors',
          method  : 'POST',
          headers : new Headers({
            'Content-Type'     : 'application/json',
            'Accept'           : 'application/json',
            'X-Requested-With' : 'fetch'
          }),
          body: JSON.stringify({id:parseInt(sectionId, 10), filename:filePathAndName}),
          credentials: 'same-origin',
        })
        .then(
          function(response) {
            response.json().then(function(data) {
              if (response.ok && !data.error) {
                console.log(data);
                setNotice('Template Created');
                templatemaker.form.removeClass('loading');
                return data
              } else {
                templatemaker.form.addClass('error');
                setError('Failed to created Template');
                setTimeout(function(){ templatemaker.form.removeClass('error loading') }, 1000);
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

    templatemaker.overwrite.on('change', function(event) {
      event.preventDefault();
      if ($(this).is(':checked')) {
        templatemaker.form.addClass('overwrite');
      } else {
        templatemaker.form.removeClass('overwrite');
      }
    })

  }

// })();

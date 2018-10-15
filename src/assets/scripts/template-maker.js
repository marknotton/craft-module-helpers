////////////////////////////////////////////////////////////////////////////////
// Template Maker
////////////////////////////////////////////////////////////////////////////////

// && typeof initFLD !== 'undefined'

if ( typeof templateMaker !== 'undefined' && 'fetch' in window) {

  // ===========================================================================
  // Init
  // ===========================================================================

  // Append form to page
  $('form#main-form').after(templateMaker);

  // Move filename input to aesthetically better position
  $('#template-field').appendTo("#path-field > .input");

  var timer = null;
  var filePathAndName = '';
  var templatemaker = {
    form      : $('form#template-maker'),
    notice    : $('form#template-maker > p.notice'),
    path      : $('form#template-maker input#path'),
    template  : $('form#template-maker input#template'),
    overwrite : $('form#template-maker #overwrite'),
  };

  var nameInput = $('form#main-form input#name');
  var handleInput = $('form#main-form input#handle');

  $('#template-field').attr('data-timestamp', timestamp);

  // If any elements are moved aroud show a message advising the user
  // to save the entrytype before creating a template.
  $(function() {
    $(".fld-tabs").on('DOMSubtreeModified', function() {
        $(this).unbind('DOMSubtreeModified');
        templatemaker.notice.show();
    });
  });


  // ===========================================================================
  // File name and path sanitiser
  // ===========================================================================

  function fileExistance() {
    clearTimeout(timer);

    if (templatemaker.template.val().length == 0) {
      timer = setTimeout(function(){
        templatemaker.template.val(defaultTemplate);
        fileExistance();
      }, 1000);
    } else {
      templatemaker.template.val(templatemaker.template.val().replace(/[^a-zA-Z0-9-_]/g, '').toLowerCase());
    }

    templatemaker.path.first().val(templatemaker.path.first().val().replace(/\/\/+/g, '/').replace(/^\/+/g, '').replace(/[^a-zA-Z0-9-_/]/g, ''));

    filePathAndName = (templatemaker.path.val().replace(/\/$/, "")+ '/' +templatemaker.template.val() + '.twig').replace(/^\//, '');
    if (existingFiles.includes(filePathAndName)) {
      templatemaker.form.addClass('exists');
    } else {
      templatemaker.form.removeClass('exists');
    }
    $('warning-message > p em').text(filePathAndName);
  }

  $.merge(templatemaker.path, templatemaker.template).on('input', fileExistance);
  fileExistance();

  // ===========================================================================
  // Template field smart updater
  // ===========================================================================

  // On page load, if the default template maker file name and path is set to exist,
  // and the tempalte value is specifically '_entry' and handle input is blank,
  // Then begin some UI that cleverly updates the template field on-the-fly
  // whilst the name or handle is being edited.
  if (templatemaker.form.hasClass('exists') && templatemaker.template.val() == '_entry' && handleInput.val() == "") {

    var allowBlur = true;

    // If name input is selected and the tab keyboard key is used to move down
    // to the 'handle' input, temporily disable the unbinding of 'change input'
    // on the 'handle' input. This is similar behavour to how Craft do theres.
    nameInput.keydown( function(e) {
      allowBlur = false;
      if (e.keyCode == 9 && !e.shiftKey) {
        setTimeout(function(){ allowBlur = true; }, 1000);
      }
    });

    // When name input or handle input is blurred (clicked or tabbed out),
    // then remove the binder tha amends the template field on-the-fly.
    $.merge(nameInput, handleInput).blur(function() {
      if ( allowBlur && handleInput.val() !== '') {
        console.log("No more smart template name making");
        handleInput.unbind( "change input" );
      }
    });

    // Update the template field on-the-fly whilst the handle input changes.
    handleInput.on('change input', function() {
      templatemaker.template.val(handleInput.val().replace(/[^a-zA-Z0-9-_]/g, '').toLowerCase());
      if ( templatemaker.template.val() == templatemaker.path.val()) {
        templatemaker.template.first().val('index');
      }
    });

  }

  // ===========================================================================
  // Overwrite toggle listener
  // ===========================================================================

  templatemaker.overwrite.on('change', function(event) {
    event.preventDefault();
    if ($(this).is(':checked')) {
      templatemaker.form.addClass('overwrite');
    } else {
      templatemaker.form.removeClass('overwrite');
    }
  })

  // ===========================================================================
  // Form submission
  // ===========================================================================

  templatemaker.form.on('submit', function(event) {

    event.preventDefault();

    // If overwrite is checked, show a popup message with one final warning.
    if (templatemaker.overwrite.is(":checked")) {
      if (!confirm("You are about to overwrite: "+filePathAndName+".\n Are you sure you want to do this? This can not be undone.")) {
        setError('Template file was not created');
        return false;
      }
    }

    // Apply the loading class to disable any further input and show the animation.
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
}

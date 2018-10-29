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
  // $('#screens #site').after(optionsScreen);
});

var optionsScreen = `
<div id="options" class="screen hidden">

  <div class="icon">
    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" style="enable-background:new 0 0 100 100;" xml:space="preserve">
      <path d="M91.7,82.7c0,9.5-6.2,17.3-13.9,17.3H22.2c-7.6,0-13.9-7.7-13.9-17.3c0-17.1,4.2-36.9,21.3-36.9c5.3,5.1,12.4,8.3,20.4,8.3s15.1-3.2,20.4-8.3C87.4,45.8,91.7,65.6,91.7,82.7z M75,25c0,13.8-11.2,25-25,25S25,38.8,25,25S36.2,0,50,0S75,11.2,75,25z"></path>
    </svg>
  </div>

  <h1>Project Options</h1>

  <form accept-charset="UTF-8">

    <div class="field first" id="doggistyle-field">
      <div class="heading">
        <label id="doggistyle-label" for="doggistyle">Username</label>
      </div>
      <div class="input ltr">
        <input class="text fullwidth" type="text" id="doggistyle" maxlength="255" autocomplete="off" title="Install">
      </div>
    </div>

    <div class="buttons">
      <div class="btn big submit" tabindex="0">Next
        <input type="submit" tabindex="-1">
      </div>
    </div>
  </form>

</div>`;

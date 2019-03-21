////////////////////////////////////////////////////////////////////////////////
// Notification Messages
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

////////////////////////////////////////////////////////////////////////////////
// Error Messages
////////////////////////////////////////////////////////////////////////////////

if ( typeof allowAdminChanges !== 'undefined' && allowAdminChanges === false ) {
  if ( $('#message').find('p').text() == 'Administrative changes are disallowed in this environment.') {
    $('#message h2').text(`Schema changes are not allowed in this ${environment} environment`)
    $('#message').css({'width':450, 'transform':'translateY(-240px)'}).find('p').text(`
      You are seeing this because the following areas can only be amended in Development environments:`).after(`
      <ul style="list-style-type: circle; padding-left:16px">
        <li>Asset volumes and image transforms</li>
        <li>Category groups</li>
        <li>Email settings</li>
        <li>Fields and field groups</li>
        <li>Global set settings</li>
        <li>Matrix block types</li>
        <li>Plugin settings</li>
        <li>Routes (defined in the control panel)</li>
        <li>Sections and entry types</li>
        <li>Sites and site groups</li>
        <li>System settings</li>
        <li>Tag groups</li>
        <li>User groups and settings</li>
      </ul>
      <p>
        To retain consistency between all environments any Schema amendments should
        be managed by your web developers to ensure they are thoroughly tested and deployed
        properly.
      </p>
      <p>
        On-the-fly updates in this environment are discouraged to
        avoid conflicts and data loss as-per the introduction of the Craft 3.1 <a target="_blank" href="https://medium.com/@ben_45934/understanding-project-config-in-craft-cms-3dd70b232dfa">Project Config.</a>
      </p>
      <p><strong>
        You can still manage <a href="${Craft.baseCpUrl}/entries">Entries</a>, <a href="${Craft.baseCpUrl}/globals">Globals</a>, <a href="${Craft.baseCpUrl}/categories">Categories</a>,
        <a href="${Craft.baseCpUrl}/assets">Assets</a>, <a href="${Craft.baseCpUrl}/users">Users</a>, and <a href="${Craft.baseCpUrl}/utilities">Utilities</a>
      </strong></p>
      <small style="font-size:smaller"><i>Speak to your project manager at <a href="mailto:support@yello.studio?subject=${Craft.baseSiteUrl}: Schema changes are not allowed in this Development environment">Yello Studio</a> for more details</i></small>
    `)
  }
}

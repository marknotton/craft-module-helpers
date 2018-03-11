<?php
return [

  // On each use of the function classes(),
  // these rules will be called and return an appropriate string

  // Example: homepage
  'entry-slug' => true,

  // Example: id-2
  'entry-id' => 'entry-id-%i',

  // Example: parent
  // Is it a parent, child, or both
  'hierarchy' => true,

  // Example: level-2
  // level number if hierarchy is found
  'level' => true,

  // Example: single
  'section-type' => true,

  // Example: news
  'section-handle' => true,

  // Example: page-2
  // If it's greater than 1
  'page-number' => true,

  // Example: error-404 or status-301
  // Error status codes, excluding 2xx's
  'error' => true,

  // Example: holding-page
  'holdingpage' => true,

  // Example: admin
  'admin' => true,

  // Example: first-visit
  'first-visit' => 'this-is-my-first-visit',

  // Example: devmode
  'devmode' => true,

  // Example: production-environment
  'environment' => true,

  // Example: local
  // Checks if your working locally (with MAMP/WAMP)
  'local' => true,

  // Example: mac
  // Requires Browser plugin. Desktop/Tablet/Mobile
  'device' => true,

  // Example: high-sierra
  // Requires Browser plugin. Windows, Mac, Linux
  'operating-system' => true,

  // Example: supported
  // Requires Browser plugin.
  // Checks critieia is met for the current browser to be supported.
  'supported' => true,

];

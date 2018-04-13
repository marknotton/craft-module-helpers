# Helpers module for Craft CMS 3

## Requirements

This module requires Craft CMS 3.0.0 or later.

## Installation

To install the module, follow these instructions.

You will need to add the following content to your `config/app.php` file. This ensures that your module will get loaded for each request. You can remove components if you don't require the full set of features this module offers.
```
return [
  'modules' => [
    'helpers' => [
    'class' => \modules\helpers\Helpers::class,
      'components' => [
        'services' => [
          'class' => 'modules\helpers\services\Services',
        ],
        'queries' => [
          'class' => 'modules\helpers\services\Queries',
        ]
      ],
    ],
  ],
  'bootstrap' => ['helpers'],
];
```
You'll also need to make sure that you add the following to your project's `composer.json` file so that Composer can find your module:

```
"autoload": {
  "psr-4": {
    "modules\\": "modules/",
    "modules\\helpers\\": "modules/helpers/module/"
  }
},
```

After you have added this, you may need to run `composer dump-autoload` from the project’s root directory to rebuild the Composer autoload map. This will happen automatically any time you do a `composer install` or `composer update` as well.

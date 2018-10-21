/**
 * Apply common fixes
 * @constructor
 * @param {args} methods - List of methods you want to include. Leave blank to apply all methods
 * @example new Fixers(); // Runs all fixers
 * @example new Fixers('featuredImages', 'links') // Runs only the fastclick and links fixers;
 */

class Fixers {
  constructor () {
    let $this = this;

    let fixes = [];

    // Get a list of all methods in this fixer class
    let allFixes = Object.getOwnPropertyNames(this.constructor.prototype).slice(1);

    // Get a list of any arguments passed
    let args = Array.prototype.slice.call(arguments);

    // Define fixes that exist and were passed into the original class as strings
    if ( args.length ) {
      args.forEach(function (value) {
        if ( allFixes.includes(value) ) {
          fixes.push(value);
        }
      });
    } else {
      // If no arguments were passed, run all fixes.
      fixes = allFixes;
    }

    // Run fixer functions
    fixes.forEach(function (method) {
      $this[method](this);
    })

  }

  // Add 'srcset' fallback for IE and Edge browsers when using feature-images custom elements
  featuredImages (customElement = 'featured-image') {
    if (typeof browser !== 'undefined' ) {
      if( browser.name == 'ie' || browser.name == 'edge' ) {
        $(customElement).each(function() {
          var $this = $(this);
          if ( $this.attr('data-desktop') || $this.attr('data-mobile')) {
            $this.css('background-image', 'url('+(mobile ? $this.data('mobile') : $this.data('desktop'))+')').find('img').hide();
          }
        })
      }
    }
  }

  // Make all external links open a new tab.
  links () {
    $('a').each(function() {
      const a = new RegExp('/' + window.location.host + '/');
      if (!a.test(this.href)) {
        if (!this.href.startsWith('callto') && !this.href.startsWith('tel') && !this.href.startsWith('mailto') && !this.href.startsWith('skype')) {
          $(this).attr({'rel': 'noopener', 'target': '_blank'});
        }
      }
    });
  }

  // Wrap all iframe videos witinh a div element
  videos (containerClass = 'video-container') {
    $('iframe[src*="youtube"], iframe[src*="vimeo"]').each(function() {
      if(!$(this).parent().hasClass(containerClass) && !$(this).parent().prop("tagName") == 'VIDEO-CONTAINER') {
        $(this).wrap("<div class='"+containerClass+"'></div>");
      }
    });
  }
}

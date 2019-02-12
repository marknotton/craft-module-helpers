////////////////////////////////////////////////////////////////////////////////
// On Page
////////////////////////////////////////////////////////////////////////////////

/**
* Do some fancy regex stuff to place the version number within the filename string
* @param {object} elements  HTML elements.
* @param {array}  args      Accepts upto two functions for callbacks,
*                           and a number for optional delays.
*        {func}             First function will be used as a callback if elements exists
*                           This returns the HTML element in the callback.
*        {func}             section function will be used as a callback if elements doesn't exists
*        {number}           The number of miliseconds to delay the callback. 3000 = 3 seconds.
* @example $('main').onPage(500, ( element ) => { ... }, () => { ... });
*/

class onPage {

  constructor() {

		let elements      = null;
		let name          = null;
		let callbackTrue  = null;
		let callbackFalse = null;
		let delay         = null;

		this.functions = [];

		Array.prototype.slice.call(arguments).forEach((arg) => {

			switch (typeof arg) {
				case 'function':
					if ( callbackTrue == null ) { callbackTrue = arg }
					else if ( callbackFalse == null ) { callbackFalse = arg	}
				break;
				case 'number':
					delay = arg;
				break;
				case 'object':
					elements = arg;
				break;
				case 'string':
					name = arg;
				break;
			}

		});

		if (name == null) {
			name = arguments[1].toString().match(/[^ =]*/i)[0];
		}

    // Manage Callbacks ========================================================

    if ( callbackTrue !== null ) {

			// this.functions[name] = {'true' : {
			// 	functions : callbackTrue,
			// 	elements : elements,
			// 	delay : delay || false }
			// };

      if ( elements.length ) {

        // Elements exist ------------------------------------------------------

        if ( delay ) {

          setTimeout(() => {
            callbackTrue(elements);
          }, delay);

        } else {

          callbackTrue(elements);

        }

      } else if ( callbackFalse !== null) {

        // Elements don't exist ------------------------------------------------

        if ( delay ) {

          setTimeout(() => {
            callbackFalse(elements);
          }, delay);

        } else {

          callbackFalse();

        }

      }
    }
  }
}

// =============================================================================
// Pluginify - Convert plugin class into a jQuery plugin
// =============================================================================
if ( window.jQuery && typeof pluginify !== 'undefined') {
  pluginify.add('onPage', onPage);
}

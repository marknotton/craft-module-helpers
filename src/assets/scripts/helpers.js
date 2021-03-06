// =============================================================================
// Dom Listener
// @see https://stackoverflow.com/a/37403125/843131
// =============================================================================
// This function can be defined multiple times. Each iterations
// will be triggered after all DOM elements and scripts have loaded.
// Usage:
// dom.ready(() => {
//   console.log("All DOM elements and script files loaded.")
// });

const dom = {
  functions : [],
  triggered : false,
  ready : (func) => dom.functions.push(func),
  set status(val) {
    if (!dom.triggered) {
      loaded = val;
      dom.triggered = true;
      if (loaded == 'loaded') {
        dom.functions.forEach((func) => func());
      }
    }
  },
  get status() {
    return loaded;
  }
};

// =============================================================================
// Pluginify
// =============================================================================

const pluginify = {

	instances : {},

	add : (name, classname) => {

		let $this = pluginify

		if ( typeof $this.instances[name] !== 'undefined' ) {
			return $this.instances[name]
		}

		$.fn[name] = function(...options) {
			$this.instances[name] = new classname(this, ...options)
			return $this.instances[name]
		}
	}
}

// =============================================================================
// Add a 'locked' getter and setter to thebody
// =============================================================================

dom.ready(() => {

	if ( window.jQuery && typeof body !== 'undefined' ) {
		body['_locked'] = false;
		Object.defineProperty(body, 'locked', {
			set: function(value) {
				if ( typeof value == 'boolean' ) {
					this._locked = value;
					if ( value ) { body.addClass('locked').bind('mousewheel touchmove', (e) => { e.preventDefault() }) }
					else { body.removeClass('locked').unbind('mousewheel touchmove') }
				}
			},
			get : function() { return this._locked }
		});
	}

})


// =============================================================================
// Debounce
// =============================================================================

const debounce = (fn, time = 10) => {
  let timeout;

  return function() {
    const functionCall = () => fn.apply(this, arguments);

    clearTimeout(timeout);
    timeout = setTimeout(functionCall, time);
  }
};

// =============================================================================
// Dimensions
// =============================================================================
// These add 'width' and 'height' getters to the window and document objects respectively

if ( typeof window.width == 'undefined' ) {
  Object.defineProperty(window, 'width', {
    get : () => { return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth }
  });
}

if ( typeof window.height == 'undefined' ) {
  Object.defineProperty(window, 'height', {
    get : () => { return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight }
  });
}

if ( typeof document.width == 'undefined' ) {
  Object.defineProperty(document, 'width', {
    get : (body = document.body, html = document.documentElement) => {
      return Math.max( body.scrollWidth, body.offsetWidth, html.clientWidth, html.scrollWidth, html.offsetWidth );
    }
  });
}

if ( typeof document.height == 'undefined' ) {
  Object.defineProperty(document, 'height', {
    get : (body = document.body, html = document.documentElement) => {
      return Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
    }
  });
}

// =============================================================================
// Disable Console Logging on Production Environments for non-admins.
// =============================================================================

if ( document.body.classList.contains('production-environment') && !document.body.classList.contains('admin') ) {

  window['_logger'] = { status : true, old : null };

  Object.defineProperty(window, 'logger', {
    get : () => {
      return window._logger.status;
    },
    set : (value = true) => {
      if ( typeof value == 'boolean') {
        window._logger.status = value;
        if (value) {
          // Enable Logger
          if(window._logger.old == null) { return; }
          window['console']['log'] = window._logger.old;
        } else {
          // Disable Logger
          window._logger.old = console.log;
          window['console']['log'] = function() {};
        }
      }
    }
  });

  logger = false;

}

// =============================================================================
// Scrollbar
// =============================================================================

const scrollbar = {
  y : window.pageYOffset || document.documentElement.scrollTop,
  x : window.pageXOffset || document.documentElement.scrollLeft,
  get width() {
    return window.innerWidth - document.documentElement.clientWidth || 0;
  },
  get position() {
    return Math.round(((window.scrollY / (document.height - document.body.clientHeight)) * 100) * 100) / 100 || 0;
  },
  get direction() {

    let results = [];

    // Vertical check
    let y = window.pageYOffset || document.documentElement.scrollTop;
    let v = y < scrollbar.y ? 'up' : (y > scrollbar.y ? 'down' : false);
    if (v !== false) { results.push(v) }
    scrollbar.y = y <= 0 ? 0 : y;

    // Horizontal check
    let x = window.pageXOffset || document.documentElement.scrollLeft;
    let h = x < scrollbar.x ? 'left' : (x > scrollbar.x ? 'right' : false);
    if (h !== false) { results.push(h) }
    scrollbar.x = x <= 0 ? 0 : x;

    return results;
  }
};

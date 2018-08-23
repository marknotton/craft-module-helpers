////////////////////////////////////////////////////////////////////////////////
// On Window Resize
////////////////////////////////////////////////////////////////////////////////

class OnWindowResize {
  constructor (elements = window, args) {

    this.functions = [];

    this.defaults = this.constructor.defaults;

    // handle event

    let running = false;

    window.addEventListener("resize", () => {
      if (running) { return; }
      running = true;
      requestAnimationFrame(function() {
        window.dispatchEvent(new CustomEvent("windowResize"));
        running = false;
      });
    });

    window.addEventListener("windowResize", this.resize);

    // Trigger Resize on load
    if (this.defaults.triggerOnLoad ) {
      if (typeof dom !== undefined) {
        dom.ready(this.resize);
      } else {
        this.resize();
      }
    }

  }

  set listener (options) {

    if (typeof options == 'object') {

      options.forEach(option => {

        let properties = {
          callback : null,
          callout : null,
          breakpoints : [this.defaults.min, this.defaults.max]
        }

        // Manage Functions
        if (typeof option == 'function') {
          if ( properties.callback == null ) {
            properties.callback = option;
          } else if ( properties.callout == null ) {
            properties.callout = option;
          }
        }

        // Manage breakpoints
        if (typeof option == 'array' || typeof option == 'number') {
          properties.breakpoints = option;
        }

        // Store properties
        this.functions.push(properties);

      })
    }

    console.log('Listeners ', this.functions);
  }

  resize () {
    // console.log('reize func')
  }

  windowWidth () {
    return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  }

  windowHeight () {
    return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
  }

}

////////////////////////////////////////////////////////////////////////////////
// Default Settigs
////////////////////////////////////////////////////////////////////////////////

OnWindowResize.defaults = {
  min : 320,
  max : null,
  triggerOnLoad : true
}

////////////////////////////////////////////////////////////////////////////////
// Initialisers
////////////////////////////////////////////////////////////////////////////////

const _owr = new OnWindowResize();

function onWindowResize(...options) {
  _owr.listener = options;
}

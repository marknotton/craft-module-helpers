class Iterator {

  ////////////////////////////////////////////////////////////////////////////////
  // Default Settigs
  ////////////////////////////////////////////////////////////////////////////////

  get defaults() {
    return {
      duration  : 3000,       // Time (in miliseconds) between each iteration callback
      instant   : true,       // Trigger iniitial callback instantly with no delay
      delay     : 0,          // End function delay.
      loop      : 3,          // Iterations loop infinetly or by a set amount
      autostop  : true,       // Adds a listener to pause iterations when user is not activly looking at page
      startfrom : 1,          // Choose to start the iteration from a particular index
      endon     : 1,          // Loop rules are ignored until a particular index is found after the final loop.
      direction : 'forwards', // Start playing immediatly
      autoplay  : true,       // Start playing immediatly
      log       : false       // Dev option to console log out status changes.
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Constructor - Manage all arguments and define settings
  //////////////////////////////////////////////////////////////////////////////

  constructor (elements, args) {

    if ( typeof elements == 'undefined' || typeof elements !== 'object' ) {
      return false;
    }

    this.queue = [];
    this.iteration = 1;
    this.elements = elements;
    this.looped = 0;
    this.total = elements.length;
    this.state = 'stopped';

    if ( this.total && args ) {

      let settings = {};

      // If the first argument is an object, assume this is a list of named options.
      //  Merge it's contents to settings.
      if (typeof args[0] == 'object') {

        let purge = Object.assign({}, args)
        delete purge[0]
        settings = Object.assign({}, purge, args[0]);

      } else {

        // Otherwise, run through any annonymous settings.
        // Loop through the object and define it's key and value
        Object.entries(args).forEach((entry) => {
          const [key, value] = entry;

          if (!isNaN(key)) {
            switch (typeof value) {
              case 'function':
                if (typeof settings.start == 'undefined') {
                  settings.start = value;
                } else if (typeof settings.end == 'undefined') {
                  settings.end = value;
                }
              break
              case 'number':
                if (typeof settings.duration == 'undefined') {
                  settings.duration = value;
                } else if (typeof settings.delay == 'undefined') {
                  settings.delay = value;
                } else if (typeof settings.loop == 'undefined') {
                  settings.loop = value;
                }
              break
              case 'boolean':
                if (typeof settings.loop == 'undefined') {
                  settings.loop = value;
                } else if (typeof settings.autoplay == 'undefined') {
                  settings.autoplay = value;
                }
              break
            }
          }
        });
      }

      // Merge all settings and default settings to a final list of usuable options.
      settings = Object.assign({}, this.defaults, this.constructor.defaults, settings);

      if ( typeof settings.start == 'undefined' ) {
        console.warn("You must define a function to be called back on each iteration")
        return false;
      }

      // Apply all settings to the class
      Object.assign(this, settings);

      // Translate loop count to 1 if false was passed
      this.loop = this.loop == false ? 1 : this.loop;

      // If autostop is enabled, creating a document Event Listener to check if
      // the user is still actively on the page. Use this data to manage pause/play statuses
      if ( this.autostop ) {
        document.addEventListener('visibilitychange', () => {
          if ( !this.status != 'stopped' ) {
            if (document.visibilityState === 'hidden' ) {
              this.pause();
            } else if ( this.autoplay ) {
              this.play();
            }
          }
        })
      }

      if (this.autoplay) {
        this.play(this.startfrom);
      }

    }

  }

  //////////////////////////////////////////////////////////////////////////////
  // Pause - Pausing the iteration will retain the iteration count
  //////////////////////////////////////////////////////////////////////////////

  pause() {
    if (this._status('paused')) {
      clearInterval(this.timer);
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Stop - Same as pausing, only the iteration counter gets reset.
  //////////////////////////////////////////////////////////////////////////////

  stop() {
    if (this._status('stopped')) {
      this.iteration = this.startfrom;
      clearInterval(this.timer);
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Next
  //////////////////////////////////////////////////////////////////////////////

  next() {
    let nextExists = true;

    clearInterval(this.timer);

    if ( this.iteration == this.total ) {
      if (this.loop === 1) {
        nextExists = false;
      } else {
        this.iteration = 1
      }
    } else {
      this.iteration ++;
    }

    if (this.log) { console.log('next') }

    return this._callback();
  }

  prev() {
    let prevExists = true;
    clearInterval(this.timer);
    if ( this.iteration == this.startfrom ) {
      if (this.loop === 1) {
        prevExists = false;
      } else {
        this.iteration = this.total
      }
    } else {
      this.iteration --;
    }
    this._callback()
    if (this.log) { console.log('prev', prevExists) }
    return prevExists;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Play
  //////////////////////////////////////////////////////////////////////////////

  play(iteration = this.iteration) {
    if (this._status('playing')) {

      this.iteration = iteration >= this.total || iteration < 1 ? 1 : iteration;

      // If instant is enabled. Trigger the first callback immidiatly.
      // Instead of wating until the first duration has passed.
      if ( !this.first && this.instant ) {
        this.first = true;
        this._callback()
        this.iteration ++;
      }

      if ( this.total > 1) {

        this.timer = setInterval(() => {

          this._callback()

          if ( this.iteration == this.total || this.loop != true && this.looped >= this.loop ) {

            this.looped ++;

            if ( this.loop === 1 || this.loop != true && this.looped >= this.loop ) {

              if ( this.loop === 1 || this.iteration == this.endon || typeof this.endon == 'string') {
                this.stop();
              } else {
                if (this.iteration == this.total) {
                  this.iteration = 1;
                } else {
                  this.iteration ++;
                }
              }

            } else {
              this.iteration = 1;
            }
          } else {
            this.iteration ++;
          }

        }, this.duration );

      }
    }

  }

  //////////////////////////////////////////////////////////////////////////////
  // Private methods
  //////////////////////////////////////////////////////////////////////////////

  _callback(direction = this.direction) {

    let index    = this.iteration - 1;
    let current  = this.elements[index];
    let previous = this.elements[index - 1] || (this.loop === 1 ? false : this.elements[this.total - 1]);
    let next     = this.elements[index + 1] || (this.loop === 1 ? false : this.elements[0]);

    this.start.call(null, current, index)

    if ( direction == 'forwards' ) {
      // Next

      if ( this.total > 1 && typeof this.end != 'undefined' ) {

        if (this.delay > 0) {

          setTimeout(() => {
            this.end.call(null, previous, index)
          }, this.delay);

        } else {

          this.end.call(null, previous, index)

        }
      }
    } else {
      // Previous

    }

  }

  _status(status) {
    if (this.status != status ) {
      this.status = status;
      if (this.log) {
        console.log(this.status);
      }
      return true;
    } else {
      return false;
    }
  }
}


////////////////////////////////////////////////////////////////////////////////
// Pluginify - Convert plugin class into a jQuery plugin
////////////////////////////////////////////////////////////////////////////////
if ( window.jQuery && typeof pluginify !== 'undefined') {
  pluginify('iterate', Iterator, false);
}

class Breakpoints {

  constructor(element) {

    this.defaults = this.constructor.defaults;
    this.functions = { in : { beyond : [] }, out : { beyond : [] } };
    this.inFunctionsExist = false;
    this.outFunctionsExist = false;

    // Copy all breakpoint values if they exist
    let breaks = this.defaults.breaks || {};

    if (Object.keys(breaks).length) {

      // Convert any strings into usable numbers (example '520px' > 520)
      Object.keys(breaks).map(key => breaks[key] = parseInt(breaks[key], 10));

      // Set the breaks whilst also managing the order from high to low.
      this.breaks = this._sort(breaks);

      // Predefined breakpoints are made accisble so we don't have to keep
      // looping through they keys and values
      this.names = Object.keys(this.breaks);
      this.points = Object.values(this.breaks);

      // Define each breakpoint as a getter, each returning a bool if
      // the breakpoint is equal or greater than the window width.
      // Also, define some emtry arrays for each breakpoint valie.
      Object.entries(breaks).forEach((breakpoint) => {
        const [name, value] = breakpoint;
        Object.defineProperty(this, name, {
          get : () => {
            return value >= this._width()
          }
        })
      });

      // Setup the listener, this method throttles the resize listener
      let running = false;
      window.addEventListener("resize", () => {
        if (running) { return; }
        running = true;
        requestAnimationFrame(() => {
          window.dispatchEvent(new CustomEvent("breakpoints"));
          running = false;
        });
      });

      window.addEventListener("breakpoints", () => {
        this.resize();
      }, false)

      // Trigger Resize on load
      if (this.defaults.triggerOnLoad ) {
        if (typeof dom !== undefined) {
          dom.ready(() => { this.resize() });
        } else {
          this.resize();
        }
      }
    }
  }

  in(...args) {

    let breakpoint, one, func;

    if (args.length) {
      args.forEach((value) => {
        switch (typeof value) {
          case 'number':
          case 'string':
            breakpoint = parseInt(value, 10) || this.breaks[value];
          break;
          case 'object':
            breakpoint = value.map((val) => typeof val == 'number' ? val : parseInt(val, 10) || this.breaks[val]).sort((a, b) => a - b);
          break;
          case 'boolean':
            one = value;
          break;
          case 'function':
            func = value;
          break;
        }
      })
    }

    if (typeof breakpoint === 'object') {
      this.between(breakpoint, one, func);
    } else {
      if ( typeof breakpoint !== undefined && typeof func !== undefined ) {
        this.inFunctionsExist = true;
        if (this.functions.in[breakpoint]) {
          this.functions.in[breakpoint].push(func);
        } else {
          this.functions.in[breakpoint] = [func];
        }
      }
    }
  }

  out(...args) {

    let breakpoint, one, func;

    if (args.length) {
      args.forEach((value) => {
        switch (typeof value) {
          case 'number':
          case 'string':
            breakpoint = parseInt(value, 10) || this.breaks[value];
          break;
          case 'object':
            breakpoint = value.map((val) => typeof val == 'number' ? val : parseInt(val, 10) || this.breaks[val]).sort((a, b) => a - b);
          break;
          case 'boolean':
            one = value;
          break;
          case 'function':
            func = value;
          break;
        }
      })
    }

    if (typeof breakpoint === 'object') {
      this.between(breakpoint, one, func);
    } else {
      if ( typeof breakpoint !== undefined && typeof func !== undefined ) {
        this.outFunctionsExist = true;
        if (this.functions.out[breakpoint]) {
          this.functions.out[breakpoint].push(func);
        } else {
          this.functions.out[breakpoint] = [func];
        }
      }
    }

  }

  resize() {

    let found = this.points.some((breakpoint, index) => {
      if (breakpoint <= this._width()) {
        this.current = this.names[index - 1];
        return true;
      }
    });

    if (!found && this.current === false) {
      this.current = this.names[this.names.length - 1]
    } else if (this.current === undefined) {
      this.current = 'beyond';
    }

    if (this.inFunctionsExist) {
      this._actions(this.functions.in, 'in')
    }

    if (this.outFunctionsExist) {
      this._actions(this.functions.out, 'out')
    }


  }

  //////////////////////////////////////////////////////////////////////////////
  // Private
  //////////////////////////////////////////////////////////////////////////////

  _actions (funcs, bool) {
    let filteredFunctions = Object.keys(funcs).filter(value => {
      if (bool == 'in') {
        return (parseInt(value, 10) > this._width()) && funcs[value].length
      } else {
        return (parseInt(value, 10) <= this._width()) && funcs[value].length
      }
    })
    filteredFunctions.forEach(value => {
      funcs[value].forEach((func) => {
        func()
      });
    })
  }

  _sort(obj, reversed = true) {
    let sortable = [];
    let object = {}

    for (var key in obj) {
      if (obj.hasOwnProperty(key)) {
        sortable.push([key, obj[key]]);
      }
    }

    sortable.sort((a, b) => (reversed ? -1 : 1) * (a[1] - b[1]));

    sortable.map((value, i) => {
      object[value[0]] =  value[1];
    })

    return object;
  }

  _width () {
    return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  }

}

Breakpoints.defaults = {
  breaks : {
    "max"          : 1200,
    "large"        : 970,
    "medium"       : 800,
    "small-medium" : 640,
    "small"        : 480,
    "min"          : 320,
  },
  once          : true,
  mobileFirst   : false,
  triggerOnLoad : true
};

////////////////////////////////////////////////////////////////////////////////
// Dom Listener
// @see https://stackoverflow.com/a/37403125/843131
////////////////////////////////////////////////////////////////////////////////
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
        dom.functions.forEach((func) => {
          func()
        });
      }
    }
  },
  get status() {
    return loaded;
  }
}

////////////////////////////////////////////////////////////////////////////////
// Debounce
////////////////////////////////////////////////////////////////////////////////

const debounce = (fn, time = 10) => {
  let timeout;

  return function() {
    const functionCall = () => fn.apply(this, arguments);

    clearTimeout(timeout);
    timeout = setTimeout(functionCall, time);
  }
}

////////////////////////////////////////////////////////////////////////////////
// Dimensions
////////////////////////////////////////////////////////////////////////////////

const windowWidth = () => window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

const windowHeight = () => window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

const documentWidth = (body = document.body, html = document.documentElement) => {
  return Math.max( body.scrollWidth, body.offsetWidth, html.clientWidth, html.scrollWidth, html.offsetWidth );
}

const documentHeight = (body = document.body, html = document.documentElement) => {
  return Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
}

////////////////////////////////////////////////////////////////////////////////
// Scrollbar
////////////////////////////////////////////////////////////////////////////////

const scrollbarWidth = () => window.innerWidth - document.documentElement.clientWidth;

const scrollbarPosition = () => Math.round(((window.scrollY / (documentHeight() - document.body.clientHeight)) * 100) * 100) / 100;

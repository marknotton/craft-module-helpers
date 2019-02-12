/** Convert plugin class into a jQuery plugin
// @see https://github.com/marknotton/jquery-pluginify
**/

function pluginify(name, classname, iterate = true, shorthand = false, ignoreWarning = false) {

  if ( typeof name == 'undefined' || typeof classname == 'undefined' ) {
    console.warn('Pluginify requires a name and class reference');
    return false;
  }

  if (!window.jQuery && !ignoreWarning) {

    console.error(`Pluginify tried to turn "${name}" into a jQuery plugin, but jQuery was not found.`)

  } else {

    let dataName = `__${name}`;
    let old = $.fn[name];
    let warned = false;

    $.fn[name] = function (...option) {

      if (iterate) {

        return this.each((index, element) => {

          let $this = $(element);
          let data = $this.data(dataName);
          let options = $.extend({}, classname.defaults, $this.data(), typeof option === 'object' && option);

          if (!data) {
            $this.data(dataName, (data = new classname(this, options)));
          } else {
            if (!warned) {
              console.warn($this[0],  " These items have already been set to iterate already");
            }
            warned = true;
          }

          if (typeof option === 'string') {
            data[option]();
          }

        });

      } else {

        let $this = $(this);
        let data = $this.data(dataName);
        let options = $.extend({}, classname.defaults, $this.data(), typeof option === 'object' && option);

        if (!data) {
          $this.data(dataName, (data = new classname(this, options)));
        } else {
          if (!warned) {
            console.warn($this[0],  " These items have already been set to iterate already");
          }
          warned = true;
        }

        if (typeof option === 'string') {
          data[option]();
        }

        return data;

      }

    };

    // Generate a shorthand as $.pluginName
    if (shorthand) {
      $[name] = (options) => $({})[name](options);
    }

    // - No conflict
    $.fn[name].noConflict = () => $.fn[name] = old;

  }
}

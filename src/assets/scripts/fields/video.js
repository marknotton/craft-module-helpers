;(function ( $, window, document, undefined ) {

  var pluginName = "video";
  var defaults = {};

  // Plugin constructor
  function Plugin( element, options ) {
    this.element = element;
    this.options = $.extend( {}, defaults, options) ;

    this._defaults = defaults;
    this._name = pluginName;

    this.init();
  }

  Plugin.prototype = {

    init: function(id) {
      var $this = this;

      $(function () {


        //////////////
        //////////////
        //////////////
        //////////////
        //////////////

        var element = $($this.element);
        var namespace = $this.options.namespace;
        var field = element.find('input[name="fields['+$this.options.name+']"]');


        //
        // var notice = $('#{{namespace}}-notice p');
        //
        // var regexes = {
        //   {% for key, options in $this.options.expressions.formats %}
        //     {{ key }}:{{options.regex|raw}},
        //   {% endfor %}
        // };

        console.log($this.options);
        //
        // {% set formats = settings.formats|keys %}
        //
        // var enabled = {
        //   {% for format in formats %}
        //     {% if format != 'url' %}
        //       {{format}} : {{ settings[format] ? 1 : 0}},
        //     {% endif %}
        //   {% endfor %}
        // };
        //
        // field.data('old', field.val());
        //
        field.bind('propertychange change click keyup input cut blur paste', validate);

        function validate() {
          console.log(field.val());
          // if (field.data('old') != field.val()) {
          //
          //   field.data('old', field.val());
          //
          //   var url = field.val();
          //
          //   if ( {{urlRegex}}.test(url) ) {
          //
          //     {% for format in formats %}
          //        {{ not loop.first ? 'else ' }}if ( checker(url, '{{format}}') ) {
          //         notice.text('{{format|title}} URL detected' + (!enabled['{{format}}'] ? ' and {{format|title}} videos have been disabled for this field' : ''));
          //       }
          //     {% endfor %}
          //
          //     else {
          //       notice.text('URL not valid');
          //     }
          //
          //   } else {
          //     notice.text('URL not valid');
          //   }
          // }
        }

        function checker(url, format) {
          var matches = regexes[format].exec(url);
          return matches && matches[1];
        }

        //////////////
        //////////////
        //////////////
        //////////////


      });
    }
  };

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn[pluginName] = function ( options ) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName,
        new Plugin( this, options ));
      }
    });
  };

})( jQuery, window, document );

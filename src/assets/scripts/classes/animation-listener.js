(function($) {

  function aniListener($this, $event, $callback, $animation, $delay, $type, $count) {
    // console.log($type + ' has finished.');
    if ( $delay && $delay !== 0 ) {
      // Delayed callback
      // console.log('There is a ' + $delay/1000 + ' seconds delay before the callback is called.');
      if ( $animation === false || $animation.indexOf($type) > -1)  {
        var timer = setTimeout(function() {
          clearTimeout(timer);
          // console.log('callback has been called.');
          return $callback.apply($this, [$type, $count, $event]);
        }, $delay);
      }
    } else {
      // Immediate Callback
      // console.log('callback has been called.');
      if ( $animation === false || $animation.indexOf($type) > -1)  {
        return $callback.apply($this, [$type, $count, $event]);
      }
    }
  }

  function setListener($this, $arguments, $listener, _checkerTransition) {
    var args       = Array.prototype.slice.call($arguments),
        options    = args.length > 1 ? args.slice(0,-1) : args,
        callback   = args.pop(),
        animation  = false,
        type       = null,
        delay      = false,
        count      = 0,
        onOrOne    = 'on';

    // OPTIONS
    if ( options.length ) {
      for (var i in options) {
        var option = options[i];
        if ( typeof(option) === 'string' ) {
          if (option == 'on' || option == 'one') {
            onOrOne = option;
          } else {
            animation = option.split(' ');
          }
        } else if ( typeof(option) === 'array' ) {
          animation = option;
        } else if ( typeof(option) === 'number' ) {
          delay = option;
        }
      }
    }

    // ON
    if (onOrOne == 'on') {
      $this.on($listener, function(event) {
        type = _checkerTransition === true ? event.originalEvent.animationName : event.originalEvent.propertyName;
        count ++;
        return aniListener($this, event, callback, animation, delay, type, count);
      });
    } else {
    // ONE
      $this.one($listener, function(event) {
        type = _checkerTransition === true ? event.originalEvent.animationName : event.originalEvent.propertyName;
        count ++;
        return aniListener($this, event, callback, animation, delay, type, count);
      });
    }
  };

  $.fn.animationend = function() {
    setListener(this, arguments, 'webkitAnimationEnd oanimationend msAnimationEnd animationend', true);
    return this;
  }

  $.fn.animationstart = function() {
    setListener(this, arguments, 'webkitAnimationStart oanimationStart msAnimationStart animationstart', true);
    return this;
  }

  $.fn.animationiteration = function() {
    setListener(this, arguments, 'webkitAnimationIteration oanimationIteration msAnimationIteration animationiteration', true);
    return this;
  }

  $.fn.transitionend = function() {
    setListener(this, arguments, 'webkitTransitionEnd oTransitionEnd msTransitionEnd transitionend', false);
    return this;
  }

}( jQuery ));

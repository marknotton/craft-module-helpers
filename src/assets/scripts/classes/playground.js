
animation.add($('promise-container'), {
  last       : ['button:last', 'opacity'],
  first      : ['button:first', 'opacity'],
  onStart    : ('button:first', 'opacity') => { },
  onComplete : ('button:last', 'opacity') => { },
  inView     : (element) => { },
  outView    : (element) => { },
  breakpoint : 'large'
})


$('.element').animation.add(..., (element) => {

})



// media query event handler
if (matchMedia) {
const mq = window.matchMedia("(min-width: 500px)");
mq.addListener(WidthChange);
WidthChange(mq);
}

// media query change
function WidthChange(mq) {
if (mq.matches) {
console.log('window width is at least 500px')
} else {
console.log('window width is less than 500px')
}

}


class Animation  {
  constructor () {

    this.actions = {};

  }

  inview (node) {
    var rect = node.getBoundingClientRect();
    return rect.height > 0 && rect.bottom >= 0 && rect.top <= (window.innerHeight || document.documentElement.clientHeight);
  }

  add () {

    let breakpoint, one, callback, element;

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
            callback = value;
          break;
        }
      })
    }

  }

}

const animation = new Animation();

animation.add($('body'), (element) => {
  console.log(element);
})

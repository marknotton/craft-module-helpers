////////////////////////////////////////////////////////////////////////////////
// Accordion
////////////////////////////////////////////////////////////////////////////////

class Accordion {

  constructor(container, args) {

    this.container = container;

    let settings = {
      'max-height' : 500,
      'min-height' : 500
    }

    if (container.length) {
      container.find('> *:first-child').on('click', (element) => {
        this.click($(element.currentTarget))
      });
    }

    container.filter('.open').each((index, element) => {
      $(element).removeClass('open').find('> *:first-child').trigger("click");
    })

  }

  click($this) {

    var theHeight = $this.next().find('> *:first-child').innerHeight();

    if($this.parent().hasClass("open")) {
      this.close($this, theHeight)
    } else {
      this.open($this, theHeight)
    }
  }

  close($this, theHeight) {
    $this = $this.next();
    $this.height(theHeight).height(0).transitionend('height', () => {
      $this.css('height','');
    }).parent().removeClass("open");
  }

  open($this, theHeight) {
    $this = $this.next();
    $this.height(theHeight).transitionend('height', () => {
       $this.css({'height':'auto'});
    }).parent().addClass("open");
  }

  openAll() {

  }

  closeAll() {

  }

}

if ( typeof pluginify !== 'undefined') {
  pluginify('accordion', Accordion, false);
}

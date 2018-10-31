class Modal {

  constructor (element, args) {
    this.container = element;
  }

  open (content, callback) {

    this.container.append(content);

    $.merge($('modal-background'), $('modal-wrapper .close')).on('click', this.close);

    // $.merge(html, body).stop().animate({ scrollTop: 0 }, 300).promise().then(() => {
      // Do something after the page has scrolled to the top
    // });

    body.animate().addClass('modal locked');

    if ( callback !== 'undefined' ) {
      callback();
    }

  }

  close () {

    body.removeClass('modal locked');

    setTimeout(() => {

      $('modal-wrapper').remove();

    }, 600);

  }

}

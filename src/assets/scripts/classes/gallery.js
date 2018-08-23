class Gallery {
  constructor () {
    this.galleryimage = 'gallery-image';
    this.showclass    = 'show';
  }

  init() {
    let $this = this;
    this.gallery = $('gallery-container');
    let next = this.gallery.find('.next');
    let prev = this.gallery.find('.prev');
    
    next.off().on('click', event => this.next() );
    prev.off().on('click', event => this.prev() );

    if (mobile || tablet) {
      var mc = new Hammer(this.gallery[0]);

      mc.on("swipeleft swiperight", function(event) {
        switch(event.type) {
          case 'swipeleft':
            $this.next();
          break;
          case 'swiperight':
            $this.prev();
          break;
        }
      });
      this.gallery.addClass('swipe-ani');
      setTimeout(() => {
        this.gallery.removeClass('swipe-ani');
      }, 3000);
    }

  }

  next(){
    let $current = $('.'+this.showclass);
    let $next = $current.next(this.galleryimage);

    if($next.length){
      $current.removeClass(this.showclass);
      $next.addClass(this.showclass);
    } else {
      $current.removeClass(this.showclass);
      $(this.galleryimage).first().addClass(this.showclass);
    }
  }

  prev(){
    let $current = $('.'+this.showclass);
    let $prev = $current.prev(this.galleryimage);

    if($prev.length){
      $current.removeClass(this.showclass);
      $prev.addClass(this.showclass);
    } else {
      $current.removeClass(this.showclass);
      $(this.galleryimage).last().addClass(this.showclass);
    }
  }


}

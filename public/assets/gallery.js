import PhotoSwipeLightbox from '/vendor/photoswipe/photoswipe-lightbox.esm.min.js';

const lightbox = new PhotoSwipeLightbox({
  gallery: '#gallery',
  children: 'a',
  pswpModule: () => import('/vendor/photoswipe/photoswipe.esm.min.js'),
  bgOpacity: 1,
  returnFocus: false,
  closeOnVerticalDrag: true,
  clickToCloseNonZoomable: true
});

lightbox.init();
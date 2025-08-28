(function(){
  function byClass(el, cls){ return el.getElementsByClassName(cls); }
  function initCarousel(wrapper){
    var track = byClass(wrapper, 'nlp-carousel')[0];
    if(!track) return;
    var prev = byClass(wrapper, 'nlp-nav--prev')[0];
    var next = byClass(wrapper, 'nlp-nav--next')[0];
    function scrollBy(delta){
      track.scrollLeft += delta;
    }
    function cardWidth(){
      var item = track.querySelector('.nlp-item');
      return item ? (item.getBoundingClientRect().width + 16) : 320;
    }
    if(prev){ prev.addEventListener('click', function(){ scrollBy(-cardWidth()); }); }
    if(next){ next.addEventListener('click', function(){ scrollBy(cardWidth()); }); }
    // Keyboard support
    track.addEventListener('keydown', function(e){
      if(e.key === 'ArrowLeft'){ e.preventDefault(); scrollBy(-cardWidth()); }
      if(e.key === 'ArrowRight'){ e.preventDefault(); scrollBy(cardWidth()); }
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    var wrappers = document.getElementsByClassName('nlp-wrapper');
    for(var i=0;i<wrappers.length;i++){
      if(wrappers[i].getAttribute('data-layout') === 'carousel'){
        initCarousel(wrappers[i]);
      }
    }
  });
})();

(function () {
  function byClass(el, cls) {
    return el.getElementsByClassName(cls);
  }
  function getCount(wrapper) {
    var c = parseInt(wrapper.getAttribute("data-count"), 10);
    return isNaN(c) || c < 1 ? 1 : c;
  }
  function getGapPx(track) {
    var cs = window.getComputedStyle(track);
    var g = parseFloat(cs.columnGap || cs.gap || "16");
    return isNaN(g) ? 16 : g;
  }
  function animateScroll(track, targetLeft) {
    try {
      track.scrollTo({ left: targetLeft, behavior: "smooth" });
      return;
    } catch (e) {}
    var start = track.scrollLeft,
      delta = targetLeft - start,
      startTime = null,
      duration = 300;
    function step(ts) {
      if (startTime === null) startTime = ts;
      var t = (ts - startTime) / duration;
      if (t > 1) t = 1;
      track.scrollLeft = start + delta * t;
      if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  function initCarousel(wrapper) {
    var track = byClass(wrapper, "nlp-carousel")[0];
    if (!track) return;
    var prev = byClass(wrapper, "nlp-nav--prev")[0];
    var next = byClass(wrapper, "nlp-nav--next")[0];
    var count = getCount(wrapper);
    function cardStep() {
      var item = track.querySelector(".nlp-item");
      if (!item) return 320 * count;
      var rect = item.getBoundingClientRect();
      var gap = getGapPx(track);
      return (rect.width + gap) * count;
    }
    function clamp(to) {
      if (to < 0) return 0;
      var max = track.scrollWidth - track.clientWidth;
      return to > max ? max : to;
    }
    function scrollByStep(dir) {
      var delta = cardStep() * dir;
      animateScroll(track, clamp(track.scrollLeft + delta));
    }
    function handleActivate(dir) {
      return function (e) {
        if (e.type === "click") {
          scrollByStep(dir);
        } else if (e.type === "keydown") {
          if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
            e.preventDefault();
            scrollByStep(dir);
          }
        }
      };
    }
    if (prev) {
      prev.addEventListener("click", handleActivate(-1));
      prev.addEventListener("keydown", handleActivate(-1));
    }
    if (next) {
      next.addEventListener("click", handleActivate(1));
      next.addEventListener("keydown", handleActivate(1));
    }
    track.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        scrollByStep(-1);
      }
      if (e.key === "ArrowRight") {
        e.preventDefault();
        scrollByStep(1);
      }
    });
  }
  document.addEventListener("DOMContentLoaded", function () {
    var wrappers = document.getElementsByClassName("nlp-wrapper");
    for (var i = 0; i < wrappers.length; i++) {
      if (wrappers[i].getAttribute("data-layout") === "carousel") {
        initCarousel(wrappers[i]);
      }
    }
  });
})();

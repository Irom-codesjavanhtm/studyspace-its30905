/* Falling-book loader. Books loop, then the overlay eases out. */
(function () {
  'use strict';

  class StudySpaceLoader {
    constructor(elementId) {
      this.loader = document.getElementById(elementId || 'studyspace-loader');
    }

    show() {
      if (!this.loader) {
        return;
      }
      this.loader.classList.remove('is-hidden');
    }

    hide() {
      if (!this.loader) {
        return;
      }
      this.loader.classList.add('is-hidden');
    }
  }

  var MIN_VISIBLE_MS = 1500;

  document.addEventListener('DOMContentLoaded', function () {
    var loader = new StudySpaceLoader('studyspace-loader');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var shownAt = Date.now();

    loader.show();

    var scheduleHide = function () {
      var elapsed = Date.now() - shownAt;
      var wait = Math.max(0, MIN_VISIBLE_MS - elapsed);
      setTimeout(function () {
        loader.hide();
      }, wait);
    };

    if (reduce) {
      scheduleHide();
      return;
    }

    if (document.readyState === 'complete') {
      scheduleHide();
    } else {
      window.addEventListener('load', scheduleHide, { once: true });
    }
  });
}());

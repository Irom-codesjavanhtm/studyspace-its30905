/* Landing page: sections slide in as they enter the viewport. */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var nodes = document.querySelectorAll('.reveal');
    if (!nodes.length) { return; }

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
      Array.prototype.forEach.call(nodes, function (node) { node.classList.add('is-in'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        entry.target.classList.add('is-in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.16, rootMargin: '0px 0px -6% 0px' });

    Array.prototype.forEach.call(nodes, function (node) { observer.observe(node); });
  });
}());

/* Admin panel: smooth add-room fold tab. */
(function () {
  'use strict';

  function setOpen(panel, open) {
    var trigger = panel.querySelector('.add-panel-trigger');
    var body = panel.querySelector('.add-panel-body');
    var label = panel.querySelector('.add-panel-label');
    if (!trigger || !body) { return; }

    panel.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    body.setAttribute('aria-hidden', open ? 'false' : 'true');

    if ('inert' in body) {
      body.inert = !open;
    }

    if (label) {
      var closed = label.getAttribute('data-label-closed') || 'Add room';
      var opened = label.getAttribute('data-label-open') || 'Hide form';
      label.textContent = open ? opened : closed;
    }

    if (open) {
      var focusField = body.querySelector('input, select, textarea');
      if (focusField && panel.hasAttribute('data-user-toggled')) {
        window.setTimeout(function () { focusField.focus(); }, 280);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-add-panel]'), function (panel) {
      var trigger = panel.querySelector('.add-panel-trigger');
      if (!trigger) { return; }

      var startOpen = panel.hasAttribute('data-start-open') || panel.classList.contains('is-open');
      setOpen(panel, startOpen);

      trigger.addEventListener('click', function () {
        panel.setAttribute('data-user-toggled', '1');
        setOpen(panel, !panel.classList.contains('is-open'));
      });
    });
  });
}());

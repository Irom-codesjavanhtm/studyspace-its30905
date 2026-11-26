/* StudySpace
Logged in user shared behaviour: navbar toggle, toasts, confirm dialog, form validation. 
*/
(function () {
  'use strict';

  document.documentElement.classList.add('js');
  var SS = (window.StudySpace = window.StudySpace || {});

  /* ---------- Time helpers ---------- */
  SS.toMinutes = function (hhmm) {
    var parts = String(hhmm).split(':');
    return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
  };
  SS.fromMinutes = function (minutes) {
    var h = Math.floor(minutes / 60);
    var m = minutes % 60;
    return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
  };
  SS.hoursLabel = function (minutes) {
    var h = minutes / 60;
    return (Math.round(h * 10) / 10) + ' h';
  };

  /* ---------- Toasts ---------- */
  SS.toast = function (message, type, timeout) {
    var region = document.getElementById('toast-region');
    if (!region) { return; }
    type = type || 'info';
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    var mark = document.createElement('span');
    mark.className = 'toast-mark';
    var text = document.createElement('p');
    text.className = 'toast-text';
    text.textContent = message;
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '&times;';

    toast.appendChild(mark);
    toast.appendChild(text);
    toast.appendChild(close);

    var life = timeout || (type === 'error' ? 6500 : 4500);
    toast.style.setProperty('--toast-life', life + 'ms');
    region.insertBefore(toast, region.firstChild);
    while (region.children.length > 4) { region.removeChild(region.lastChild); }

    var gone = false;
    var dismiss = function () {
      if (gone) { return; }
      gone = true;
      toast.classList.add('is-leaving');
      setTimeout(function () { if (toast.parentNode) { toast.parentNode.removeChild(toast); } }, 280);
    };
    close.addEventListener('click', dismiss);
    setTimeout(dismiss, life);
  };

  /* ---------- Confirm dialog ---------- */
  SS.confirm = function (opts) {
    var dialog = document.getElementById('confirm-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') {
      return Promise.resolve(window.confirm(opts.message));
    }
    document.getElementById('confirm-title').textContent = opts.title || 'Are you sure?';
    document.getElementById('confirm-message').textContent = opts.message || '';
    document.getElementById('confirm-ok').textContent = opts.okLabel || 'Confirm';
    document.getElementById('confirm-cancel').textContent = opts.cancelLabel || 'Cancel';
    dialog.classList.toggle('is-danger', !!opts.danger);
    dialog.returnValue = 'cancel';

    return new Promise(function (resolve) {
      var onClose = function () {
        dialog.removeEventListener('close', onClose);
        resolve(dialog.returnValue === 'ok');
      };
      dialog.addEventListener('close', onClose);
      dialog.showModal();
      document.getElementById('confirm-cancel').focus();
    });
  };

  /* ---------- Form validation ---------- */
  function errorBox(field) {
    var id = field.name + '-error';
    var box = document.getElementById(id);
    if (!box) {
      box = document.createElement('p');
      box.id = id;
      box.className = 'field-error';
      box.setAttribute('role', 'alert');
      field.parentNode.appendChild(box);
    }
    return box;
  }

  function replay(el, className) {
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
  }

  /* Postel's law: accept spaces, dashes and odd case, then keep one clean value. */
  function liberalize(field) {
    if (!field || field.readOnly || field.disabled || field.type === 'password' || field.type === 'hidden') { return; }
    var value = field.value;
    if (typeof value !== 'string' || value === '') { return; }
    if (field.type === 'email') {
      field.value = value.trim().replace(/\s+/g, '').toLowerCase();
      return;
    }
    if (field.name === 'student_id' || field.name === 'username' || field.name === 'q') {
      var compact = value.trim().replace(/[\s\-]/g, '');
      if (/^039\d{4}$/i.test(compact)) { field.value = compact; }
      else if (/^admin$/i.test(compact)) { field.value = 'admin'; }
      else if (field.name === 'student_id') { field.value = compact.toUpperCase(); }
      else { field.value = value.trim(); }
      return;
    }
    if (field.type === 'text' || field.tagName === 'TEXTAREA') {
      field.value = value.trim().replace(/\s+/g, ' ');
    }
  }

  /* Jakob's law: the message sits under the field, the same place people expect it on other sites. */
  SS.setError = function (field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid', 'is-bloom');
    field.setAttribute('aria-invalid', 'true');
    var box = errorBox(field);
    box.textContent = message;
    replay(box, 'is-pop');
    replay(field, 'is-shaking');
    field.setAttribute('aria-describedby', box.id);
  };

  SS.clearError = function (field) {
    field.classList.remove('is-invalid', 'is-shaking');
    field.removeAttribute('aria-invalid');
    var box = document.getElementById(field.name + '-error');
    if (box) {
      box.textContent = '';
      box.classList.remove('is-pop');
    }
  };

  SS.validateField = function (field) {
    if (field.disabled || field.readOnly || field.type === 'hidden' || !field.name) { return true; }
    liberalize(field);
    if (field.checkValidity()) {
      SS.clearError(field);
      if (field.value !== '') {
        field.classList.add('is-valid');
        replay(field, 'is-bloom');
      }
      return true;
    }
    SS.setError(field, field.dataset.msg || field.validationMessage);
    return false;
  };

  SS.validateForm = function (form) {
    var invalid = [];
    Array.prototype.forEach.call(form.elements, function (field) {
      if (field.willValidate !== false && !SS.validateField(field)) { invalid.push(field); }
    });
    return invalid;
  };

  function attachValidation(form) {
    form.addEventListener('focusout', function (event) {
      var field = event.target;
      var next = event.relatedTarget;
      if (next && (next.tagName === 'BUTTON' || next.tagName === 'A')) { return; }
      if (field.name && field.form === form && field.value !== '' && !field.readOnly && field.willValidate && !field.checkValidity()) {
        SS.validateField(field);
      }
    });
    form.addEventListener('input', function (event) {
      var field = event.target;
      if (field.classList && field.classList.contains('is-invalid')) {
        if (field.checkValidity()) { SS.clearError(field); }
      }
      field.classList.remove('is-valid');
    });
    form.addEventListener('submit', function (event) {
      var invalid = SS.validateForm(form);
      if (typeof form.extraValidation === 'function') {
        invalid = invalid.concat(form.extraValidation());
      }
      if (invalid.length) {
        event.preventDefault();
        replay(form, 'is-shaking');
        invalid[0].focus();
        var box = document.getElementById(invalid[0].name + '-error');
        SS.toast((box && box.textContent) || 'Fix the highlighted field, then try again.', 'error');
      }
    });
  }

  function initLookupForm(form) {
    var input = form.querySelector('input[name="q"]');
    form.addEventListener('submit', function (event) {
      liberalize(input);
      var value = input.value.trim();
      var message = '';
      if (value === '') {
        message = 'Enter your student ID or email to find your bookings.';
      } else if (value.indexOf('@') !== -1) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { message = 'That does not look like a valid email address.'; }
      } else if (!/^[A-Za-z0-9]{5,12}$/.test(value)) {
        message = 'Student ID must be 5 to 12 letters or digits, e.g. TU100231.';
      }
      if (message) {
        event.preventDefault();
        SS.setError(input, message);
        SS.toast(message, 'error');
        input.focus();
      }
    });
    input.addEventListener('input', function () { SS.clearError(input); });
  }

  /* ---------- Sidebar (mobile drawer only) + profile menu ---------- */
  function initSidebar() {
    var body = document.body;
    if (!body.classList.contains('app-body')) { return; }

    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    var mqMobile = window.matchMedia('(max-width: 960px)');
    var motionMs = 220;
    var closeTimer = 0;

    try { localStorage.removeItem('studyspace-sidebar-collapsed'); } catch (e) { /* ignore */ }
    body.classList.remove('sidebar-collapsed');

    if (!toggle) { return; }

    var isMobile = function () { return mqMobile.matches; };

    var setToggle = function (open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };

    var hideBackdrop = function () {
      if (backdrop) { backdrop.hidden = true; }
    };

    var showBackdrop = function () {
      if (backdrop) { backdrop.hidden = false; }
    };

    var closeDrawer = function (instant) {
      window.clearTimeout(closeTimer);
      body.classList.remove('sidebar-open');
      setToggle(false);
      if (instant) {
        hideBackdrop();
        return;
      }
      closeTimer = window.setTimeout(hideBackdrop, motionMs);
    };

    var openDrawer = function () {
      window.clearTimeout(closeTimer);
      showBackdrop();
      body.classList.add('sidebar-open');
      setToggle(true);
    };

    var resetDesktop = function () {
      body.classList.remove('sidebar-open');
      hideBackdrop();
      setToggle(false);
    };

    toggle.addEventListener('click', function () {
      if (!isMobile()) { return; }
      if (body.classList.contains('sidebar-open')) {
        closeDrawer(false);
      } else {
        openDrawer();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', function () { closeDrawer(false); });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isMobile()) { closeDrawer(false); }
    });

    var onViewportChange = function () {
      if (isMobile()) {
        closeDrawer(true);
      } else {
        resetDesktop();
      }
    };

    if (typeof mqMobile.addEventListener === 'function') {
      mqMobile.addEventListener('change', onViewportChange);
    } else if (typeof mqMobile.addListener === 'function') {
      mqMobile.addListener(onViewportChange);
    }

    onViewportChange();
  }

  function initProfileMenu() {
    var menu = document.querySelector('[data-profile-menu]');
    if (!menu) { return; }
    var trigger = menu.querySelector('[data-profile-trigger]');
    var dropdown = menu.querySelector('[data-profile-dropdown]');
    if (!trigger || !dropdown) { return; }

    var setOpen = function (open) {
      menu.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
      dropdown.hidden = !open;
    };

    trigger.addEventListener('click', function (event) {
      event.stopPropagation();
      setOpen(dropdown.hidden);
    });
    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) { setOpen(false); }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { setOpen(false); }
    });
  }

  function initPasswordToggles() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-password-toggle]'), function (btn) {
      btn.addEventListener('click', function () {
        var wrap = btn.closest('.password-wrap');
        var input = wrap ? wrap.querySelector('input') : null;
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var eye = btn.querySelector('.password-icon-show');
        var hide = btn.querySelector('.password-icon-hide');
        if (eye) { eye.hidden = show; }
        if (hide) { hide.hidden = !show; }
      });
    });
  }

  function initFlashToasts() {
    var flashes = (window.StudySpace && window.StudySpace.flashes) || [];
    if (!flashes.length) { return; }

    flashes.forEach(function (item) {
      var type = item.type || 'info';
      var message = item.message || '';
      if (!message) { return; }
      SS.toast(message, type, type === 'error' ? 7000 : 4500);
      if (type === 'error') {
        var card = document.querySelector('.login-card');
        if (card) {
          card.classList.add('is-alarm');
          replay(card, 'is-shaking');
        }
      }
    });

    window.StudySpace.flashes = [];
  }

  /** Hide sticky chrome when the footer scrolls into view (after the user has scrolled). */
  function initChromeRecede() {
    var footer = document.querySelector('.site-footer');
    if (!footer) { return; }
    var chrome = document.querySelectorAll('.public-header, .topbar.app-topbar');
    if (!chrome.length) { return; }
    if (document.body.getAttribute('data-page') === 'login') { return; }
    if (document.documentElement.scrollHeight <= window.innerHeight + 8) { return; }

    var setReceded = function (recede) {
      chrome.forEach(function (el) {
        el.classList.toggle('is-receded', recede);
      });
      document.body.classList.toggle('chrome-receded', recede);
    };

    var footerVisible = false;

    var syncRecede = function () {
      var scrolled = window.scrollY || document.documentElement.scrollTop || 0;
      setReceded(footerVisible && scrolled >= 48);
    };

    var observer = new IntersectionObserver(function (entries) {
      footerVisible = entries[0].isIntersecting;
      syncRecede();
    }, { threshold: 0.06 });

    observer.observe(footer);
    window.addEventListener('scroll', syncRecede, { passive: true });
    syncRecede();
  }

  /* ---------- Init ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    initChromeRecede();
    initSidebar();
    initProfileMenu();
    initPasswordToggles();
    initFlashToasts();

    document.addEventListener('click', function (event) {
      var btn = event.target.closest('.btn');
      if (btn && !btn.disabled && btn.getAttribute('aria-disabled') !== 'true') {
        var old = btn.querySelector('.btn-pip');
        if (old) { old.remove(); }
        var pip = document.createElement('span');
        pip.className = 'btn-pip';
        pip.setAttribute('aria-hidden', 'true');
        btn.appendChild(pip);
        setTimeout(function () { if (pip.parentNode) { pip.parentNode.removeChild(pip); } }, 420);
      }
      var close = event.target.closest('.flash-close');
      if (close) {
        var flash = close.closest('.flash');
        flash.classList.add('is-leaving');
        setTimeout(function () { flash.remove(); }, 200);
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (event.defaultPrevented || !form.hasAttribute || !form.hasAttribute('data-confirm')) { return; }
      if (form.dataset.confirmed === '1') { return; }
      event.preventDefault();
      var submitter = event.submitter;
      SS.confirm({
        message: form.dataset.confirm,
        title: form.dataset.confirmTitle,
        okLabel: form.dataset.confirmOk,
        cancelLabel: form.dataset.confirmCancel,
        danger: form.hasAttribute('data-confirm-danger')
      }).then(function (ok) {
        if (!ok) { return; }
        form.dataset.confirmed = '1';
        if (typeof form.requestSubmit === 'function') { form.requestSubmit(submitter || undefined); } else { form.submit(); }
      });
    });

    Array.prototype.forEach.call(document.querySelectorAll('form[data-autosubmit]'), function (form) {
      var date = form.querySelector('input[type="date"]');
      if (date) {
        date.addEventListener('change', function () {
          if (date.value) { form.requestSubmit ? form.requestSubmit() : form.submit(); }
        });
      }
    });

    Array.prototype.forEach.call(document.querySelectorAll('form[data-filter-bar]'), function (form) {
      form.addEventListener('change', function (event) {
        if (!event.target.closest('[data-filter-change]')) { return; }
        if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
        else { form.submit(); }
      });
    });

    Array.prototype.forEach.call(document.querySelectorAll('form[data-auth-form]'), function (form) {
      var password = form.querySelector('input[name="password"]');
      var confirm = form.querySelector('input[name="password_confirm"]');
      if (!password || !confirm) { return; }
      form.extraValidation = function () {
        if (password.value !== confirm.value) {
          SS.setError(confirm, 'Enter the same password again.');
          return [confirm];
        }
        return [];
      };
    });
    Array.prototype.forEach.call(document.querySelectorAll('form[data-validate], form[data-booking-form]'), attachValidation);
    Array.prototype.forEach.call(document.querySelectorAll('form[data-lookup-form]'), initLookupForm);
    Array.prototype.forEach.call(document.querySelectorAll('.table-wrap .data-table'), initTablePagination);
  });

  /* ---------- Table pagination ---------- */
  function initTablePagination(table) {
    var wrap = table.closest('.table-wrap');
    var body = table.tBodies[0];
    if (!wrap || !body) { return; }

    var rows = Array.prototype.slice.call(body.rows);
    var perPage = parseInt(table.getAttribute('data-page-size') || wrap.getAttribute('data-page-size') || '8', 10);
    if (!perPage || perPage < 1) { perPage = 8; }
    if (rows.length <= perPage) { return; }

    var page = 1;
    var totalPages = Math.ceil(rows.length / perPage);
    var nav = document.createElement('nav');
    nav.className = 'table-pager';
    nav.setAttribute('aria-label', 'Table pages');

    var info = document.createElement('p');
    info.className = 'table-pager-info';

    var controls = document.createElement('div');
    controls.className = 'table-pager-controls';

    var prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'btn btn-outline btn-sm';
    prev.textContent = 'Previous';

    var next = document.createElement('button');
    next.type = 'button';
    next.className = 'btn btn-outline btn-sm';
    next.textContent = 'Next';

    controls.appendChild(prev);
    controls.appendChild(next);
    nav.appendChild(info);
    nav.appendChild(controls);
    wrap.appendChild(nav);

    function render() {
      var start = (page - 1) * perPage;
      var end = start + perPage;
      rows.forEach(function (row, index) {
        row.hidden = index < start || index >= end;
      });
      var shownStart = start + 1;
      var shownEnd = Math.min(end, rows.length);
      info.textContent = 'Showing ' + shownStart + '–' + shownEnd + ' of ' + rows.length;
      prev.disabled = page <= 1;
      next.disabled = page >= totalPages;
      prev.setAttribute('aria-disabled', prev.disabled ? 'true' : 'false');
      next.setAttribute('aria-disabled', next.disabled ? 'true' : 'false');
    }

    prev.addEventListener('click', function () {
      if (page <= 1) { return; }
      page -= 1;
      render();
    });
    next.addEventListener('click', function () {
      if (page >= totalPages) { return; }
      page += 1;
      render();
    });

    render();
  }
}());

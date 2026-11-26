/* StudySpace - booking form: cross-field validation, live availability and free-window shortcuts. */
(function () {
  'use strict';

  var SS = window.StudySpace;

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-booking-form]');
    if (!form) { return; }

    var cfg = {
      nowDate: form.dataset.nowDate,
      nowMinutes: parseInt(form.dataset.nowMinutes, 10),
      open: parseInt(form.dataset.open, 10) * 60,
      close: parseInt(form.dataset.close, 10) * 60,
      maxMinutes: parseInt(form.dataset.maxHours, 10) * 60,
      api: form.dataset.api,
      except: form.dataset.except
    };

    var f = {
      room: form.elements.room_id,
      date: form.elements.booking_date,
      start: form.elements.start_time,
      end: form.elements.end_time,
      people: form.elements.attendees
    };
    var box = {
      panel: document.querySelector('[data-availability]'),
      empty: document.querySelector('[data-availability-empty]'),
      body: document.querySelector('[data-availability-body]'),
      title: document.querySelector('[data-availability-title]'),
      sub: document.querySelector('[data-availability-sub]'),
      busy: document.querySelector('[data-busy-list]'),
      windows: document.querySelector('[data-free-windows]'),
      warning: document.querySelector('[data-conflict-warning]')
    };

    var booked = [];
    var loadedKey = '';
    var token = 0;
    var timer = null;

    function roomOption() {
      return f.room.options[f.room.selectedIndex];
    }

    function selectedRange() {
      if (!f.start.value || !f.end.value) { return null; }
      return { start: SS.toMinutes(f.start.value), end: SS.toMinutes(f.end.value) };
    }

    function overlaps(range) {
      return booked.filter(function (b) { return range.start < b.end && range.end > b.start; });
    }

    function renderAvailability(code) {
      box.title.textContent = code;
      box.sub.textContent = f.date.value + ' · open ' + SS.fromMinutes(cfg.open) + ' to ' + SS.fromMinutes(cfg.close);
      box.busy.innerHTML = '';
      if (!booked.length) {
        var none = document.createElement('li');
        none.className = 'is-free';
        none.textContent = 'No bookings: free all day';
        box.busy.appendChild(none);
      }
      booked.forEach(function (b) {
        var li = document.createElement('li');
        var label = document.createElement('span');
        label.textContent = 'Booked';
        var time = document.createElement('span');
        time.textContent = SS.fromMinutes(b.start) + ' – ' + SS.fromMinutes(b.end);
        li.appendChild(label);
        li.appendChild(time);
        box.busy.appendChild(li);
      });

      box.windows.innerHTML = '';
      var cursor = cfg.open;
      if (f.date.value === cfg.nowDate) {
        cursor = Math.max(cursor, Math.ceil(cfg.nowMinutes / 30) * 30);
      }
      var windows = [];
      booked.forEach(function (b) {
        if (b.start - cursor >= 30) { windows.push({ start: cursor, end: b.start }); }
        cursor = Math.max(cursor, b.end);
      });
      if (cfg.close - cursor >= 30) { windows.push({ start: cursor, end: cfg.close }); }

      if (!windows.length) {
        var full = document.createElement('p');
        full.className = 'muted';
        full.textContent = 'No free windows left on this day.';
        box.windows.appendChild(full);
      }
      windows.forEach(function (w) {
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'pill';
        chip.textContent = SS.fromMinutes(w.start) + ' – ' + SS.fromMinutes(w.end);
        chip.addEventListener('click', function () {
          var end = Math.min(w.end, w.start + Math.min(cfg.maxMinutes, 60));
          f.start.value = SS.fromMinutes(w.start);
          f.end.value = SS.fromMinutes(end);
          SS.clearError(f.start);
          SS.clearError(f.end);
          checkConflict();
          SS.toast('Time set to ' + f.start.value + ' – ' + f.end.value + '. Adjust the end time if you need longer.', 'info');
        });
        box.windows.appendChild(chip);
      });
    }

    function checkConflict() {
      var range = selectedRange();
      if (!loadedKey || !range || range.end <= range.start) {
        box.warning.hidden = true;
        return [];
      }
      var clashes = overlaps(range);
      var code = roomOption().dataset.code || 'This room';
      if (clashes.length) {
        box.warning.className = 'availability-warning';
        box.warning.textContent = code + ' is already booked ' + clashes.map(function (c) {
          return SS.fromMinutes(c.start) + '–' + SS.fromMinutes(c.end);
        }).join(', ') + '. Pick another time or room.';
      } else {
        box.warning.className = 'availability-warning is-ok';
        box.warning.textContent = code + ' is free from ' + f.start.value + ' to ' + f.end.value + '.';
      }
      box.warning.hidden = false;
      return clashes;
    }

    function loadAvailability() {
      if (!f.room.value || !f.date.value) {
        box.empty.hidden = false;
        box.body.hidden = true;
        box.warning.hidden = true;
        loadedKey = '';
        return;
      }
      var key = f.room.value + '|' + f.date.value;
      if (key === loadedKey) { checkConflict(); return; }
      var mine = ++token;
      var url = cfg.api + '?room_id=' + encodeURIComponent(f.room.value) + '&date=' + encodeURIComponent(f.date.value);
      if (cfg.except) { url += '&except=' + encodeURIComponent(cfg.except); }

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) { throw new Error('bad'); } return r.json(); })
        .then(function (data) {
          if (mine !== token) { return; }
          booked = data.booked.map(function (b) { return { start: SS.toMinutes(b.start), end: SS.toMinutes(b.end) }; });
          cfg.nowMinutes = data.now.minutes;
          cfg.nowDate = data.now.date;
          loadedKey = key;
          box.empty.hidden = true;
          box.body.hidden = false;
          renderAvailability(roomOption().dataset.code);
          checkConflict();
        })
        .catch(function () {
          if (mine !== token) { return; }
          SS.toast('Could not check room availability. The server will still check when you submit.', 'error');
        });
    }

    function scheduleLoad() {
      clearTimeout(timer);
      timer = setTimeout(loadAvailability, 120);
    }

    var endCatalog = [];
    Array.prototype.forEach.call(f.end.querySelectorAll('option'), function (option) {
      if (!option.value) { return; }
      endCatalog.push({
        value: option.value,
        label: option.textContent,
        group: option.parentElement && option.parentElement.tagName === 'OPTGROUP' ? option.parentElement.label : ''
      });
    });

    function refreshStarts() {
      var today = f.date.value === cfg.nowDate;
      Array.prototype.forEach.call(f.start.options, function (option) {
        if (!option.value) { return; }
        var minutes = SS.toMinutes(option.value);
        option.disabled = today && minutes < cfg.nowMinutes;
      });
      if (f.start.selectedOptions[0] && f.start.selectedOptions[0].disabled) {
        f.start.value = '';
      }
    }

    function rebuildEnds() {
      var start = f.start.value ? SS.toMinutes(f.start.value) : 0;
      var selected = f.end.value;
      var groups = [];
      var current = null;
      f.end.innerHTML = '';
      var blank = document.createElement('option');
      blank.value = '';
      blank.textContent = 'End';
      f.end.appendChild(blank);
      endCatalog.forEach(function (item) {
        var minutes = SS.toMinutes(item.value);
        if (start && (minutes <= start || minutes - start > cfg.maxMinutes || minutes > cfg.close)) { return; }
        if (!current || current.label !== item.group) {
          current = document.createElement('optgroup');
          current.label = item.group;
          groups.push(current);
          f.end.appendChild(current);
        }
        var option = document.createElement('option');
        option.value = item.value;
        option.textContent = item.label;
        current.appendChild(option);
      });
      var stillThere = Array.prototype.some.call(f.end.options, function (option) { return option.value === selected; });
      f.end.value = stillThere ? selected : '';
    }

    function pushEndAfterStart() {
      refreshStarts();
      rebuildEnds();
      if (!f.start.value) { return; }
      var start = SS.toMinutes(f.start.value);
      var end = f.end.value ? SS.toMinutes(f.end.value) : 0;
      if (end <= start) {
        var next = Math.min(start + 60, cfg.close);
        var nextValue = SS.fromMinutes(next);
        if (Array.prototype.some.call(f.end.options, function (option) { return option.value === nextValue; })) {
          f.end.value = nextValue;
          SS.clearError(f.end);
        }
      }
    }

    f.room.addEventListener('change', function () {
      var option = roomOption();
      var capacity = parseInt(option.dataset.capacity || '60', 10);
      f.people.max = String(capacity);
      f.people.dataset.msg = option.value ? option.dataset.code + ' seats ' + capacity + ' people at most.' : 'Enter how many people will use the room.';
      scheduleLoad();
    });
    f.date.addEventListener('change', function () { refreshStarts(); pushEndAfterStart(); scheduleLoad(); });
    f.start.addEventListener('change', function () { pushEndAfterStart(); checkConflict(); });
    f.end.addEventListener('change', checkConflict);

    form.extraValidation = function () {
      var bad = [];
      var range = selectedRange();
      if (range) {
        var nowStart = f.date.value === cfg.nowDate && range.start < cfg.nowMinutes;
        if (range.end <= range.start) {
          SS.setError(f.end, 'End time must be after the start time.');
          bad.push(f.end);
        } else if (range.end - range.start > cfg.maxMinutes) {
          SS.setError(f.end, 'A single booking can last at most ' + (cfg.maxMinutes / 60) + ' hours.');
          bad.push(f.end);
        } else if (range.start < cfg.open || range.end > cfg.close) {
          SS.setError(f.start, 'Rooms are open ' + SS.fromMinutes(cfg.open) + ' to ' + SS.fromMinutes(cfg.close) + '.');
          bad.push(f.start);
        } else if (nowStart) {
          SS.setError(f.start, 'That start time has already passed.');
          bad.push(f.start);
        }
      }
      var capacity = parseInt((roomOption() || {}).dataset ? roomOption().dataset.capacity : '', 10);
      if (capacity && parseInt(f.people.value, 10) > capacity) {
        SS.setError(f.people, roomOption().dataset.code + ' seats ' + capacity + ' people at most.');
        bad.push(f.people);
      }
      return bad.filter(function (field, i) { return bad.indexOf(field) === i; });
    };

    form.addEventListener('submit', function (event) {
      if (event.defaultPrevented) { return; }
      var clashes = checkConflict();
      if (clashes.length) {
        SS.toast('Heads up: that slot overlaps an existing booking. The server will reject it.', 'error', 6000);
      } else {
        SS.toast('Checking availability and saving your booking…', 'info', 2500);
      }
    });

    pushEndAfterStart();
    if (f.room.value) { f.room.dispatchEvent(new Event('change')); }
    loadAvailability();
  });
}());

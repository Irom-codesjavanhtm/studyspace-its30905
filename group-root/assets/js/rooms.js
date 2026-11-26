/* StudySpace - floor plan: floor switch, room selection, slot picker, activity filter. */
(function () {
  'use strict';

  var SS = window.StudySpace;

  document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('selected-panel');
    if (!panel) { return; }

    var cfg = {
      date: panel.dataset.date,
      today: panel.dataset.today,
      nowMinutes: parseInt(panel.dataset.nowMinutes, 10),
      open: parseInt(panel.dataset.open, 10),
      close: parseInt(panel.dataset.close, 10),
      maxMinutes: parseInt(panel.dataset.maxHours, 10) * 60,
      api: panel.dataset.api,
      bookUrl: panel.dataset.bookUrl
    };

    var cards = Array.prototype.slice.call(document.querySelectorAll('.room-card'));
    var els = {
      empty: panel.querySelector('[data-empty]'),
      body: panel.querySelector('[data-body]'),
      code: panel.querySelector('[data-room-code]'),
      meta: panel.querySelector('[data-room-meta]'),
      tags: panel.querySelector('[data-room-tags]'),
      desc: panel.querySelector('[data-room-desc]'),
      from: panel.querySelector('[data-from]'),
      to: panel.querySelector('[data-to]'),
      summary: panel.querySelector('[data-summary]'),
      hint: panel.querySelector('[data-slot-hint]'),
      reserve: panel.querySelector('[data-reserve]'),
      clear: panel.querySelector('[data-clear]'),
      selectionLabel: panel.querySelector('[data-selection-label]'),
      deskBody: panel.querySelector('[data-desk-body]'),
      deskCode: panel.querySelector('[data-desk-code]'),
      deskMeta: panel.querySelector('[data-desk-meta]'),
      deskNote: panel.querySelector('[data-desk-note]'),
      clearDesk: panel.querySelector('[data-clear-desk]'),
      roomField: document.querySelector('[data-room-field]'),
      activity: document.querySelector('[data-activity]')
    };

    var current = null;
    var currentDesk = null;
    var booked = [];
    var selection = null;
    var defaultHint = els.hint ? els.hint.textContent : '';
    var pickItem = null;
    var requestToken = 0;
    var movedOffClosedDay = false;

    function nextDay(iso) {
      var parts = iso.split('-');
      var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      d.setDate(d.getDate() + 1);
      var month = String(d.getMonth() + 1);
      var day = String(d.getDate());
      if (month.length < 2) { month = '0' + month; }
      if (day.length < 2) { day = '0' + day; }
      return d.getFullYear() + '-' + month + '-' + day;
    }

    function dayHasPassed() {
      if (cfg.date !== cfg.today) { return false; }
      var close = cfg.close * 60;
      for (var m = cfg.open * 60; m < close; m += 30) {
        if (!isPast(m)) { return false; }
      }
      return true;
    }

    /* ----- Floors ----- */
    var floorButtons = Array.prototype.slice.call(document.querySelectorAll('[data-floor-btn]'));
    var floorPanels = Array.prototype.slice.call(document.querySelectorAll('.floor-panel'));

    function showFloor(floor) {
      floorPanels.forEach(function (p) { p.classList.toggle('is-active', p.dataset.floor === floor); });
      floorButtons.forEach(function (b) {
        var on = b.dataset.floorBtn === floor;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }
    floorButtons.forEach(function (b) {
      b.addEventListener('click', function () { showFloor(b.dataset.floorBtn); });
    });

    /* ----- Helpers ----- */
    function isBooked(startMin, endMin) {
      return booked.some(function (b) { return startMin < b.end && endMin > b.start; });
    }
    function isPast(startMin) {
      return cfg.date === cfg.today && startMin < cfg.nowMinutes;
    }
    function rangeOk(from, to) {
      var open = cfg.open * 60;
      var close = cfg.close * 60;
      if (to <= from || to - from > cfg.maxMinutes) { return false; }
      if (from < open || to > close || from % 30 !== 0 || to % 30 !== 0) { return false; }
      if (isPast(from)) { return false; }
      return !isBooked(from, to);
    }
    function hasFreeEnd(start) {
      var close = cfg.close * 60;
      for (var end = start + 30; end <= close && end - start <= cfg.maxMinutes; end += 30) {
        if (rangeOk(start, end)) { return true; }
      }
      return false;
    }
    function cardLabel(card) {
      return card.querySelector('.room-state');
    }

    /* ----- From / To rendering ----- */
    function fillSelect(select, options, placeholder, selected) {
      select.innerHTML = '';
      var blank = document.createElement('option');
      blank.value = '';
      blank.textContent = placeholder;
      select.appendChild(blank);
      var group = null;
      var groupName = '';
      options.forEach(function (minutes) {
        var hour = Math.floor(minutes / 60);
        var part = hour < 12 ? 'Morning' : (hour < 17 ? 'Afternoon' : 'Evening');
        if (part !== groupName) {
          group = document.createElement('optgroup');
          group.label = part;
          select.appendChild(group);
          groupName = part;
        }
        var option = document.createElement('option');
        option.value = String(minutes);
        option.textContent = SS.fromMinutes(minutes);
        if (String(selected) === String(minutes)) { option.selected = true; }
        group.appendChild(option);
      });
      select.disabled = options.length === 0;
    }

    function renderTimes() {
      var close = cfg.close * 60;
      var starts = [];
      for (var m = cfg.open * 60; m < close; m += 30) {
        if (hasFreeEnd(m)) { starts.push(m); }
      }
      var keepFrom = selection ? selection.start : '';
      if (starts.indexOf(keepFrom) === -1) {
        selection = null;
        keepFrom = '';
      }
      fillSelect(els.from, starts, starts.length ? 'Start' : 'No times', keepFrom);
      renderEnds(keepFrom, selection ? selection.end : '');
      els.hint.textContent = starts.length ? defaultHint : 'No free time left on this day.';
      updateSummary();
    }

    function renderEnds(start, selectedEnd) {
      var ends = [];
      if (start !== '' && start !== null && !isNaN(start)) {
        var close = cfg.close * 60;
        start = parseInt(start, 10);
        for (var end = start + 30; end <= close && end - start <= cfg.maxMinutes; end += 30) {
          if (rangeOk(start, end)) { ends.push(end); }
        }
      }
      if (ends.indexOf(parseInt(selectedEnd, 10)) === -1) { selectedEnd = ''; }
      fillSelect(els.to, ends, 'End', selectedEnd);
      els.to.disabled = ends.length === 0;
    }

    function updateSummary() {
      var code = current ? current.dataset.code : '';
      if (selection) {
        els.summary.textContent = SS.fromMinutes(selection.start) + ' – ' + SS.fromMinutes(selection.end) +
          ' · ' + SS.hoursLabel(selection.end - selection.start);
        els.reserve.disabled = false;
        els.reserve.textContent = 'Reserve ' + code;
      } else {
        els.summary.textContent = els.from && els.from.value ? 'Now choose an end time' : 'No time selected';
        els.reserve.disabled = true;
        els.reserve.textContent = code ? 'Reserve ' + code : 'Reserve';
      }
      updatePick();
    }

    function updatePick() {
      if (!els.activity) { return; }
      if (!selection || !current) {
        if (pickItem) { pickItem.remove(); pickItem = null; }
        return;
      }
      if (!pickItem) {
        pickItem = document.createElement('li');
        pickItem.className = 'activity-item activity-pick';
        pickItem.innerHTML = '<span class="activity-main"><span class="activity-code"></span><span class="activity-time"></span></span><span class="activity-tag">Your pick</span>';
        els.activity.appendChild(pickItem);
      }
      pickItem.dataset.type = current.dataset.type;
      pickItem.querySelector('.activity-code').textContent = current.dataset.code;
      pickItem.querySelector('.activity-time').textContent = SS.fromMinutes(selection.start) + ' – ' + SS.fromMinutes(selection.end);
      applyFilter(activeFilter);
    }

    /* ----- From / To changes ----- */
    els.from.addEventListener('change', function () {
      var start = els.from.value === '' ? null : parseInt(els.from.value, 10);
      selection = null;
      renderEnds(start === null ? '' : start, '');
      updateSummary();
    });

    els.to.addEventListener('change', function () {
      var start = parseInt(els.from.value, 10);
      var end = parseInt(els.to.value, 10);
      if (!els.from.value || !els.to.value || !rangeOk(start, end)) {
        selection = null;
      } else {
        selection = { start: start, end: end };
      }
      updateSummary();
    });

    /* ----- Room selection ----- */
    function setRoomField(id) {
      if (els.roomField) { els.roomField.value = id || ''; }
      if (window.history && history.replaceState) {
        var url = new URL(window.location.href);
        if (id) { url.searchParams.set('room', id); } else { url.searchParams.delete('room'); }
        history.replaceState(null, '', url);
      }
    }

    function setSelectionLabel(text) {
      if (els.selectionLabel) { els.selectionLabel.textContent = text; }
    }

    function clearDeskHighlight() {
      if (!currentDesk) { return; }
      currentDesk.classList.remove('is-selected');
      currentDesk.setAttribute('aria-pressed', 'false');
      currentDesk = null;
    }

    function showEmpty() {
      els.empty.hidden = false;
      els.body.hidden = true;
      if (els.deskBody) { els.deskBody.hidden = true; }
      setSelectionLabel('01 / Selection');
    }

    function deselect() {
      if (current) {
        current.classList.remove('is-selected');
        current.setAttribute('aria-pressed', 'false');
        var label = cardLabel(current);
        if (label && current.dataset.originalLabel) { label.textContent = current.dataset.originalLabel; }
      }
      current = null;
      selection = null;
      booked = [];
      clearDeskHighlight();
      showEmpty();
      updatePick();
      setRoomField('');
    }

    function selectDesk(btn) {
      if (btn.dataset.status !== 'available') {
        SS.toast('Only a green desk can be selected.', 'error');
        return;
      }
      if (currentDesk === btn) {
        clearDeskHighlight();
        if (!current) { showEmpty(); }
        return;
      }
      if (current) {
        current.classList.remove('is-selected');
        current.setAttribute('aria-pressed', 'false');
        var label = cardLabel(current);
        if (label && current.dataset.originalLabel) { label.textContent = current.dataset.originalLabel; }
        current = null;
        selection = null;
        booked = [];
        updatePick();
        setRoomField('');
      }
      clearDeskHighlight();
      currentDesk = btn;
      btn.classList.add('is-selected');
      btn.setAttribute('aria-pressed', 'true');
      els.empty.hidden = true;
      els.body.hidden = true;
      els.deskBody.hidden = false;
      els.deskCode.textContent = btn.dataset.deskCode;
      els.deskMeta.textContent = 'Quiet hall · Level ' + btn.dataset.floorNo + ' · Walk-in';
      els.deskNote.textContent = btn.dataset.note || 'Open desk. Take a seat if it is free.';
      setSelectionLabel('01 / Selected desk');
      showFloor(btn.dataset.floorNo);
    }

    function selectRoom(card, silent) {
      if (card.dataset.state === 'out_of_order') {
        SS.toast(card.dataset.code + ' is out of order and cannot be booked right now.', 'error');
        return;
      }
      if (current === card) { return; }
      if (current) { deselect(); }

      current = card;
      selection = null;
      var label = cardLabel(card);
      card.dataset.originalLabel = label.textContent;
      label.textContent = 'Selected · ' + card.dataset.seats + ' seats';
      card.classList.add('is-selected');
      card.setAttribute('aria-pressed', 'true');

      clearDeskHighlight();
      els.empty.hidden = true;
      els.body.hidden = false;
      if (els.deskBody) { els.deskBody.hidden = true; }
      setSelectionLabel('01 / Selected room');
      els.code.textContent = card.dataset.code;
      els.meta.textContent = card.dataset.name + ' · ' + card.dataset.typeLabel + ' · Level ' + card.dataset.floorNo + ' · ' + card.dataset.seats + ' seats';
      els.desc.textContent = card.dataset.description;
      els.tags.innerHTML = '';
      card.dataset.facilities.split(',').forEach(function (item) {
        item = item.trim();
        if (!item) { return; }
        var li = document.createElement('li');
        li.className = 'tag';
        li.textContent = item;
        els.tags.appendChild(li);
      });
      setRoomField(card.dataset.roomId);
      showFloor(card.dataset.floorNo);
      loadAvailability(card, silent);
    }

    function loadAvailability(card, silent) {
      var token = ++requestToken;
      booked = [];
      selection = null;
      els.from.disabled = true;
      els.to.disabled = true;
      els.hint.textContent = 'Loading times…';
      els.reserve.disabled = true;
      els.reserve.textContent = 'Reserve ' + card.dataset.code;

      fetch(cfg.api + '?room_id=' + encodeURIComponent(card.dataset.roomId) + '&date=' + encodeURIComponent(cfg.date), { headers: { 'Accept': 'application/json' } })
        .then(function (response) {
          if (!response.ok) { throw new Error('bad response'); }
          return response.json();
        })
        .then(function (data) {
          if (token !== requestToken) { return; }
          booked = data.booked.map(function (b) { return { start: SS.toMinutes(b.start), end: SS.toMinutes(b.end) }; });
          cfg.nowMinutes = data.now.minutes;
          cfg.today = data.now.date;
          if (!movedOffClosedDay && dayHasPassed()) {
            movedOffClosedDay = true;
            var dateInput = document.getElementById('plan-date');
            if (dateInput) {
              dateInput.value = nextDay(cfg.today);
              SS.toast('Today is closed. Showing the next day so you can pick a time.', 'info');
              dateInput.dispatchEvent(new Event('change', { bubbles: true }));
              return;
            }
          }
          renderTimes();
          if (!silent) {
            var free = 0;
            for (var m = cfg.open * 60; m < cfg.close * 60; m += 30) { if (hasFreeEnd(m)) { free++; } }
            SS.toast(card.dataset.code + ' selected: ' + free + ' start time' + (free === 1 ? '' : 's') + ' free on this day.', 'success');
          }
        })
        .catch(function () {
          if (token !== requestToken) { return; }
          els.hint.textContent = 'Could not load times. Please try again.';
          SS.toast('Could not load the times for ' + card.dataset.code + '.', 'error');
        });
    }

    cards.forEach(function (card) {
      card.addEventListener('click', function () { selectRoom(card, false); });
    });

    Array.prototype.forEach.call(document.querySelectorAll('.hall-desk-available'), function (btn) {
      btn.addEventListener('click', function () { selectDesk(btn); });
    });

    if (els.clearDesk) {
      els.clearDesk.addEventListener('click', function () {
        clearDeskHighlight();
        showEmpty();
        SS.toast('Selection cleared.', 'info');
      });
    }

    els.clear.addEventListener('click', function () {
      deselect();
      SS.toast('Selection cleared.', 'info');
    });

    els.reserve.addEventListener('click', function () {
      if (!current || !selection) { return; }
      var params = new URLSearchParams({
        room: current.dataset.roomId,
        date: cfg.date,
        start: SS.fromMinutes(selection.start),
        end: SS.fromMinutes(selection.end)
      });
      SS.toast('Taking you to the booking form…', 'success', 1500);
      window.location.href = cfg.bookUrl + '?' + params.toString();
    });

    /* ----- Activity filter ----- */
    var activeFilter = 'all';
    var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));

    function applyFilter(type) {
      activeFilter = type;
      if (!els.activity) { return; }
      Array.prototype.forEach.call(els.activity.querySelectorAll('.activity-item'), function (item) {
        item.hidden = !(type === 'all' || item.dataset.type === type);
      });
    }
    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterButtons.forEach(function (b) {
          b.classList.toggle('is-active', b === btn);
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });
        applyFilter(btn.dataset.filter);
      });
    });

    var initial = panel.dataset.initialRoom;
    if (initial) {
      var match = cards.filter(function (c) { return c.dataset.roomId === initial; })[0];
      if (match) { selectRoom(match, true); }
    }
  });
}());

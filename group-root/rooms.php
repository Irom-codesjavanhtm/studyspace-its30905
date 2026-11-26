<?php
require __DIR__ . '/includes/app.php';
require_login();

$dateGiven = isset($_GET['date']) && $_GET['date'] !== '';
$date = $dateGiven ? (string) $_GET['date'] : today();
if (!valid_date($date)) {
    flash('error', 'That date was not valid, showing the next open day instead.');
    $date = today();
    $dateGiven = false;
}
// After the last bookable hour, today has no slots left. Open on the next day.
$lastSlotStart = (int) cfg('booking', 'close_hour') * 60 - 30;
if (!$dateGiven && now_minutes() >= $lastSlotStart) {
    $date = date('Y-m-d', strtotime(today() . ' +1 day'));
}
$isToday = $date === today();
$initialRoom = (int) ($_GET['room'] ?? 0);

require __DIR__ . '/includes/floor-layout.php';

$rooms = array_values(array_filter(
    rooms_all(),
    static fn (array $room): bool => in_array((int) $room['floor_no'], [2, 4], true)
));
$bookings = bookings_for_date($date);
$states = room_states($rooms, $bookings, $date);

$counts = ['available' => 0, 'occupied' => 0, 'out_of_order' => 0];
foreach ($states as $s) {
    $counts[$s['state']]++;
}

$roomsByCode = [];
$roomsOnFloor = [];
foreach ($rooms as $room) {
    $roomsByCode[$room['room_code']] = $room;
    $roomsOnFloor[(int) $room['floor_no']][] = $room;
}
$planFloors = plan_floors();
$defaultFloor = 2;
if ($initialRoom && ($found = room_find($initialRoom)) && in_array((int) $found['floor_no'], [2, 4], true)) {
    $defaultFloor = (int) $found['floor_no'];
}

$filters = ['all' => 'All', 'quiet' => 'Quiet', 'group' => 'Group', 'seminar' => 'Seminar'];
$nowMin = now_minutes();

$pageTitle = 'Study Rooms';
$activeNav = 'rooms';
$pageId = 'rooms';
$shell = 'app';
$pageScripts = ['rooms'];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section dash-section">
        <div class="dash-head">
          <div class="dash-head-copy">
            <p class="eyebrow eyebrow-accent">Floor plan</p>
            <h1>Study Rooms</h1>
            <p class="page-sub">Pick a date, tap an R room on the map, then reserve a free slot.</p>
          </div>
          <dl class="dash-kpis" aria-label="Room status for <?= e(pretty_date($date)) ?>">
            <div class="dash-kpi">
              <dt>Available</dt>
              <dd><?= str_pad((string) $counts['available'], 2, '0', STR_PAD_LEFT) ?></dd>
            </div>
            <div class="dash-kpi">
              <dt>Occupied</dt>
              <dd><?= str_pad((string) $counts['occupied'], 2, '0', STR_PAD_LEFT) ?></dd>
            </div>
            <div class="dash-kpi">
              <dt>Out of order</dt>
              <dd><?= str_pad((string) $counts['out_of_order'], 2, '0', STR_PAD_LEFT) ?></dd>
            </div>
          </dl>
        </div>

        <div class="rooms-layout">
          <section class="panel plan-panel" aria-label="Floor plan">
            <div class="plan-wrap">
              <div class="plan-floors">
                    <?php foreach ($planFloors as $floorNo):
                        $closed = plan_floor_closed($floorNo);
                        $floorRooms = $roomsOnFloor[$floorNo] ?? [];
                    ?>
                <div class="floor-panel<?= $floorNo === $defaultFloor ? ' is-active' : '' ?>" data-floor="<?= $floorNo ?>">
                  <p class="floor-title"><?= $closed ? 'Level ' . $floorNo . ' · Under renovation' : 'Level ' . $floorNo . ' · ' . count($floorRooms) . ' rooms' ?></p>
                  <div class="plan-frame<?= $closed ? ' is-closed' : '' ?>">
                    <?php if ($closed): ?>
                    <div class="plan-reno" role="status">
                      <span>Under Renovation</span>
                    </div>
                    <?php else: ?>
                    <div class="plan-grid">
                    <?php foreach (plan_floor_blocks($floorNo) as $block):
                        $place = plan_place($block);
                        if ($block['type'] === 'room'):
                            $room = $roomsByCode[$block['code']] ?? null;
                            if (!$room) { continue; }
                            $st = $states[$room['room_id']];
                    ?>
                      <button class="room-card room-card-<?= e($st['state']) ?>" type="button" style="<?= e($place) ?>"
                        data-room-id="<?= (int) $room['room_id'] ?>"
                        data-code="<?= e($room['room_code']) ?>"
                        data-name="<?= e($room['room_name']) ?>"
                        data-type="<?= e($room['room_type']) ?>"
                        data-type-label="<?= e(type_label($room['room_type'])) ?>"
                        data-floor-no="<?= (int) $room['floor_no'] ?>"
                        data-seats="<?= (int) $room['capacity'] ?>"
                        data-facilities="<?= e($room['facilities']) ?>"
                        data-description="<?= e($room['description']) ?>"
                        data-state="<?= e($st['state']) ?>"
                        aria-pressed="false"
                        aria-label="<?= e($room['room_code'] . ', ' . $room['capacity'] . ' seats, ' . str_replace('_', ' ', $st['state'])) ?>">
                        <span class="room-code"><?= e($room['room_code']) ?></span>
                        <span class="room-state"><?= e(state_label($room, $st)) ?></span>
                      </button>
                        <?php elseif ($block['type'] === 'quiet'): ?>
                      <div class="plan-quiet" style="<?= e($place) ?>" role="note" aria-label="Quiet hall, walk-in tables, no booking">
                        <?php if ($block['label'] !== ''): ?>
                        <span class="plan-label"><?= e($block['label']) ?></span>
                        <?php endif; ?>
                      </div>
                        <?php else: ?>
                      <div class="plan-decor plan-<?= e($block['kind']) ?><?= !empty($block['vertical']) ? ' is-vertical' : '' ?>" style="<?= e($place) ?>"<?= $block['label'] === '' ? ' aria-hidden="true"' : '' ?>>
                        <span class="plan-label"><?= e($block['label']) ?></span>
                      </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                  <?php endforeach; ?>
              </div>
              <div class="floor-switch" role="group" aria-label="Choose floor">
                  <?php foreach ($planFloors as $floorNo): $closed = plan_floor_closed($floorNo); ?>
                <button class="floor-btn<?= $floorNo === $defaultFloor ? ' is-active' : '' ?><?= $closed ? ' is-closed' : '' ?>" type="button" data-floor-btn="<?= $floorNo ?>" aria-pressed="<?= $floorNo === $defaultFloor ? 'true' : 'false' ?>" aria-label="Level <?= $floorNo ?><?= $closed ? ', under renovation' : '' ?>"><?= $floorNo ?></button>
                  <?php endforeach; ?>
              </div>
            </div>
            <ul class="legend">
              <li><span class="legend-room"></span>Meeting room</li>
              <li><span class="legend-quiet"></span>Quiet hall, walk-in</li>
              <li><span class="legend-decor"></span>Lift, exit, shelves, WC</li>
              <li><span class="legend-selected"></span>Selected</li>
            </ul>
          </section>

          <aside class="panel selected-panel dash-side" id="selected-panel" aria-label="Selected room"
            data-date="<?= e($date) ?>"
            data-today="<?= e(today()) ?>"
            data-now-minutes="<?= $nowMin ?>"
            data-open="<?= (int) cfg('booking', 'open_hour') ?>"
            data-close="<?= (int) cfg('booking', 'close_hour') ?>"
            data-max-hours="<?= (int) cfg('booking', 'max_hours') ?>"
            data-api="api/availability.php"
            data-book-url="book.php"
            data-initial-room="<?= $initialRoom ?: '' ?>">
            <div class="dash-side-head">
              <p class="eyebrow eyebrow-accent" data-selection-label>Selection</p>
              <form class="date-form" method="get" action="rooms.php" data-autosubmit>
                <div class="field">
                  <label for="plan-date">Date</label>
                  <input class="input" type="date" id="plan-date" name="date" value="<?= e($date) ?>" min="<?= e(today()) ?>" max="<?= e(date('Y-m-d', strtotime('+' . cfg('booking', 'max_days_ahead') . ' days'))) ?>">
                </div>
                <input type="hidden" name="room" value="<?= $initialRoom ?: '' ?>" data-room-field>
              </form>
            </div>
            <div class="selected-empty" data-empty>
              <p class="room-title room-title-empty">Nothing selected</p>
              <p class="muted">Tap an R room to book it. Slate halls are walk-in tables. Levels 3 and 5 are closed.</p>
            </div>
            <div class="selected-body" data-body hidden>
              <h2 class="room-title" data-room-code>R-000</h2>
              <p class="room-meta" data-room-meta></p>
              <ul class="tags" data-room-tags></ul>
              <p class="room-desc" data-room-desc></p>
              <p class="eyebrow eyebrow-accent"><span data-slots-label><?= $isToday ? "Today's time" : 'Time on ' . e(date('j M', strtotime($date))) ?></span></p>
              <div class="time-range">
                <div class="field">
                  <label for="slot-from">From</label>
                  <select class="input" id="slot-from" data-from disabled>
                    <option value="">Start</option>
                  </select>
                </div>
                <div class="field">
                  <label for="slot-to">To</label>
                  <select class="input" id="slot-to" data-to disabled>
                    <option value="">End</option>
                  </select>
                </div>
              </div>
              <p class="slot-hint" data-slot-hint>Choose a start and an end. Up to <?= (int) cfg('booking', 'max_hours') ?> hours, on the hour or half hour.</p>
              <p class="slot-summary" data-summary>No time selected</p>
              <button class="btn btn-dark btn-block" type="button" data-reserve disabled>Reserve</button>
              <button class="btn btn-ghost btn-block" type="button" data-clear>Clear selection</button>
            </div>
            <div class="selected-body" data-desk-body hidden>
              <h2 class="room-title" data-desk-code>H-000</h2>
              <p class="room-meta" data-desk-meta></p>
              <p class="room-desc" data-desk-note></p>
              <p class="slot-summary">Walk-in desk. Take a seat while it stays green. These desks are not reserved.</p>
              <button class="btn btn-ghost btn-block" type="button" data-clear-desk>Clear selection</button>
            </div>
          </aside>

          <aside class="panel activity-panel dash-side" aria-label="Room activity">
            <div class="dash-side-head">
              <p class="eyebrow eyebrow-accent"><?= $isToday ? 'Today' : e(date('j M', strtotime($date))) ?></p>
              <h2 class="panel-title">Room activity</h2>
            </div>
            <ul class="activity-list" data-activity>
<?php foreach ($bookings as $b):
    $start = to_minutes(hm($b['start_time']));
    $end = to_minutes(hm($b['end_time']));
    if (!$isToday) {
        [$tag, $tagClass] = ['Booked', 'booked'];
    } elseif ($end <= $nowMin) {
        [$tag, $tagClass] = ['Done', 'done'];
    } elseif ($start <= $nowMin) {
        [$tag, $tagClass] = ['In use', 'live'];
    } else {
        [$tag, $tagClass] = ['Upcoming', 'next'];
    }
?>
              <li class="activity-item activity-<?= $tagClass ?>" data-type="<?= e($b['room_type']) ?>">
                <span class="activity-main">
                  <span class="activity-code"><?= e($b['room_code']) ?></span>
                  <span class="activity-time"><?= e(hm($b['start_time'])) ?> – <?= e(hm($b['end_time'])) ?></span>
                </span>
                <span class="activity-tag"><?= e($tag) ?></span>
              </li>
<?php endforeach; ?>
            </ul>
<?php if (!$bookings): ?>
            <p class="muted">No bookings on this date yet. Every room is free.</p>
<?php endif; ?>
            <p class="eyebrow eyebrow-accent">Filter</p>
            <div class="pill-group" role="group" aria-label="Filter activity by room type">
<?php foreach ($filters as $key => $label): ?>
              <button class="pill<?= $key === 'all' ? ' is-active' : '' ?>" type="button" data-filter="<?= $key ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"><?= e($label) ?></button>
<?php endforeach; ?>
            </div>
          </aside>
        </div>
      </section>

      <section class="site-container page-section">
        <header class="section-head">
          <p class="eyebrow eyebrow-accent">Directory</p>
          <h2>All rooms and facilities</h2>
        </header>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Room</th>
                <th scope="col">Type</th>
                <th scope="col">Level</th>
                <th scope="col">Seats</th>
                <th scope="col">Facilities</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-label">Action</span></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($rooms as $room): $st = $states[$room['room_id']]; ?>
              <tr>
                <td data-label="Room">
                  <span class="cell-strong mono"><?= e($room['room_code']) ?></span>
                  <span class="cell-sub"><?= e($room['room_name']) ?></span>
                </td>
                <td data-label="Type"><?= e(type_label($room['room_type'])) ?></td>
                <td data-label="Level"><?= (int) $room['floor_no'] ?></td>
                <td data-label="Seats"><?= (int) $room['capacity'] ?></td>
                <td data-label="Facilities"><?php render_facility_tags($room['facilities'], 'tags tags-sm'); ?></td>
                <td data-label="Status">
<?php render_status_badge($st['state'], $st['state'] === 'out_of_order' ? 'Out of order' : ($st['state'] === 'occupied' ? 'Occupied' : 'Available')); ?>
                </td>
                <td data-label="Action">
<?php if ($room['status'] === 'available'): ?>
                  <a class="btn btn-outline btn-sm" href="<?= e(page_url('book.php', ['room' => $room['room_id'], 'date' => $date])) ?>">Book</a>
<?php else: ?>
                  <span class="muted">Unavailable</span>
<?php endif; ?>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="site-container page-section">
        <header class="section-head">
          <p class="eyebrow eyebrow-accent">Quiet hall</p>
          <h2>Open desks, walk-in only</h2>
          <p class="page-sub">These tables sit in the slate quiet halls. They are walk-in only and are never booked.</p>
        </header>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Desk</th>
                <th scope="col">Level</th>
                <th scope="col">Spot</th>
                <th scope="col">Note</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
<?php foreach (quiet_desks_all() as $desk): if (!in_array((int) $desk['floor_no'], [2, 4], true)) { continue; } ?>
              <tr>
                <td data-label="Desk"><span class="cell-strong mono"><?= e($desk['desk_code']) ?></span></td>
                <td data-label="Level"><?= (int) $desk['floor_no'] ?></td>
                <td data-label="Spot"><?= (int) $desk['position_no'] ?></td>
                <td data-label="Note"><?= e($desk['note']) ?></td>
                <td data-label="Status"><?php render_status_badge($desk['status'], quiet_desk_status_label($desk['status'])); ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

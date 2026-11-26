<?php
require __DIR__ . '/includes/app.php';
require_login();

$date = (string) ($_GET['date'] ?? today());
if (!valid_date($date)) {
    flash('error', 'That date was not valid, showing today instead.');
    $date = today();
}

$rooms = rooms_all();
$allDay = bookings_for_date($date, true);
$confirmed = array_values(array_filter($allDay, fn ($b) => $b['status'] === 'confirmed'));
$cancelled = array_values(array_filter($allDay, fn ($b) => $b['status'] === 'cancelled'));

$open = cfg('booking', 'open_hour');
$close = cfg('booking', 'close_hour');
$windowMinutes = ($close - $open) * 60;

$perRoom = [];
foreach ($rooms as $room) {
    $perRoom[$room['room_id']] = ['room' => $room, 'count' => 0, 'minutes' => 0, 'hours' => array_fill($open, $close - $open, 0)];
}
$hourLoad = array_fill($open, $close - $open, 0);
$totalMinutes = 0;
$people = 0;

foreach ($confirmed as $b) {
    $s = to_minutes(hm($b['start_time']));
    $e = to_minutes(hm($b['end_time']));
    $perRoom[$b['room_id']]['count']++;
    $perRoom[$b['room_id']]['minutes'] += $e - $s;
    $totalMinutes += $e - $s;
    $people += (int) $b['attendees'];
    for ($h = $open; $h < $close; $h++) {
        $overlap = min($e, ($h + 1) * 60) - max($s, $h * 60);
        if ($overlap > 0) {
            $perRoom[$b['room_id']]['hours'][$h] = max($perRoom[$b['room_id']]['hours'][$h], $overlap >= 60 ? 2 : 1);
            $hourLoad[$h]++;
        }
    }
}

$usableRooms = count(array_filter($rooms, fn ($r) => $r['status'] === 'available'));
$utilisation = $usableRooms > 0 ? round($totalMinutes / ($usableRooms * $windowMinutes) * 100) : 0;
$roomsUsed = count(array_filter($perRoom, fn ($r) => $r['count'] > 0));
$peakLoad = max($hourLoad);
$peakHour = $peakLoad > 0 ? array_search($peakLoad, $hourLoad, true) : null;

$busiest = null;
foreach ($perRoom as $row) {
    if ($row['minutes'] > 0 && ($busiest === null || $row['minutes'] > $busiest['minutes'])) {
        $busiest = $row;
    }
}

$strip = booking_counts_by_day(date('Y-m-d', strtotime($date . ' -3 days')), 7);
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));

$pageTitle = 'Daily Summary';
$activeNav = 'summary';
$pageId = 'summary';
$shell = 'app';
$pageScripts = [];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section">
        <div class="page-head">
          <div>
            <p class="eyebrow eyebrow-accent">07 / Daily summary</p>
            <h1><?= e(pretty_date($date)) ?><?= $date === today() ? ' <span class="today-chip">Today</span>' : '' ?></h1>
            <p class="page-sub">How busy the study rooms are on this day.</p>
          </div>
          <form class="date-form" method="get" action="summary.php" data-autosubmit>
            <a class="btn btn-ghost btn-sm" href="summary.php?date=<?= e($prev) ?>" aria-label="Previous day">&larr;</a>
            <label class="sr-label" for="summary-date">Date</label>
            <input class="input input-compact" type="date" id="summary-date" name="date" value="<?= e($date) ?>">
            <a class="btn btn-ghost btn-sm" href="summary.php?date=<?= e($next) ?>" aria-label="Next day">&rarr;</a>
            <button class="btn btn-dark btn-sm" type="submit">Show</button>
          </form>
        </div>

        <ul class="day-strip" aria-label="Nearby days">
<?php foreach (array_keys(array_fill(0, 7, 0)) as $i):
    $d = date('Y-m-d', strtotime($date . ' ' . ($i - 3) . ' days')); ?>
          <li>
            <a class="day-chip<?= $d === $date ? ' is-active' : '' ?>" href="summary.php?date=<?= e($d) ?>"<?= $d === $date ? ' aria-current="date"' : '' ?>>
              <span class="day-name"><?= e(date('D', strtotime($d))) ?></span>
              <span class="day-num"><?= e(date('j', strtotime($d))) ?></span>
              <span class="day-count"><?= (int) ($strip[$d] ?? 0) ?> booked</span>
            </a>
          </li>
<?php endforeach; ?>
        </ul>

        <div class="kpi-grid">
          <div class="panel kpi">
            <p class="eyebrow">Bookings</p>
            <p class="kpi-value"><?= count($confirmed) ?></p>
            <p class="kpi-note"><?= count($cancelled) ?> cancelled</p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">Hours booked</p>
            <p class="kpi-value"><?= e(rtrim(rtrim(number_format($totalMinutes / 60, 1), '0'), '.')) ?></p>
            <p class="kpi-note"><?= $people ?> student visits</p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">Rooms used</p>
            <p class="kpi-value"><?= $roomsUsed ?><span class="kpi-of">/<?= $usableRooms ?></span></p>
            <p class="kpi-note"><?= $busiest ? 'Busiest: ' . e($busiest['room']['room_code']) : 'No bookings yet' ?></p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">Utilisation</p>
            <p class="kpi-value"><?= (int) $utilisation ?><span class="kpi-of">%</span></p>
            <p class="kpi-note"><?= $peakHour !== null ? 'Peak ' . sprintf('%02d:00', $peakHour) . ' (' . $peakLoad . ' rooms)' : 'Quiet day' ?></p>
          </div>
        </div>

        <header class="section-head">
          <p class="eyebrow eyebrow-accent">08 / Timeline by room</p>
          <h2>Hour by hour</h2>
        </header>
        <div class="panel timeline">
          <div class="timeline-scroll">
            <div class="timeline-row timeline-head">
              <span class="timeline-room"><span class="sr-label">Room</span></span>
              <span class="timeline-cells">
<?php for ($h = $open; $h < $close; $h++): ?>
                <span class="timeline-hour"><?= sprintf('%02d', $h) ?></span>
<?php endfor; ?>
              </span>
              <span class="timeline-meta"><span class="sr-label">Hours</span></span>
            </div>
<?php foreach ($perRoom as $row): $room = $row['room']; ?>
            <div class="timeline-row">
              <span class="timeline-room mono"><?= e($room['room_code']) ?></span>
              <span class="timeline-cells">
<?php foreach ($row['hours'] as $h => $level): ?>
                <span class="timeline-cell<?= $room['status'] === 'out_of_order' ? ' is-off' : ($level === 2 ? ' is-busy' : ($level === 1 ? ' is-part' : '')) ?>" title="<?= e($room['room_code'] . ' ' . sprintf('%02d:00', $h)) ?>"></span>
<?php endforeach; ?>
              </span>
              <span class="timeline-meta mono"><?= $room['status'] === 'out_of_order' ? 'n/a' : e(rtrim(rtrim(number_format($row['minutes'] / 60, 1), '0'), '.')) . ' h' ?></span>
            </div>
<?php endforeach; ?>
          </div>
          <ul class="legend">
            <li><span class="legend-cell"></span>Free</li>
            <li><span class="legend-cell is-part"></span>Part booked</li>
            <li><span class="legend-cell is-busy"></span>Booked</li>
            <li><span class="legend-cell is-off"></span>Out of order</li>
          </ul>
        </div>

        <header class="section-head">
          <p class="eyebrow eyebrow-accent">09 / Room totals</p>
          <h2>Bookings per room</h2>
        </header>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Room</th>
                <th scope="col">Bookings</th>
                <th scope="col">Hours</th>
                <th scope="col">Utilisation</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($perRoom as $row): $room = $row['room']; $pct = $windowMinutes ? round($row['minutes'] / $windowMinutes * 100) : 0; ?>
              <tr>
                <td data-label="Room">
                  <span class="cell-strong mono"><?= e($room['room_code']) ?></span>
                  <span class="cell-sub"><?= e($room['room_name']) ?></span>
                </td>
                <td data-label="Bookings"><?= (int) $row['count'] ?></td>
                <td data-label="Hours"><?= e(rtrim(rtrim(number_format($row['minutes'] / 60, 1), '0'), '.')) ?></td>
                <td data-label="Utilisation">
<?php if ($room['status'] === 'out_of_order'): ?>
                  <span class="muted">Out of order</span>
<?php else: ?>
                  <span class="meter-row"><progress class="meter" max="100" value="<?= (int) $pct ?>"><?= (int) $pct ?>%</progress><span class="mono"><?= (int) $pct ?>%</span></span>
<?php endif; ?>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <header class="section-head">
          <p class="eyebrow eyebrow-accent">10 / Booking list</p>
          <h2>All bookings on <?= e(date('j M', strtotime($date))) ?></h2>
        </header>
<?php if (!$allDay): ?>
        <div class="panel empty-state">
          <p>No bookings on this date. <a class="text-link" href="<?= e(page_url('book.php', ['date' => $date >= today() ? $date : today()])) ?>">Book a room</a>.</p>
        </div>
<?php else: ?>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Time</th>
                <th scope="col">Room</th>
                <th scope="col">Student</th>
                <th scope="col">People</th>
                <th scope="col">Purpose</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($allDay as $b): [$kind, $label] = booking_status_label($b); ?>
              <tr>
                <td data-label="Time" class="mono"><?= e(hm($b['start_time'])) ?> – <?= e(hm($b['end_time'])) ?></td>
                <td data-label="Room"><span class="cell-strong mono"><?= e($b['room_code']) ?></span></td>
                <td data-label="Student">
                  <span class="cell-strong"><?= e($b['student_name']) ?></span>
                  <span class="cell-sub mono"><?= e(admin_logged_in() ? $b['student_id'] : mask_id($b['student_id'])) ?></span>
                </td>
                <td data-label="People"><?= (int) $b['attendees'] ?></td>
                <td data-label="Purpose"><?= e($b['purpose']) ?></td>
                <td data-label="Status"><?php render_status_badge($kind, $label); ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

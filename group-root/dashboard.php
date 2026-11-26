<?php
require __DIR__ . '/includes/app.php';
require_login();

$user = current_user();
if (admin_logged_in()) {
    redirect('admin.php');
}

$rooms = array_values(array_filter(
    rooms_all(),
    static fn (array $room): bool => in_array((int) $room['floor_no'], [2, 4], true)
));
$todayBookings = bookings_for_date(today());
$states = room_states($rooms, $todayBookings, today());
$counts = ['available' => 0, 'occupied' => 0, 'out_of_order' => 0];
foreach ($states as $s) {
    $counts[$s['state']]++;
}

$myBookings = bookings_for_student((string) $user['username']);
$upcoming = array_values(array_filter($myBookings, 'booking_is_active'));
$upcoming = array_slice($upcoming, 0, 5);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$pageId = 'dashboard';
$shell = 'app';
$pageScripts = [];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section dash-section">
        <div class="dash-head">
          <div class="dash-head-copy">
            <p class="eyebrow eyebrow-accent">Welcome back</p>
            <h1>Hi, <?= e((string) ($user['name'] !== '' ? $user['name'] : $user['username'])) ?></h1>
            <p class="page-sub">Pick a room, book a slot, or check your upcoming reservations.</p>
          </div>
          <div class="dash-head-actions">
            <a class="btn btn-dark" href="rooms.php">Open floor plan</a>
            <a class="btn btn-outline" href="book.php">Book a room</a>
          </div>
        </div>

        <dl class="dash-kpis dash-kpis-row" aria-label="Today at a glance">
          <div class="dash-kpi">
            <dt>Available now</dt>
            <dd><?= str_pad((string) $counts['available'], 2, '0', STR_PAD_LEFT) ?></dd>
          </div>
          <div class="dash-kpi">
            <dt>In use</dt>
            <dd><?= str_pad((string) $counts['occupied'], 2, '0', STR_PAD_LEFT) ?></dd>
          </div>
          <div class="dash-kpi">
            <dt>Your active</dt>
            <dd><?= str_pad((string) count(array_filter($myBookings, 'booking_is_active')), 2, '0', STR_PAD_LEFT) ?></dd>
          </div>
        </dl>

        <div class="dash-home-grid">
          <section class="panel dash-panel">
            <header class="dash-panel-head">
              <h2>Quick actions</h2>
            </header>
            <ul class="dash-action-list">
              <li><a href="rooms.php"><strong>Browse the floor plan</strong><span>See what’s free on Levels 2 and 4.</span></a></li>
              <li><a href="book.php"><strong>Book a room</strong><span>Choose a room, date, and time slot.</span></a></li>
              <li><a href="my-bookings.php"><strong>Manage bookings</strong><span>Edit or cancel upcoming reservations.</span></a></li>
              <li><a href="summary.php"><strong>Daily summary</strong><span>Check how busy the floors are today.</span></a></li>
            </ul>
          </section>

          <section class="panel dash-panel">
            <header class="dash-panel-head">
              <h2>Upcoming bookings</h2>
              <a class="text-link" href="my-bookings.php">View all</a>
            </header>
<?php if (!$upcoming): ?>
            <div class="empty-state empty-state-compact">
              <p>No active bookings yet.</p>
              <a class="btn btn-dark btn-sm" href="book.php">Book a room</a>
            </div>
<?php else: ?>
            <ul class="dash-booking-list">
<?php foreach ($upcoming as $b): ?>
              <li>
                <div>
                  <p class="dash-booking-code mono"><?= e($b['room_code']) ?></p>
                  <p class="dash-booking-meta"><?= e(pretty_date($b['booking_date'])) ?> · <?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?></p>
                </div>
                <a class="btn btn-outline btn-sm" href="edit-booking.php?id=<?= (int) $b['booking_id'] ?>">Edit</a>
              </li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
          </section>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

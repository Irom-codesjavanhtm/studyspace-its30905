<?php
require __DIR__ . '/includes/app.php';

if (!current_user()) {
    redirect('login.php?next=' . urlencode('my-bookings.php'));
}

if (is_post()) {
    require_csrf('my-bookings.php');
    $id = (int) ($_POST['booking_id'] ?? 0);
    $booking = $id > 0 ? booking_find($id) : null;
    $back = page_url('my-bookings.php', ['q' => $_SESSION['student_lookup'] ?? '']);

    if ($booking === null || !owns_booking($booking)) {
        flash('error', 'That booking could not be found for your account.');
    } elseif (!booking_is_active($booking)) {
        flash('error', 'Booking #' . $id . ' can no longer be cancelled because it is already cancelled or finished.');
    } else {
        booking_cancel($id);
        flash('success', sprintf('Booking #%d (%s, %s %s–%s) was cancelled. The slot is free again.', $id, $booking['room_code'], pretty_date($booking['booking_date']), hm($booking['start_time']), hm($booking['end_time'])));
    }
    redirect($back);
}

$isStudent = student_logged_in();
$q = $isStudent
    ? current_user()['username']
    : trim((string) ($_GET['q'] ?? ($_SESSION['student_lookup'] ?? '')));
$lookupError = '';
$bookings = [];
$searched = $isStudent || $q !== '';

if ($isStudent) {
    $bookings = bookings_for_student($q);
} elseif ($searched) {
    $isEmail = str_contains($q, '@');
    if ($isEmail && !filter_var($q, FILTER_VALIDATE_EMAIL)) {
        $lookupError = 'That does not look like a valid email address.';
    } elseif (!$isEmail && !preg_match('/^[A-Za-z0-9]{5,12}$/', $q)) {
        $lookupError = 'Student ID must be 5 to 12 letters or digits.';
    } else {
        $q = $isEmail ? strtolower($q) : strtoupper($q);
        $_SESSION['student_lookup'] = $q;
        $bookings = bookings_for_student($q);
    }
}

$upcoming = array_filter($bookings, 'booking_is_active');
$history = array_filter($bookings, fn ($b) => !booking_is_active($b));

$pageTitle = 'My Bookings';
$activeNav = 'bookings';
$pageId = 'bookings';
$shell = 'app';
$pageScripts = [];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section dash-section">
        <div class="dash-head">
          <div class="dash-head-copy">
            <p class="eyebrow eyebrow-accent">Your reservations</p>
            <h1>My Bookings</h1>
            <p class="page-sub"><?= $isStudent
                ? 'Bookings for student account ' . e(current_user()['username']) . '. Edit or cancel anything still active.'
                : 'Signed in as admin. Look up a student ID or email, then edit or cancel a booking.' ?></p>
          </div>
<?php if ($searched && !$lookupError): ?>
          <dl class="dash-kpis" aria-label="Booking counts">
            <div class="dash-kpi">
              <dt>Active</dt>
              <dd><?= str_pad((string) count($upcoming), 2, '0', STR_PAD_LEFT) ?></dd>
            </div>
            <div class="dash-kpi">
              <dt>Past</dt>
              <dd><?= str_pad((string) count($history), 2, '0', STR_PAD_LEFT) ?></dd>
            </div>
          </dl>
<?php endif; ?>
        </div>

<?php if (!$isStudent): ?>
        <form class="lookup-form dash-lookup" method="get" action="my-bookings.php" novalidate data-lookup-form>
          <div class="field">
            <label for="q">Student ID or email</label>
            <input class="input" type="text" id="q" name="q" value="<?= e($q) ?>" required maxlength="120" placeholder="TU100231 or aisha.rahman@example.edu" data-msg="Enter a student ID (5 to 12 letters or digits) or a valid email.">
            <p class="field-error" id="q-error" role="alert"><?= e($lookupError) ?></p>
          </div>
          <button class="btn btn-dark" type="submit">Find bookings</button>
        </form>
<?php endif; ?>

<?php if ($searched && !$lookupError): ?>
        <div class="result-head">
          <p class="eyebrow eyebrow-accent">Results for <?= e($q) ?></p>
          <p class="muted"><?= count($upcoming) ?> active &middot; <?= count($history) ?> past or cancelled</p>
        </div>

<?php if (!$bookings): ?>
        <div class="panel empty-state">
          <h2>No bookings found</h2>
          <p>We could not find any bookings for <strong><?= e($q) ?></strong>. Check the spelling or make a new booking.</p>
          <a class="btn btn-dark" href="book.php">Book a room</a>
        </div>
<?php else: ?>
        <h2 class="subhead">Active bookings</h2>
<?php if ($upcoming): ?>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Ref</th>
                <th scope="col">Room</th>
                <th scope="col">Date</th>
                <th scope="col">Time</th>
                <th scope="col">Purpose</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-label">Actions</span></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($upcoming as $b): [$kind, $label] = booking_status_label($b); ?>
              <tr>
                <td data-label="Ref" class="mono">#<?= (int) $b['booking_id'] ?></td>
                <td data-label="Room">
                  <span class="cell-strong mono"><?= e($b['room_code']) ?></span>
                  <span class="cell-sub"><?= e($b['room_name']) ?></span>
                </td>
                <td data-label="Date"><?= e(pretty_date($b['booking_date'])) ?></td>
                <td data-label="Time" class="mono"><?= e(hm($b['start_time'])) ?> – <?= e(hm($b['end_time'])) ?> <span class="cell-sub"><?= e(duration_label($b['start_time'], $b['end_time'])) ?></span></td>
                <td data-label="Purpose"><?= e($b['purpose']) ?></td>
                <td data-label="Status"><?php render_status_badge($kind, $label); ?></td>
                <td data-label="Actions">
                  <div class="row-actions">
                    <a class="btn btn-outline btn-sm" href="edit-booking.php?id=<?= (int) $b['booking_id'] ?>">Edit</a>
                    <form method="post" action="my-bookings.php" data-confirm="Cancel booking #<?= (int) $b['booking_id'] ?> for <?= e($b['room_code']) ?> on <?= e(pretty_date($b['booking_date'])) ?>, <?= e(hm($b['start_time'])) ?>–<?= e(hm($b['end_time'])) ?>?" data-confirm-title="Cancel this booking?" data-confirm-ok="Yes, cancel it" data-confirm-cancel="Keep booking" data-confirm-danger>
                      <?= csrf_field() ?>
                      <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                      <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                    </form>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php else: ?>
        <div class="panel empty-state">
          <p>No active bookings. <a class="text-link" href="book.php">Book a room</a> to get started.</p>
        </div>
<?php endif; ?>

<?php if ($history): ?>
        <h2 class="subhead">Past and cancelled</h2>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Ref</th>
                <th scope="col">Room</th>
                <th scope="col">Date</th>
                <th scope="col">Time</th>
                <th scope="col">Purpose</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($history as $b): [$kind, $label] = booking_status_label($b); ?>
              <tr>
                <td data-label="Ref" class="mono">#<?= (int) $b['booking_id'] ?></td>
                <td data-label="Room">
                  <span class="cell-strong mono"><?= e($b['room_code']) ?></span>
                  <span class="cell-sub"><?= e($b['room_name']) ?></span>
                </td>
                <td data-label="Date"><?= e(pretty_date($b['booking_date'])) ?></td>
                <td data-label="Time" class="mono"><?= e(hm($b['start_time'])) ?> – <?= e(hm($b['end_time'])) ?></td>
                <td data-label="Purpose"><?= e($b['purpose']) ?></td>
                <td data-label="Status"><?php render_status_badge($kind, $label); ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

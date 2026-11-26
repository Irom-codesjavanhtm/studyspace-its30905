<?php
require __DIR__ . '/includes/app.php';

if (!current_user()) {
    redirect('login.php?next=' . urlencode(current_page_url()));
}

$bookingId = (int) ($_GET['id'] ?? 0);
$booking = $bookingId > 0 ? booking_find($bookingId) : null;
$back = admin_logged_in() && ($_GET['from'] ?? '') === 'admin' ? 'admin.php?tab=bookings' : 'my-bookings.php';

if ($booking === null || !owns_booking($booking)) {
    flash('error', 'Look up your bookings first, then choose Edit on the booking you want to change.');
    redirect('my-bookings.php');
}
if (!booking_is_active($booking)) {
    flash('error', 'Booking #' . $bookingId . ' is cancelled or already finished, so it cannot be edited.');
    redirect($back);
}

$rooms = rooms_all();
$errors = [];
$form = [
    'student_name' => $booking['student_name'],
    'student_id'   => $booking['student_id'],
    'email'        => $booking['email'],
    'room_id'      => (int) $booking['room_id'],
    'booking_date' => $booking['booking_date'],
    'start_time'   => hm($booking['start_time']),
    'end_time'     => hm($booking['end_time']),
    'attendees'    => (string) $booking['attendees'],
    'purpose'      => $booking['purpose'],
];

if (is_post()) {
    require_csrf('edit-booking.php?id=' . $bookingId);
    if (student_logged_in()) {
        $me = current_user();
        $_POST['student_name'] = $me['name'];
        $_POST['student_id'] = $me['username'];
        $_POST['email'] = $me['email'];
    } else {
        $_POST['student_id'] = $booking['student_id'];
    }
    [$clean, $errors, $room] = validate_booking($_POST);
    $form = $clean;

    if (!$errors) {
        $result = booking_save($clean, $bookingId);
        if ($result['ok']) {
            flash('success', sprintf('Booking #%d updated: %s on %s, %s–%s.', $bookingId, $room['room_code'], pretty_date($clean['booking_date']), $clean['start_time'], $clean['end_time']));
            redirect($back);
        }
        http_response_code(409);
        $conflictSlots = $result['conflicts'];
        $errors['start_time'] = 'This time overlaps an existing booking.';
        flash('error', 'Double booking prevented. ' . conflict_message($room, $conflictSlots));
    } else {
        http_response_code(422);
        flash('error', 'Please fix the highlighted fields and try again.');
    }
}

$pageTitle = 'Edit Booking #' . $bookingId;
$activeNav = 'bookings';
$pageId = 'edit';
$shell = 'app';
$pageScripts = ['booking'];
$mode = 'edit';
$lockIdentity = student_logged_in();
if ($lockIdentity) {
    $me = current_user();
    $form['student_name'] = $me['name'];
    $form['student_id'] = $me['username'];
    $form['email'] = $me['email'];
}
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section">
        <div class="page-head">
          <div>
            <p class="eyebrow eyebrow-accent">Edit / Booking #<?= $bookingId ?></p>
            <h1>Edit Booking</h1>
            <p class="page-sub">Change the room, time or details. Your own current slot does not count as a clash.</p>
          </div>
        </div>
<?php require __DIR__ . '/includes/booking-form.php'; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

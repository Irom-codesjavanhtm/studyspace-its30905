<?php
require __DIR__ . '/includes/app.php';

if (!current_user()) {
    redirect('login.php?next=' . urlencode(current_page_url()));
}

$rooms = rooms_all();
$errors = [];
$me = student_logged_in() ? current_user() : null;

$prefillDate = (string) ($_GET['date'] ?? today());
$form = [
    'student_name' => $me['name'] ?? '',
    'student_id'   => $me['username'] ?? ($_SESSION['student_lookup_id'] ?? ''),
    'email'        => $me['email'] ?? ($_SESSION['student_lookup_email'] ?? ''),
    'room_id'      => (int) ($_GET['room'] ?? 0),
    'booking_date' => valid_date($prefillDate) && $prefillDate >= today() ? $prefillDate : today(),
    'start_time'   => preg_match('/^\d{2}:\d{2}$/', (string) ($_GET['start'] ?? '')) ? $_GET['start'] : '',
    'end_time'     => preg_match('/^\d{2}:\d{2}$/', (string) ($_GET['end'] ?? '')) ? $_GET['end'] : '',
    'attendees'    => '',
    'purpose'      => '',
];

if (is_post()) {
    require_csrf('book.php');
    if ($me) {
        $_POST['student_name'] = $me['name'];
        $_POST['student_id'] = $me['username'];
        $_POST['email'] = $me['email'];
    }
    [$clean, $errors, $room] = validate_booking($_POST);
    $form = $clean;

    if (!$errors) {
        $result = booking_save($clean);
        if ($result['ok']) {
            $_SESSION['student_lookup'] = $clean['student_id'];
            $_SESSION['student_lookup_id'] = $clean['student_id'];
            $_SESSION['student_lookup_email'] = $clean['email'];
            flash('success', sprintf(
                'Booking #%d confirmed: %s on %s, %s–%s.',
                $result['id'], $room['room_code'], pretty_date($clean['booking_date']), $clean['start_time'], $clean['end_time']
            ));
            redirect('my-bookings.php?q=' . urlencode($clean['student_id']));
        }
        http_response_code(409);
        $conflictSlots = $result['conflicts'];
        $message = conflict_message($room, $conflictSlots);
        $errors['start_time'] = 'This time overlaps an existing booking.';
        flash('error', 'Double booking prevented. ' . $message);
    } else {
        http_response_code(422);
        flash('error', 'Please fix the highlighted fields and try again.');
    }
}

$pageTitle = 'Book a Room';
$activeNav = 'book';
$pageId = 'book';
$shell = 'app';
$pageScripts = ['booking'];
$mode = 'create';
$lockIdentity = $me !== null;
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section">
        <div class="page-head">
          <div>
            <p class="eyebrow eyebrow-accent">04 / Reservation</p>
            <h1>Book a Room</h1>
            <p class="page-sub"><?= $me ? 'Your name and student ID come from your account. Pick a room and a time. We check for clashes before saving.' : 'Tell us who the booking is for, pick a room and a time. We check for clashes before saving.' ?></p>
          </div>
        </div>
<?php require __DIR__ . '/includes/booking-form.php'; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

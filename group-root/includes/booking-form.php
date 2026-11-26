<?php
/**
 * Booking form used by book.php (create) and edit-booking.php (edit).
 *
 * @var array  $form
 * @var array  $errors
 * @var array  $rooms
 * @var string $mode
 * @var int    $bookingId
 * @var bool   $lockIdentity
 * @var array  $conflictSlots
 */
$mode = $mode ?? 'create';
$isEdit = $mode === 'edit';
$lockIdentity = $lockIdentity ?? false;
$conflictSlots = $conflictSlots ?? [];
$errors = $errors ?? [];
$rooms = $rooms ?? [];
$bookingId = $bookingId ?? 0;
$form = array_merge([
    'student_name' => '',
    'student_id'   => '',
    'email'        => '',
    'room_id'      => 0,
    'booking_date' => today(),
    'start_time'   => '',
    'end_time'     => '',
    'attendees'    => '',
    'purpose'      => '',
], $form ?? []);
$maxDate = date('Y-m-d', strtotime('+' . cfg('booking', 'max_days_ahead') . ' days'));
$startOptions = time_options(false);
$endOptions = array_slice(time_options(true), 1);
$roomsByFloor = [];
foreach ($rooms as $room) {
    $roomsByFloor[(int) $room['floor_no']][] = $room;
}
ksort($roomsByFloor);
$backHref = 'rooms.php';
$backLabel = 'Back to floor plan';
if ($isEdit) {
    $backHref = (($_GET['from'] ?? '') === 'admin') ? 'admin.php?tab=bookings' : 'my-bookings.php';
    $backLabel = (($_GET['from'] ?? '') === 'admin') ? 'Back to admin' : 'Back to my bookings';
}
?>
        <div class="booking-layout">
          <form class="panel form-card" method="post" action="<?= e($isEdit ? 'edit-booking.php?id=' . (int) $bookingId . (($_GET['from'] ?? '') === 'admin' ? '&from=admin' : '') : 'book.php') ?>" novalidate
            data-booking-form
            data-now-date="<?= e(today()) ?>"
            data-now-minutes="<?= now_minutes() ?>"
            data-open="<?= (int) cfg('booking', 'open_hour') ?>"
            data-close="<?= (int) cfg('booking', 'close_hour') ?>"
            data-max-hours="<?= (int) cfg('booking', 'max_hours') ?>"
            data-api="api/availability.php"
            data-except="<?= $isEdit ? (int) $bookingId : '' ?>">
            <?= csrf_field() ?>
            <p class="eyebrow eyebrow-accent">01 / Your details</p>
<?php if ($lockIdentity): ?>
            <p class="muted">These details come from your student account and stay tied to <?= e($form['student_id']) ?>.</p>
<?php endif; ?>
            <div class="form-grid">
              <div class="field">
                <label for="student_name">Student name</label>
                <input class="input" type="text" id="student_name" name="student_name" value="<?= e($form['student_name']) ?>" required minlength="2" maxlength="100" autocomplete="name" placeholder="e.g. Aisha Rahman"<?= $lockIdentity ? ' readonly' : '' ?> data-msg="Enter your full name (2 to 100 characters).">
                <?= field_error($errors, 'student_name') ?>
              </div>
              <div class="field">
                <label for="student_id">Student ID</label>
                <input class="input" type="text" id="student_id" name="student_id" value="<?= e($form['student_id']) ?>" required pattern="<?= $lockIdentity ? '039[0-9]{4}' : '[A-Za-z0-9]{5,12}' ?>" maxlength="12" placeholder="<?= $lockIdentity ? '0391234' : 'e.g. TU100231 or 0391234' ?>"<?= ($isEdit || $lockIdentity) ? ' readonly' : '' ?> data-msg="<?= $lockIdentity ? 'Student ID must look like 039XXXX, for example 0391234.' : 'Student ID must be 5 to 12 letters or digits.' ?>">
                <?= field_error($errors, 'student_id') ?>
              </div>
              <div class="field field-wide">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email" placeholder="you@example.edu"<?= $lockIdentity ? ' readonly' : '' ?> data-msg="Enter a valid email address.">
                <?= field_error($errors, 'email') ?>
              </div>
            </div>

            <p class="eyebrow eyebrow-accent">02 / Room and time</p>
            <div class="form-grid">
              <div class="field">
                <label for="room_id">Room</label>
                <select class="input" id="room_id" name="room_id" required data-msg="Please choose a room from the list.">
                  <option value="">Choose a room</option>
<?php foreach ($roomsByFloor as $floorNo => $floorRooms): ?>
                  <optgroup label="Level <?= (int) $floorNo ?>">
<?php foreach ($floorRooms as $room): ?>
                  <option value="<?= (int) $room['room_id'] ?>" data-capacity="<?= (int) $room['capacity'] ?>" data-code="<?= e($room['room_code']) ?>"<?= (int) $form['room_id'] === (int) $room['room_id'] ? ' selected' : '' ?><?= $room['status'] === 'out_of_order' ? ' disabled' : '' ?>><?= e($room['room_code'] . ' · ' . $room['room_name'] . ' (' . $room['capacity'] . ' seats)' . ($room['status'] === 'out_of_order' ? ' – out of order' : '')) ?></option>
<?php endforeach; ?>
                  </optgroup>
<?php endforeach; ?>
                </select>
                <?= field_error($errors, 'room_id') ?>
              </div>
              <div class="field">
                <label for="booking_date">Date</label>
                <input class="input" type="date" id="booking_date" name="booking_date" value="<?= e($form['booking_date']) ?>" required min="<?= e(today()) ?>" max="<?= e($maxDate) ?>" data-msg="Choose a date from today onwards.">
                <?= field_error($errors, 'booking_date') ?>
              </div>
              <div class="field">
                <label for="start_time">Start time</label>
                <select class="input" id="start_time" name="start_time" required data-msg="Choose a start time.">
                  <option value="">Start</option>
<?php foreach (time_groups($startOptions) as $part => $times): ?>
                  <optgroup label="<?= e($part) ?>">
<?php foreach ($times as $t): ?>
                  <option value="<?= e($t) ?>"<?= $form['start_time'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
                  </optgroup>
<?php endforeach; ?>
                </select>
                <?= field_error($errors, 'start_time') ?>
              </div>
              <div class="field">
                <label for="end_time">End time</label>
                <select class="input" id="end_time" name="end_time" required data-msg="Choose an end time.">
                  <option value="">End</option>
<?php foreach (time_groups($endOptions) as $part => $times): ?>
                  <optgroup label="<?= e($part) ?>">
<?php foreach ($times as $t): ?>
                  <option value="<?= e($t) ?>"<?= $form['end_time'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
                  </optgroup>
<?php endforeach; ?>
                </select>
                <?= field_error($errors, 'end_time') ?>
              </div>
              <div class="field">
                <label for="attendees">Number of people</label>
                <input class="input" type="number" id="attendees" name="attendees" value="<?= e((string) $form['attendees']) ?>" required min="1" max="60" inputmode="numeric" data-msg="Enter how many people will use the room.">
                <?= field_error($errors, 'attendees') ?>
              </div>
              <div class="field field-wide">
                <label for="purpose">Purpose</label>
                <textarea class="input" id="purpose" name="purpose" rows="3" required minlength="3" maxlength="255" placeholder="e.g. Group project meeting" data-msg="Describe the purpose in 3 to 255 characters."><?= e($form['purpose']) ?></textarea>
                <?= field_error($errors, 'purpose') ?>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn btn-dark btn-lg" type="submit"><?= $isEdit ? 'Save changes' : 'Confirm booking' ?></button>
              <a class="btn btn-ghost btn-lg" href="<?= e($backHref) ?>"><?= e($backLabel) ?></a>
            </div>
          </form>

          <aside class="side-stack">
            <section class="panel availability-panel" data-availability aria-live="polite">
              <p class="eyebrow eyebrow-accent">03 / Room availability</p>
              <p class="availability-empty" data-availability-empty>Choose a room and date to see which times are already taken.</p>
              <div hidden data-availability-body>
                <h2 class="panel-title" data-availability-title>R-000</h2>
                <p class="availability-sub" data-availability-sub></p>
                <ul class="busy-list" data-busy-list></ul>
                <p class="eyebrow">Free windows</p>
                <div class="pill-group" data-free-windows></div>
              </div>
<?php if ($conflictSlots):
    $conflictRoom = null;
    foreach ($rooms as $candidate) {
        if ((int) $candidate['room_id'] === (int) $form['room_id']) {
            $conflictRoom = $candidate;
            break;
        }
    }
?>
              <p class="availability-warning" data-conflict-warning><?= e($conflictRoom ? conflict_message($conflictRoom, $conflictSlots) : 'This time overlaps an existing booking.') ?></p>
<?php else: ?>
              <p class="availability-warning" data-conflict-warning hidden></p>
<?php endif; ?>
            </section>
            <section class="panel rules-panel">
              <p class="eyebrow eyebrow-accent">04 / Booking rules</p>
              <ul class="rule-list">
                <li>Open <?= e(sprintf('%02d:00', cfg('booking', 'open_hour'))) ?> to <?= e(sprintf('%02d:00', cfg('booking', 'close_hour'))) ?>, on the hour or half hour.</li>
                <li>Up to <?= (int) cfg('booking', 'max_hours') ?> hours per booking.</li>
                <li>Number of people cannot exceed the room's seats.</li>
                <li>A room cannot be booked twice for overlapping times.</li>
              </ul>
            </section>
          </aside>
        </div>

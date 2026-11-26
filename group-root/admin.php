<?php
require __DIR__ . '/includes/app.php';

$tab = (string) ($_GET['tab'] ?? 'overview');
if (!in_array($tab, ['overview', 'rooms', 'bookings', 'desks'], true)) {
    $tab = 'overview';
}

$roomErrors = [];
$roomForm = null;

if (!admin_logged_in()) {
    require_role('admin');
}

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');
    require_csrf('admin.php');

    if ($action === 'logout') {
        logout_user();
        flash('success', 'You have been signed out.');
        redirect('login.php');
    } elseif ($action === 'room_save') {
        $roomId = (int) ($_POST['room_id'] ?? 0) ?: null;
        [$data, $roomErrors] = validate_room($_POST, $roomId);
        if ($roomId !== null && room_find($roomId) === null) {
            flash('error', 'That room no longer exists.');
            redirect('admin.php?tab=rooms');
        }
        if (!$roomErrors) {
            if ($roomId === null) {
                room_insert($data);
                flash('success', 'Room ' . $data['room_code'] . ' (' . $data['room_name'] . ') was added.');
            } else {
                room_update($roomId, $data);
                flash('success', 'Room ' . $data['room_code'] . ' was updated.');
            }
            redirect('admin.php?tab=rooms');
        }
        http_response_code(422);
        $tab = 'rooms';
        $roomForm = $data + ['room_id' => $roomId];
        flash('error', 'Please fix the highlighted fields and save again.');
    } elseif ($action === 'room_delete') {
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $room = room_find($roomId);
        if ($room === null) {
            flash('error', 'That room no longer exists.');
        } else {
            $removed = room_booking_count($roomId);
            room_delete($roomId);
            flash('success', 'Room ' . $room['room_code'] . ' was deleted' . ($removed ? ' together with ' . $removed . ' booking(s).' : '.'));
        }
        redirect('admin.php?tab=rooms');
    } elseif ($action === 'booking_cancel' || $action === 'booking_delete') {
        $id = (int) ($_POST['booking_id'] ?? 0);
        $booking = booking_find($id);
        $back = 'admin.php?tab=bookings&' . http_build_query(array_filter([
            'date' => $_POST['f_date'] ?? '', 'room_id' => $_POST['f_room'] ?? '',
            'status' => $_POST['f_status'] ?? '', 'q' => $_POST['f_q'] ?? '',
        ]));
        if ($booking === null) {
            flash('error', 'That booking no longer exists.');
        } elseif ($action === 'booking_cancel') {
            booking_cancel($id);
            flash('success', 'Booking #' . $id . ' (' . $booking['room_code'] . ') was cancelled.');
        } else {
            booking_delete($id);
            flash('success', 'Booking #' . $id . ' (' . $booking['room_code'] . ') was permanently deleted.');
        }
        redirect($back);
    } elseif ($action === 'desk_status') {
        $deskId = (int) ($_POST['desk_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $desk = quiet_desk_find($deskId);
        if ($desk === null) {
            flash('error', 'That quiet hall desk no longer exists.');
        } elseif (!quiet_desk_set_status($deskId, $status)) {
            flash('error', 'Choose available, occupied, empty or out of order for that desk.');
        } else {
            flash('success', 'Desk ' . $desk['desk_code'] . ' is now ' . strtolower(quiet_desk_status_label($status)) . '. The floor plan uses this status.');
        }
        redirect('admin.php?tab=desks');
    }
}

$pageTitle = 'Admin';
$activeNav = 'admin';
$pageId = 'admin';
$shell = 'app';
$pageScripts = ['admin'];
require __DIR__ . '/includes/header.php';

$overview = counts_overview();
?>
      <section class="site-container page-section">
        <div class="page-head">
          <div>
            <p class="eyebrow eyebrow-accent">Admin / Control panel</p>
            <h1>Admin panel</h1>
            <p class="page-sub">Manage study rooms, quiet hall desks and every booking.</p>
          </div>
        </div>

        <nav class="tab-bar" aria-label="Admin sections">
          <a class="tab<?= $tab === 'overview' ? ' is-active' : '' ?>" href="admin.php?tab=overview">Overview</a>
          <a class="tab<?= $tab === 'rooms' ? ' is-active' : '' ?>" href="admin.php?tab=rooms">Rooms</a>
          <a class="tab<?= $tab === 'desks' ? ' is-active' : '' ?>" href="admin.php?tab=desks">Quiet hall</a>
          <a class="tab<?= $tab === 'bookings' ? ' is-active' : '' ?>" href="admin.php?tab=bookings">All bookings</a>
        </nav>

<?php if ($tab === 'overview'): ?>
        <div class="kpi-grid">
          <div class="panel kpi">
            <p class="eyebrow">Rooms</p>
            <p class="kpi-value"><?= $overview['rooms'] ?></p>
            <p class="kpi-note"><?= $overview['broken'] ?> out of order</p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">Booked today</p>
            <p class="kpi-value"><?= $overview['today'] ?></p>
            <p class="kpi-note"><a class="text-link" href="summary.php">Daily summary</a></p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">Upcoming</p>
            <p class="kpi-value"><?= $overview['upcoming'] ?></p>
            <p class="kpi-note">confirmed from today</p>
          </div>
          <div class="panel kpi">
            <p class="eyebrow">All bookings</p>
            <p class="kpi-value"><?= $overview['all'] ?></p>
            <p class="kpi-note">including cancelled</p>
          </div>
        </div>
        <div class="admin-shortcuts">
          <a class="feature-card" href="admin.php?tab=rooms"><span class="feature-num">01</span><h3>Manage rooms</h3><p>Add, update or delete study rooms and mark them out of order.</p></a>
          <a class="feature-card" href="admin.php?tab=desks"><span class="feature-num">02</span><h3>Quiet hall desks</h3><p>Mark each walk-in desk available, occupied or empty. The floor plan reads this table.</p></a>
          <a class="feature-card" href="admin.php?tab=bookings"><span class="feature-num">03</span><h3>Review bookings</h3><p>Filter every booking by date, room, status or student.</p></a>
        </div>

<?php elseif ($tab === 'rooms'):
    $editId = (int) ($_GET['edit'] ?? 0);
    $editing = $roomForm ?? ($editId ? room_find($editId) : null);
    $form = $editing ?: ['room_id' => null, 'room_code' => '', 'room_name' => '', 'room_type' => 'group', 'floor_no' => 1, 'capacity' => 4, 'facilities' => '', 'description' => '', 'status' => 'available'];
    $isEditing = !empty($form['room_id']);
    $allRooms = rooms_with_counts();
?>
<?php
    $roomPanelOpen = $isEditing || !empty($roomErrors);
?>
        <div class="admin-grid">
          <div class="add-panel<?= $roomPanelOpen ? ' is-open' : '' ?>" data-add-panel<?= $roomPanelOpen ? ' data-start-open' : '' ?>>
            <button class="add-panel-trigger" type="button" aria-expanded="<?= $roomPanelOpen ? 'true' : 'false' ?>" aria-controls="room-form-panel" id="room-form-toggle">
              <span class="add-panel-plus" aria-hidden="true"></span>
              <span class="add-panel-label" data-label-closed="Add room" data-label-open="<?= $isEditing ? 'Hide edit form' : 'Hide form' ?>"><?= $isEditing ? 'Editing room' : 'Add room' ?></span>
            </button>
            <div class="add-panel-body" id="room-form-panel" role="region" aria-labelledby="room-form-toggle">
              <div class="add-panel-inner">
                <form class="panel form-card add-panel-card" method="post" action="admin.php?tab=rooms" novalidate data-validate>
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="room_save">
                  <input type="hidden" name="room_id" value="<?= $isEditing ? (int) $form['room_id'] : '' ?>">
                  <p class="eyebrow eyebrow-accent"><?= $isEditing ? 'Update room' : 'Add a room' ?></p>
                  <h2 class="panel-title"><?= $isEditing ? 'Edit ' . e($form['room_code']) : 'New study room' ?></h2>
                  <div class="form-grid">
                    <div class="field">
                      <label for="room_code">Room code</label>
                      <input class="input" type="text" id="room_code" name="room_code" value="<?= e($form['room_code']) ?>" required pattern="[A-Za-z0-9\-]{2,10}" maxlength="10" placeholder="R-105" data-msg="Use 2 to 10 letters, digits or hyphens, e.g. R-105.">
                      <?= field_error($roomErrors, 'room_code') ?>
                    </div>
                    <div class="field">
                      <label for="room_name">Room name</label>
                      <input class="input" type="text" id="room_name" name="room_name" value="<?= e($form['room_name']) ?>" required minlength="2" maxlength="80" placeholder="Garden Nook" data-msg="Room name must be 2 to 80 characters.">
                      <?= field_error($roomErrors, 'room_name') ?>
                    </div>
                    <div class="field">
                      <label for="room_type">Type</label>
                      <select class="input" id="room_type" name="room_type" required data-msg="Choose a room type.">
<?php foreach (['group' => 'Group room', 'quiet' => 'Quiet room', 'seminar' => 'Seminar room'] as $value => $label): ?>
                        <option value="<?= $value ?>"<?= $form['room_type'] === $value ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                      </select>
                      <?= field_error($roomErrors, 'room_type') ?>
                    </div>
                    <div class="field">
                      <label for="status">Status</label>
                      <select class="input" id="status" name="status" required data-msg="Choose a status.">
                        <option value="available"<?= $form['status'] === 'available' ? ' selected' : '' ?>>Available</option>
                        <option value="out_of_order"<?= $form['status'] === 'out_of_order' ? ' selected' : '' ?>>Out of order</option>
                      </select>
                      <?= field_error($roomErrors, 'status') ?>
                    </div>
                    <div class="field">
                      <label for="floor_no">Level</label>
                      <input class="input" type="number" id="floor_no" name="floor_no" value="<?= e((string) $form['floor_no']) ?>" required min="1" max="9" data-msg="Level must be a number from 1 to 9.">
                      <?= field_error($roomErrors, 'floor_no') ?>
                    </div>
                    <div class="field">
                      <label for="capacity">Seats</label>
                      <input class="input" type="number" id="capacity" name="capacity" value="<?= e((string) $form['capacity']) ?>" required min="1" max="60" data-msg="Seats must be a number from 1 to 60.">
                      <?= field_error($roomErrors, 'capacity') ?>
                    </div>
                    <div class="field field-wide">
                      <label for="facilities">Facilities <span class="label-hint">comma separated</span></label>
                      <input class="input" type="text" id="facilities" name="facilities" value="<?= e($form['facilities']) ?>" maxlength="255" placeholder="Whiteboard, Display, Power x4" data-msg="Facilities list is too long.">
                      <?= field_error($roomErrors, 'facilities') ?>
                    </div>
                    <div class="field field-wide">
                      <label for="description">Description</label>
                      <textarea class="input" id="description" name="description" rows="2" maxlength="255" placeholder="Short description shown to students" data-msg="Description is too long."><?= e($form['description']) ?></textarea>
                      <?= field_error($roomErrors, 'description') ?>
                    </div>
                  </div>
                  <div class="form-actions">
                    <button class="btn btn-dark" type="submit"><?= $isEditing ? 'Save changes' : 'Add room' ?></button>
<?php if ($isEditing): ?>
                    <a class="btn btn-ghost" href="admin.php?tab=rooms">Cancel edit</a>
<?php endif; ?>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="table-wrap panel">
            <table class="data-table responsive-table">
              <thead>
                <tr>
                  <th scope="col">Room</th>
                  <th scope="col">Type</th>
                  <th scope="col">Level</th>
                  <th scope="col">Seats</th>
                  <th scope="col">Status</th>
                  <th scope="col">Bookings</th>
                  <th scope="col"><span class="sr-label">Actions</span></th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($allRooms as $room): ?>
                <tr>
                  <td data-label="Room">
                    <span class="cell-strong mono"><?= e($room['room_code']) ?></span>
                    <span class="cell-sub"><?= e($room['room_name']) ?></span>
                  </td>
                  <td data-label="Type"><?= e(type_label($room['room_type'])) ?></td>
                  <td data-label="Level"><?= (int) $room['floor_no'] ?></td>
                  <td data-label="Seats"><?= (int) $room['capacity'] ?></td>
                  <td data-label="Status"><?php render_status_badge($room['status'] === 'available' ? 'available' : 'out_of_order', $room['status'] === 'available' ? 'Available' : 'Out of order'); ?></td>
                  <td data-label="Bookings"><?= (int) $room['booking_total'] ?></td>
                  <td data-label="Actions">
                    <div class="row-actions">
                      <a class="btn btn-outline btn-sm" href="admin.php?tab=rooms&amp;edit=<?= (int) $room['room_id'] ?>">Edit</a>
                      <form method="post" action="admin.php?tab=rooms" data-confirm="Delete room <?= e($room['room_code']) ?> (<?= e($room['room_name']) ?>)? <?= (int) $room['booking_total'] ? 'Its ' . (int) $room['booking_total'] . ' booking(s) will be deleted too. ' : '' ?>This cannot be undone." data-confirm-title="Delete this room?" data-confirm-ok="Delete room" data-confirm-cancel="Keep room" data-confirm-danger>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="room_delete">
                        <input type="hidden" name="room_id" value="<?= (int) $room['room_id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

<?php elseif ($tab === 'desks'): ?>
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
<?php foreach (quiet_desks_all() as $desk): ?>
              <tr>
                <td data-label="Desk"><span class="cell-strong mono"><?= e($desk['desk_code']) ?></span></td>
                <td data-label="Level"><?= (int) $desk['floor_no'] ?></td>
                <td data-label="Spot"><?= (int) $desk['position_no'] ?></td>
                <td data-label="Note"><?= e($desk['note']) ?></td>
                <td data-label="Status">
                  <form class="row-actions" method="post" action="admin.php?tab=desks">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="desk_status">
                    <input type="hidden" name="desk_id" value="<?= (int) $desk['desk_id'] ?>">
                    <select class="input input-compact" name="status" aria-label="Status for <?= e($desk['desk_code']) ?>">
<?php foreach (['available' => 'Available', 'occupied' => 'Occupied', 'empty' => 'Empty', 'out_of_order' => 'Out of order'] as $value => $label): ?>
                      <option value="<?= $value ?>"<?= $desk['status'] === $value ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline btn-sm" type="submit">Save</button>
                  </form>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>

<?php else:
    $filters = [
        'date'    => valid_date((string) ($_GET['date'] ?? '')) ? $_GET['date'] : '',
        'room_id' => (int) ($_GET['room_id'] ?? 0) ?: '',
        'status'  => in_array($_GET['status'] ?? '', ['confirmed', 'cancelled'], true) ? $_GET['status'] : '',
        'q'       => trim((string) ($_GET['q'] ?? '')),
    ];
    $list = bookings_filtered($filters);
    $roomOptions = rooms_all();
?>
<?php
    $filtersActive = $filters['date'] !== '' || $filters['room_id'] !== '' || $filters['status'] !== '' || $filters['q'] !== '';
?>
        <form class="filter-bar" method="get" action="admin.php" data-filter-bar>
          <input type="hidden" name="tab" value="bookings">
          <div class="filter-bar-top">
            <div class="filter-search">
              <label class="sr-label" for="f-q">Search students</label>
              <span class="filter-search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
              </span>
              <input class="input filter-search-input" type="search" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by name, ID or email" maxlength="120">
            </div>
            <button class="btn btn-dark btn-sm" type="submit">Search</button>
          </div>
          <div class="filter-bar-row">
            <div class="filter-controls">
              <div class="filter-control">
                <label for="f-date">Date</label>
                <input class="input input-compact" type="date" id="f-date" name="date" value="<?= e($filters['date']) ?>" data-filter-change>
              </div>
              <div class="filter-control">
                <label for="f-room">Room</label>
                <select class="input input-compact" id="f-room" name="room_id" data-filter-change>
                  <option value="">All rooms</option>
<?php foreach ($roomOptions as $room): ?>
                  <option value="<?= (int) $room['room_id'] ?>"<?= (int) $filters['room_id'] === (int) $room['room_id'] ? ' selected' : '' ?>><?= e($room['room_code']) ?></option>
<?php endforeach; ?>
                </select>
              </div>
              <div class="filter-control filter-status" role="group" aria-label="Status">
                <span class="filter-control-label">Status</span>
                <div class="seg-control">
                  <label class="seg-option<?= $filters['status'] === '' ? ' is-active' : '' ?>">
                    <input type="radio" name="status" value=""<?= $filters['status'] === '' ? ' checked' : '' ?> data-filter-change>
                    <span>All</span>
                  </label>
                  <label class="seg-option<?= $filters['status'] === 'confirmed' ? ' is-active' : '' ?>">
                    <input type="radio" name="status" value="confirmed"<?= $filters['status'] === 'confirmed' ? ' checked' : '' ?> data-filter-change>
                    <span>Confirmed</span>
                  </label>
                  <label class="seg-option<?= $filters['status'] === 'cancelled' ? ' is-active' : '' ?>">
                    <input type="radio" name="status" value="cancelled"<?= $filters['status'] === 'cancelled' ? ' checked' : '' ?> data-filter-change>
                    <span>Cancelled</span>
                  </label>
                </div>
              </div>
            </div>
            <div class="filter-bar-aside">
              <p class="filter-count"><?= count($list) ?> booking<?= count($list) === 1 ? '' : 's' ?></p>
<?php if ($filtersActive): ?>
              <a class="filter-clear" href="admin.php?tab=bookings">Clear</a>
<?php endif; ?>
            </div>
          </div>
        </form>
<?php if (!$list): ?>
        <div class="panel empty-state">
          <p>No bookings match these filters.</p>
        </div>
<?php else: ?>
        <div class="table-wrap panel">
          <table class="data-table responsive-table">
            <thead>
              <tr>
                <th scope="col">Ref</th>
                <th scope="col">Student</th>
                <th scope="col">Room</th>
                <th scope="col">When</th>
                <th scope="col">People</th>
                <th scope="col">Purpose</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-label">Actions</span></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($list as $b): [$kind, $label] = booking_status_label($b); ?>
              <tr>
                <td data-label="Ref" class="mono">#<?= (int) $b['booking_id'] ?></td>
                <td data-label="Student">
                  <span class="cell-strong"><?= e($b['student_name']) ?></span>
                  <span class="cell-sub mono"><?= e($b['student_id']) ?></span>
                  <span class="cell-sub"><?= e($b['email']) ?></span>
                </td>
                <td data-label="Room"><span class="cell-strong mono"><?= e($b['room_code']) ?></span></td>
                <td data-label="When">
                  <span class="cell-strong"><?= e(pretty_date($b['booking_date'])) ?></span>
                  <span class="cell-sub mono"><?= e(hm($b['start_time'])) ?> – <?= e(hm($b['end_time'])) ?></span>
                </td>
                <td data-label="People"><?= (int) $b['attendees'] ?></td>
                <td data-label="Purpose"><?= e($b['purpose']) ?></td>
                <td data-label="Status"><?php render_status_badge($kind, $label); ?></td>
                <td data-label="Actions">
                  <div class="row-actions">
<?php if (booking_is_active($b)): ?>
                    <a class="btn btn-outline btn-sm" href="edit-booking.php?id=<?= (int) $b['booking_id'] ?>&amp;from=admin">Edit</a>
                    <form method="post" action="admin.php?tab=bookings" data-confirm="Cancel booking #<?= (int) $b['booking_id'] ?> for <?= e($b['student_name']) ?> (<?= e($b['room_code']) ?>, <?= e(pretty_date($b['booking_date'])) ?>)?" data-confirm-title="Cancel this booking?" data-confirm-ok="Yes, cancel it" data-confirm-cancel="Keep booking" data-confirm-danger>
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="booking_cancel">
                      <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                      <input type="hidden" name="f_date" value="<?= e($filters['date']) ?>">
                      <input type="hidden" name="f_room" value="<?= e((string) $filters['room_id']) ?>">
                      <input type="hidden" name="f_status" value="<?= e($filters['status']) ?>">
                      <input type="hidden" name="f_q" value="<?= e($filters['q']) ?>">
                      <button class="btn btn-ghost btn-sm" type="submit">Cancel</button>
                    </form>
<?php endif; ?>
                    <form method="post" action="admin.php?tab=bookings" data-confirm="Permanently delete booking #<?= (int) $b['booking_id'] ?>? This removes the record from the database." data-confirm-title="Delete this booking?" data-confirm-ok="Delete booking" data-confirm-cancel="Keep booking" data-confirm-danger>
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="booking_delete">
                      <input type="hidden" name="booking_id" value="<?= (int) $b['booking_id'] ?>">
                      <input type="hidden" name="f_date" value="<?= e($filters['date']) ?>">
                      <input type="hidden" name="f_room" value="<?= e((string) $filters['room_id']) ?>">
                      <input type="hidden" name="f_status" value="<?= e($filters['status']) ?>">
                      <input type="hidden" name="f_q" value="<?= e($filters['q']) ?>">
                      <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

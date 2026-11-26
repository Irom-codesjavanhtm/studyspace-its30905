<?php
declare(strict_types=1);

/**
 * Validate and normalise booking input.
 *
 * @return array{0: array, 1: array, 2: ?array} [clean values, errors keyed by field, room row]
 */
function validate_booking(array $in): array
{
    $errors = [];
    $d = [
        'student_name' => accept_name((string) ($in['student_name'] ?? '')),
        'student_id'   => accept_id((string) ($in['student_id'] ?? '')),
        'email'        => accept_email((string) ($in['email'] ?? '')),
        'room_id'      => (int) ($in['room_id'] ?? 0),
        'booking_date' => trim((string) ($in['booking_date'] ?? '')),
        'start_time'   => accept_time((string) ($in['start_time'] ?? '')),
        'end_time'     => accept_time((string) ($in['end_time'] ?? '')),
        'attendees'    => trim((string) ($in['attendees'] ?? '')),
        'purpose'      => accept_name((string) ($in['purpose'] ?? '')),
    ];

    $nameLen = mb_strlen($d['student_name']);
    if ($nameLen < 2 || $nameLen > 100) {
        $errors['student_name'] = 'Enter your full name (2 to 100 characters).';
    } elseif (!preg_match("/^[\\p{L}\\p{M}' .-]+$/u", $d['student_name'])) {
        $errors['student_name'] = 'Names can only contain letters, spaces, apostrophes, dots and hyphens.';
    }

    if (!preg_match('/^[A-Z0-9]{5,12}$/', $d['student_id'])) {
        $errors['student_id'] = 'Student ID must be 5 to 12 letters or digits, e.g. TU100231.';
    }

    if ($d['email'] === '' || mb_strlen($d['email']) > 120 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $room = $d['room_id'] > 0 ? room_find($d['room_id']) : null;
    if ($room === null) {
        $errors['room_id'] = 'Please choose a room from the list.';
    } elseif ($room['status'] === 'out_of_order') {
        $errors['room_id'] = $room['room_code'] . ' is out of order and cannot be booked.';
    }

    $dateOk = false;
    if (!valid_date($d['booking_date'])) {
        $errors['booking_date'] = 'Choose a valid date.';
    } elseif ($d['booking_date'] < today()) {
        $errors['booking_date'] = 'Bookings cannot be made for a past date.';
    } elseif ($d['booking_date'] > date('Y-m-d', strtotime('+' . cfg('booking', 'max_days_ahead') . ' days'))) {
        $errors['booking_date'] = 'Bookings open up to ' . cfg('booking', 'max_days_ahead') . ' days ahead.';
    } else {
        $dateOk = true;
    }

    $open = cfg('booking', 'open_hour') * 60;
    $close = cfg('booking', 'close_hour') * 60;
    $maxMinutes = cfg('booking', 'max_hours') * 60;
    $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
    $startOk = preg_match($timeRe, $d['start_time']) === 1;
    $endOk = preg_match($timeRe, $d['end_time']) === 1;

    if (!$startOk) {
        $errors['start_time'] = 'Choose a start time.';
    }
    if (!$endOk) {
        $errors['end_time'] = 'Choose an end time.';
    }
    if ($startOk && $endOk) {
        $s = to_minutes($d['start_time']);
        $en = to_minutes($d['end_time']);
        if ($s % 30 !== 0 || $en % 30 !== 0) {
            $errors['end_time'] = 'Times must be on the hour or half hour.';
        } elseif ($s < $open || $en > $close) {
            $errors['start_time'] = sprintf('Rooms are open %s to %s.', from_minutes($open), from_minutes($close));
        } elseif ($en <= $s) {
            $errors['end_time'] = 'End time must be after the start time.';
        } elseif ($en - $s > $maxMinutes) {
            $errors['end_time'] = 'A single booking can last at most ' . cfg('booking', 'max_hours') . ' hours.';
        } elseif ($dateOk && $d['booking_date'] === today() && $s < now_minutes()) {
            $errors['start_time'] = 'That start time has already passed.';
        }
    }

    if (!ctype_digit($d['attendees']) || (int) $d['attendees'] < 1) {
        $errors['attendees'] = 'Enter how many people will use the room (at least 1).';
    } elseif ($room !== null && (int) $d['attendees'] > (int) $room['capacity']) {
        $errors['attendees'] = $room['room_code'] . ' seats ' . $room['capacity'] . ' people at most.';
    } else {
        $d['attendees'] = (int) $d['attendees'];
    }

    $purposeLen = mb_strlen($d['purpose']);
    if ($purposeLen < 3 || $purposeLen > 255) {
        $errors['purpose'] = 'Describe the purpose in 3 to 255 characters.';
    }

    return [$d, $errors, $room];
}

/** Human readable description of overlapping bookings returned by booking_conflicts(). */
function conflict_message(array $room, array $conflicts): string
{
    $slots = array_map(fn ($c) => hm($c['start_time']) . '–' . hm($c['end_time']), $conflicts);
    return $room['room_code'] . ' is already booked ' . implode(', ', $slots)
         . ' on that day. Choose a different time or room.';
}

/**
 * Validate admin room form input.
 *
 * @return array{0: array, 1: array}
 */
function validate_room(array $in, ?int $roomId = null): array
{
    $errors = [];
    $d = [
        'room_code'   => strtoupper(trim((string) ($in['room_code'] ?? ''))),
        'room_name'   => trim((string) ($in['room_name'] ?? '')),
        'room_type'   => (string) ($in['room_type'] ?? ''),
        'floor_no'    => trim((string) ($in['floor_no'] ?? '')),
        'capacity'    => trim((string) ($in['capacity'] ?? '')),
        'facilities'  => trim((string) ($in['facilities'] ?? '')),
        'description' => trim((string) ($in['description'] ?? '')),
        'status'      => (string) ($in['status'] ?? ''),
    ];

    if (!preg_match('/^[A-Z0-9-]{2,10}$/', $d['room_code'])) {
        $errors['room_code'] = 'Room code must be 2 to 10 characters (letters, digits, hyphen), e.g. R-105.';
    } elseif (room_code_exists($d['room_code'], $roomId)) {
        $errors['room_code'] = 'Another room already uses code ' . $d['room_code'] . '.';
    }

    $nameLen = mb_strlen($d['room_name']);
    if ($nameLen < 2 || $nameLen > 80) {
        $errors['room_name'] = 'Room name must be 2 to 80 characters.';
    }
    if (!in_array($d['room_type'], ['quiet', 'group', 'seminar'], true)) {
        $errors['room_type'] = 'Choose a room type.';
    }
    if (!ctype_digit($d['floor_no']) || (int) $d['floor_no'] < 1 || (int) $d['floor_no'] > 9) {
        $errors['floor_no'] = 'Floor must be a number from 1 to 9.';
    }
    if (!ctype_digit($d['capacity']) || (int) $d['capacity'] < 1 || (int) $d['capacity'] > 60) {
        $errors['capacity'] = 'Capacity must be a number from 1 to 60.';
    }
    if (mb_strlen($d['facilities']) > 255) {
        $errors['facilities'] = 'Facilities list is too long (255 characters max).';
    }
    if (mb_strlen($d['description']) > 255) {
        $errors['description'] = 'Description is too long (255 characters max).';
    }
    if (!in_array($d['status'], ['available', 'out_of_order'], true)) {
        $errors['status'] = 'Choose a status.';
    }

    $d['floor_no'] = (int) $d['floor_no'];
    $d['capacity'] = (int) $d['capacity'];

    return [$d, $errors];
}

/** Escape-safe error line shown under a form control. */
function field_error(array $errors, string $field): string
{
    $id = e($field) . '-error';
    $text = isset($errors[$field]) ? e($errors[$field]) : '';
    $pop = $text !== '' ? ' is-pop' : '';
    return '<p class="field-error' . $pop . '" id="' . $id . '" role="alert">' . $text . '</p>';
}

/**
 * Validate a new student account. The role is not accepted from the form.
 *
 * @return array{0: array, 1: array}
 */
function validate_student_registration(array $in): array
{
    $errors = [];
    $d = [
        'username'         => accept_id((string) ($in['username'] ?? '')),
        'full_name'        => accept_name((string) ($in['full_name'] ?? '')),
        'email'            => accept_email((string) ($in['email'] ?? '')),
        'password'         => (string) ($in['password'] ?? ''),
        'password_confirm' => (string) ($in['password_confirm'] ?? ''),
    ];

    if (!valid_student_id($d['username'])) {
        $errors['username'] = 'Student ID must look like 039XXXX, for example 0391234.';
    } elseif (user_find_by_username($d['username']) !== null) {
        $errors['username'] = 'That student ID is already registered. Sign in instead.';
    }

    $nameLen = mb_strlen($d['full_name']);
    if ($nameLen < 2 || $nameLen > 100) {
        $errors['full_name'] = 'Enter your full name (2 to 100 characters).';
    } elseif (!preg_match("/^[\\p{L}\\p{M}' .-]+$/u", $d['full_name'])) {
        $errors['full_name'] = 'Names can only contain letters, spaces, apostrophes, dots and hyphens.';
    }

    if ($d['email'] === '' || mb_strlen($d['email']) > 120 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (user_find_by_email($d['email']) !== null) {
        $errors['email'] = 'That email is already registered. Sign in instead.';
    }

    $passwordLen = strlen($d['password']);
    if ($passwordLen < 8 || $passwordLen > 72) {
        $errors['password'] = 'Choose a password of 8 to 72 characters.';
    } elseif ($d['password'] === $d['username']) {
        $errors['password'] = 'Do not use your student ID as the password.';
    }

    if ($d['password_confirm'] === '' || !hash_equals($d['password'], $d['password_confirm'])) {
        $errors['password_confirm'] = 'Enter the same password again.';
    }

    return [$d, $errors];
}

/**
 * Validate profile settings updates for the signed-in account.
 *
 * @return array{0: array, 1: array}
 */
function validate_profile_update(array $in, array $currentUser, ?array $dbUser): array
{
    $errors = [];
    $d = [
        'full_name'        => accept_name((string) ($in['full_name'] ?? '')),
        'email'            => accept_email((string) ($in['email'] ?? '')),
        'current_password' => (string) ($in['current_password'] ?? ''),
        'password'         => (string) ($in['password'] ?? ''),
        'password_confirm' => (string) ($in['password_confirm'] ?? ''),
    ];

    $nameLen = mb_strlen($d['full_name']);
    if ($nameLen < 2 || $nameLen > 100) {
        $errors['full_name'] = 'Enter your full name (2 to 100 characters).';
    } elseif (!preg_match("/^[\\p{L}\\p{M}' .-]+$/u", $d['full_name'])) {
        $errors['full_name'] = 'Names can only contain letters, spaces, apostrophes, dots and hyphens.';
    }

    if ($d['email'] === '' || mb_strlen($d['email']) > 120 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $other = user_find_by_email($d['email']);
        if ($other !== null && (int) $other['user_id'] !== (int) $currentUser['id']) {
            $errors['email'] = 'That email is already used by another account.';
        }
    }

    $changingPassword = $d['password'] !== '' || $d['password_confirm'] !== '' || $d['current_password'] !== '';
    if ($changingPassword) {
        if ($dbUser === null || !password_verify($d['current_password'], (string) $dbUser['password_hash'])) {
            $errors['current_password'] = 'Enter your current password to change it.';
        }
        $passwordLen = strlen($d['password']);
        if ($passwordLen < 8 || $passwordLen > 72) {
            $errors['password'] = 'Choose a password of 8 to 72 characters.';
        } elseif ($d['password'] === (string) $currentUser['username']) {
            $errors['password'] = 'Do not use your username as the password.';
        }
        if ($d['password_confirm'] === '' || !hash_equals($d['password'], $d['password_confirm'])) {
            $errors['password_confirm'] = 'Enter the same password again.';
        }
    }

    return [$d, $errors];
}

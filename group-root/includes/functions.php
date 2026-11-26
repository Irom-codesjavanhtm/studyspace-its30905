<?php
declare(strict_types=1);

if (!function_exists('mb_strlen')) {
    /** Fallback so the app runs on PHP builds without the mbstring extension. */
    function mb_strlen(string $value): int
    {
        return (int) preg_match_all('/./us', $value);
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cfg(string $group, string $key)
{
    return $GLOBALS['config'][$group][$key];
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    return $sent !== '' && hash_equals(csrf_token(), $sent);
}

/**
 * Aborts a POST request with a flash message when the CSRF token is missing or wrong.
 */
function require_csrf(string $backTo): void
{
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        redirect($backTo);
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------- Accounts and roles ---------- */

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function admin_logged_in(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function student_logged_in(): bool
{
    return (current_user()['role'] ?? '') === 'student';
}

/** Student IDs are 039 followed by exactly four digits, for example 0391234. */
function valid_student_id(string $id): bool
{
    return preg_match('/^039\d{4}$/', $id) === 1;
}

function safe_next(string $next): string
{
    $next = str_replace(["\r", "\n", "\\"], '', $next);
    if (!preg_match('/^[a-z0-9_-]+\.php(?:\?[a-z0-9_=&%.:~+-]*)?$/i', $next)) {
        return '';
    }
    return $next;
}

function current_page_url(): string
{
    $page = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return safe_next($page . ($qs !== '' ? '?' . $qs : '')) ?: 'index.php';
}

/**
 * Start a session from a users row. The role stored on the row is the only role
 * this account can have; nothing in the request can change it.
 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $role = $user['role'] === 'admin' ? 'admin' : 'student';
    $_SESSION['user'] = [
        'id'       => (int) $user['user_id'],
        'username' => (string) $user['username'],
        'name'     => (string) $user['full_name'],
        'email'    => (string) $user['email'],
        'role'     => $role,
    ];
    $_SESSION['is_admin'] = $role === 'admin';
    if ($role === 'student') {
        $_SESSION['student_lookup'] = $user['username'];
        $_SESSION['student_lookup_id'] = $user['username'];
        $_SESSION['student_lookup_email'] = $user['email'];
    } else {
        unset($_SESSION['student_lookup'], $_SESSION['student_lookup_id'], $_SESSION['student_lookup_email']);
    }
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Send guests to sign in before they can use the dashboard. */
function require_login(): void
{
    if (current_user() === null) {
        redirect('login.php?next=' . urlencode(current_page_url()));
    }
}

/** Default post-login destination for each role. */
function dashboard_url(?string $role = null): string
{
    $role = $role ?? (current_user()['role'] ?? '');
    return $role === 'admin' ? 'admin.php' : 'dashboard.php';
}

/** Send guests to the single login page. Send the wrong role away from a gated page. */
function require_role(string $role): void
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php?next=' . urlencode(current_page_url()));
    }
    if ($user['role'] === $role) {
        return;
    }
    flash('error', $role === 'admin'
        ? 'That page is only for the admin account. Student accounts stay on the student side.'
        : 'That page is only for student accounts.');
    redirect($user['role'] === 'admin' ? 'admin.php' : 'my-bookings.php');
}

/* ---------- Dates and times ---------- */

function today(): string
{
    return date('Y-m-d');
}

function now_minutes(): int
{
    return (int) date('G') * 60 + (int) date('i');
}

function to_minutes(string $time): int
{
    [$h, $m] = array_map('intval', explode(':', $time));
    return $h * 60 + $m;
}

function from_minutes(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function hm(string $time): string
{
    return substr($time, 0, 5);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

function pretty_date(string $date): string
{
    return date('D, j M Y', strtotime($date));
}

function duration_label(string $start, string $end): string
{
    $minutes = to_minutes(hm($end)) - to_minutes(hm($start));
    $hours = $minutes / 60;
    return rtrim(rtrim(number_format($hours, 1), '0'), '.') . ' h';
}

/**
 * Postel's law: accept spaces, dashes and odd case, then store one clean value.
 */
function accept_id(string $value): string
{
    return strtoupper(preg_replace('/[\s\-]+/', '', trim($value)) ?? '');
}

function accept_email(string $value): string
{
    return strtolower(preg_replace('/\s+/', '', trim($value)) ?? '');
}

function accept_name(string $value): string
{
    return preg_replace('/\s+/', ' ', trim($value)) ?? '';
}

function accept_time(string $value): string
{
    $value = trim(str_replace('.', ':', $value));
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($h <= 23 && $min <= 59) {
            return sprintf('%02d:%02d', $h, $min);
        }
    }
    return $value;
}

/**
 * Miller's law: a long time list is easier as morning, afternoon and evening.
 *
 * @param array<int, string> $times
 * @return array<string, array<int, string>>
 */
function time_groups(array $times): array
{
    $groups = ['Morning' => [], 'Afternoon' => [], 'Evening' => []];
    foreach ($times as $time) {
        $hour = (int) substr($time, 0, 2);
        if ($hour < 12) {
            $groups['Morning'][] = $time;
        } elseif ($hour < 17) {
            $groups['Afternoon'][] = $time;
        } else {
            $groups['Evening'][] = $time;
        }
    }
    return array_filter($groups);
}

/** Half-hour time options between opening and closing time. */
function time_options(bool $includeClose = true): array
{
    $options = [];
    $open = cfg('booking', 'open_hour') * 60;
    $close = cfg('booking', 'close_hour') * 60;
    for ($m = $open; $m <= $close; $m += 30) {
        if (!$includeClose && $m === $close) {
            continue;
        }
        $options[] = from_minutes($m);
    }
    return $options;
}

/* ---------- Rooms ---------- */

function facility_list(string $facilities): array
{
    return array_values(array_filter(array_map('trim', explode(',', $facilities)), 'strlen'));
}

function type_label(string $type): string
{
    return ['quiet' => 'Quiet room', 'group' => 'Group room', 'seminar' => 'Seminar room'][$type] ?? ucfirst($type);
}

function quiet_desk_status_label(string $status): string
{
    return ['available' => 'Available', 'occupied' => 'Occupied', 'empty' => 'Empty', 'out_of_order' => 'Out of order'][$status] ?? ucfirst($status);
}

/**
 * Work out each room's state (available / occupied / out_of_order) for a date.
 * "Occupied" only applies when the date is today and a booking is running right now.
 *
 * @param array $rooms    rooms rows
 * @param array $bookings confirmed bookings for $date
 */
function room_states(array $rooms, array $bookings, string $date): array
{
    $byRoom = [];
    foreach ($bookings as $b) {
        $byRoom[$b['room_id']][] = $b;
    }

    $nowMin = now_minutes();
    $isToday = $date === today();
    $states = [];

    foreach ($rooms as $room) {
        $list = $byRoom[$room['room_id']] ?? [];
        usort($list, fn ($a, $b) => strcmp($a['start_time'], $b['start_time']));

        $state = [
            'state'   => $room['status'] === 'out_of_order' ? 'out_of_order' : 'available',
            'free_at' => null,
            'count'   => count($list),
        ];

        if ($state['state'] === 'available' && $isToday) {
            $freeAt = null;
            foreach ($list as $b) {
                $start = to_minutes(hm($b['start_time']));
                $end = to_minutes(hm($b['end_time']));
                if ($freeAt === null && $start <= $nowMin && $nowMin < $end) {
                    $freeAt = $end;
                } elseif ($freeAt !== null && $start === $freeAt) {
                    $freeAt = $end;
                }
            }
            if ($freeAt !== null) {
                $state['state'] = 'occupied';
                $state['free_at'] = from_minutes($freeAt);
            }
        }

        $states[$room['room_id']] = $state;
    }

    return $states;
}

function state_label(array $room, array $state): string
{
    switch ($state['state']) {
        case 'occupied':
            return 'Occupied · free ' . $state['free_at'];
        case 'out_of_order':
            return 'Out of order';
        default:
            return 'Available · ' . $room['capacity'] . ' seats';
    }
}

/* ---------- Booking helpers ---------- */

/** A booking can be changed while it is confirmed and has not finished yet. */
function booking_is_active(array $booking): bool
{
    if ($booking['status'] !== 'confirmed') {
        return false;
    }
    if ($booking['booking_date'] > today()) {
        return true;
    }
    return $booking['booking_date'] === today() && to_minutes(hm($booking['end_time'])) > now_minutes();
}

function booking_status_label(array $booking): array
{
    if ($booking['status'] === 'cancelled') {
        return ['cancelled', 'Cancelled'];
    }
    if (booking_is_active($booking)) {
        if ($booking['booking_date'] === today()
            && to_minutes(hm($booking['start_time'])) <= now_minutes()) {
            return ['live', 'In progress'];
        }
        return ['confirmed', 'Confirmed'];
    }
    return ['done', 'Completed'];
}

/** True when the signed-in account may change this booking. Students only own their own ID. */
function owns_booking(array $booking): bool
{
    if (admin_logged_in()) {
        return true;
    }
    $user = current_user();
    if ($user === null || $user['role'] !== 'student') {
        return false;
    }
    return strcasecmp($user['username'], (string) $booking['student_id']) === 0;
}

function page_url(string $page, array $params = []): string
{
    $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
    return $page . ($params ? '?' . http_build_query($params) : '');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Hide the middle of a student ID on public pages, e.g. TU100231 becomes TU1***31. */
function mask_id(string $id): string
{
    $len = strlen($id);
    return $len <= 5 ? $id : substr($id, 0, 3) . str_repeat('*', $len - 5) . substr($id, -2);
}

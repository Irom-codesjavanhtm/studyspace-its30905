<?php
declare(strict_types=1);

/* ---------- Rooms ---------- */

function rooms_all(): array
{
    return db()->query('SELECT * FROM rooms ORDER BY floor_no, room_code')->fetchAll();
}

function room_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM rooms WHERE room_id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function room_code_exists(string $code, ?int $exceptId = null): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM rooms WHERE room_code = :code AND room_id <> :id');
    $stmt->execute(['code' => $code, 'id' => $exceptId ?? 0]);
    return (int) $stmt->fetchColumn() > 0;
}

function room_insert(array $d): int
{
    $stmt = db()->prepare(
        'INSERT INTO rooms (room_code, room_name, room_type, floor_no, capacity, facilities, description, status)
         VALUES (:room_code, :room_name, :room_type, :floor_no, :capacity, :facilities, :description, :status)'
    );
    $stmt->execute($d);
    return (int) db()->lastInsertId();
}

function room_update(int $id, array $d): void
{
    $stmt = db()->prepare(
        'UPDATE rooms SET room_code = :room_code, room_name = :room_name, room_type = :room_type,
                floor_no = :floor_no, capacity = :capacity, facilities = :facilities,
                description = :description, status = :status
         WHERE room_id = :id'
    );
    $stmt->execute($d + ['id' => $id]);
}

function room_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM rooms WHERE room_id = :id');
    $stmt->execute(['id' => $id]);
}

function room_booking_count(int $id): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = :id');
    $stmt->execute(['id' => $id]);
    return (int) $stmt->fetchColumn();
}

/* ---------- Users ---------- */

function user_find_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    return $stmt->fetch() ?: null;
}

function user_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    return $stmt->fetch() ?: null;
}

/** Creates a student only. The role is fixed in SQL and cannot be chosen on the form. */
function user_insert_student(string $username, string $name, string $email, string $passwordHash): int
{
    $stmt = db()->prepare(
        "INSERT INTO users (username, full_name, email, password_hash, role)
         VALUES (:username, :full_name, :email, :password_hash, 'student')"
    );
    $stmt->execute([
        'username'      => $username,
        'full_name'     => $name,
        'email'         => $email,
        'password_hash' => $passwordHash,
    ]);
    return (int) db()->lastInsertId();
}

function user_update_profile(int $userId, string $fullName, string $email): void
{
    $stmt = db()->prepare(
        'UPDATE users SET full_name = :full_name, email = :email WHERE user_id = :id'
    );
    $stmt->execute([
        'full_name' => $fullName,
        'email'     => $email,
        'id'        => $userId,
    ]);
}

function user_update_password(int $userId, string $passwordHash): void
{
    $stmt = db()->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
    $stmt->execute([
        'hash' => $passwordHash,
        'id'   => $userId,
    ]);
}

/**
 * Keeps the staff account present, on the admin role, and on the password hash in config.php.
 * Registration cannot create or change this row.
 */
function user_ensure_admin(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $hash = (string) $GLOBALS['config']['admin']['password_hash'];
    $existing = user_find_by_username('admin');
    if ($existing === null) {
        $stmt = db()->prepare(
            "INSERT INTO users (username, full_name, email, password_hash, role)
             VALUES ('admin', 'Library admin', 'admin@studyspace.local', :hash, 'admin')"
        );
        $stmt->execute(['hash' => $hash]);
        return;
    }
    if (!hash_equals((string) $existing['password_hash'], $hash) || $existing['role'] !== 'admin') {
        $stmt = db()->prepare("UPDATE users SET password_hash = :hash, role = 'admin' WHERE username = 'admin'");
        $stmt->execute(['hash' => $hash]);
    }
}

/* ---------- Quiet hall desks ---------- */

function quiet_desks_all(): array
{
    return db()->query('SELECT * FROM quiet_desks ORDER BY floor_no, position_no')->fetchAll();
}

function quiet_desks_for_floor(int $floor): array
{
    $stmt = db()->prepare('SELECT * FROM quiet_desks WHERE floor_no = :floor ORDER BY position_no');
    $stmt->execute(['floor' => $floor]);
    return $stmt->fetchAll();
}

function quiet_desk_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM quiet_desks WHERE desk_id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function quiet_desk_set_status(int $id, string $status): bool
{
    if (!in_array($status, ['available', 'occupied', 'empty', 'out_of_order'], true)) {
        return false;
    }
    $stmt = db()->prepare('UPDATE quiet_desks SET status = :status WHERE desk_id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
    return $stmt->rowCount() >= 0 && quiet_desk_find($id) !== null;
}

/* ---------- Bookings ---------- */

const BOOKING_SELECT = 'SELECT b.*, r.room_code, r.room_name, r.room_type, r.floor_no, r.capacity
                        FROM bookings b JOIN rooms r ON r.room_id = b.room_id';

function booking_find(int $id): ?array
{
    $stmt = db()->prepare(BOOKING_SELECT . ' WHERE b.booking_id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function bookings_for_date(string $date, bool $includeCancelled = false): array
{
    $sql = BOOKING_SELECT . ' WHERE b.booking_date = :d';
    if (!$includeCancelled) {
        $sql .= " AND b.status = 'confirmed'";
    }
    $stmt = db()->prepare($sql . ' ORDER BY b.start_time, r.room_code');
    $stmt->execute(['d' => $date]);
    return $stmt->fetchAll();
}

function bookings_for_student(string $lookup): array
{
    $stmt = db()->prepare(
        BOOKING_SELECT . ' WHERE b.student_id = :id OR b.email = :email
         ORDER BY b.booking_date DESC, b.start_time DESC'
    );
    $stmt->execute(['id' => $lookup, 'email' => $lookup]);
    return $stmt->fetchAll();
}

/** Admin listing with optional filters: date, room_id, status, q (name/ID/email). */
function bookings_filtered(array $f): array
{
    $where = [];
    $args = [];
    if (!empty($f['date'])) {
        $where[] = 'b.booking_date = :date';
        $args['date'] = $f['date'];
    }
    if (!empty($f['room_id'])) {
        $where[] = 'b.room_id = :room_id';
        $args['room_id'] = (int) $f['room_id'];
    }
    if (!empty($f['status'])) {
        $where[] = 'b.status = :status';
        $args['status'] = $f['status'];
    }
    if (!empty($f['q'])) {
        $where[] = '(b.student_name LIKE :q1 OR b.student_id LIKE :q2 OR b.email LIKE :q3)';
        $like = '%' . $f['q'] . '%';
        $args += ['q1' => $like, 'q2' => $like, 'q3' => $like];
    }
    $sql = BOOKING_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY b.booking_date DESC, b.start_time DESC, r.room_code LIMIT 500';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/** Confirmed bookings that overlap [start, end) in a room on a date. */
function booking_conflicts(PDO $pdo, int $roomId, string $date, string $start, string $end, ?int $exceptId = null): array
{
    $stmt = $pdo->prepare(
        "SELECT booking_id, start_time, end_time FROM bookings
         WHERE room_id = :room AND booking_date = :d AND status = 'confirmed'
           AND start_time < :end AND end_time > :start AND booking_id <> :except
         ORDER BY start_time"
    );
    $stmt->execute(['room' => $roomId, 'd' => $date, 'start' => $start . ':00', 'end' => $end . ':00', 'except' => $exceptId ?? 0]);
    return $stmt->fetchAll();
}

/**
 * Insert or update a booking inside a transaction. The room row is locked first so that two
 * simultaneous requests for the same room cannot both pass the overlap check.
 *
 * @return array{ok: bool, id: int, conflicts: array}
 */
function booking_save(array $d, ?int $id = null): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT room_id FROM rooms WHERE room_id = :id FOR UPDATE');
        $lock->execute(['id' => $d['room_id']]);
        if (!$lock->fetch()) {
            $pdo->rollBack();
            return ['ok' => false, 'id' => 0, 'conflicts' => [], 'missing_room' => true];
        }

        $conflicts = booking_conflicts($pdo, (int) $d['room_id'], $d['booking_date'], $d['start_time'], $d['end_time'], $id);
        if ($conflicts) {
            $pdo->rollBack();
            return ['ok' => false, 'id' => 0, 'conflicts' => $conflicts];
        }

        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (room_id, student_name, student_id, email, booking_date, start_time, end_time, attendees, purpose)
                 VALUES (:room_id, :student_name, :student_id, :email, :booking_date, :start_time, :end_time, :attendees, :purpose)'
            );
            $stmt->execute([
                'room_id' => $d['room_id'], 'student_name' => $d['student_name'], 'student_id' => $d['student_id'],
                'email' => $d['email'], 'booking_date' => $d['booking_date'],
                'start_time' => $d['start_time'] . ':00', 'end_time' => $d['end_time'] . ':00',
                'attendees' => $d['attendees'], 'purpose' => $d['purpose'],
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE bookings SET room_id = :room_id, student_name = :student_name, email = :email,
                        booking_date = :booking_date, start_time = :start_time, end_time = :end_time,
                        attendees = :attendees, purpose = :purpose
                 WHERE booking_id = :id'
            );
            $stmt->execute([
                'room_id' => $d['room_id'], 'student_name' => $d['student_name'], 'email' => $d['email'],
                'booking_date' => $d['booking_date'], 'start_time' => $d['start_time'] . ':00',
                'end_time' => $d['end_time'] . ':00', 'attendees' => $d['attendees'],
                'purpose' => $d['purpose'], 'id' => $id,
            ]);
        }

        $pdo->commit();
        return ['ok' => true, 'id' => $id, 'conflicts' => []];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function booking_cancel(int $id): void
{
    $stmt = db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :id");
    $stmt->execute(['id' => $id]);
}

function booking_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM bookings WHERE booking_id = :id');
    $stmt->execute(['id' => $id]);
}

/* ---------- Summary ---------- */

function counts_overview(): array
{
    $pdo = db();
    $rooms = $pdo->query(
        "SELECT COUNT(*) AS total, SUM(status = 'out_of_order') AS broken FROM rooms"
    )->fetch();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = :d AND status = 'confirmed'");
    $stmt->execute(['d' => today()]);
    $todayCount = (int) $stmt->fetchColumn();
    $upcoming = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date >= :d AND status = 'confirmed'");
    $upcoming->execute(['d' => today()]);

    return [
        'rooms'    => (int) $rooms['total'],
        'broken'   => (int) $rooms['broken'],
        'today'    => $todayCount,
        'upcoming' => (int) $upcoming->fetchColumn(),
        'all'      => (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    ];
}

/** Confirmed bookings per day for a run of days, keyed by date. */
function booking_counts_by_day(string $from, int $days): array
{
    $to = date('Y-m-d', strtotime($from . " +{$days} days"));
    $stmt = db()->prepare(
        "SELECT booking_date, COUNT(*) AS n FROM bookings
         WHERE status = 'confirmed' AND booking_date >= :f AND booking_date < :t
         GROUP BY booking_date"
    );
    $stmt->execute(['f' => $from, 't' => $to]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['booking_date']] = (int) $row['n'];
    }
    return $out;
}

function rooms_with_counts(): array
{
    return db()->query(
        'SELECT r.*, (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.room_id) AS booking_total
         FROM rooms r ORDER BY r.floor_no, r.room_code'
    )->fetchAll();
}

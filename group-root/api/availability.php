<?php
require dirname(__DIR__) . '/includes/app.php';

$roomId = (int) ($_GET['room_id'] ?? 0);
$date = (string) ($_GET['date'] ?? today());
$except = isset($_GET['except']) ? (int) $_GET['except'] : null;

if (!valid_date($date)) {
    json_response(['error' => 'Invalid date.'], 400);
}

$room = $roomId > 0 ? room_find($roomId) : null;
if ($room === null) {
    json_response(['error' => 'Room not found.'], 404);
}

$stmt = db()->prepare(
    "SELECT booking_id, start_time, end_time FROM bookings
     WHERE room_id = :room AND booking_date = :d AND status = 'confirmed' AND booking_id <> :except
     ORDER BY start_time"
);
$stmt->execute(['room' => $roomId, 'd' => $date, 'except' => $except ?? 0]);

$booked = array_map(
    fn ($b) => ['start' => hm($b['start_time']), 'end' => hm($b['end_time'])],
    $stmt->fetchAll()
);

json_response([
    'room' => [
        'id'         => (int) $room['room_id'],
        'code'       => $room['room_code'],
        'status'     => $room['status'],
        'capacity'   => (int) $room['capacity'],
    ],
    'date'   => $date,
    'now'    => ['date' => today(), 'minutes' => now_minutes()],
    'open'   => cfg('booking', 'open_hour'),
    'close'  => cfg('booking', 'close_hour'),
    'booked' => $booked,
]);

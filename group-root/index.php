<?php
require __DIR__ . '/includes/app.php';

if (current_user()) {
    redirect(dashboard_url());
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

$pageTitle = 'Home';
$activeNav = 'home';
$pageId = 'home';
$shell = 'public';
$pageScripts = ['home'];
require __DIR__ . '/includes/header.php';
?>
      <section class="hero hero-public">
        <pre class="hero-corner hero-corner-tl" aria-hidden="true">
          ⠀⠀⠀⠀⠀⠀⠀⠀⣠⠴⠶⣄⡢⡀⠀⠀⠀⠀⠀⠀⢀⣤⣶⣶⣤⡀⠀⢀⡴⣊⣭⣽⣦⣤⣄⡀⠠⣄⠀⠀⠀⠀⢠⣴⣶⣶⣄⠀⣀⢔⣒⣓⣐⠒⠂⢄⡠⡀⠀⠀⠀⠀⢀⣀⡀
          ⠀⠀⠀⠀⠀⠀⠀⠸⢯⠀⠀⠈⢿⣎⠳⣄⠀⠀⠀⠀⠘⠿⠗⠘⣿⣿⢤⣫⣾⠟⠉⠀⠈⠉⠻⣿⣦⡈⢧⡀⠀⠀⠀⠉⢉⣿⣿⠀⢰⠟⠉⣽⣿⠇⠀⠀⠈⢮⠰⢄⡀⠀⠈⠁⡿
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⡔⡄⠀⠘⣿⡄⠈⠳⢤⣀⠀⠀⠀⣠⣾⣿⠏⣢⢿⠗⠋⠐⢦⢰⣿⣆⣼⣿⠇⠣⡙⠶⢤⣤⣤⣾⠟⠁⠀⠀⠀⠀⠀⠀⠀⡤⠄⠀⢸⠀⠀⠈⠑⠒⠉⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⡔⡄⠀⠘⣿⡄⠈⠳⢤⣀⠀⠀⠀⣠⣾⣿⠏⣢⢿⠗⠋⠐⢦⢰⣿⣆⣼⣿⠇⠣⡙⠶⢤⣤⣤⣾⠟⠁⠀⠀⠀⠀⠀⠀⠀⡤⠄⠀⢸⠀⠀⠈⠑⠒⠉⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⢣⢻⡄⠀⣿⡇⠐⡄⠀⠈⠉⠛⠛⣛⣉⡀⠀⠀⠀⠀⠘⠧⠊⠀⠉⠉⠉⠁⠀⠀⠑⢄⠀⠀⠀⠛⢙⡆⠀⠀⠀⠀⠀⠀⠀⠑⠤⠔⠋⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⣀⡀⠀⠀⢸⠘⣷⠀⣿⡇⠀⠈⠢⡀⠀⠀⠀⠉⠀⣹⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠑⠢⠤⠔⠋⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⢾⠁⠚⠀⢀⡎⠀⣿⡄⣿⡅⠀⠀⠐⢌⠐⠠⠤⠤⠚⠁⠀⠀⠀⣀⣠⣤⣤⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⣀⣀⣈⡒⠤⠔⠊⠀⠀⣿⢀⣿⡇⡄⠀⠀⠀⠑⢄⠀⠀⠀⠀⠀⢠⣾⡿⠟⠛⠛⠿⣿⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⣠⣾⠟⠛⢿⣿⡆⠀⠀⠀⢰⡟⢨⣿⢰⠀⠀⠀⠀⠀⠀⠱⡀⠀⠀⠀⣾⣿⡁⠀⠀⠀⠀⠈⢻⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⣿⣿⠀⠀⠘⠛⠁⠀⠀⢠⡿⠁⠈⣿⣷⡀⠀⠀⠀⠀⢀⣀⣈⢆⠀⠀⠈⠻⠷⠶⠄⠀⠀⠀⠀⣿⣿⣇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠘⣿⣦⡀⠀⠀⠀⣀⡴⠟⢠⡄⠀⢿⣟⣇⠀⠀⠀⠀⠻⠇⠈⣧⠳⡀⠀⠀⠀⠀⠀⠀⠀⣀⡤⢼⣿⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠈⠙⠛⠓⠒⠋⠉⠀⠀⡼⠀⠱⡘⢿⡜⢦⠀⠀⠀⠀⠀⢀⡿⠀⠱⡀⠀⠀⠀⠀⢠⠞⠁⠀⣼⣿⡿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⣠⠞⠁⠀⠀⠹⡌⢻⣆⠙⠢⠄⡤⠴⠋⠁⠀⠀⠹⡄⠀⠀⠀⣾⠀⠀⣰⣿⢿⡗⣸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⣠⡾⠋⠀⡤⠤⡄⠀⢹⠀⠙⠷⣤⡀⠀⠀⠀⠀⠀⠀⠀⢳⠀⠀⠀⠸⣠⣼⡿⠋⠀⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⢠⡾⠋⠀⠀⠘⣇⠀⠁⣠⠟⠀⠀⠀⠈⠙⠳⢦⣄⣀⣀⠀⠀⣈⣆⣤⡴⣾⠟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⢠⡿⠁⠀⠀⠀⠀⠀⠉⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠉⠉⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣾⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣿⠀⠀⠀⠀⠀⠀⣠⣴⣦⣤⣀⠀⠀⠀⠀⠀⢀⡀⢀⡀⠀⠀⠀⠀⡟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣿⣾⠀⠀⠀⢀⢀⡿⠟⠀⠹⣿⣇⠀⠀⠀⣰⠁⠀⠀⠀⠀⠀⠀⣸⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠘⣿⡣⡀⠀⠘⠀⡽⠀⠀⢀⣿⡟⠀⠀⠀⠳⡀⠀⠀⠀⠀⠀⡰⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠈⠻⣮⣍⡀⠉⠀⢀⣠⡾⠟⠀⠀⠀⠀⠀⠁⠢⠤⠤⠔⠊⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠪⣍⣉⠉⠉⣡⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⢤⣱⠙⢷⡒⠀⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣦⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⣠⣴⡶⠄⠀⢿⣇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠰⣿⡇⠀⠀⠀⣾⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠙⠿⠦⠤⢞⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⡠⠔⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⡜⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠘⡃⠀⠀⣤⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠱⢄⣀⡸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
        </pre>
        <pre class="hero-corner hero-corner-br" aria-hidden="true">
          ⠀⠀⠀⠀⠀⠀⠀⠀⣠⠴⠶⣄⡢⡀⠀⠀⠀⠀⠀⠀⢀⣤⣶⣶⣤⡀⠀⢀⡴⣊⣭⣽⣦⣤⣄⡀⠠⣄⠀⠀⠀⠀⢠⣴⣶⣶⣄⠀⣀⢔⣒⣓⣐⠒⠂⢄⡠⡀⠀⠀⠀⠀⢀⣀⡀
          ⠀⠀⠀⠀⠀⠀⠀⠸⢯⠀⠀⠈⢿⣎⠳⣄⠀⠀⠀⠀⠘⠿⠗⠘⣿⣿⢤⣫⣾⠟⠉⠀⠈⠉⠻⣿⣦⡈⢧⡀⠀⠀⠀⠉⢉⣿⣿⠀⢰⠟⠉⣽⣿⠇⠀⠀⠈⢮⠰⢄⡀⠀⠈⠁⡿
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⡔⡄⠀⠘⣿⡄⠈⠳⢤⣀⠀⠀⠀⣠⣾⣿⠏⣢⢿⠗⠋⠐⢦⢰⣿⣆⣼⣿⠇⠣⡙⠶⢤⣤⣤⣾⠟⠁⠀⠀⠀⠀⠀⠀⠀⡤⠄⠀⢸⠀⠀⠈⠑⠒⠉⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⢣⢻⡄⠀⣿⡇⠐⡄⠀⠈⠉⠛⠛⣛⣉⡀⠀⠀⠀⠀⠘⠧⠊⠀⠉⠉⠉⠁⠀⠀⠑⢄⠀⠀⠀⠛⢙⡆⠀⠀⠀⠀⠀⠀⠀⠑⠤⠔⠋⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⣀⡀⠀⠀⢸⠘⣷⠀⣿⡇⠀⠈⠢⡀⠀⠀⠀⠉⠀⣹⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠑⠢⠤⠔⠋⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⢾⠁⠚⠀⢀⡎⠀⣿⡄⣿⡅⠀⠀⠐⢌⠐⠠⠤⠤⠚⠁⠀⠀⠀⣀⣠⣤⣤⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⣀⣀⣈⡒⠤⠔⠊⠀⠀⣿⢀⣿⡇⡄⠀⠀⠀⠑⢄⠀⠀⠀⠀⠀⢠⣾⡿⠟⠛⠛⠿⣿⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⣠⣾⠟⠛⢿⣿⡆⠀⠀⠀⢰⡟⢨⣿⢰⠀⠀⠀⠀⠀⠀⠱⡀⠀⠀⠀⣾⣿⡁⠀⠀⠀⠀⠈⢻⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⣿⣿⠀⠀⠘⠛⠁⠀⠀⢠⡿⠁⠈⣿⣷⡀⠀⠀⠀⠀⢀⣀⣈⢆⠀⠀⠈⠻⠷⠶⠄⠀⠀⠀⠀⣿⣿⣇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠘⣿⣦⡀⠀⠀⠀⣀⡴⠟⢠⡄⠀⢿⣟⣇⠀⠀⠀⠀⠻⠇⠈⣧⠳⡀⠀⠀⠀⠀⠀⠀⠀⣀⡤⢼⣿⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠈⠙⠛⠓⠒⠋⠉⠀⠀⡼⠀⠱⡘⢿⡜⢦⠀⠀⠀⠀⠀⢀⡿⠀⠱⡀⠀⠀⠀⠀⢠⠞⠁⠀⣼⣿⡿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⣠⠞⠁⠀⠀⠹⡌⢻⣆⠙⠢⠄⡤⠴⠋⠁⠀⠀⠹⡄⠀⠀⠀⣾⠀⠀⣰⣿⢿⡗⣸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⣠⡾⠋⠀⡤⠤⡄⠀⢹⠀⠙⠷⣤⡀⠀⠀⠀⠀⠀⠀⠀⢳⠀⠀⠀⠸⣠⣼⡿⠋⠀⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⢠⡾⠋⠀⠀⠘⣇⠀⠁⣠⠟⠀⠀⠀⠈⠙⠳⢦⣄⣀⣀⠀⠀⣈⣆⣤⡴⣾⠟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⢠⡿⠁⠀⠀⠀⠀⠀⠉⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠉⠉⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣾⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣿⠀⠀⠀⠀⠀⠀⣠⣴⣦⣤⣀⠀⠀⠀⠀⠀⢀⡀⢀⡀⠀⠀⠀⠀⡟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⣿⣾⠀⠀⠀⢀⢀⡿⠟⠀⠹⣿⣇⠀⠀⠀⣰⠁⠀⠀⠀⠀⠀⠀⣸⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠘⣿⡣⡀⠀⠘⠀⡽⠀⠀⢀⣿⡟⠀⠀⠀⠳⡀⠀⠀⠀⠀⠀⡰⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠈⠻⣮⣍⡀⠉⠀⢀⣠⡾⠟⠀⠀⠀⠀⠀⠁⠢⠤⠤⠔⠊⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠪⣍⣉⠉⠉⣡⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⢤⣱⠙⢷⡒⠀⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣦⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⣠⣴⡶⠄⠀⢿⣇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠰⣿⡇⠀⠀⠀⣾⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠙⠿⠦⠤⢞⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠀⡠⠔⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⡜⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠘⡃⠀⠀⣤⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
          ⠀⠀⠀⠀⠀⠀⠀⠀⠱⢄⣀⡸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
        </pre>
        <div class="hero-shell site-container">
          <div class="hero-copy">
            <p class="hero-brand">StudySpace</p>
            <h1 class="hero-title">Book a Level 2 or 4 study room before you walk over</h1>
            <p class="hero-lead">Sign in with your student ID, open the floor plan, and reserve an R room for up to <?= (int) cfg('booking', 'max_hours') ?> hours. Quiet halls stay walk-in.</p>
            <div class="hero-actions">
              <a class="btn btn-dark btn-lg" href="login.php">Sign in</a>
              <a class="btn btn-outline btn-lg" href="login.php?mode=register">Create account</a>
            </div>
            <dl class="hero-live" aria-label="Live campus snapshot">
              <div>
                <dt>Available now</dt>
                <dd><span class="hero-live-dot is-free" aria-hidden="true"></span><?= (int) $counts['available'] ?></dd>
              </div>
              <div>
                <dt>In use</dt>
                <dd><span class="hero-live-dot is-busy" aria-hidden="true"></span><?= (int) $counts['occupied'] ?></dd>
              </div>
              <div>
                <dt>Booked today</dt>
                <dd><?= (int) count($todayBookings) ?></dd>
              </div>
            </dl>
          </div>
        </div>
      </section>

      <section id="how-it-works" class="site-container page-section home-flow">
        <header class="section-head section-head-center">
          <h2>How it works after you sign in</h2>
          <p class="page-sub">Your dashboard keeps booking, floor plans, and reservations in one place</p>
        </header>
        <ol class="home-steps">
          <li>
            <span class="home-step-num" aria-hidden="true">01</span>
            <div>
              <h3>Sign in</h3>
              <p>Use your student ID or the admin account to open the StudySpace dashboard</p>
            </div>
          </li>
          <li>
            <span class="home-step-num" aria-hidden="true">02</span>
            <div>
              <h3>Pick a room</h3>
              <p>Browse Levels 2 and 4 on the floor plan. Quiet halls stay walk-in only</p>
            </div>
          </li>
          <li>
            <span class="home-step-num" aria-hidden="true">03</span>
            <div>
              <h3>Reserve and manage</h3>
              <p>Choose a slot, confirm, then edit or cancel anytime from My Bookings</p>
            </div>
          </li>
        </ol>
      </section>

      <section id="campus-hours" class="site-container page-section home-facilities">
        <div class="home-facility-band home-facility-band-center">
          <div>
            <h2>Open while campus is open</h2>
            <p class="page-sub">Daily <?= e(sprintf('%02d:00', cfg('booking', 'open_hour'))) ?> to <?= e(sprintf('%02d:00', cfg('booking', 'close_hour'))) ?>. Bookings last up to <?= (int) cfg('booking', 'max_hours') ?> hours. Levels 3 and 5 are under renovation</p>
          </div>
          <div class="hero-actions">
            <a class="btn btn-dark" href="login.php">Sign in to book</a>
            <a class="btn btn-outline" href="about.php">About the project</a>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

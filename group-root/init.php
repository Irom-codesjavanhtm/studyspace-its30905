<?php
/**
 * Database setup script. Creates the database, tables and dummy data from schema.sql.
 *
 *   CLI:      php init.php            (uses the MySQL user from config.php)
 *             php init.php root secret (use another account, e.g. root, to create the database)
 *   Browser:  http://localhost:8000/init.php  (only while the database is still empty, or when signed in as admin)
 *
 * WARNING: running it again drops and re-creates the rooms and bookings tables.
 */
declare(strict_types=1);

require __DIR__ . '/includes/db.php';
$config = require __DIR__ . '/config.php';
$GLOBALS['config'] = $config;
date_default_timezone_set($config['timezone']);

$isCli = PHP_SAPI === 'cli';
$messages = [];
$ok = false;

function say(string $line): void
{
    global $isCli, $messages;
    $messages[] = $line;
    if ($isCli) {
        echo $line . PHP_EOL;
    }
}

function tables_exist(array $c): bool
{
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']),
            $c['user'], $c['pass'], db_options()
        );
        return (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('rooms','bookings')")->fetchColumn() === 2;
    } catch (PDOException $e) {
        return false;
    }
}

$allowed = $isCli;
if (!$isCli) {
    session_start();
    $allowed = !tables_exist($config['db']) || !empty($_SESSION['is_admin']);
}

if (!$allowed) {
    http_response_code(403);
    say('Setup is locked because the database already exists. Sign in as admin first, or run "php init.php" from the command line.');
} else {
    $user = $isCli && isset($argv[1]) ? $argv[1] : $config['db']['user'];
    $pass = $isCli && isset($argv[2]) ? $argv[2] : $config['db']['pass'];
    $sql = file_get_contents(__DIR__ . '/schema.sql');

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=%s', $config['db']['host'], $config['db']['port'], $config['db']['charset']),
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]
        );
        $pdo->exec($sql);
        $rooms = (int) $pdo->query('SELECT COUNT(*) FROM studyspace.rooms')->fetchColumn();
        $bookings = (int) $pdo->query('SELECT COUNT(*) FROM studyspace.bookings')->fetchColumn();
        $students = (int) $pdo->query("SELECT COUNT(*) FROM studyspace.users WHERE role = 'student'")->fetchColumn();
        say("Database 'studyspace' is ready: $rooms rooms, $bookings bookings, $students dummy student account(s).");
        $ok = true;
    } catch (Throwable $e) {
        http_response_code(500);
        say('Setup failed: ' . $e->getMessage());
        say('Tip: run "php init.php root <password>" or import schema.sql with the mysql client instead.');
    }
}

if ($isCli) {
    exit($ok ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup | StudySpace</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="setup">
  <main class="site-main">
    <section class="site-container page-section">
      <div class="panel empty-state">
        <p class="eyebrow eyebrow-accent">Setup</p>
        <h1>Database setup</h1>
<?php foreach ($messages as $line): ?>
        <p><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
<?php if ($ok): ?>
        <a class="btn btn-dark" href="index.php">Open StudySpace</a>
<?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>

<?php
declare(strict_types=1);

function db_options(): array
{
    return [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
}

/**
 * Shared PDO connection. Shows a friendly setup page when the database is unreachable.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = $GLOBALS['config']['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);

    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], db_options());
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        db_unavailable($e);
    }

    return $pdo;
}

function db_unavailable(PDOException $e): void
{
    http_response_code(503);
    error_log('StudySpace DB error: ' . $e->getMessage());
    $pageTitle = 'Database unavailable';
    $activeNav = '';
    require __DIR__ . '/header.php';
    ?>
      <section class="site-container page-section">
        <div class="panel empty-state">
          <p class="eyebrow">503 / Setup needed</p>
          <h1>Database not reachable</h1>
          <p>StudySpace could not connect to MySQL/MariaDB. Create the database, import <code>schema.sql</code> (or run <code>php init.php</code>) and check the credentials in <code>config.php</code>.</p>
        </div>
      </section>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}

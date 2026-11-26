<?php
/**
 * Shared page chrome. Expects: $pageTitle, $activeNav, optional $pageScripts, $pageId, $shell.
 * $shell = 'public' (marketing) or 'app' (signed-in dashboard). Defaults from auth state.
 */
$pageScripts = $pageScripts ?? [];
$pageId = $pageId ?? 'page';
$activeNav = $activeNav ?? '';
$user = current_user();
$shell = $shell ?? ($user ? 'app' : 'public');
if ($shell === 'app') {
    require_login();
    $user = current_user();
}

$navItems = [
    'dashboard' => ['dashboard.php', 'Dashboard', 'M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z'],
    'rooms'     => ['rooms.php', 'Study Rooms', 'M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z'],
    'book'      => ['book.php', 'Book a Room', 'M7 3h10a2 2 0 0 1 2 2v14l-7-3-7 3V5a2 2 0 0 1 2-2z'],
    'bookings'  => ['my-bookings.php', 'My Bookings', 'M8 7h8M8 12h8M8 17h5M5 3h14a1 1 0 0 1 1 1v16l-8-3-8 3V4a1 1 0 0 1 1-1z'],
    'summary'   => ['summary.php', 'Daily Summary', 'M4 19V5m0 14h16M8 17V9m4 8V7m4 10v-4'],
];

$profileName = $user['name'] ?? ($user['username'] ?? 'Guest');
$profileInitials = 'SS';
if ($user) {
    $parts = preg_split('/\s+/', trim((string) $profileName)) ?: [];
    if (count($parts) >= 2) {
        $profileInitials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    } else {
        $profileInitials = strtoupper(mb_substr((string) ($user['username'] ?? 'U'), 0, 2));
    }
}

ob_start('reindent_html');
require_once dirname(__DIR__) . '/loading.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="StudySpace - browse study rooms, book a slot and manage your bookings.">
  <title><?= e($pageTitle) ?> | StudySpace</title>
  <link rel="icon" href="assets/img/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css?v=landing-catalog">
  <link rel="stylesheet" href="assets/css/loading.css">
  <script src="assets/js/app.js" defer></script>
  <script src="assets/js/loading.js" defer></script>
<?php foreach ($pageScripts as $script): ?>
  <script src="assets/js/<?= e($script) ?>.js" defer></script>
<?php endforeach; ?>
</head>
<body data-page="<?= e($pageId) ?>" data-shell="<?= e($shell) ?>" class="<?= $shell === 'app' ? 'app-body' : 'public-body' ?>">
<?= renderStudySpaceLoader('studyspace-loader') ?>
  <a class="skip-link" href="#main">Skip to content</a>
<?php if ($shell === 'app'): ?>
  <div class="app-shell">
    <aside class="sidebar" id="app-sidebar" aria-label="Main sidebar">
      <div class="sidebar-brand">
        <a class="brand" href="dashboard.php" aria-label="StudySpace dashboard">
          <span class="brand-mark" aria-hidden="true">
            <img src="assets/img/logo.svg" alt="" width="34" height="34">
          </span>
          <span class="brand-copy">
            <span class="brand-name">StudySpace</span>
            <span class="brand-tag">Room booking</span>
          </span>
        </a>
      </div>
      <nav class="sidebar-nav" aria-label="Main navigation">
        <p class="sidebar-section">Workspace</p>
        <ul class="sidebar-list">
<?php foreach ($navItems as $key => [$href, $label, $icon]): ?>
          <li>
            <a class="sidebar-link<?= $activeNav === $key ? ' is-active' : '' ?>" href="<?= e($href) ?>" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>>
              <svg class="sidebar-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($icon) ?>"></path></svg>
              <span><?= e($label) ?></span>
            </a>
          </li>
<?php endforeach; ?>
        </ul>
<?php if (admin_logged_in()): ?>
        <p class="sidebar-section">Admin</p>
        <ul class="sidebar-list">
          <li>
            <a class="sidebar-link<?= $activeNav === 'admin' ? ' is-active' : '' ?>" href="admin.php" aria-label="Admin panel" title="Admin panel"<?= $activeNav === 'admin' ? ' aria-current="page"' : '' ?>>
              <svg class="sidebar-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.2.6.7 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path></svg>
              <span>Admin panel</span>
            </a>
          </li>
        </ul>
<?php endif; ?>
      </nav>
      <div class="sidebar-foot">
        <p class="sidebar-foot-note">Open <?= e(sprintf('%02d:00', cfg('booking', 'open_hour'))) ?>–<?= e(sprintf('%02d:00', cfg('booking', 'close_hour'))) ?></p>
      </div>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>

    <div class="app-frame">
      <header class="topbar app-topbar">
        <div class="chrome-accent" aria-hidden="true"></div>
        <div class="topbar-left">
          <button class="sidebar-toggle" type="button" aria-expanded="false" aria-controls="app-sidebar" aria-label="Open menu" data-sidebar-toggle>
            <span class="sidebar-toggle-bar"></span>
            <span class="sidebar-toggle-bar"></span>
            <span class="sidebar-toggle-bar"></span>
          </button>
          <div class="topbar-title">
            <p class="topbar-kicker">StudySpace</p>
            <p class="topbar-page"><?= e($pageTitle) ?></p>
          </div>
        </div>
        <div class="topbar-right">
          <div class="profile-menu" data-profile-menu>
            <button class="profile-trigger" type="button" aria-expanded="false" aria-haspopup="menu" aria-controls="profile-menu" data-profile-trigger>
              <span class="profile-avatar" aria-hidden="true"><?= e($profileInitials) ?></span>
              <span class="profile-meta">
                <span class="profile-name"><?= e($profileName) ?></span>
                <span class="profile-role"><?= e(ucfirst((string) $user['role'])) ?></span>
              </span>
              <svg class="profile-caret" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
            </button>
            <div class="profile-dropdown" id="profile-menu" role="menu" hidden data-profile-dropdown>
              <a class="profile-item" role="menuitem" href="profile.php">Profile settings</a>
              <form class="profile-item-form" method="post" action="login.php" data-confirm="Sign out of StudySpace?" data-confirm-title="Sign out" data-confirm-ok="Sign out" data-confirm-cancel="Stay signed in">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button class="profile-item profile-item-danger" type="submit" role="menuitem">Sign out</button>
              </form>
            </div>
          </div>
        </div>
      </header>
      <main id="main" class="site-main">
<?php else: ?>
  <header class="public-header">
    <div class="chrome-accent" aria-hidden="true"></div>
    <div class="public-utility">
      <div class="site-container public-utility-bar">
        <ul class="public-utility-links">
          <li><a href="index.php#how-it-works">How it works</a></li>
          <li><a href="index.php#campus-hours">Campus hours</a></li>
          <li><a href="about.php">About the project</a></li>
        </ul>
<?php if ($user): ?>
        <div class="public-utility-actions" aria-label="Account shortcuts">
          <a class="public-utility-icon" href="<?= e(dashboard_url()) ?>" aria-label="Open dashboard">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"></path></svg>
          </a>
        </div>
<?php endif; ?>
      </div>
    </div>
    <div class="site-container public-header-main">
      <a class="brand public-brand" href="index.php" aria-label="StudySpace home">
        <span class="brand-mark" aria-hidden="true">
          <img src="assets/img/logo.svg" alt="" width="36" height="36">
        </span>
        <span class="brand-name">StudySpace</span>
      </a>
      <nav class="public-nav-main" aria-label="Primary navigation">
<?php if ($user): ?>
        <a class="public-nav-main-link<?= $activeNav === 'dashboard' ? ' is-active' : '' ?>" href="<?= e(dashboard_url()) ?>"<?= $activeNav === 'dashboard' ? ' aria-current="page"' : '' ?>>Dashboard</a>
<?php else: ?>
        <a class="public-nav-main-link<?= $activeNav === 'login' ? ' is-active' : '' ?>" href="login.php"<?= $activeNav === 'login' ? ' aria-current="page"' : '' ?>>Sign in</a>
        <span class="public-nav-sep" aria-hidden="true">|</span>
        <a class="public-nav-main-link public-nav-main-link-accent" href="login.php?mode=register">Create account</a>
<?php endif; ?>
      </nav>
    </div>
  </header>
  <main id="main" class="site-main">
<?php endif; ?>
<?php
$flashMessages = flash_pull();
if ($flashMessages):
?>
  <script>
    window.StudySpace = window.StudySpace || {};
    window.StudySpace.flashes = <?= json_encode(array_values($flashMessages), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
<?php endif; ?>

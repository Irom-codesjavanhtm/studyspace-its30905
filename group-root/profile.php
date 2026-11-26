<?php
require __DIR__ . '/includes/app.php';

if (!current_user()) {
    redirect('login.php?next=' . urlencode('profile.php'));
}

$user = current_user();
$errors = [];
$form = [
    'full_name' => (string) ($user['name'] ?? ''),
    'email'     => (string) ($user['email'] ?? ''),
];

if (is_post()) {
    require_csrf('profile.php');
    $dbUser = user_find_by_username((string) $user['username']);
    [$clean, $errors] = validate_profile_update($_POST, $user, $dbUser);
    $form['full_name'] = $clean['full_name'];
    $form['email'] = $clean['email'];

    if (!$errors) {
        user_update_profile((int) $user['id'], $clean['full_name'], $clean['email']);
        if ($clean['password'] !== '') {
            user_update_password((int) $user['id'], password_hash($clean['password'], PASSWORD_DEFAULT));
        }
        $_SESSION['user']['name'] = $clean['full_name'];
        $_SESSION['user']['email'] = $clean['email'];
        if (($user['role'] ?? '') === 'student') {
            $_SESSION['student_lookup_email'] = $clean['email'];
        }
        flash('success', $clean['password'] !== ''
            ? 'Profile and password were updated.'
            : 'Profile settings were saved.');
        redirect('profile.php');
    }
    http_response_code(422);
    flash('error', 'Please fix the highlighted fields and try again.');
}

$pageTitle = 'Profile settings';
$activeNav = 'profile';
$pageId = 'profile';
$shell = 'app';
$pageScripts = [];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section dash-section">
        <div class="dash-head">
          <div class="dash-head-copy">
            <p class="eyebrow eyebrow-accent">Account</p>
            <h1>Profile settings</h1>
            <p class="page-sub">Update your name, email, or password for <?= e((string) $user['username']) ?>.</p>
          </div>
        </div>

        <form class="panel form-card profile-card" method="post" action="profile.php" novalidate data-validate>
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field">
              <label for="username">Username</label>
              <input class="input" type="text" id="username" value="<?= e((string) $user['username']) ?>" readonly>
            </div>
            <div class="field">
              <label for="role">Role</label>
              <input class="input" type="text" id="role" value="<?= e(ucfirst((string) $user['role'])) ?>" readonly>
            </div>
            <div class="field field-wide">
              <label for="full_name">Full name</label>
              <input class="input" type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" required minlength="2" maxlength="100" autocomplete="name" data-msg="Enter your full name (2 to 100 characters).">
              <?= field_error($errors, 'full_name') ?>
            </div>
            <div class="field field-wide">
              <label for="email">Email</label>
              <input class="input" type="email" id="email" name="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email" data-msg="Enter a valid email address.">
              <?= field_error($errors, 'email') ?>
            </div>
          </div>

          <h2 class="subhead">Change password</h2>
          <p class="muted profile-hint">Leave blank to keep your current password.</p>
          <div class="form-grid">
            <div class="field field-wide">
              <label for="current_password">Current password</label>
              <input class="input" type="password" id="current_password" name="current_password" maxlength="72" autocomplete="current-password" data-msg="Enter your current password to change it.">
              <?= field_error($errors, 'current_password') ?>
            </div>
            <div class="field">
              <label for="password">New password</label>
              <input class="input" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" data-msg="Choose a password of 8 to 72 characters.">
              <?= field_error($errors, 'password') ?>
            </div>
            <div class="field">
              <label for="password_confirm">Confirm new password</label>
              <input class="input" type="password" id="password_confirm" name="password_confirm" minlength="8" maxlength="72" autocomplete="new-password" data-msg="Enter the same password again.">
              <?= field_error($errors, 'password_confirm') ?>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-dark" type="submit">Save settings</button>
            <a class="btn btn-ghost" href="<?= admin_logged_in() ? 'admin.php' : 'my-bookings.php' ?>">Back</a>
          </div>
        </form>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

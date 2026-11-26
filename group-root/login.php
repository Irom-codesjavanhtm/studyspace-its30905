<?php
require __DIR__ . '/includes/app.php';
/**
 * I have added this function to redirect the user to the appropriate page based on their role.
 * It checks if the next parameter is a valid URL and if the role is admin or not.
 * If the role is admin or not, it returns the next parameter.
 * If the role is not admin, it returns the dashboard URL for the role.
 * ---------------------------------------------------------------------
 * Bhone Myat San 0390536 & Kaung Khant Naing 0390575
 * Kaung Khant Naing 0390575 is the original author assigned for this function,
 * Bhone have modified it to add the ability to redirect the user to the appropriate page based on their role.
 **/
function auth_destination(string $role, string $next): string
{
    if ($next !== '' && !str_starts_with($next, 'login.php') && !str_starts_with($next, 'index.php')) {
        if ($role === 'admin' || !str_starts_with($next, 'admin.php')) {
            return $next;
        }
    }
    return dashboard_url($role);
}

$next = safe_next((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
$mode = (($_POST['mode'] ?? $_GET['mode'] ?? 'login') === 'register') ? 'register' : 'login';
$errors = [];
$form = [
    'username'  => '',
    'full_name' => '',
    'email'     => '',
];

if (is_post()) {
    require_csrf('login.php' . ($mode === 'register' ? '?mode=register' : '') . ($next !== '' ? ($mode === 'register' ? '&' : '?') . 'next=' . urlencode($next) : ''));
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'logout') {
        logout_user();
        flash('success', 'You have been signed out.');
        redirect('index.php');
    }

    if ($action === 'register') {
        $mode = 'register';
        [$clean, $errors] = validate_student_registration($_POST);
        $form = $clean;
        if (!$errors) {
            $id = user_insert_student($clean['username'], $clean['full_name'], $clean['email'], password_hash($clean['password'], PASSWORD_DEFAULT));
            $user = user_find_by_username($clean['username']);
            if ($user === null || (int) $user['user_id'] !== $id || $user['role'] !== 'student') {
                flash('error', 'The account could not be created. Please try again.');
                redirect('login.php?mode=register');
            }
            login_user($user);
            flash('success', 'Account created for ' . $user['username'] . '. You are signed in as a student.');
            redirect(auth_destination('student', $next));
        }
        http_response_code(422);
        flash('error', 'Please fix the highlighted fields and try again.');
    } elseif ($action === 'login') {
        $mode = 'login';
        $raw = accept_id((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $form['username'] = $raw === 'ADMIN' ? 'admin' : $raw;

        if (valid_student_id($raw)) {
            $username = $raw;
        } elseif ($raw === 'ADMIN') {
            $username = 'admin';
        } else {
            $username = '';
            $errors['username'] = 'Use a student ID like 0391234, or the admin username.';
            flash('error', 'Sign-in failed. Check the ID and password.');
        }

        $user = $username !== '' ? user_find_by_username($username) : null;
        if (!$errors && ($user === null || !password_verify($password, (string) $user['password_hash']))) {
            usleep(400000);
            http_response_code(401);
            $errors['username'] = $errors['username'] ?? 'Sign-in failed. Check the ID and password.';
            $errors['password'] = 'Sign-in failed. Check the ID and password.';
            flash('error', 'Sign-in failed. Check the ID and password.');
        } elseif (!$errors) {
            if (!in_array($user['role'], ['admin', 'student'], true)) {
                flash('error', 'This account has no role and cannot sign in.');
                redirect('login.php');
            }
            login_user($user);
            if ($user['role'] === 'admin') {
                flash('success', 'Welcome back. You are signed in as admin.');
            } else {
                flash('success', 'Welcome back, ' . $user['full_name'] . '. You are signed in as a student.');
            }
            redirect(auth_destination($user['role'], $next));
        }
    }
}

if (current_user() && !is_post()) {
    $user = current_user();
    redirect(auth_destination($user['role'], $next));
}

$pageTitle = $mode === 'register' ? 'Register' : 'Sign in';
$activeNav = 'login';
$pageId = 'login';
$shell = 'public';
require __DIR__ . '/includes/header.php';

$switchBase = 'login.php?mode=';
$nextQuery = $next !== '' ? '&next=' . urlencode($next) : '';
?>
      <section class="login-page<?= $mode === 'register' ? ' is-register' : '' ?>" aria-labelledby="login-heading">
        <div class="login-split panel login-card">
          <div class="login-split-form">
            <div class="login-split-form-inner">
              <p class="eyebrow eyebrow-accent">StudySpace</p>
              <h1 id="login-heading"><?= $mode === 'register' ? 'Create your account' : 'Welcome back' ?></h1>
              <p class="login-split-lead"><?= $mode === 'register'
                  ? 'Register as a student with your campus ID. The admin account is provisioned separately.'
                  : 'Welcome back — enter your student ID or admin username and password.' ?></p>
              <nav class="tab-bar login-split-tabs" aria-label="Sign in or register">
                <a class="tab<?= $mode === 'login' ? ' is-active' : '' ?>" href="<?= e($switchBase . 'login' . $nextQuery) ?>">Sign in</a>
                <a class="tab<?= $mode === 'register' ? ' is-active' : '' ?>" href="<?= e($switchBase . 'register' . $nextQuery) ?>">Register</a>
              </nav>

<?php if ($mode === 'register'): ?>
            <form class="<?= $errors ? 'is-auth-error' : '' ?>" method="post" action="login.php?mode=register<?= e($next !== '' ? '&next=' . urlencode($next) : '') ?>" novalidate data-validate data-auth-form>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="register">
              <input type="hidden" name="mode" value="register">
              <input type="hidden" name="next" value="<?= e($next) ?>">
              <div class="field">
                <label for="username">Student ID</label>
                <input class="input<?= isset($errors['username']) ? ' is-invalid' : '' ?>" type="text" id="username" name="username" value="<?= e($form['username']) ?>" required pattern="039[0-9]{4}" maxlength="12" inputmode="text" autocomplete="username" placeholder="0391234" data-msg="Student ID must look like 039XXXX, for example 0391234."<?= isset($errors['username']) ? ' aria-invalid="true"' : '' ?>>
                <?= field_error($errors, 'username') ?>
              </div>
              <div class="field">
                <label for="full_name">Full name</label>
                <input class="input<?= isset($errors['full_name']) ? ' is-invalid' : '' ?>" type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" required minlength="2" maxlength="100" autocomplete="name" placeholder="Aisha Rahman" data-msg="Enter your full name (2 to 100 characters).">
                <?= field_error($errors, 'full_name') ?>
              </div>
              <div class="field">
                <label for="email">Email</label>
                <input class="input<?= isset($errors['email']) ? ' is-invalid' : '' ?>" type="email" id="email" name="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email" placeholder="you@example.edu" data-msg="Enter a valid email address.">
                <?= field_error($errors, 'email') ?>
              </div>
              <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                  <input class="input<?= isset($errors['password']) ? ' is-invalid' : '' ?>" type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password" data-msg="Choose a password of 8 to 72 characters.">
                  <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                    <svg class="password-icon password-icon-show" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <svg class="password-icon password-icon-hide" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden><path d="M3 3l18 18"></path><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-3.1 3.9"></path><path d="M6.1 6.1C3.9 7.8 2 12 2 12a17.5 17.5 0 0 0 6.5 5.4"></path></svg>
                  </button>
                </div>
                <?= field_error($errors, 'password') ?>
              </div>
              <div class="field">
                <label for="password_confirm">Confirm password</label>
                <div class="password-wrap">
                  <input class="input<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" type="password" id="password_confirm" name="password_confirm" required minlength="8" maxlength="72" autocomplete="new-password" data-msg="Enter the same password again.">
                  <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                    <svg class="password-icon password-icon-show" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <svg class="password-icon password-icon-hide" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden><path d="M3 3l18 18"></path><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-3.1 3.9"></path><path d="M6.1 6.1C3.9 7.8 2 12 2 12a17.5 17.5 0 0 0 6.5 5.4"></path></svg>
                  </button>
                </div>
                <?= field_error($errors, 'password_confirm') ?>
              </div>
              <button class="btn btn-login-primary btn-block btn-lg" type="submit">Create student account</button>
            </form>
<?php else: ?>
            <form class="<?= $errors ? 'is-auth-error' : '' ?>" method="post" action="login.php<?= e($next !== '' ? '?next=' . urlencode($next) : '') ?>" novalidate data-validate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="login">
              <input type="hidden" name="mode" value="login">
              <input type="hidden" name="next" value="<?= e($next) ?>">
              <div class="field">
                <label for="username">Student ID or admin username</label>
                <input class="input<?= isset($errors['username']) ? ' is-invalid' : '' ?>" type="text" id="username" name="username" value="<?= e($form['username']) ?>" required maxlength="12" autocomplete="username" placeholder="0391234 or admin" data-msg="Enter a student ID like 0391234, or the admin username."<?= isset($errors['username']) ? ' aria-invalid="true"' : '' ?>>
                <?= field_error($errors, 'username') ?>
              </div>
              <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                  <input class="input<?= isset($errors['password']) ? ' is-invalid' : '' ?>" type="password" id="password" name="password" required autocomplete="current-password" data-msg="Enter your password."<?= isset($errors['password']) ? ' aria-invalid="true"' : '' ?>>
                  <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                    <svg class="password-icon password-icon-show" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <svg class="password-icon password-icon-hide" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden><path d="M3 3l18 18"></path><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-3.1 3.9"></path><path d="M6.1 6.1C3.9 7.8 2 12 2 12a17.5 17.5 0 0 0 6.5 5.4"></path></svg>
                  </button>
                </div>
                <?= field_error($errors, 'password') ?>
              </div>
              <button class="btn btn-login-primary btn-block btn-lg" type="submit">Sign in</button>
            </form>
<?php endif; ?>
              <p class="login-split-switch">
<?php if ($mode === 'register'): ?>
                Already have an account? <a class="text-link" href="<?= e($switchBase . 'login' . $nextQuery) ?>">Sign in</a>
<?php else: ?>
                Don’t have an account? <a class="text-link" href="<?= e($switchBase . 'register' . $nextQuery) ?>">Create one for free</a>
<?php endif; ?>
              </p>
              <p class="muted login-hint">Student IDs use 039XXXX. New registrations are always students.</p>
            </div>
          </div>
          <div class="login-split-art" aria-hidden="true">
            <div class="login-split-art-ribbed"></div>
            <p class="login-split-art-caption">Level 2 &amp; 4 rooms · Quiet halls walk-in</p>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

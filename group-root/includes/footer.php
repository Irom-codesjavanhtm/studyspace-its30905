<?php $shell = $shell ?? (current_user() ? 'app' : 'public'); ?>
  </main>
<?php if ($shell === 'app'): ?>
      <footer class="site-footer site-footer-app">
        <div class="site-container footer-base footer-base-shell">
          <p>&copy; <?= date('Y') ?> StudySpace &middot; Study Room Management System</p>
          <p class="mono-note">PHP &middot; MySQL &middot; Vanilla JS</p>
        </div>
      </footer>
    </div>
  </div>
<?php else: ?>
  <footer class="site-footer">
    <div class="site-container footer-grid">
      <div class="footer-brand">
        <a class="brand" href="index.php" aria-label="StudySpace home">
          <span class="brand-mark" aria-hidden="true">
            <img src="assets/img/logo.svg" alt="" width="34" height="34">
          </span>
          <span class="brand-name">StudySpace</span>
        </a>
        <p>Sign in to book a study room, manage reservations, and check what’s free today.</p>
      </div>
      <div>
        <p class="eyebrow">Get started</p>
        <ul class="footer-links">
          <li><a href="login.php">Sign in</a></li>
          <li><a href="login.php?mode=register">Create account</a></li>
          <li><a href="about.php">About StudySpace</a></li>
        </ul>
      </div>
      <div>
        <p class="eyebrow">Hours</p>
        <ul class="footer-links">
          <li>Open <?= e(sprintf('%02d:00', cfg('booking', 'open_hour'))) ?>–<?= e(sprintf('%02d:00', cfg('booking', 'close_hour'))) ?></li>
          <li>Levels 2 and 4 bookable</li>
          <li>Quiet halls are walk-in</li>
        </ul>
      </div>
    </div>
    <div class="site-container footer-base">
      <p>&copy; <?= date('Y') ?> StudySpace &middot; Study Room Management System</p>
      <p class="mono-note">PHP &middot; MySQL &middot; Vanilla JS</p>
    </div>
  </footer>
<?php endif; ?>
  <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="false"></div>
  <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title">
    <form method="dialog" class="confirm-card">
      <p class="eyebrow" id="confirm-eyebrow">Please confirm</p>
      <h2 id="confirm-title">Are you sure?</h2>
      <p id="confirm-message"></p>
      <div class="confirm-actions">
        <button class="btn btn-ghost" value="cancel" id="confirm-cancel">Keep it</button>
        <button class="btn btn-dark" value="ok" id="confirm-ok">Confirm</button>
      </div>
    </form>
  </dialog>
</body>
</html>
<?php ob_end_flush(); ?>

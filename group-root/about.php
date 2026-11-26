<?php
require __DIR__ . '/includes/app.php';

$counts = counts_overview();

$pageTitle = 'About';
$activeNav = 'about';
$pageId = 'about';
$shell = 'public';
$pageScripts = [];
require __DIR__ . '/includes/header.php';
?>
      <section class="site-container page-section">
        <div class="page-head">
          <div>
            <p class="eyebrow eyebrow-accent">// About</p>
            <h1>About StudySpace</h1>
            <p class="page-sub">A study room booking management system for students who are dedicated..</p>
          </div>
        </div>

        <div class="about-grid">
          <article class="panel prose">
            <p class="eyebrow eyebrow-accent">Purpose</p>
            <h2>Why it exists</h2>
            <p>Study rooms are always in demand around assessment weeks. Paper sign-up sheets get lost, rooms are booked twice and nobody knows which rooms are broken. StudySpace gives students one clear place to see what is free and book it, and gives library staff a simple way to look after the rooms.</p>
            <p>The project was built as a database-driven web application: users enter information in a form, it is stored in the database, and it can be listed, updated and deleted through the app.</p>
          </article>
          <aside class="panel stat-card">
            <p class="eyebrow eyebrow-accent">Right now</p>
            <dl class="stat-list">
              <div><dt>Study rooms</dt><dd><?= $counts['rooms'] ?></dd></div>
              <div><dt>Out of order</dt><dd><?= $counts['broken'] ?></dd></div>
              <div><dt>Booked today</dt><dd><?= $counts['today'] ?></dd></div>
              <div><dt>Upcoming bookings</dt><dd><?= $counts['upcoming'] ?></dd></div>
            </dl>
          </aside>
        </div>

        <header class="section-head">
          <p class="eyebrow eyebrow-accent">// Features</p>
          <h2>What the system does</h2>
        </header>
        <div class="feature-grid">
          <div class="feature-card">
            <span class="feature-num">A</span>
            <h3>Display rooms and facilities</h3>
            <p>An interactive floor plan and a room directory list seats, level, type and facilities such as displays and whiteboards.</p>
          </div>
          <div class="feature-card">
            <span class="feature-num">B</span>
            <h3>Book a room</h3>
            <p>A validated booking form checks name, student ID, email, room, date, times, group size and purpose before saving.</p>
          </div>
          <div class="feature-card">
            <span class="feature-num">C</span>
            <h3>View, edit or cancel</h3>
            <p>Students sign in with an ID in the form 039XXXX, then see only their own bookings. They can change the details or cancel with a confirmation step.</p>
          </div>
          <div class="feature-card">
            <span class="feature-num">D</span>
            <h3>No double booking</h3>
            <p>The server rejects any booking that overlaps an existing one in the same room, even if two people click at once.</p>
          </div>
          <div class="feature-card">
            <span class="feature-num">E</span>
            <h3>Admin controls</h3>
            <p>The same sign-in page gives the admin account the admin role. Staff can add, update and delete rooms, set rooms out of order and review or remove any booking. Students cannot open that panel.</p>
          </div>
          <div class="feature-card">
            <span class="feature-num">F</span>
            <h3>Daily summary</h3>
            <p>Pick any date to see bookings, hours booked, room utilisation and an hour-by-hour timeline.</p>
          </div>
        </div>

        <div class="about-grid about-grid-even">
          <article class="panel prose">
            <p class="eyebrow eyebrow-accent">// Built with</p>
            <h2>Technology</h2>
            <ul class="tags tags-lg">
              <li class="tag">PHP 8 &middot; PDO</li>
              <li class="tag">MySQL / MariaDB</li>
              <li class="tag">Vanilla JavaScript</li>
              <li class="tag">HTML5</li>
              <li class="tag">CSS3 &middot; Grid &amp; Flexbox</li>
            </ul>
            <p>No UI frameworks, CDNs, or npm build chain. Layout and components live in authored CSS (<code>style.css</code>, <code>loading.css</code>) with system fonts and vanilla JS. All queries use PDO prepared statements, every input is validated on the server, and the interface is fully responsive.</p>
          </article>
          <article class="panel prose">
            <p class="eyebrow eyebrow-accent">// The team</p>
            <h2>Who brought this idea to life</h2>
            <ul class="team-list">
              <li><span class="team-name">Bhone Myat San</span><span class="team-role">Team Leader · Design</span></li>
              <li><span class="team-name">Kaung Khant Naing</span><span class="team-role">Design · Frontend</span></li>
              <li><span class="team-name">Alvin Wee Ze Quan</span><span class="team-role">Backend · Database</span></li>
              <li><span class="team-name">Muhammad Raifan Bin Mohd Razif</span><span class="team-role">Backend · Database</span></li>
              <li><span class="team-name">Won Jee Hong</span><span class="team-role">Backend · Database</span></li>
            </ul>
          </article>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

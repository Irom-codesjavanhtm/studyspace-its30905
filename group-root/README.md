# StudySpace (group-root)

Merged project: login UI from `login_register_page` + app data/admin from `root`.

## Hierarchy

```
group-root/
  *.php                 App pages (index, login, admin, dashboard, …)
  config.php            DB + booking settings (from root)
  schema.sql / init.php Database setup
  includes/             PHP components (header, footer, db, queries, loader, …)
  assets/
    css/                style.css, login.css, loading.css, loader.css
    js/                 app.js, login.js, admin.js, …
    img/                favicon.svg, eye_hide.svg, eye_show.svg
  api/                  availability endpoint
```

## Auth flow

- `login.php` — teammate sign-in/register UI (`assets/css/login.css` + `assets/js/login.js`)
- Uses PDO auth from `includes/` (studyspace DB), not the old mysqli `users_db`
- Admin → `admin.php` | Student → `dashboard.php`
- Legacy aliases: `admin_page.php`, `user_page.php`

## Setup

1. Import `schema.sql` or run `php init.php`
2. Point the web root at `group-root/`
3. Sign in: `admin` / `Admin@2026`

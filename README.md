# Elderly Care Management System

Deploy this folder to `htdocs/elderly_care/` (XAMPP/WAMP) so it's reachable at
`http://localhost/elderly_care/`. The app uses absolute paths (`/elderly_care/...`)
throughout, so it must live at that URL — if you rename the folder, update those
paths to match.

## Structure

```
elderly_care/
├── index.php              Public homepage
├── login.php               Login form
├── login_process.php       Handles login POST, redirects by role
├── logout.php               Destroys session
├── functionalities.php     Public "what this system does" page
├── help.php                 FAQ page
├── config/
│   └── db.php               MySQL connection settings
├── pages/                   Role-specific dashboards (require login)
│   ├── admin.php
│   ├── admin_records.php
│   ├── admin_requests.php
│   ├── admin_schedules.php
│   ├── caregiver.php
│   └── family.php
├── assets/
│   ├── css/style.css
│   └── js/navbar.js
├── database/
│   └── elderly_care.sql     Import this into MySQL first
└── legacy/                  Old/unused code, kept for reference only — see below
```

## Setup

1. Create the database: import `database/elderly_care.sql` into MySQL
   (creates and seeds `elderly_care_db`).
2. Check `config/db.php` matches your local MySQL credentials.
3. Default logins (seeded in the SQL dump): `uoc` / `uoc` (admin),
   `caregiver1` / `uoc` (caregiver), `family1` / `uoc` (family).

## What was fixed during reorganization

- **`config/db.php` had an unresolved git merge conflict** (leftover
  `<<<<<<<`/`=======`/`>>>>>>>` markers) — resolved to the values matching
  the actual SQL dump (`elderly_care_db`).
- **Two competing homepages existed** (`index.php` using a separate
  header/footer + JSON data store, and `home.php` as a standalone page).
  `home.php` is now `index.php`; the old one is archived in `legacy/`.
- **Two competing data layers existed**: MySQL (`db.php`, used by login and
  the admin pages) and a JSON-file store (`includes/data.php`, used only by
  the old `index.php`). MySQL is now the only active data layer; the JSON
  system is archived in `legacy/`.
- **Broken link**: `home.php` linked to `login.html`, which never existed →
  now correctly links to `login.php`.
- **Broken redirect**: `admin_requests.php` redirected to
  `admin_family_requests.php` (a file that doesn't exist) → fixed to
  redirect to itself, `admin_requests.php`.
- **Shared navbar bug**: `navbar.js`'s "Home" link pointed to `admin.php`
  for every user, which would send caregivers/family members straight to
  a login redirect. It now points to `index.php` for everyone, and all
  navbar links use absolute paths so they resolve correctly whether the
  including page lives at the site root or inside `pages/`.
- **Exposed credential**: `test.php` (a leftover debug script) had a real
  plaintext MySQL root password hardcoded in it. It's archived in
  `legacy/` with the password stripped — don't restore it, and don't
  deploy this file.

## Known incomplete features (not touched — needs a decision from you)

These forms currently POST to files that don't exist and have no matching
database tables yet, so submitting them will 404:

- `pages/caregiver.php` → posts to `caregiver_logs_process.php`
  (medication log entries are currently hardcoded HTML, not from the DB).
- `pages/family.php` → posts to `family_request_process.php`
  (this is separate from the working `visit_requests` flow in
  `pages/admin_requests.php`).

I left these alone rather than guessing at a schema — let me know if you'd
like these built out.

## `legacy/` folder

Old/unused files kept only as a reference, not loaded by the live app:
`index_old.php`, `header.php`, `footer.php`, `data.php`, `data_store.json`
(the abandoned JSON-based data system), and `test.php` (debug script).
Safe to delete once you've confirmed you don't need anything from them.

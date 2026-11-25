# T@ke9ine Hotel & Suites

A PHP and MySQL hotel website with guest registration, booking, password-reset,
and administration features.

## Requirements

- PHP 8 or later with PDO MySQL enabled
- MySQL
- Composer
- XAMPP or another PHP/MySQL environment

## Local setup

1. Start Apache and MySQL.
2. Copy `.env.example` to `.env` if `.env` does not already exist. The included
   local `.env` uses XAMPP defaults; keep real hosting credentials out of Git.
3. Import `database/schema.sql` into MySQL using phpMyAdmin or the MySQL command line.
   The script creates the `T@ke9ine` database and its tables, and seeds sample
   room and staff records.
4. Run `composer install` from the project directory.
5. Set `APP_ENV=local`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and
   `DB_PASSWORD` in `.env` for local work. For hosting, set these as protected
   server environment variables and use a database account with only the
   privileges the application needs.
6. Open `http://localhost/T@ke9ine/` in a browser.

Apache hosting must enable `mod_rewrite` and allow `.htaccess` overrides for
the frontend asset routes and private-directory access rules to work.

Password-reset email requires Gmail OAuth credentials. Add
`GMAIL_SENDER_EMAIL`, `GMAIL_CLIENT_ID`, `GMAIL_CLIENT_SECRET`, and
`GMAIL_REFRESH_TOKEN` to the private `.env` file after following
[GMAIL_API_SETUP.md](GMAIL_API_SETUP.md). Password-reset email remains disabled
until valid OAuth credentials are supplied.

To create the first administrator, set `ADMIN_USERNAME`, `ADMIN_EMAIL`, and
`ADMIN_PASSWORD` in the private `.env`, then run `php create_admin.php` from the
project root. The command-line script hashes the password and refuses duplicate
accounts; do not expose or commit those credentials.

## Project layout

- `index.php`, `Login.html`, and `booking.php` are the existing public page URLs.
- `frontend/css/`, `frontend/js/`, and `frontend/assets/` contain frontend
  styles, scripts, and images. Apache rewrites the existing `/css`, `/js`, and
  `/assets` URLs to those directories.
- `admin/` and `backend/` contain server-side implementation, while root PHP
  endpoints preserve the existing public URLs.
- `database/schema.sql` is the schema and sample-data script.
- `config/` contains environment loading and the shared database connection.
- Private implementation directories are denied direct web requests by Apache.

## Repository and deployment notes

- `composer.json` and `composer.lock` are the dependency manifests; generated
  Composer files in `vendor/` are excluded from Git.
- `database/schema.sql` is the project schema and sample-data setup script. Do not
  replace it with a database export containing real customer or account data.
- Configure all PHP endpoints through the shared database configuration before
  deploying. Keep `.env` outside the public web root on hosted deployments
  where possible, and ensure the host honors the included access restrictions.
- Keep secrets and local configuration files out of source control. The
  repository's `.gitignore` excludes common credential files and local
  artifacts.

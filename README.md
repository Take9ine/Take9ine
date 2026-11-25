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
2. Import `T@ke9ine.sql` into MySQL using phpMyAdmin or the MySQL command line.
   The script creates the `T@ke9ine` database and its tables, and seeds sample
   room and staff records.
3. Run `composer install` from the project directory to install PHPMailer.
4. Configure Apache's PHP environment with `APP_ENV=local` when using the
   default local authentication database settings. Other application pages
   currently use the XAMPP defaults (`localhost`, database `T@ke9ine`, user
   `root`, and an empty password).
5. Open `http://localhost/T@ke9ine/` in a browser.

Password-reset email requires the Gmail API environment variables described in
[GMAIL_API_SETUP.md](GMAIL_API_SETUP.md). Do not put credentials in this
repository or in client-side code.

## Repository and deployment notes

- `composer.json` and `composer.lock` are the dependency manifests; generated
  Composer files in `vendor/` are excluded from Git.
- `T@ke9ine.sql` is the project schema and sample-data setup script. Do not
  replace it with a database export containing real customer or account data.
- Several PHP endpoints still use local XAMPP database settings. Before
  deploying to a public host, move those settings to protected server
  environment variables and review the deployment configuration.
- Keep secrets and local configuration files out of source control. The
  repository's `.gitignore` excludes common credential files and local
  artifacts.

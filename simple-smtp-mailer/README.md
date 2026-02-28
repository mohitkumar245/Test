# Simple SMTP Mailer

A lightweight WordPress plugin that routes `wp_mail()` through an SMTP server.

## Features

- SMTP host, port, and encryption configuration.
- Optional SMTP authentication (username/password).
- Optional custom `From` email and name.
- Settings page under **Settings → Simple SMTP Mailer**.

## Installation

1. Copy the `simple-smtp-mailer` folder into `wp-content/plugins/`.
2. Activate **Simple SMTP Mailer** in the WordPress admin.
3. Go to **Settings → Simple SMTP Mailer** and enter your SMTP details.

## Notes

- Leave **SMTP Host** empty to disable SMTP routing.
- Use `tls` with port `587` or `ssl` with port `465` depending on your mail provider.

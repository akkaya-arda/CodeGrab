# CodeGrab - Deployment & Installation Guide

This guide explains how to install and deploy CodeGrab release packages on your web server. CodeGrab supports two deployment structures depending on your hosting control panel permissions: **Single-Folder (Stealth)** and **Dual-Folder (Highly Secure)**.

---

## Prerequisites

- **PHP**: version `>= 8.3.0`
- **PHP Extensions**: `PDO`, `OpenSSL`, `Mbstring`, `Tokenizer`, `XML`, `Ctype`, `JSON`, `BCMath`, `Zip`
- **URL Rewriting**: Apache mod_rewrite enabled (or IIS URL Rewrite configured)
- **Writable Directories**: Ensure `storage` and `bootstrap/cache` are writable (`chmod 775` or `777`).

---

## Option 1: Single-Folder (Stealth) Deployment (Recommended for Shared Hosting)
This option is designed for basic shared hosting accounts (like cPanel) where you cannot upload files outside of the public web directory. It uses URL rewriting to tunnel public assets and protect sensitive Laravel backend directories.

### Steps:
1. Extract the release archive.
2. Open the `SINGLE-FOLDER-DEPLOYMENT` directory.
3. Upload all files and folders inside this directory directly into your public web root directory (e.g., `public_html`, `www`, or `httpdocs`).
4. Set permissions for the `storage` and `bootstrap/cache` folders to be writable by the web server.
5. Visit your domain: `https://yourdomain.com/install`.
6. Follow the installation wizard to finalize setup.

*Note: Web server `.htaccess` and `web.config` rules are pre-configured to return 403 Forbidden if someone attempts to access backend folders (`app`, `config`, `database`, `storage`, `.env`, etc.) directly.*

---

## Option 2: Dual-Folder (Secure) Deployment (Recommended for VPS or Advanced Hosting)
This option splits public web files from the backend core logic. The backend resides outside of the public directory, completely preventing any direct web access to sensitive config files, logs, or databases.

### Steps:
1. Extract the release archive.
2. Upload the contents of the `public_html` directory into your public web root directory (e.g., `/var/www/html` or `/home/user/public_html`).
3. Upload the `guard-helper-backend` directory to the folder parallel to your public folder (e.g., `/home/user/guard-helper-backend`).
4. Structure on server:
   ```
   /home/user/
   ├── guard-helper-backend/   <-- Contains Laravel core folders (app, config, storage)
   └── public_html/            <-- Contains public assets (index.html, index.php, htaccess)
   ```
5. Ensure the `guard-helper-backend/storage` directory has write permissions.
6. Open your browser and navigate to `https://yourdomain.com/install`.
7. Follow the installation wizard to finalize setup.

---

## Post-Installation Actions

### 1. Verification Code Retrieval
- To retrieve codes from **Gmail** or **Outlook**, configure the corresponding OAuth parameters under settings.
- For generic email hosting providers, set up the **IMAP** settings in the Administrator panel.

### 2. Cron Configuration
Configure a Cron Job to trigger the code extraction command automatically:
- Command: `cd /path/to/guard-helper-backend && php artisan schedule:run >> /dev/null 2>&1`
- Interval: Run every minute (`* * * * *`)

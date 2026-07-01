# CodeGrab - Open Source 2FA & Verification Code Proxy Portal

CodeGrab is a self-hosted, white-label 2FA and verification code extraction portal. It automates the retrieval of login codes from incoming emails (via Gmail, Outlook, or generic IMAP) and safely exposes them on a rate-limited Public Portal. Teammates, managers, or clients can access verification codes instantly without having direct access to the main credentials or primary email mailboxes.

This project is specially prepared and shared for the **R10.Net** webmaster community, focusing on solving shared-account bottlenecks, multi-profile management, and multi-agency delegation workflows.

---

## Key Features

- **Automated Heuristic Extraction**: Automatically extracts verification codes from platforms like Steam, Epic Games, Ubisoft, Netflix, Disney+, EA App, Riot Games, and Rockstar Games using regex filtering and heuristic content parsing.
- **Secure Access Delegation**: Generate rate-limited, expiring token links for specific mailboxes. Teammates can only access the codes they are authorized to view.
- **Public Portal Access**: Easily toggle global public access for specific accounts (e.g., sharing a Netflix or Disney+ verification code with family or clients).
- **Telegram Bot Webhooks**: Automatically dispatches notification logs and access details to Telegram channels or private chats.
- **Shared Hosting Optimized**: Features a low-footprint, automatic in-memory cache system with expired key pruning, reducing resource consumption on limited shared hosting plans.
- **Production-Ready Installation Wizard**: Includes a simple, classic installation wizard that checks PHP extensions, configures database credentials, auto-generates secure encryption keys, and sets up production variables automatically.
- **Database Safety Guard**: Detects MAC mismatches on pre-existing database tables and offers a one-click database reset option during installation.

---

## Core Scenarios & Usage Workflows

### Scenario A: Team Delegation in Webmaster & Marketing Agencies
1. **The Administrator** sets up the platform credentials and connects the company's master email address (IMAP or Google/Microsoft OAuth) in the Admin panel.
2. The Admin generates an invitation token (e.g. valid for 10 code fetches) for a media buyer or developer, assigning them access to specific platforms.
3. The **Teammate** visits the invitation link (Public Portal). When they attempt to sign in to the platform, the platform sends a 2FA code to the master email.
4. The teammate fetches the code from the Public Portal in one click. They never see the master email password or other unrelated emails.

### Scenario B: Shared Streaming Account Access (Netflix, Disney+)
1. The Admin activates the **Public Access Portal** mode and configures IMAP for the shared streaming email account.
2. The Admin shares the CodeGrab public portal link with the family members or clients.
3. When a user tries to log in to Netflix on a smart TV and triggers a code, they open the CodeGrab portal, select Netflix, and retrieve the code dynamically.
4. The system caches the code for 30 seconds to minimize mail server API requests, automatically cleaning up expired cache memory.

### Scenario C: Webhook Integrations (N8N, Make, or custom scripts)
1. Developers dispatch POST requests containing authorization details to `/api/webhook/generate-access` using the API secret key.
2. CodeGrab generates the required access token and immediately sends the access link to the configured Telegram bot.
3. Teammates can query the Telegram bot to grab the code on the go.

---

## Installation

1. Upload the project to your server (maintains a public folder layout structure).
2. Point your web browser to your site's root or `/install` (e.g., `https://yourdomain.com/install`).
3. Follow the installation wizard steps:
   - **Step 1**: Enter an existing encryption key or choose auto-generation.
   - **Step 2**: Provide your database credentials (supports MySQL, PostgreSQL, and SQLite). The wizard validates the connection and checks for existing tables.
   - **Step 3**: Define system brand names, set up Telegram notifications, and create your Administrator account.
4. Once completed, the wizard automatically configures `.env` values, sets `APP_DEBUG=false`, sets `APP_ENV=production`, and creates the `storage/installed` configuration lock.

---

## Technology Stack

- **Backend**: Laravel 11, PHP >= 8.3
- **Frontend**: Angular UI, Bootstrap, CSS Signals
- **Database**: MySQL, PostgreSQL, or SQLite

---

## License

This project is licensed under the MIT License - see the LICENSE file for details.

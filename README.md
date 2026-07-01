# CodeGrab

## Self-hosted 2FA & Verification Code Delegation Portal

CodeGrab is a self-hosted verification code management platform designed to solve one of the biggest problems in shared account environments:

**How can teams access 2FA verification codes without sharing mailbox credentials?**

Instead of exposing primary email accounts, CodeGrab connects to mail providers, detects incoming verification emails, extracts security codes automatically, and provides controlled access through temporary, permission-based access links.

Built for agencies, teams, shared environments, and anyone managing multiple accounts that require frequent verification.

---

## Why CodeGrab?

Managing shared accounts often creates security and workflow problems:

* Sharing email passwords with employees or clients
* Giving access to unrelated emails inside the mailbox
* Losing control over who accessed verification codes
* Manually forwarding 2FA emails repeatedly
* Managing dozens of accounts across different platforms

CodeGrab introduces a delegation layer between the mailbox and the user.

```
Email Provider
(Gmail / Outlook / IMAP)
        |
        |
        v
   CodeGrab Engine
        |
        |
        +----------------+
        |                |
        v                v
 Admin Panel       Public Access Portal
        |                |
        |                |
        v                v
 Manage Accounts   Retrieve Authorized Codes
```

Users only receive access to the verification flow they are authorized for.

---

# Features

## Automated OTP Extraction Engine

CodeGrab includes a flexible extraction engine capable of identifying verification codes from incoming emails.

Supported extraction methods:

* Custom Regex patterns
* Heuristic content analysis
* Keyword proximity scoring
* Platform-specific extraction rules

Example supported services:

* Steam
* Epic Games
* Ubisoft
* EA App
* Riot Games
* Rockstar Games
* Netflix
* Disney+
* Custom user-defined platforms

The extraction engine does not depend on fixed templates. Administrators can define their own rules.

---

# Secure Access Delegation

Generate temporary access grants instead of sharing credentials.

Each access token can define:

* Expiration time
* Maximum usage count
* Assigned platform
* Assigned mailbox
* Visibility settings

Example:

```
Steam Account
        |
        |
        v
Access Grant
10 uses
Expires in 24 hours
        |
        |
        v
Employee / Client
```

The recipient never needs:

* Mailbox password
* IMAP credentials
* OAuth tokens
* Access to unrelated emails

---

# Supported Mail Providers

## Gmail

OAuth2 authentication.

No mailbox password storage required.

## Outlook / Microsoft

OAuth2 authentication.

Uses delegated access tokens.

## Generic IMAP

Supports custom mail providers:

* cPanel mailboxes
* Private domains
* Custom email servers

---

# Public Portal

CodeGrab includes a lightweight public-facing portal where authorized users can retrieve verification codes.

Features:

* Token-based access
* Rate limiting
* Expiration control
* Usage tracking
* Access logging
* Optional email hiding

Example workflow:

```
User opens token link

        ↓

Selects platform

        ↓

CodeGrab checks permissions

        ↓

Fetches latest verification email

        ↓

Extracts OTP code

        ↓

Returns verification code
```

---

# Telegram Integration

CodeGrab can integrate with Telegram bots for automation workflows.

Supported actions:

* Generate access links
* Receive system notifications
* Monitor code retrieval events

Example:

```
/generate-token steam 24h 5
```

Automatically creates a controlled access link.

---

# Webhook Automation

CodeGrab provides webhook endpoints for external automation tools.

Compatible with:

* n8n
* Make
* Custom scripts
* Internal dashboards

Example workflow:

```
Automation Trigger

        ↓

Webhook Request

        ↓

Generate Access Token

        ↓

Send Notification

        ↓

User Retrieves Code
```

---

# Security Features

## Credential Encryption

Sensitive data is encrypted before database storage.

Protected information includes:

* Platform passwords
* API tokens
* Mail credentials

Uses Laravel encryption with application key validation.

---

## Installation Integrity Protection

CodeGrab detects encryption mismatches during installation.

If an existing database was created with another encryption key:

* Decryption integrity is checked
* Corrupted configuration is detected
* Optional database reset is provided

---

## Rate Limiting

Protected endpoints include:

* Public code retrieval
* Authentication endpoints
* Webhooks
* Sensitive actions

---

## Shared Hosting Friendly

Designed to run even on limited hosting environments.

Optimizations include:

* Lightweight caching
* Automatic cache cleanup
* Reduced background polling
* No external queue requirement

Compatible with:

* cPanel hosting
* VPS environments
* Dedicated servers

---

# Installation

Requirements:

* PHP >= 8.3
* Laravel compatible hosting
* MySQL / PostgreSQL / SQLite
* Required PHP extensions:

  * OpenSSL
  * PDO
  * IMAP (for IMAP accounts)

---

Installation steps:

1. Upload CodeGrab to your server

2. Open:

```
https://your-domain.com/install
```

3. Complete the installation wizard:

* Environment validation
* Database configuration
* Encryption setup
* Administrator creation
* Production configuration

After installation:

* APP_DEBUG is disabled
* Production mode is enabled
* Installation lock is created

---

# Technology Stack

## Backend

* Laravel
* PHP 8.3+
* Laravel Sanctum
* Eloquent ORM

## Frontend

* Angular
* Bootstrap
* Angular Signals
* Vanilla CSS

## Database

Supported:

* MySQL
* PostgreSQL
* SQLite

---

# Project Architecture

CodeGrab follows a modular service-oriented architecture.

Main components:

```
app/
 ├── Infrastructure/
 │    ├── Gmail
 │    ├── Outlook
 │    └── IMAP
 │
 ├── Services/
 │    └── CodeExtractor
 │
 ├── Models/
 │
 ├── Controllers/
 │
 └── Notifications/
      ├── Telegram
      └── SMTP
```

---

# Open Source

CodeGrab is released under the MIT License.

You are free to:

* Use
* Modify
* Extend
* Self-host
* Integrate into your own workflows

---

# Disclaimer

CodeGrab is designed for legitimate account management, team collaboration, and delegated verification workflows.

Users are responsible for ensuring they have authorization to access connected accounts and services.

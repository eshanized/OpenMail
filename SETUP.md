# OpenMail — Complete Setup & User Guide

Welcome to **OpenMail**! OpenMail is a modern, self-hosted webmail application designed to bring a clean, fast, Gmail-like experience to your company's existing email infrastructure.

This guide is written specifically for **non-technical users and administrators**. You do **not** need to use the command line, run terminal commands, or edit complex code files. Everything is designed to be installed and managed through your hosting control panel (like **cPanel**) and your web browser.

---

## Table of Contents
1. [Prerequisites](#1-prerequisites)
2. [Quick Deployment Overview](#2-quick-deployment-overview)
3. [Step 1: Uploading OpenMail to cPanel](#step-1-uploading-openmail-to-cpanel)
4. [Step 2: Pointing Your Domain to the Public Folder](#step-2-pointing-your-domain-to-the-public-folder)
5. [Step 3: Creating Your MySQL Database](#step-3-creating-your-mysql-database)
6. [Step 4: Verifying Folder Permissions](#step-4-verifying-folder-permissions)
7. [Step 5: The Web Setup Wizard (Step-by-Step)](#step-5-the-web-setup-wizard-step-by-step)
8. [Step 6: Logging In & Using OpenMail](#step-6-logging-in--using-openmail)
9. [Troubleshooting & Frequently Asked Questions](#troubleshooting--frequently-asked-questions)

---

## 1. Prerequisites

Before starting, make sure you have:

- **Shared Web Hosting or VPS** with **cPanel** (or any hosting panel like DirectAdmin, Plesk, or CyberPanel).
- **PHP 8.2, 8.3, or 8.4** installed and enabled on your hosting account.
- **MySQL 8.0+ or MariaDB 10.6+** (standard on almost all cPanel hosts).
- **Your Email Server Details** (IMAP & SMTP hostnames and ports). Usually:
  - IMAP Host: `mail.yourdomain.com` (Port: `993`, Encryption: `SSL/TLS`)
  - SMTP Host: `mail.yourdomain.com` (Port: `465`, Encryption: `SSL/TLS`)
- **The Deployment Package**: `openmail-deploy.zip` (pre-bundled with all dependencies and compiled frontend assets).

---

## 2. Quick Deployment Overview

The installation follows the familiar **WordPress-style pattern**:

```mermaid
graph LR
    A[1. Upload ZIP] --> B[2. Extract Files]
    B --> C[3. Create Database]
    C --> D[4. Open Browser]
    D --> E[5. Web Setup Wizard]
```

You upload the zip file, extract it, create an empty database in cPanel, and open your domain in your browser. The setup wizard does the rest!

---

## Step 1: Uploading OpenMail to cPanel

1. Log in to your **cPanel dashboard**.
2. Under the **Files** section, click **File Manager**.
3. Choose where you want to install OpenMail:
   - **Recommended**: In your home root folder (`/home/yourusername/`), create a new directory called `openmail`.
   - **Alternative**: If installing directly inside your main web directory, open `public_html`.
4. Click the **Upload** button in the top toolbar.
5. Drag and drop your `openmail-deploy.zip` file. Wait until the upload progress bar turns **100% green**.
6. Go back to File Manager, select `openmail-deploy.zip`, right-click it, and click **Extract**.
7. Once extracted, you can delete the `openmail-deploy.zip` file to save disk space.

---

## Step 2: Pointing Your Domain to the Public Folder

For security, modern web applications like OpenMail keep their core system files protected and expose only the `public` directory to the internet.

### Option A: Subdomain or New Domain (Recommended)
If you are installing OpenMail on a subdomain (like `mail.yourcompany.com` or `webmail.yourcompany.com`):
1. In cPanel, go to **Domains** (or **Subdomains**).
2. Click **Create A New Domain** or add your subdomain.
3. In the **Document Root** field, enter:
   - `openmail/public` (if you extracted into the `openmail` folder)
   - OR `public_html/public` (if you extracted into `public_html`).
4. Click **Submit**.

### Option B: Main Domain (Where Document Root cannot be changed)
Some shared hosting providers lock your primary domain strictly to `public_html`. If you extracted OpenMail inside `public_html`, do this:
1. In cPanel File Manager, make sure **Show Hidden Files (dotfiles)** is enabled in Settings (top right gear icon).
2. Inside `public_html`, check if a file named `.htaccess` exists. If not, click **+ File** and name it `.htaccess`.
3. Right-click `.htaccess`, select **Edit**, and paste the following code:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_URI} !^/public/
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```
4. Click **Save Changes**. Now all visitors to your domain are automatically routed into the secure `public/` directory!

---

## Step 3: Creating Your MySQL Database

OpenMail stores user accounts, address books, signatures, and cached message headers in a MySQL database.

1. In cPanel, navigate to the **Databases** section and click **MySQL® Databases** (or **MySQL® Database Wizard**).
2. **Create Database**:
   - In *New Database*, type a name (e.g. `openmail`).
   - Note the full name, which usually includes your cPanel prefix (e.g., `company_openmail`). Click **Create Database**.
3. **Create Database User**:
   - Scroll down to *Add New User*.
   - Username: e.g. `mailuser` (full name will be `company_mailuser`).
   - Password: Click **Password Generator** to create a strong password. **Copy this password somewhere safe!**
   - Click **Create User**.
4. **Link User to Database**:
   - Scroll down to *Add User to Database*.
   - Select your user and your database from the dropdowns and click **Add**.
   - On the next screen, check the box for **ALL PRIVILEGES** and click **Make Changes**.

> [!NOTE]
> Keep your Database Name, Username, and Password handy. You will enter them in Step 3 of the browser wizard.

---

## Step 4: Verifying Folder Permissions

OpenMail needs permission to write session data and cache files. In 99% of cPanel servers, permissions are already configured correctly automatically.

To double-check:
1. In cPanel **File Manager**, locate the `storage` and `bootstrap/cache` folders.
2. The permission numbers should be `755` (or `775`).
3. If they show `644` or `700`, right-click each folder, select **Change Permissions**, check the read/write/execute boxes until the permission value is `755`, and click **Change Permissions**.

---

## Step 5: The Web Setup Wizard (Step-by-Step)

Now open your web browser (Chrome, Firefox, Safari, Edge) and go to:
```
https://yourdomain.com/
```
OpenMail will automatically detect that it is not yet installed and redirect you to `https://yourdomain.com/install`.

You will see the 8-step OpenMail setup wizard:

### Step 1: Welcome
- Choose your application name (default: `OpenMail` or your company name, e.g. `Acme Mail`).
- Click **Continue**.

### Step 2: System Requirements Check
- The installer automatically tests your server for PHP version and required extensions (`pdo_mysql`, `mbstring`, `curl`, `xml`, etc.) and folder write permissions.
- Everything will show green checkmarks (✓).
- If any extension shows a red warning, see the [Troubleshooting](#troubleshooting--frequently-asked-questions) section to enable it in cPanel with one click.
- Click **Continue**.

### Step 3: Database Configuration
- **Database Host**: Usually `localhost` or `127.0.0.1` (on shared cPanel, leave as `localhost` or `127.0.0.1`).
- **Port**: `3306` (standard MySQL port).
- **Database Name**: The full database name from Step 3 (e.g., `company_openmail`).
- **Database Username**: The full username from Step 3 (e.g., `company_mailuser`).
- **Database Password**: The password you generated in Step 3.
- Click **Test Connection**. Once confirmed with a green checkmark, click **Run Migrations & Continue**. The wizard will set up all the database tables automatically.

### Step 4: Mail Server (IMAP & SMTP) Configuration
- You can enter an email address (e.g. `info@yourcompany.com`) and click **Auto-Detect**, or enter your company mail server details directly:
  - **IMAP Host**: e.g., `mail.yourcompany.com`
  - **IMAP Port**: `993`
  - **IMAP Encryption**: `SSL/TLS`
  - **SMTP Host**: e.g., `mail.yourcompany.com`
  - **SMTP Port**: `465`
  - **SMTP Encryption**: `SSL/TLS`
- Click **Test Mail Connection**. Once green, click **Continue**.

### Step 5: Organization Settings
- **Organization Name**: Your company or team name.
- **Company Domain**: Your primary email domain (e.g., `yourcompany.com`).
- **Timezone**: Select your local timezone from the dropdown.
- Click **Continue**.

### Step 6: Administrator Account Creation
- Create your primary administrator user:
  - **Full Name**: e.g. `Admin User`
  - **Email Address**: Your company email address (e.g. `admin@yourcompany.com`).
  - **Password**: Create a strong password (at least 8 characters, with letters and numbers).
- Click **Create Account & Continue**.

### Step 7: Security Options
- Select your security options:
  - **Force HTTPS**: Recommended (Yes).
  - **Secure Cookies**: Yes (when using HTTPS).
  - **Session Expiry**: Time before an idle user is logged out (default: 1440 minutes / 24 hours).
- Click **Continue**.

### Step 8: Verification & Complete
- The wizard runs a complete health check: database connected, mail settings verified, administrator account ready.
- Click **Complete Installation**.
- OpenMail locks the installer for security and redirects you straight to the **Login Screen**!

---

## Step 6: Logging In & Using OpenMail

### 1. Logging In
- Navigate to `https://yourdomain.com/login`.
- Enter your email address and password.
- You are now in your OpenMail webmail dashboard!

### 2. Reading Emails & Folders
- The left sidebar lists your email folders: **Inbox**, **Sent**, **Drafts**, **Trash**, **Archive**, and any custom IMAP folders.
- Click on any message in the list to read it.
- Messages in the same conversation are automatically threaded together (JWZ conversation algorithm), just like in Gmail.

### 3. Composing & Sending
- Click the **Compose** button in the top left.
- Enter recipients in the `To`, `Cc`, or `Bcc` fields. As you type, OpenMail suggests matching contacts.
- Format your email with the rich text editor (bold, italics, bullet points, links).
- Drag and drop files to attach them (up to 25 MB per file).
- Click **Send**. If you make a mistake, an **Undo Send** popup appears for a few seconds so you can cancel delivery.

### 4. Labels & Organization
- You can create custom color-coded labels (e.g., "Urgent", "Finance", "Clients").
- Apply labels to messages to organize your inbox without moving emails out of folders.

### 5. Signatures & Preferences
- Click on your profile avatar (top right) and open **Settings**.
- **Signatures**: Create one or more formatted email signatures and pick your default.
- **Appearance**: Toggle between Light Mode and Dark Mode, and adjust message list density (Compact, Regular, Comfortable).
- **Security**: View currently active browser sessions and log out of remote devices with one click.

---

## Troubleshooting & Frequently Asked Questions

### 1. The browser shows "404 Not Found" when visiting `/install`
- **Cause**: OpenMail has already been installed, or an old `storage/installed` file exists.
- **Fix**: If you need to re-install from scratch, open cPanel File Manager, navigate to `storage/`, and delete the file named `installed`. Then visit `/install` again.

### 2. The browser shows "Index of /" or lists files instead of the website
- **Cause**: The web server is looking at the root folder instead of the `public/` folder.
- **Fix**: Follow [Step 2](#step-2-pointing-your-domain-to-the-public-folder). Either change the Document Root in cPanel to point to `public`, or create the `.htaccess` file inside `public_html`.

### 3. A PHP extension shows a red cross in Step 2 of the wizard
- **Cause**: An extension like `pdo_mysql`, `mbstring`, or `intl` is not enabled in your PHP profile.
- **Fix in cPanel**:
  1. Go to cPanel home and search for **Select PHP Version** (or **MultiPHP INI Editor**).
  2. Make sure your PHP version is set to **8.2** or **8.3**.
  3. Click on the **Extensions** tab.
  4. Check the box for the missing extension (e.g. `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `intl`).
  5. Return to the browser wizard and click **Refresh / Check Again**.

### 4. "Database Connection Failed" in Step 3
- **Cause**: Incorrect database name, username, or password.
- **Fix**:
  - Make sure you included the cPanel username prefix (e.g., `company_openmail`, not just `openmail`).
  - Make sure you added the user to the database and clicked **ALL PRIVILEGES** in cPanel (Step 3).
  - Use `127.0.0.1` or `localhost` as the database host.

### 5. "IMAP Connection Failed" in Step 4
- **Cause**: Wrong port, encryption type, or server address.
- **Fix**:
  - For standard cPanel mail: use `mail.yourdomain.com` with Port `993` and `SSL/TLS`.
  - Check with your hosting provider if external mail server ports (like 993/465) are blocked by a hosting firewall.

### 6. The website displays "HTTP 500 Internal Server Error"
- **Cause**: Incorrect file permissions on `storage` or `bootstrap/cache`.
- **Fix**: In cPanel File Manager, make sure permissions for `storage/` and `bootstrap/cache/` are set to `755` (or `775`). Also verify your server is running PHP 8.2 or newer.

---

*Enjoy using OpenMail! For questions, bug reports, or feature requests, visit the project repository.*

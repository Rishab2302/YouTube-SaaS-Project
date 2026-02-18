# Tech Stack — Todo List Tracker Application

## Overview

This document defines the technology stack for a Todo List Tracker application built on a traditional LAMP stack with modern frontend enhancements using HTMX and Alpine.js. This file is intended to be used as a reference for Claude Code when building the application.

---

## Server Environment

- **Operating System:** Ubuntu 24.04 LTS (Noble Numbat)
- **Web Server:** Apache 2.4+ with `mod_rewrite` enabled
- **Database:** MySQL 8.0+ (or MariaDB 10.11+ as a drop-in alternative)
- **Server-Side Language:** PHP 8.3+ with the following extensions:
  - `php-mysql` (PDO MySQL driver)
  - `php-mbstring`
  - `php-xml`
  - `php-curl`
  - `php-json` (bundled by default in PHP 8.x)

### Directory Structure Convention

The application should follow a standard LAMP project layout with a public-facing `public/` directory configured as the Apache `DocumentRoot`, and application logic kept outside the web root.

```
/var/www/todo-app/
├── public/             # Apache DocumentRoot
│   ├── index.php       # Front controller / entry point
│   ├── css/
│   ├── js/
│   └── .htaccess       # URL rewriting rules
├── src/                # PHP application source code
│   ├── controllers/
│   ├── models/
│   ├── views/          # PHP template files
│   └── helpers/
├── config/             # Database and app configuration
├── migrations/         # SQL migration files
├── vendor/             # Composer dependencies (if used)
├── tech-stack.md       # This file
└── README.md
```

---

## Frontend Stack

### Bootstrap 5.3

- **Source:** https://getbootstrap.com/
- **Version:** 5.3.x (latest stable)
- **Include via CDN:**
  - CSS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css`
  - JS Bundle: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js`
- **Note:** Bootstrap 5 does **not** require jQuery. The Bootstrap JS bundle includes Popper.js for dropdowns, tooltips, and popovers. jQuery should only be included if explicitly needed for custom application logic outside of Bootstrap.

### HTMX

- **Version:** Latest stable (2.x)
- **Include via CDN:** `https://unpkg.com/htmx.org`
- **Purpose:** Provides AJAX-driven partial page updates without writing custom JavaScript. The server returns HTML fragments that HTMX swaps into the DOM.
- **Key usage patterns for this app:**
  - `hx-get`, `hx-post`, `hx-put`, `hx-delete` for CRUD operations on todos
  - `hx-target` to specify which DOM element receives the server response
  - `hx-swap` to control how content is inserted (e.g., `innerHTML`, `outerHTML`, `beforeend`)
  - `hx-trigger` for custom event-driven requests
  - `hx-confirm` for delete confirmations
  - `hx-indicator` for loading spinners during requests
- **Server-side pattern:** PHP endpoints should detect HTMX requests via the `HX-Request` header and return HTML fragments (not full pages) when present.

### Alpine.js

- **Version:** Latest stable (3.x)
- **Include via CDN:** `https://unpkg.com/alpinejs` (with `defer` attribute)
- **Purpose:** Lightweight client-side reactivity for UI state that doesn't require server roundtrips.
- **Key usage patterns for this app:**
  - `x-data` for component-level state (e.g., toggling edit mode on a todo, managing filter state)
  - `x-show` / `x-if` for conditional rendering (e.g., showing/hiding edit forms inline)
  - `x-on` for handling UI interactions (e.g., click, keyup events)
  - `x-bind` for dynamic attribute binding (e.g., conditional CSS classes)
  - `x-transition` for smooth show/hide animations
- **Interaction with HTMX:** Alpine.js handles local UI state while HTMX handles server communication. They complement each other — Alpine manages what the user sees instantly, HTMX manages what persists to the server.

### jQuery (Optional)

- **Version:** 3.7.x (latest stable)
- **Include via CDN:** `https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js`
- **Note:** jQuery is **not required** by Bootstrap 5.3. Include it only if the application has specific needs for jQuery plugins or if developer preference dictates its use. For most interactions, HTMX and Alpine.js will be sufficient.

---

## Frontend Script Loading Order

Scripts should be loaded in the following order in the base layout template:

```html
<!-- CSS (in <head>) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/css/app.css">

<!-- JS (before </body>) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script> <!-- Only if needed -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/htmx.org"></script>
<script defer src="https://unpkg.com/alpinejs"></script>
<script src="/js/app.js"></script>
```

---

## Database Schema Guidelines

- Use InnoDB engine for all tables.
- Use `utf8mb4` character set and `utf8mb4_unicode_ci` collation.
- Primary keys should be auto-incrementing unsigned integers or UUIDs depending on preference.
- Include `created_at` and `updated_at` timestamp columns on all tables.
- Use prepared statements (PDO) for all database queries to prevent SQL injection.

### Core Table: `todos`

| Column        | Type                          | Description                          |
|---------------|-------------------------------|--------------------------------------|
| `id`          | INT UNSIGNED AUTO_INCREMENT   | Primary key                          |
| `title`       | VARCHAR(255) NOT NULL         | Todo item title                      |
| `description` | TEXT NULL                     | Optional detailed description        |
| `is_completed`| TINYINT(1) DEFAULT 0          | Completion status (0 = false, 1 = true) |
| `priority`    | ENUM('low','medium','high') DEFAULT 'medium' | Priority level     |
| `due_date`    | DATE NULL                     | Optional due date                    |
| `created_at`  | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Record creation time           |
| `updated_at`  | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last modified |

---

## Architecture & Design Patterns

### Request Flow

1. All requests hit `public/index.php` via Apache `mod_rewrite`.
2. A simple PHP router dispatches to the appropriate controller.
3. Controllers interact with models (which use PDO for database access).
4. Controllers render PHP view templates.
5. For HTMX requests (detected via `HX-Request` header), controllers return only the HTML fragment that changed — not the full page layout.

### Key Architectural Principles

- **No frontend build step.** All frontend assets are loaded via CDN or static files. No Node.js, npm, Webpack, or Vite is required.
- **Server-rendered HTML.** PHP renders all HTML. HTMX enhances the experience by swapping HTML fragments without full page reloads.
- **Progressive enhancement.** The app should function with basic form submissions even if JavaScript fails to load. HTMX and Alpine.js enhance the experience but are not strictly required for core functionality.
- **Separation of concerns.** Keep SQL out of controllers, keep HTML out of models, keep business logic out of views.

---

## Development & Deployment Notes

- **Local development:** Use PHP's built-in server (`php -S localhost:8000 -t public/`) or a local Apache + MySQL setup.
- **Dependency management:** Use Composer if any PHP packages are needed (e.g., a router library, dotenv for config). Otherwise, keep it dependency-free.
- **Configuration:** Store database credentials and environment settings in a `config/` directory outside the web root. Use a `.env` file pattern if Composer is available with `vlucas/phpdotenv`.
- **Version control:** Include a `.gitignore` that excludes `vendor/`, `.env`, and any IDE-specific files.

---

## Summary

| Layer     | Technology                     | Version  |
|-----------|--------------------------------|----------|
| OS        | Ubuntu                         | 24.04 LTS |
| Web Server| Apache                         | 2.4+     |
| Database  | MySQL                          | 8.0+     |
| Backend   | PHP                            | 8.3+     |
| CSS Framework | Bootstrap                  | 5.3.x    |
| AJAX/Hypermedia | HTMX                      | 2.x      |
| Client Reactivity | Alpine.js                | 3.x      |
| jQuery    | jQuery (optional)              | 3.7.x    |

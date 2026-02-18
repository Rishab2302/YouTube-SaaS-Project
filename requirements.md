# Requirements Document — TaskFlow Todo List Tracker

## Document Context

This requirements document defines the functional and non-functional requirements for TaskFlow, a SaaS todo list tracker application. It should be read alongside `tech-stack.md` (technology choices) and `design-notes.md` (UI/UX specifications). Together, these three documents provide Claude Code with everything needed to build the application end to end.

---

## 1. Product Overview

### 1.1 Purpose

TaskFlow is a multi-tenant, web-based SaaS application that allows individual users to create, organize, and track their tasks across multiple views — list, kanban board, and calendar. The application runs on a LAMP stack (Linux, Apache, MySQL, PHP) with Bootstrap 5.3, HTMX, and Alpine.js on the frontend.

### 1.2 Target Users

Individual professionals, freelancers, and small teams who need a straightforward task management tool accessible from any web browser. No desktop or native mobile client is required — the application is fully responsive for mobile browsers.

### 1.3 Core Value Proposition

A fast, lightweight, server-rendered task manager with no JavaScript build tooling, no heavy SPA framework, and minimal client-side complexity. Pages load as HTML; HTMX provides seamless partial updates; Alpine.js handles ephemeral UI state.

---

## 2. User Authentication & Account Management

### 2.1 Registration

- **REQ-AUTH-001**: Users can register with an email address and password.
- **REQ-AUTH-002**: Email must be unique across the system. Registration with a duplicate email must return a clear validation error.
- **REQ-AUTH-003**: Password must meet the following minimum requirements: at least 8 characters, at least one uppercase letter, at least one lowercase letter, and at least one number.
- **REQ-AUTH-004**: Passwords must be hashed using PHP's `password_hash()` with the `PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID` algorithm. Plaintext passwords must never be stored or logged.
- **REQ-AUTH-005**: Upon successful registration, the system sends a verification email with a unique, time-limited token (expires after 24 hours).
- **REQ-AUTH-006**: Users cannot log in until their email is verified. Unverified users who attempt to log in receive a message with an option to resend the verification email.
- **REQ-AUTH-007**: The registration form collects: first name, last name, email, password, and password confirmation.
- **REQ-AUTH-008**: All registration form fields must be validated both client-side (HTML5 validation + Alpine.js) and server-side (PHP). Server-side validation is the authoritative check.

### 2.2 Login

- **REQ-AUTH-010**: Users log in with their email and password.
- **REQ-AUTH-011**: On successful login, the server creates a PHP session and stores the user ID in `$_SESSION`.
- **REQ-AUTH-012**: Sessions must use secure, HTTP-only cookies. The `SameSite` attribute must be set to `Lax` or `Strict`.
- **REQ-AUTH-013**: Implement a "Remember Me" checkbox that extends the session lifetime to 30 days using a persistent, hashed token stored in the database (not by extending PHP's session timeout).
- **REQ-AUTH-014**: After 5 consecutive failed login attempts for the same email address, the account is temporarily locked for 15 minutes. A clear message is displayed to the user.
- **REQ-AUTH-015**: All login attempts (successful and failed) must be logged in a `login_attempts` table with IP address, user agent, timestamp, and result.
- **REQ-AUTH-016**: After login, the user is redirected to the Dashboard page. If the user was attempting to access a protected URL before being redirected to login, they should be sent to that original URL after authentication.

### 2.3 Logout

- **REQ-AUTH-020**: A "Sign Out" link in the sidebar destroys the PHP session, clears all session cookies, and invalidates any "Remember Me" token.
- **REQ-AUTH-021**: After logout, the user is redirected to the login page.
- **REQ-AUTH-022**: Sessions expire automatically after 2 hours of inactivity (configurable).

### 2.4 Password Reset

- **REQ-AUTH-030**: A "Forgot Password?" link on the login page allows users to request a password reset by entering their email.
- **REQ-AUTH-031**: If the email exists, the system sends a password reset email with a unique, time-limited token (expires after 1 hour). If the email does not exist, the same generic success message is displayed to prevent email enumeration.
- **REQ-AUTH-032**: The reset link directs the user to a form where they enter a new password and confirmation. The token is validated server-side before displaying the form.
- **REQ-AUTH-033**: After a successful password reset, all existing sessions and "Remember Me" tokens for that user are invalidated.
- **REQ-AUTH-034**: Used or expired reset tokens must be rejected with a clear message and an option to request a new reset.

### 2.5 Profile Management

- **REQ-AUTH-040**: Authenticated users can access a "Settings" or "Profile" page to update their first name, last name, and email address.
- **REQ-AUTH-041**: Changing the email address requires re-verification via email before the new address takes effect. The old email remains active until the new one is confirmed.
- **REQ-AUTH-042**: Users can change their password from the Profile page. The current password must be provided and verified before the new password is accepted.
- **REQ-AUTH-043**: Users can delete their own account. This action requires password confirmation and a confirmation modal. Account deletion is soft-delete by default (sets a `deleted_at` timestamp), with data being permanently purged after 30 days.
- **REQ-AUTH-044**: Users can set their preferred theme (light, dark, or auto) from the Profile page. This preference is stored in the database and applied on login, overriding any `localStorage` value.
- **REQ-AUTH-045**: Users can configure their timezone from the Profile page. All dates and times displayed in the application should be localized to the user's timezone.

---

## 3. Task Management (Core CRUD)

### 3.1 Creating Tasks

- **REQ-TASK-001**: Authenticated users can create a new task. Each task belongs to the user who created it.
- **REQ-TASK-002**: The minimum required field to create a task is the title (up to 255 characters).
- **REQ-TASK-003**: Optional fields at creation time: description (text, unlimited length), priority (low, medium, high — defaults to medium), status (backlog, todo, in_progress, review, done — defaults to todo), due date, and category.
- **REQ-TASK-004**: Tasks can be created from three entry points: the quick-add dropdown in the navbar (title + priority + due date only), the full "New Task" form accessible from any view, and the "+" button within a Kanban column (pre-sets the status to that column's status).
- **REQ-TASK-005**: After creation, the new task appears in the current view without a full page reload (via HTMX `hx-swap`). A success toast notification is displayed.

### 3.2 Reading / Viewing Tasks

- **REQ-TASK-010**: Users can only view their own tasks. A user must never see another user's tasks.
- **REQ-TASK-011**: Tasks are viewable in three primary views: list (default on "My Tasks" page), kanban board, and calendar.
- **REQ-TASK-012**: The list view displays tasks in a table with columns: checkbox (completion toggle), title, priority, due date, status, and actions.
- **REQ-TASK-013**: Clicking a task title or an "Edit" action opens the task detail/edit view in a modal.
- **REQ-TASK-014**: The task detail view shows all task fields, creation date, last modified date, and any sub-tasks or checklist items.

### 3.3 Updating Tasks

- **REQ-TASK-020**: Users can update any field of a task they own.
- **REQ-TASK-021**: Toggling a task's completion status (checkbox) in the list or kanban view updates the task via HTMX without opening a modal or navigating away. A brief success toast is shown.
- **REQ-TASK-022**: Dragging a task card from one Kanban column to another updates its status field via an HTMX request on drop.
- **REQ-TASK-023**: The task title can be edited inline in the list view (click-to-edit pattern using Alpine.js), with changes saved via HTMX on blur or Enter.
- **REQ-TASK-024**: The full edit form (in modal) allows updating all fields. Changes are submitted via HTMX and the modal closes on success with a toast notification.
- **REQ-TASK-025**: The `updated_at` timestamp is automatically set by the database on every update.

### 3.4 Deleting Tasks

- **REQ-TASK-030**: Users can delete a task they own. Deletion triggers a confirmation modal before proceeding.
- **REQ-TASK-031**: Deletion is a soft-delete — the task row receives a `deleted_at` timestamp and no longer appears in any view.
- **REQ-TASK-032**: Soft-deleted tasks can be recovered from a "Trash" view accessible from the sidebar within 30 days.
- **REQ-TASK-033**: Tasks in the Trash can be permanently deleted or restored. Permanent deletion removes the row from the database.
- **REQ-TASK-034**: After 30 days, soft-deleted tasks are permanently purged by a scheduled cron job or on-demand cleanup.

### 3.5 Sub-Tasks / Checklist Items

- **REQ-TASK-040**: Each task can have zero or more sub-tasks (checklist items). Sub-tasks have a title and a completed boolean.
- **REQ-TASK-041**: Sub-tasks are displayed within the task detail modal as a list of checkboxes.
- **REQ-TASK-042**: Sub-tasks can be added, toggled, reordered (via a sort_order field), and deleted from within the task detail modal.
- **REQ-TASK-043**: Sub-task toggling and adding are performed via HTMX without closing the modal.
- **REQ-TASK-044**: The parent task's detail view shows a sub-task progress indicator (e.g., "3 of 5 completed").

---

## 4. Views

### 4.1 Dashboard

- **REQ-VIEW-001**: The Dashboard is the landing page after login.
- **REQ-VIEW-002**: It displays four summary stat cards: Total Tasks, Completed Tasks, In Progress Tasks, and Overdue Tasks (tasks past their due date and not completed).
- **REQ-VIEW-003**: A progress bar shows the overall completion percentage for the current week (Monday–Sunday).
- **REQ-VIEW-004**: A "Recent Tasks" section shows the 10 most recently updated tasks in a table.
- **REQ-VIEW-005**: An "Upcoming Deadlines" section shows the next 5 tasks by due date.
- **REQ-VIEW-006**: All stat counts and lists reflect only the authenticated user's data.

### 4.2 My Tasks (List View)

- **REQ-VIEW-010**: Displays all active (non-deleted) tasks for the current user in a paginated table.
- **REQ-VIEW-011**: Default sort order is by due date ascending (soonest first), with null due dates at the bottom.
- **REQ-VIEW-012**: Users can sort by any column header: title, priority, due date, status, created date.
- **REQ-VIEW-013**: Sorting is handled via HTMX (clicking a column header fires `hx-get` with sort parameters and swaps the table body).
- **REQ-VIEW-014**: Pagination shows 25 tasks per page. Navigation between pages is handled via HTMX without full page reloads. The current page number and total count are displayed.
- **REQ-VIEW-015**: A filter bar at the top allows filtering by: text search (searches title and description), status, priority, category, and date range (due date).
- **REQ-VIEW-016**: Filters are applied via HTMX on change. The text search has a 300ms debounce. Multiple filters combine with AND logic.
- **REQ-VIEW-017**: A "Bulk Actions" feature allows selecting multiple tasks via checkboxes and performing: mark as complete, change status, change priority, or delete.

### 4.3 Kanban Board

- **REQ-VIEW-020**: Displays tasks in vertical columns grouped by status: Backlog, Todo, In Progress, Review, Done.
- **REQ-VIEW-021**: Each column shows a count badge with the number of tasks in that status.
- **REQ-VIEW-022**: Tasks are represented as cards within their column, ordered by priority (high first) then by due date (soonest first).
- **REQ-VIEW-023**: Drag-and-drop between columns updates the task's status. The UI provides visual feedback during the drag operation.
- **REQ-VIEW-024**: Each column has an "Add Task" button that opens a quick-create form pre-set to that column's status.
- **REQ-VIEW-025**: The board is horizontally scrollable on smaller screens.
- **REQ-VIEW-026**: A filter bar (same as list view) is available above the board to filter which tasks appear.

### 4.4 Calendar View

- **REQ-VIEW-030**: Displays a monthly calendar grid. Each day cell shows task titles color-coded by priority or category.
- **REQ-VIEW-031**: Navigation arrows allow moving to previous/next month. A "Today" button returns to the current month. Navigation is powered by HTMX.
- **REQ-VIEW-032**: A week view toggle is available for a more detailed view of a single week.
- **REQ-VIEW-033**: Clicking on a day opens a modal or offcanvas panel listing all tasks for that date with the ability to add a new task for that day.
- **REQ-VIEW-034**: Tasks without a due date do not appear on the calendar.
- **REQ-VIEW-035**: Today's date is visually highlighted.
- **REQ-VIEW-036**: On small screens (below `md` breakpoint), the calendar degrades to an agenda-style list grouped by date.

### 4.5 Trash View

- **REQ-VIEW-040**: Displays all soft-deleted tasks for the current user.
- **REQ-VIEW-041**: Each item shows the task title, deletion date, and days remaining before permanent deletion.
- **REQ-VIEW-042**: Users can restore individual tasks (returns them to their previous status) or permanently delete them.
- **REQ-VIEW-043**: A "Empty Trash" button permanently deletes all trashed tasks, with a confirmation modal.

---

## 5. Categories and Tags

- **REQ-CAT-001**: Users can create, rename, and delete custom categories. Each category has a name and a color (hex value).
- **REQ-CAT-002**: Each task can be assigned to one category (one-to-many relationship: one category to many tasks).
- **REQ-CAT-003**: Categories are user-specific — each user has their own set of categories.
- **REQ-CAT-004**: Deleting a category does not delete its associated tasks. Tasks previously assigned to the deleted category become uncategorized (category field set to NULL).
- **REQ-CAT-005**: The Categories management page allows CRUD operations on categories with inline editing.
- **REQ-CAT-006**: Categories are displayed as colored badges on task cards and in the task table.
- **REQ-CAT-007**: The filter bar includes a category dropdown to filter tasks by category.
- **REQ-CAT-008**: The system provides 5 default categories upon new user registration: Work (blue), Personal (green), Health (red), Finance (yellow), Learning (purple). Users can modify or delete these.

---

## 6. Notifications

- **REQ-NOTIF-001**: Toast notifications appear in-app for CRUD action confirmations (task created, updated, deleted, restored).
- **REQ-NOTIF-002**: Toast notifications auto-dismiss after 3 seconds and can be manually dismissed by clicking the close button.
- **REQ-NOTIF-003**: Toasts are triggered by HTMX response headers (`HX-Trigger` emitting a custom event) and rendered by Alpine.js.
- **REQ-NOTIF-004**: The notification bell in the navbar shows a count of overdue tasks and tasks due today. The count refreshes on each page load or HTMX navigation.
- **REQ-NOTIF-005**: Clicking the notification bell opens a dropdown list showing overdue and due-today tasks. Clicking a task in the list opens its detail modal.
- **REQ-NOTIF-006** *(Optional / Future)*: Email notifications for tasks due tomorrow, sent as a daily digest at a user-configurable time.

---

## 7. Database Schema

### 7.1 Tables

The application requires the following database tables. All tables use InnoDB engine, `utf8mb4` charset, and `utf8mb4_unicode_ci` collation.

#### `users`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `first_name`        | VARCHAR(100) NOT NULL         |                                                   |
| `last_name`         | VARCHAR(100) NOT NULL         |                                                   |
| `email`             | VARCHAR(255) NOT NULL         | UNIQUE index                                      |
| `password_hash`     | VARCHAR(255) NOT NULL         | bcrypt or argon2id hash                           |
| `email_verified_at` | TIMESTAMP NULL                | NULL until email is verified                      |
| `verification_token`| VARCHAR(255) NULL             | Hashed token for email verification               |
| `verification_expires_at` | TIMESTAMP NULL          | Expiry for verification token                     |
| `theme_preference`  | ENUM('light','dark','auto')   | DEFAULT 'auto'                                    |
| `timezone`          | VARCHAR(64)                   | DEFAULT 'UTC', e.g. 'America/New_York'            |
| `deleted_at`        | TIMESTAMP NULL                | Soft delete for account deletion                  |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |
| `updated_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP ON UPDATE               |

#### `tasks`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `user_id`           | INT UNSIGNED NOT NULL         | FK → `users.id`, INDEX                            |
| `category_id`       | INT UNSIGNED NULL             | FK → `categories.id` SET NULL on delete, INDEX    |
| `title`             | VARCHAR(255) NOT NULL         |                                                   |
| `description`       | TEXT NULL                     |                                                   |
| `status`            | ENUM('backlog','todo','in_progress','review','done') | DEFAULT 'todo'            |
| `priority`          | ENUM('low','medium','high')   | DEFAULT 'medium'                                  |
| `due_date`          | DATE NULL                     | INDEX                                             |
| `completed_at`      | TIMESTAMP NULL                | Set when status changes to 'done'                 |
| `sort_order`        | INT UNSIGNED                  | DEFAULT 0, used for manual ordering within kanban |
| `deleted_at`        | TIMESTAMP NULL                | Soft delete                                       |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |
| `updated_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP ON UPDATE               |

Indexes: Composite index on `(user_id, status, deleted_at)` for the Kanban view query. Composite index on `(user_id, due_date, deleted_at)` for the Calendar view query.

#### `sub_tasks`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `task_id`           | INT UNSIGNED NOT NULL         | FK → `tasks.id` CASCADE on delete, INDEX          |
| `title`             | VARCHAR(255) NOT NULL         |                                                   |
| `is_completed`      | TINYINT(1)                    | DEFAULT 0                                         |
| `sort_order`        | INT UNSIGNED                  | DEFAULT 0                                         |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |
| `updated_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP ON UPDATE               |

#### `categories`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `user_id`           | INT UNSIGNED NOT NULL         | FK → `users.id`, INDEX                            |
| `name`              | VARCHAR(100) NOT NULL         |                                                   |
| `color`             | VARCHAR(7) NOT NULL           | Hex color, e.g. '#0d6efd'                         |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |
| `updated_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP ON UPDATE               |

Unique constraint: `(user_id, name)` — no duplicate category names per user.

#### `remember_tokens`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `user_id`           | INT UNSIGNED NOT NULL         | FK → `users.id` CASCADE on delete                 |
| `token_hash`        | VARCHAR(255) NOT NULL         | Hashed remember-me token                          |
| `expires_at`        | TIMESTAMP NOT NULL            |                                                   |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |

#### `password_resets`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `email`             | VARCHAR(255) NOT NULL         | INDEX                                             |
| `token_hash`        | VARCHAR(255) NOT NULL         | Hashed reset token                                |
| `expires_at`        | TIMESTAMP NOT NULL            |                                                   |
| `used_at`           | TIMESTAMP NULL                | Set when token is consumed                        |
| `created_at`        | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |

#### `login_attempts`

| Column              | Type                          | Constraints / Notes                               |
|---------------------|-------------------------------|---------------------------------------------------|
| `id`                | INT UNSIGNED AUTO_INCREMENT   | Primary key                                       |
| `email`             | VARCHAR(255) NOT NULL         | INDEX                                             |
| `ip_address`        | VARCHAR(45) NOT NULL          | Supports IPv6                                     |
| `user_agent`        | VARCHAR(500) NULL             |                                                   |
| `success`           | TINYINT(1) NOT NULL           | 1 = success, 0 = failure                          |
| `attempted_at`      | TIMESTAMP                     | DEFAULT CURRENT_TIMESTAMP                         |

---

## 8. API / Route Structure

All routes are handled by the PHP front controller (`public/index.php`). HTMX requests are distinguished by the `HX-Request` header — when present, only HTML fragments are returned.

### 8.1 Public Routes (Unauthenticated)

| Method | Path                     | Description                              |
|--------|--------------------------|------------------------------------------|
| GET    | `/login`                 | Display login form                       |
| POST   | `/login`                 | Authenticate user                        |
| GET    | `/register`              | Display registration form                |
| POST   | `/register`              | Create new user account                  |
| GET    | `/verify-email?token=`   | Verify email address                     |
| POST   | `/resend-verification`   | Resend verification email                |
| GET    | `/forgot-password`       | Display forgot password form             |
| POST   | `/forgot-password`       | Send password reset email                |
| GET    | `/reset-password?token=` | Display reset password form              |
| POST   | `/reset-password`        | Process password reset                   |

### 8.2 Protected Routes (Authenticated)

All protected routes require a valid session. If no session is found, redirect to `/login` with the intended URL preserved for post-login redirect.

| Method | Path                          | Description                                  |
|--------|-------------------------------|----------------------------------------------|
| GET    | `/dashboard`                  | Dashboard page                               |
| GET    | `/tasks`                      | My Tasks list view (supports query params for filter, sort, page) |
| POST   | `/tasks`                      | Create a new task                            |
| GET    | `/tasks/{id}`                 | Task detail (returns modal fragment for HTMX)|
| PUT    | `/tasks/{id}`                 | Update a task                                |
| DELETE | `/tasks/{id}`                 | Soft-delete a task                           |
| PATCH  | `/tasks/{id}/status`          | Update only the task's status (kanban drag)  |
| PATCH  | `/tasks/{id}/toggle`          | Toggle task completion                       |
| GET    | `/kanban`                     | Kanban board view                            |
| GET    | `/calendar`                   | Calendar view (supports month/year query params) |
| GET    | `/trash`                      | Trash view                                   |
| POST   | `/trash/{id}/restore`         | Restore a soft-deleted task                  |
| DELETE | `/trash/{id}`                 | Permanently delete a task                    |
| DELETE | `/trash`                      | Empty all trash                              |
| GET    | `/tasks/{id}/subtasks`        | List sub-tasks for a task (HTMX fragment)    |
| POST   | `/tasks/{id}/subtasks`        | Add a sub-task                               |
| PATCH  | `/subtasks/{id}/toggle`       | Toggle sub-task completion                   |
| DELETE | `/subtasks/{id}`              | Delete a sub-task                            |
| GET    | `/categories`                 | Categories management page                   |
| POST   | `/categories`                 | Create a new category                        |
| PUT    | `/categories/{id}`            | Update a category                            |
| DELETE | `/categories/{id}`            | Delete a category                            |
| GET    | `/profile`                    | Profile / settings page                      |
| PUT    | `/profile`                    | Update profile fields                        |
| PUT    | `/profile/password`           | Change password                              |
| DELETE | `/profile`                    | Soft-delete account                          |
| POST   | `/logout`                     | Destroy session and log out                  |

---

## 9. Security Requirements

- **REQ-SEC-001**: All database queries must use PDO prepared statements with parameterized inputs. No raw user input in SQL.
- **REQ-SEC-002**: All user-generated content rendered in HTML must be escaped using `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8` encoding to prevent XSS.
- **REQ-SEC-003**: All state-changing requests (POST, PUT, PATCH, DELETE) must include a CSRF token. The token is generated per session and validated server-side. HTMX requests include the token via the `hx-headers` attribute set globally.
- **REQ-SEC-004**: Session cookies must be configured with `Secure` (in production), `HttpOnly`, and `SameSite=Lax` flags.
- **REQ-SEC-005**: The application must enforce HTTPS in production. HTTP requests must be redirected to HTTPS via Apache configuration.
- **REQ-SEC-006**: Rate-limit password reset requests to 3 per email per hour and login attempts to 5 per email per 15 minutes.
- **REQ-SEC-007**: User-facing error messages must never expose system internals, stack traces, file paths, or database error messages. Log detailed errors server-side; show generic messages to users.
- **REQ-SEC-008**: All authentication tokens (email verification, password reset, remember-me) must be generated using `random_bytes(32)` and stored as hashed values (`hash('sha256', $token)`). The unhashed token is sent to the user; only the hash is stored in the database.
- **REQ-SEC-009**: User authorization is enforced at the query level — every database query that reads or modifies tasks must include a `WHERE user_id = :current_user_id` clause. No reliance on URL obfuscation alone.
- **REQ-SEC-010**: File uploads are not required for the initial version. If added later, uploaded files must be stored outside the web root, validated by MIME type, and served via a PHP download script.
- **REQ-SEC-011**: Content-Security-Policy, X-Content-Type-Options, X-Frame-Options, and Referrer-Policy headers should be set via Apache configuration or PHP.

---

## 10. Non-Functional Requirements

### 10.1 Performance

- **REQ-PERF-001**: Pages must render server-side in under 200ms for typical requests (excluding network latency).
- **REQ-PERF-002**: Database queries should be indexed appropriately. No query should perform a full table scan on tables with more than 1,000 rows.
- **REQ-PERF-003**: HTMX fragment responses should be under 50KB.
- **REQ-PERF-004**: The application must remain functional and responsive with up to 10,000 tasks per user.

### 10.2 Scalability

- **REQ-SCALE-001**: The application is designed as a single-server deployment initially (one Apache + MySQL instance).
- **REQ-SCALE-002**: The database schema supports multi-tenancy via the `user_id` foreign key on all user-owned tables. No tenant-specific databases or schemas are needed.
- **REQ-SCALE-003**: Session storage uses PHP's default file-based handler initially, but the configuration should allow switching to database or Redis-based sessions without code changes to session usage.

### 10.3 Reliability

- **REQ-REL-001**: Database migrations must be forward-only SQL scripts stored in the `migrations/` directory, named with timestamps (e.g., `001_create_users_table.sql`).
- **REQ-REL-002**: Application errors must be logged to a file in a `logs/` directory outside the web root with daily rotation.
- **REQ-REL-003**: The application should display a user-friendly error page for 404 (not found) and 500 (server error) HTTP status codes.

### 10.4 Accessibility

- **REQ-ACC-001**: The application must achieve WCAG 2.1 Level AA compliance for all core workflows (registration, login, CRUD on tasks, navigation between views).
- **REQ-ACC-002**: All forms must be keyboard navigable. Tab order must follow a logical sequence.
- **REQ-ACC-003**: All interactive elements must have visible focus indicators.
- **REQ-ACC-004**: Color alone must not convey meaning — text or icons must accompany color-coded elements (priority, status).
- **REQ-ACC-005**: Screen reader announcements must be provided for dynamic content updates (HTMX swaps) using `aria-live` regions.

### 10.5 Browser Support

- **REQ-BROWSER-001**: The application must function correctly in the latest two major versions of Chrome, Firefox, Safari, and Edge.
- **REQ-BROWSER-002**: The application must be fully functional on mobile browsers (iOS Safari, Chrome for Android) at viewport widths down to 320px.

---

## 11. Email Requirements

- **REQ-EMAIL-001**: The application sends transactional emails for: email verification, password reset, and (optionally) daily task digests.
- **REQ-EMAIL-002**: Emails are sent using PHP's `mail()` function for development. For production, the system should support SMTP configuration via environment variables compatible with libraries like PHPMailer or Symfony Mailer (installed via Composer).
- **REQ-EMAIL-003**: All emails must be sent as both HTML and plain text (multipart) for compatibility.
- **REQ-EMAIL-004**: Email templates must be simple, mobile-friendly HTML that renders in all major email clients. Use inline CSS only.
- **REQ-EMAIL-005**: The "From" address and application name used in emails must be configurable via environment variables.

---

## 12. Configuration & Environment

- **REQ-CONFIG-001**: All environment-specific configuration (database credentials, SMTP settings, app URL, debug mode) must be stored in a `.env` file in the project root, loaded via `vlucas/phpdotenv` or a custom loader.
- **REQ-CONFIG-002**: A `.env.example` file must be included in version control with placeholder values for all required variables.
- **REQ-CONFIG-003**: The application must support at least two environment modes: `development` (debug output, verbose errors) and `production` (suppressed errors, logging only).
- **REQ-CONFIG-004**: Debug mode must never be enabled in production. When debug mode is off, PHP errors are logged but not displayed.

### Required Environment Variables

```
APP_NAME=TaskFlow
APP_URL=http://localhost:8000
APP_ENV=development
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@taskflow.app
MAIL_FROM_NAME=TaskFlow

SESSION_LIFETIME=120
REMEMBER_ME_LIFETIME=43200
```

---

## 13. Deployment Requirements

- **REQ-DEPLOY-001**: The application must be deployable on a single Ubuntu 24.04 LTS server with Apache 2.4+, PHP 8.3+, and MySQL 8.0+.
- **REQ-DEPLOY-002**: Apache must be configured with `mod_rewrite` enabled and the `DocumentRoot` pointing to the `public/` directory.
- **REQ-DEPLOY-003**: A `deploy.sh` script should be provided that: runs database migrations, clears any caches, sets appropriate file permissions (`storage/` and `logs/` writable by www-data), and restarts Apache if needed.
- **REQ-DEPLOY-004**: A cron job must be configured to run a cleanup script at least once daily to permanently purge soft-deleted tasks and accounts older than 30 days.
- **REQ-DEPLOY-005**: The `.env` file, `logs/` directory, and `vendor/` directory must be excluded from version control via `.gitignore`.

---

## 14. Testing Requirements

- **REQ-TEST-001**: All authentication flows must have manual test cases documented: registration, login, logout, password reset, email verification, account lockout, remember-me.
- **REQ-TEST-002**: CRUD operations on tasks must have manual test cases covering: create, read, update (all fields), soft-delete, restore, permanent delete, and sub-tasks.
- **REQ-TEST-003**: Authorization tests must verify that a user cannot access, modify, or delete another user's tasks or categories by manipulating request parameters or URLs.
- **REQ-TEST-004**: CSRF protection must be tested by verifying that requests without a valid token are rejected with a 403 response.
- **REQ-TEST-005** *(Optional / Future)*: PHPUnit tests for model and controller logic.

---

## 15. Future Considerations (Out of Scope for V1)

The following features are explicitly out of scope for the initial release but should be considered in the architecture so they can be added without major refactoring:

- **Team workspaces** — shared task boards with role-based permissions (admin, member, viewer).
- **Task comments** — threaded comments on tasks for team collaboration.
- **File attachments** — uploading files to tasks.
- **Recurring tasks** — tasks that auto-create on a schedule (daily, weekly, monthly).
- **API access** — a RESTful JSON API for third-party integrations and mobile apps.
- **Two-factor authentication (2FA)** — TOTP-based second factor for login.
- **OAuth / social login** — Sign in with Google, GitHub, etc.
- **Webhooks** — notify external systems when tasks change status.
- **Export** — export tasks as CSV, JSON, or PDF.
- **Subscription / billing** — paid tiers with usage limits (Stripe integration).

---

## 16. Requirement Traceability

| Requirement Group        | ID Range              | Count |
|--------------------------|-----------------------|-------|
| Authentication & Accounts| REQ-AUTH-001 – 045    | 27    |
| Task CRUD                | REQ-TASK-001 – 044    | 22    |
| Views                    | REQ-VIEW-001 – 043    | 24    |
| Categories               | REQ-CAT-001 – 008     | 8     |
| Notifications            | REQ-NOTIF-001 – 006   | 6     |
| Security                 | REQ-SEC-001 – 011     | 11    |
| Performance              | REQ-PERF-001 – 004    | 4     |
| Scalability              | REQ-SCALE-001 – 003   | 3     |
| Reliability              | REQ-REL-001 – 003     | 3     |
| Accessibility            | REQ-ACC-001 – 005     | 5     |
| Browser Support          | REQ-BROWSER-001 – 002 | 2     |
| Email                    | REQ-EMAIL-001 – 005   | 5     |
| Configuration            | REQ-CONFIG-001 – 004  | 4     |
| Deployment               | REQ-DEPLOY-001 – 005  | 5     |
| Testing                  | REQ-TEST-001 – 005    | 5     |
| **Total**                |                       | **134** |

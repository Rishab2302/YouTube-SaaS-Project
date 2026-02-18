# Build Prompts — TaskFlow Todo List Tracker

## How to Use This File

These prompts are designed to be run sequentially in Claude Code. Each prompt builds on the output of the previous one. Do not skip prompts or run them out of order. After each prompt completes, verify the output works before moving to the next one.

Before starting, ensure the following files are in the project root directory so Claude Code can reference them:

- `tech-stack.md`
- `design-notes.md`
- `requirements.md`

---

## Prompt 1 — Project Scaffolding, Configuration, and Database

```
Read the following project files before doing anything: tech-stack.md, design-notes.md, and requirements.md

Set up the complete project scaffolding for the TaskFlow application.

1. Create the full directory structure as defined in tech-stack.md:
   - public/ (with index.php front controller, css/, js/, .htaccess for URL rewriting)
   - src/controllers/, src/models/, src/views/, src/helpers/
   - config/
   - migrations/
   - logs/ (with a .gitkeep)

2. Initialize Composer and install vlucas/phpdotenv. Create the composer.json with PSR-4 autoloading for the App\ namespace mapped to src/.

3. Create the .env.example file with all required environment variables from requirements.md section 12. Create a .env file as a copy with working local development defaults (DB_DATABASE=taskflow, APP_URL=http://localhost:8000).

4. Create config/database.php that reads from .env and returns a PDO connection using the singleton pattern. Configure PDO with ERRMODE_EXCEPTION, FETCH_ASSOC defaults, and utf8mb4 charset.

5. Create config/app.php that loads common application settings from .env (APP_NAME, APP_URL, APP_ENV, APP_DEBUG, SESSION_LIFETIME, etc.) and returns them as an associative array.

6. Create ALL database migration SQL files in migrations/ for every table defined in requirements.md section 7: users, tasks, sub_tasks, categories, remember_tokens, password_resets, login_attempts. Include all columns, types, constraints, foreign keys, and indexes exactly as specified. Name them sequentially: 001_create_users_table.sql, 002_create_categories_table.sql, 003_create_tasks_table.sql, etc.

7. Create a migrations/migrate.php CLI script that reads all SQL files in order and executes them against the database. It should track which migrations have been run in a migrations table and skip already-applied ones.

8. Create the public/.htaccess file with mod_rewrite rules that route all requests to index.php except existing files and directories.

9. Create a .gitignore file that excludes: vendor/, .env, logs/*.log, and IDE files.

10. Create public/index.php as the front controller that: loads Composer autoload, loads .env, initializes session configuration per REQ-SEC-004 (Secure in production, HttpOnly, SameSite=Lax), and includes a placeholder for the router.

Verify the setup by confirming: composer install runs cleanly, the migration script can connect to MySQL, and php -S localhost:8000 -t public/ serves the front controller.
```

---

## Prompt 2 — Router, Base Controller, Middleware, and Layout

```
Read tech-stack.md, design-notes.md, and requirements.md for context.

Build the routing system, base controller, middleware layer, and the master HTML layout template.

1. Create src/helpers/Router.php — a simple PHP router class that:
   - Supports GET, POST, PUT, PATCH, DELETE methods.
   - Supports route parameters like /tasks/{id}.
   - Maps routes to controller@method strings (e.g., 'TaskController@show').
   - Detects PUT/PATCH/DELETE via a hidden _method form field (since HTML forms only support GET/POST).
   - Returns a 404 response for unmatched routes.

2. Create src/helpers/Middleware.php with two middleware functions:
   - auth(): checks for a valid session (user_id in $_SESSION). If not authenticated, saves the current URL to $_SESSION['redirect_after_login'] and redirects to /login.
   - guest(): checks that the user is NOT authenticated (for login/register pages). If already logged in, redirects to /dashboard.

3. Create src/helpers/CsrfToken.php:
   - generate(): creates a CSRF token using random_bytes(32), stores it in $_SESSION, returns the hex string.
   - validate($token): compares the submitted token against the session token using hash_equals(). Returns boolean.
   - field(): returns a hidden input HTML string for use in forms.

4. Create src/controllers/BaseController.php — an abstract class that:
   - Holds the PDO database connection (injected or retrieved via the singleton).
   - Has a render($view, $data = []) method that extracts $data into variables and includes the view file from src/views/.
   - Has a renderFragment($view, $data = []) method that renders a view WITHOUT the layout wrapper (for HTMX responses). It checks for the HX-Request header to decide which to use.
   - Has a redirect($path) method.
   - Has a json($data, $status = 200) method.
   - Has a currentUser() method that returns the logged-in user's data from the session.

5. Wire up all routes in public/index.php using the Router class. Register every route from requirements.md section 8 (both public and protected). Apply the auth middleware to all protected routes and the guest middleware to login/register routes. Controllers don't need to exist yet — just register the routes so the structure is in place.

6. Create src/views/layouts/app.php — the master layout template that:
   - Includes the full HTML5 boilerplate with viewport meta tag.
   - Loads Bootstrap 5.3 CSS and Bootstrap Icons from CDN (in <head>).
   - Loads jQuery (if needed), Bootstrap JS bundle, HTMX, and Alpine.js before </body> in the order specified in tech-stack.md.
   - Loads public/css/app.css and public/js/app.js.
   - Sets data-bs-theme attribute on <html> (default "light").
   - Sets a global HTMX CSRF header using hx-headers on the <body> tag: hx-headers='{"X-CSRF-Token": "<?= $csrfToken ?>"}'.
   - Contains the top navbar structure from design-notes.md (brand name "TaskFlow", theme toggle dropdown, quick-add button placeholder, notification bell placeholder, and user dropdown with Sign Out).
   - Contains the sidebar navigation from design-notes.md with all nav links (Dashboard, My Tasks, Kanban Board, Calendar, Categories, Trash) and highlights the active page.
   - Contains a main content area that yields the page-specific content via a $content variable.
   - Contains the toast notification container at bottom-right as described in design-notes.md.

7. Create src/views/layouts/auth.php — a simpler layout for login/register/password-reset pages. Centered card on the page, no sidebar, just the Bootstrap boilerplate and a single content area.

8. Create public/css/app.css with the custom CSS classes from design-notes.md (todo-kanban-column, todo-calendar-cell, etc.) and the dashboard sidebar styles from the Bootstrap 5.3 Dashboard example.

9. Create public/js/app.js with:
   - The Bootstrap 5.3 color mode toggler JavaScript (light/dark/auto with localStorage).
   - An Alpine.js listener for the 'showToast' custom event (triggered by HTMX HX-Trigger headers) that shows a Bootstrap toast.
   - HTMX afterSwap event listener that reinitializes any Bootstrap tooltips in the swapped content.

Test by navigating to http://localhost:8000 — it should show the full layout with navbar and sidebar (even if the main content area is empty). Verify the theme toggle switches between light/dark/auto correctly.
```

---

## Prompt 3 — User Registration and Email Verification

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Pay special attention to requirements REQ-AUTH-001 through REQ-AUTH-008 and REQ-SEC-001 through REQ-SEC-008.

Build the complete user registration and email verification system.

1. Create src/models/User.php with the following methods:
   - findByEmail($email): returns user row or null.
   - findById($id): returns user row or null.
   - create($data): inserts a new user with password_hash() using PASSWORD_ARGON2ID (fall back to PASSWORD_BCRYPT if argon2 is unavailable). Returns the new user's ID.
   - verifyEmail($userId): sets email_verified_at to now.
   - findByVerificationToken($tokenHash): finds user by hashed verification token where not expired.
   - updateVerificationToken($userId, $tokenHash, $expiresAt): sets a new verification token.
   All queries must use PDO prepared statements per REQ-SEC-001. All read queries for user-owned data must scope by user_id per REQ-SEC-009.

2. Create src/models/Category.php with:
   - createDefaultCategories($userId): creates the 5 default categories from REQ-CAT-008 (Work/blue, Personal/green, Health/red, Finance/yellow, Learning/purple).

3. Create src/helpers/Email.php — a helper class that:
   - Uses PHP's mail() function in development and supports SMTP config from .env for production.
   - Has a send($to, $subject, $htmlBody, $textBody) method.
   - Has helper methods: sendVerificationEmail($user, $token) and sendPasswordResetEmail($email, $token).
   - Email templates are simple inline-CSS HTML that include the APP_NAME and APP_URL from config.
   - The verification email contains a link to /verify-email?token={unhashed_token}.

4. Create src/controllers/AuthController.php with these methods:

   showRegister(): renders the registration form view.

   register(): processes the registration form:
   - Validates all fields server-side: first_name and last_name required (max 100 chars), email required and valid format and unique, password required (min 8 chars, must contain uppercase + lowercase + number), password_confirmation must match.
   - On validation failure, re-renders the form with error messages and old input (except password).
   - On success: creates the user, generates a verification token using random_bytes(32), stores the hash in the database, sends the verification email, creates default categories, and redirects to /login with a flash message saying "Registration successful. Please check your email to verify your account."

   verifyEmail(): processes the /verify-email?token= request:
   - Hashes the token from the URL, looks up the user by token hash, checks expiry.
   - On success: sets email_verified_at, clears the token, redirects to /login with success flash message.
   - On failure: shows an error page with an option to resend.

   resendVerification(): generates a new token and re-sends the verification email.

5. Create src/views/auth/register.php — the registration form using the auth layout:
   - Uses Bootstrap form components per design-notes.md.
   - All fields use .form-control and .form-label.
   - Shows validation errors using .invalid-feedback or an alert at the top.
   - Client-side validation using HTML5 attributes (required, minlength, type="email") AND Alpine.js for real-time password strength feedback (shows which criteria are met as the user types).
   - A link to /login at the bottom: "Already have an account? Sign in".

6. Create src/helpers/Session.php — a helper for flash messages:
   - flash($key, $message): stores a message in $_SESSION['flash'][$key].
   - getFlash($key): retrieves and removes the flash message.
   - hasFlash($key): checks if a flash message exists.
   Display flash messages in the layout templates using Bootstrap alerts.

Test the full flow: visit /register, fill out the form with valid data, verify the user is created in the database with a hashed password and verification token, verify the verification email would be sent (check logs or use Mailtrap), visit the verification link, confirm email_verified_at is set, and confirm redirect to login with success message.
```

---

## Prompt 4 — Login, Logout, Session Management, and Password Reset

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Pay special attention to REQ-AUTH-010 through REQ-AUTH-034 and REQ-SEC-004 through REQ-SEC-008.

Build the complete login, logout, remember-me, session management, and password reset system.

1. Create src/models/LoginAttempt.php with:
   - record($email, $ipAddress, $userAgent, $success): logs an attempt.
   - recentFailedCount($email, $minutes = 15): counts failed attempts in the last N minutes.
   - isLocked($email): returns true if 5+ failed attempts in last 15 minutes per REQ-AUTH-014.

2. Create src/models/RememberToken.php with:
   - create($userId, $tokenHash, $expiresAt): stores a new remember-me token.
   - findByTokenHash($tokenHash): returns the token row if not expired.
   - deleteByUserId($userId): removes all remember-me tokens for a user.
   - deleteByTokenHash($tokenHash): removes a specific token.

3. Create src/models/PasswordReset.php with:
   - create($email, $tokenHash, $expiresAt): stores a reset request.
   - findByTokenHash($tokenHash): returns the row if not expired and not used.
   - markUsed($id): sets used_at to now.
   - recentCount($email, $hours = 1): counts reset requests in the last N hours for rate limiting.

4. Add these methods to AuthController.php:

   showLogin(): renders the login form view.

   login(): processes the login form:
   - Checks if the account is locked (REQ-AUTH-014). If locked, show error with time remaining.
   - Validates email/password, looks up user, verifies with password_verify().
   - If user's email is not verified, show error with "Resend verification email" link (REQ-AUTH-006).
   - Logs the attempt (success or failure) in login_attempts table (REQ-AUTH-015).
   - On success: regenerates session ID (session_regenerate_id(true)), stores user_id in $_SESSION, handles "Remember Me" checkbox by generating a persistent token stored in remember_tokens and setting a cookie, redirects to $_SESSION['redirect_after_login'] or /dashboard (REQ-AUTH-016).
   - On failure: re-renders login form with error message and old email.

   logout(): destroys session, clears session cookie, deletes remember-me token and cookie, redirects to /login (REQ-AUTH-020/021).

   showForgotPassword(): renders the forgot password form.

   forgotPassword(): processes the request:
   - Rate-limits to 3 per email per hour (REQ-SEC-006).
   - Always shows the same success message regardless of whether the email exists (REQ-AUTH-031).
   - If email exists: generates token, stores hash in password_resets, sends reset email with link to /reset-password?token={token}.

   showResetPassword(): validates the token from URL, renders the new password form if valid.

   resetPassword(): processes the new password:
   - Validates token, validates new password meets requirements.
   - Updates user's password_hash, marks the reset token as used, invalidates all sessions and remember-me tokens for the user (REQ-AUTH-033).
   - Redirects to /login with success message.

5. Update the auth middleware in Middleware.php to also check for a valid remember-me cookie if no session exists. If a valid remember-me token is found, automatically log the user in and regenerate the session.

6. Update the session configuration in public/index.php to implement the 2-hour inactivity timeout (REQ-AUTH-022). Track last activity time in $_SESSION and destroy the session if exceeded.

7. Create src/views/auth/login.php — login form with:
   - Email and password fields using Bootstrap form components.
   - "Remember Me" checkbox using .form-check.
   - "Forgot Password?" link.
   - "Don't have an account? Register" link.
   - Flash message display for success/error.

8. Create src/views/auth/forgot-password.php — email input form.

9. Create src/views/auth/reset-password.php — new password + confirmation form.

Test the full flow: register a user, verify email, log in (verify session is created), test "Remember Me" (close browser, reopen, confirm still logged in), test failed login lockout (5 failed attempts, verify 15-minute lock), test forgot password flow end-to-end, test that password reset invalidates other sessions, test logout clears everything.
```

---

## Prompt 5 — Dashboard Page

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-VIEW-001 through REQ-VIEW-006 and the Dashboard section of design-notes.md.

Build the complete Dashboard page — the first authenticated page users see after login.

1. Add the following query methods to src/models/Task.php (create the file if it doesn't exist):
   - countByStatus($userId, $status = null): returns task count, optionally filtered by status. Excludes soft-deleted tasks.
   - countOverdue($userId): counts tasks where due_date < today AND status != 'done' AND deleted_at IS NULL.
   - countCompletedThisWeek($userId): counts tasks completed (status = 'done') where completed_at is within the current Monday–Sunday week.
   - countTotalThisWeek($userId): counts all tasks (non-deleted) that were either created this week or have a due_date this week.
   - getRecent($userId, $limit = 10): returns the N most recently updated non-deleted tasks, joined with categories to get category name and color.
   - getUpcomingDeadlines($userId, $limit = 5): returns the next N tasks by due_date (future or today only, not completed, not deleted), joined with categories.

2. Create src/controllers/DashboardController.php with:
   - index(): gathers all dashboard data by calling the Task model methods above, then renders the dashboard view.
   - All queries must be scoped to the current user (REQ-SEC-009).

3. Create src/views/dashboard/index.php that renders inside the app layout:

   - Four summary stat cards in a responsive row (row-cols-1 row-cols-md-2 row-cols-xl-4 g-3) using the exact card markup from design-notes.md: Total Tasks, Completed, In Progress, Overdue. Each card has an icon with the subtle background color scheme from the design notes table.

   - A weekly progress card with a Bootstrap 5.3 progress bar showing completion percentage (completed this week / total this week). Use the v5.3 progress markup where role="progressbar" is on the .progress wrapper. Show "X of Y tasks" text.

   - A "Recent Tasks" card containing a .table.table-hover inside .table-responsive. Columns: checkbox (completion toggle), title, priority badge, due date, status badge, category badge (colored). The checkbox uses hx-patch="/tasks/{id}/toggle" hx-target="closest tr" hx-swap="outerHTML" to toggle completion without page reload. Priority and status badges use the color classes from design-notes.md.

   - An "Upcoming Deadlines" card with a .list-group.list-group-flush showing upcoming tasks. Each item shows task title, category name, and a relative date badge (Today, Tomorrow, in 3 days, Feb 25, etc.) using the markup pattern from design-notes.md.

   - If any section has no data, show the empty state pattern from design-notes.md (icon + message + add button).

4. Create a src/helpers/DateHelper.php with:
   - relativeDate($date): returns human-readable relative dates ("Today", "Tomorrow", "Yesterday", "in 3 days", "Feb 25", etc.).
   - isOverdue($date): returns true if the date is before today.
   - getCurrentWeekRange(): returns [monday, sunday] timestamps for the current week.

Test: Log in and verify the dashboard renders correctly with the sidebar highlighted on "Dashboard". Create a few test tasks directly in the database with varying statuses, priorities, due dates, and categories. Verify all four stat cards show correct counts, the progress bar reflects the right percentage, the recent tasks table shows data, and upcoming deadlines appear. Verify the completion toggle checkbox works via HTMX without a page reload and the stat cards update.
```

---

## Prompt 6 — Task CRUD and My Tasks List View

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-TASK-001 through REQ-TASK-034 and REQ-VIEW-010 through REQ-VIEW-017.

Build the complete task CRUD system and the "My Tasks" list view with filtering, sorting, pagination, and bulk actions.

1. Complete src/models/Task.php with all remaining CRUD methods:
   - create($userId, $data): inserts a new task. Returns the new task ID.
   - findById($id, $userId): returns a single task if owned by the user, else null (REQ-SEC-009).
   - update($id, $userId, $data): updates task fields. Sets completed_at when status changes to 'done', clears it when status changes away from 'done'. Returns success boolean.
   - softDelete($id, $userId): sets deleted_at. Returns success boolean.
   - restore($id, $userId): clears deleted_at. Returns success boolean.
   - permanentDelete($id, $userId): deletes the row. Returns success boolean.
   - toggleComplete($id, $userId): toggles between 'done' and the previous status (store previous status or default to 'todo'). Sets/clears completed_at accordingly.
   - updateStatus($id, $userId, $status): updates only the status field (for kanban drag).
   - getFiltered($userId, $filters = [], $sort = 'due_date', $direction = 'asc', $page = 1, $perPage = 25): returns paginated tasks with filtering. Supports filters: q (text search on title and description using LIKE), status, priority, category_id, due_date_from, due_date_to. Returns ['tasks' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int]. Excludes soft-deleted tasks. Joins with categories for name/color.
   - bulkUpdateStatus($ids, $userId, $status): updates status for multiple task IDs.
   - bulkUpdatePriority($ids, $userId, $priority): updates priority for multiple task IDs.
   - bulkDelete($ids, $userId): soft-deletes multiple tasks.

2. Create src/controllers/TaskController.php with:

   index(): renders the My Tasks list view. Reads query params (q, status, priority, category_id, due_date_from, due_date_to, sort, direction, page). Calls Task::getFiltered(). For HTMX requests, returns only the table body fragment. For full requests, returns the complete page with filters.

   store(): validates and creates a task from POST data. Title is required. On success, returns an HTMX fragment of the new task row (for hx-swap="afterbegin") and triggers a 'showToast' event via HX-Trigger header with a success message. For non-HTMX requests, redirects back with flash message.

   show($id): returns the task detail/edit modal HTML fragment for HTMX. Includes all task fields, sub-task list, and the edit form.

   update($id): validates and updates the task. Returns the updated row fragment for HTMX. Triggers success toast.

   destroy($id): soft-deletes the task. Returns HX-Trigger with success toast. If HTMX, also returns a trigger to remove the row from the DOM.

   toggleComplete($id): toggles done status. Returns the updated row fragment.

   updateStatus($id): updates status only (for kanban). Returns the updated card fragment.

   bulkAction(): processes bulk operations from checkbox selections. Reads 'action' (complete, status_change, priority_change, delete) and 'task_ids[]' from POST. Performs the action. Returns the updated table body.

3. Create src/views/tasks/index.php — the full My Tasks page:
   - Filter bar at top with: search input (hx-get="/tasks" hx-trigger="input changed delay:300ms"), status dropdown, priority dropdown, category dropdown, date range inputs. All filters use hx-get="/tasks" hx-target="#task-list-body" hx-include to send all current filter values together.
   - Bulk action bar (shown/hidden with Alpine.js x-show when any checkbox is checked): buttons for Mark Complete, Change Status, Change Priority, Delete Selected.
   - A "New Task" button that opens the full task creation modal.
   - Table with .table.table-hover inside .table-responsive. Sortable column headers (clicking sends hx-get with sort params). The table body has id="task-list-body" as the HTMX swap target.
   - Pagination controls below the table using Bootstrap pagination component, powered by HTMX.

4. Create src/views/tasks/_row.php — a single table row partial (used for HTMX swap responses):
   - Checkbox with Alpine.js for bulk selection tracking.
   - Task title (click to open detail modal via hx-get="/tasks/{id}" hx-target="#taskModalContent").
   - Priority badge, due date (with overdue styling), status badge, category badge.
   - Actions dropdown (Edit, Delete with hx-delete and hx-confirm).

5. Create src/views/tasks/_modal.php — the task detail/edit modal content:
   - All form fields per design-notes.md: title, description textarea, status select, priority select, due date input, category select.
   - Sub-tasks section (placeholder — will be completed in a later prompt).
   - Save and Delete buttons with HTMX attributes.
   - The modal shell is in the layout; this partial fills the .modal-body and .modal-footer.

6. Create src/views/tasks/_quick_add.php — the quick-add form dropdown in the navbar:
   - Compact form with: title input, priority select, due date input, and Add button.
   - Uses hx-post="/tasks" and closes on success via Alpine.js.

7. Update the app layout to include the task detail modal shell and the quick-add dropdown wired to the navbar button.

Test: Navigate to /tasks, verify the empty state shows. Create tasks via the quick-add form AND the full modal. Verify tasks appear in the list. Test sorting by each column. Test each filter individually and in combination. Test pagination with 30+ tasks. Test inline completion toggle. Test the edit modal (open, edit, save). Test soft-delete with confirmation. Test bulk select and bulk actions. Verify all operations happen via HTMX without full page reloads, and toast notifications appear.
```

---

## Prompt 7 — Kanban Board View

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-VIEW-020 through REQ-VIEW-026 and the Kanban Board section of design-notes.md.

Build the complete Kanban board view with drag-and-drop.

1. Add to src/models/Task.php:
   - getGroupedByStatus($userId, $filters = []): returns tasks grouped by status as an associative array: ['backlog' => [...], 'todo' => [...], 'in_progress' => [...], 'review' => [...], 'done' => [...]]. Each group is ordered by priority (high first) then due_date (soonest first). Supports the same filters as getFiltered (q, priority, category_id). Excludes soft-deleted. Joins with categories.
   - updateSortOrder($id, $userId, $sortOrder): updates the sort_order for manual reordering within a column.

2. Create src/controllers/KanbanController.php with:
   - index(): loads tasks grouped by status, loads categories for the filter bar, renders the kanban view. For HTMX requests, returns only the board fragment.

3. Create src/views/kanban/index.php — the full Kanban page:
   - Filter bar at top (same filters as My Tasks except status, since status is represented by columns). Uses hx-get="/kanban" hx-target="#kanban-board".
   - The board container div#kanban-board with class "d-flex overflow-auto gap-3 pb-3 align-items-start".
   - Five columns, one per status, using the exact markup from design-notes.md: .kanban-column with .card.bg-body-secondary.border-0, colored left border per the column color table, header with status name + count badge + add button, scrollable card body.
   - Each column's add button opens a quick-create form (inline within the column, toggled by Alpine.js x-show) that pre-sets the status to that column's status. The form uses hx-post="/tasks" hx-target to prepend the new card into the column.

4. Create src/views/kanban/_card.php — a single Kanban task card partial using the exact markup from design-notes.md:
   - Priority badge, three-dot dropdown menu (Edit opens the task modal, Move to... shows a submenu of other statuses, Delete with confirmation).
   - Task title, truncated description, due date with icon, category tag with icon.
   - The card has draggable="true" and data-task-id attribute.

5. Implement drag-and-drop using Alpine.js and HTMX:
   - Wrap the board in an Alpine.js x-data component that tracks: draggedTaskId, draggedFromColumn.
   - On dragstart: store the task ID and source column, add .todo-kanban-card.dragging class (opacity: 0.5).
   - On dragover for column drop zones: prevent default, add .todo-kanban-column.drag-over visual feedback (dashed border + subtle background).
   - On dragleave: remove drag-over feedback.
   - On drop: extract the target column's status, fire an HTMX request via htmx.ajax('PATCH', '/tasks/{id}/status', {values: {status: newStatus}}) to persist the change. Move the card DOM element to the new column. Update both columns' count badges. Show success toast.
   - On dragend: clean up all drag classes.

6. Make sure the PATCH /tasks/{id}/status endpoint in TaskController returns appropriate HTMX headers (HX-Trigger for toast) and the updated card fragment.

7. Ensure the kanban board is horizontally scrollable on all screen sizes with the 320px minimum column width per design-notes.md.

Test: Navigate to /kanban, verify all five columns render with correct tasks. Drag a task from "Todo" to "In Progress" — verify the card moves visually, the status updates in the database, count badges update, and a toast appears. Verify the filter bar filters cards across all columns. Test the per-column quick-add form. Test the card dropdown actions (edit opens modal, move-to changes status, delete soft-deletes). Test on a narrow viewport to verify horizontal scrolling.
```

---

## Prompt 8 — Calendar View

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-VIEW-030 through REQ-VIEW-036 and the Calendar View section of design-notes.md.

Build the complete Calendar view with month and week modes.

1. Add to src/models/Task.php:
   - getByDateRange($userId, $startDate, $endDate): returns all non-deleted tasks with a due_date between startDate and endDate (inclusive). Joined with categories for color. Ordered by priority.
   - getByDate($userId, $date): returns all non-deleted tasks for a specific date.

2. Add to src/helpers/DateHelper.php:
   - getCalendarGrid($year, $month): returns a 2D array representing the calendar grid. Each element has: date, dayNumber, isCurrentMonth (boolean), isToday (boolean), isWeekend (boolean). The grid starts on Sunday and includes leading/trailing days from adjacent months to fill complete weeks.
   - getWeekDates($year, $week): returns an array of 7 date strings for the given ISO week.
   - getMonthName($month): returns the full month name.

3. Create src/controllers/CalendarController.php with:
   - index(): reads 'month', 'year', 'view' (month or week), and 'week' from query params (defaults to current month/year). Generates the calendar grid, fetches tasks for the date range, maps tasks to their dates, renders the view. For HTMX requests (nav=prev/next/today), returns only the #calendar-grid fragment.

4. Create src/views/calendar/index.php — the full Calendar page:
   - Navigation header using the exact markup from design-notes.md: prev/next/today button group (each with hx-get="/calendar?..." hx-target="#calendar-grid"), month/year title, month/week view toggle (nav pills).
   - The #calendar-grid container that holds either the month grid or week grid.

5. Create src/views/calendar/_month_grid.php — the month view:
   - A .table.table-bordered with 7 column headers (Sun–Sat).
   - Each table cell uses the markup from design-notes.md: day number, task count badge, and task pills (colored badges with text-truncate showing task titles). Maximum of 3 task pills per cell with a "+N more" link if there are more.
   - Today's cell has .bg-primary-subtle background per design-notes.md.
   - Days outside the current month use .text-body-tertiary.
   - Clicking a day cell opens an offcanvas or modal (using hx-get to load tasks for that date) showing the day's task list with an "Add task for this date" button.

6. Create src/views/calendar/_week_grid.php — the week view:
   - A more detailed 7-column layout showing all tasks for each day as full card-like items (not just pills). Each day column shows the day name, date, and a vertical list of tasks.

7. Create src/views/calendar/_day_detail.php — the offcanvas/modal content for when a user clicks a day:
   - Shows all tasks for the selected date in a list-group.
   - Each item shows title, priority badge, status badge, and a completion checkbox.
   - An "Add Task" form at the bottom with the due_date pre-filled to the selected day.

8. Handle responsive behavior per REQ-VIEW-036: on screens below the md breakpoint, replace the calendar grid with an agenda list — a simple list grouped by date showing the next 14 days of tasks.

Test: Navigate to /calendar, verify the month grid renders with correct day layout. Add tasks with due dates and verify they appear as colored pills in the correct cells. Click prev/next arrows and verify HTMX swaps the grid without full page reload. Click "Today" to return to the current month. Switch to week view and verify it shows the current week's tasks. Click a day cell and verify the detail panel opens with that day's tasks. Test adding a task from the day detail panel. Verify today is highlighted. Test on mobile viewport to verify it degrades to an agenda list.
```

---

## Prompt 9 — Sub-Tasks, Categories Management, and Trash View

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-TASK-040 through REQ-TASK-044, REQ-CAT-001 through REQ-CAT-008, and REQ-VIEW-040 through REQ-VIEW-043.

Build sub-tasks, the categories management page, and the trash view.

1. Create src/models/SubTask.php with:
   - getByTaskId($taskId, $userId): returns all sub-tasks for a task (verifying ownership through the parent task's user_id). Ordered by sort_order.
   - create($taskId, $userId, $title): creates a sub-task after verifying the parent task belongs to the user. Returns the new sub-task.
   - toggleComplete($id, $userId): toggles is_completed (verifying ownership). Returns updated sub-task.
   - delete($id, $userId): deletes a sub-task (verifying ownership).
   - updateOrder($ids, $userId): updates sort_order for a list of sub-task IDs.
   - countByTaskId($taskId): returns total and completed counts for progress display.

2. Create src/controllers/SubTaskController.php with:
   - index($taskId): returns the sub-task list fragment for HTMX (rendered inside the task modal).
   - store($taskId): creates a new sub-task. Returns the updated sub-task list fragment with HX-Trigger for toast.
   - toggle($id): toggles completion. Returns the updated sub-task item fragment and updated progress indicator.
   - destroy($id): deletes the sub-task. Returns the updated list fragment.

3. Create src/views/subtasks/_list.php — the sub-task list partial to be included in the task detail modal:
   - A progress indicator at the top: "3 of 5 completed" with a small progress bar.
   - Each sub-task as a .form-check with: checkbox (hx-patch="/subtasks/{id}/toggle" hx-target="#subtask-list"), title text, delete button (small, outline).
   - An inline "Add sub-task" form at the bottom: text input + add button using hx-post="/tasks/{id}/subtasks" hx-target="#subtask-list" hx-swap="innerHTML".

4. Update src/views/tasks/_modal.php to include the sub-task section by loading #subtask-list content via hx-get="/tasks/{id}/subtasks" hx-trigger="load" (lazy loads sub-tasks when the modal opens).

5. Complete src/models/Category.php with:
   - getByUserId($userId): returns all categories for a user, ordered by name.
   - findById($id, $userId): returns a single category if owned by user.
   - create($userId, $name, $color): creates a category. Enforces unique (user_id, name) constraint.
   - update($id, $userId, $name, $color): updates category name and/or color.
   - delete($id, $userId): deletes the category. Does NOT delete associated tasks — their category_id is set to NULL per REQ-CAT-004.
   - getTaskCount($id, $userId): returns the number of tasks in a category.

6. Create src/controllers/CategoryController.php with:
   - index(): renders the categories management page.
   - store(): validates (name required, max 100 chars, color required as valid hex, unique name per user) and creates. Returns the updated list fragment for HTMX.
   - update($id): validates and updates. Returns the updated list item fragment.
   - destroy($id): deletes with confirmation. Returns HX-Trigger for toast and removes the item.

7. Create src/views/categories/index.php — the categories management page per design-notes.md:
   - The add-category form at top using .input-group: color picker (form-control-color), name text input, Add button. Form uses hx-post="/categories" hx-target="#category-list" hx-swap="beforeend".
   - Category list div#category-list using .list-group:
     - Each item shows: color circle swatch, category name, task count badge, edit button, delete button.
     - Edit uses Alpine.js to toggle between display mode and inline edit mode (input fields replace the name/color display).
     - Edit form uses hx-put="/categories/{id}" hx-target="closest .list-group-item" hx-swap="outerHTML".
     - Delete uses hx-delete="/categories/{id}" with hx-confirm.

8. Create src/views/categories/_item.php — a single category list item partial for HTMX swap.

9. Create src/controllers/TrashController.php with:
   - index(): loads all soft-deleted tasks for the current user, ordered by deleted_at desc. Shows deletion date and days remaining.
   - restore($id): restores a soft-deleted task (clears deleted_at). Returns updated list.
   - destroy($id): permanently deletes a task. Returns updated list.
   - empty(): permanently deletes all trashed tasks for the user. Requires confirmation.

10. Create src/views/trash/index.php — the Trash view per design-notes.md:
    - "Empty Trash" button with hx-delete="/trash" and confirmation modal.
    - Table or list showing: task title, original status, deleted date, days remaining (30 - days since deletion), restore button, permanent delete button.
    - Empty state when no trashed tasks.

11. Add a helper method to calculate days remaining before permanent purge and display it.

Test: Open a task modal, verify sub-tasks section loads. Add sub-tasks, toggle them, verify the progress indicator updates. Delete a sub-task. Test categories page: add a new category, verify it appears. Edit a category's name and color inline. Delete a category and verify its tasks become uncategorized. Test trash: delete a task, verify it appears in trash with correct date and days remaining. Restore a task and verify it returns to the task list. Permanently delete a task and verify it's gone. Test "Empty Trash" with multiple items.
```

---

## Prompt 10 — User Profile, Security Hardening, and Polish

```
Read tech-stack.md, design-notes.md, and requirements.md for context. Focus on REQ-AUTH-040 through REQ-AUTH-045, REQ-SEC-001 through REQ-SEC-011, REQ-NOTIF-004 through REQ-NOTIF-005, and deployment requirements.

Build the user profile page, harden security across the entire application, wire up notifications, and add finishing touches.

1. Create src/controllers/ProfileController.php with:
   - show(): renders the profile/settings page.
   - update(): updates first name, last name, timezone. Validates all fields. If email changes, generate a new verification token, send verification to the new email, keep the old email active until confirmed (REQ-AUTH-041). Flash success message.
   - updatePassword(): validates current password (must match), then new password (meets requirements) + confirmation. Updates the hash, invalidates all other sessions and remember-me tokens (REQ-AUTH-033 pattern). Flash success.
   - destroy(): requires password confirmation. Soft-deletes the account (sets deleted_at on users table). Logs the user out. Redirects to /login with flash message.

2. Create src/views/profile/index.php — the profile/settings page:
   - Personal Information section: first name, last name, email inputs in a card. Save button.
   - Preferences section in a second card: theme preference radio buttons (Light, Dark, Auto) and timezone select dropdown (populated with PHP's DateTimeZone::listIdentifiers()). These update via hx-put="/profile".
   - Change Password section in a third card: current password, new password, confirm password inputs. Change Password button.
   - Danger Zone section in a fourth card with .border-danger: "Delete Account" button that opens a confirmation modal requiring password input before proceeding.

3. Update the session and page rendering to apply the user's saved theme_preference from the database (not just localStorage). On login, set the theme in the session. The layout should use this value for the data-bs-theme attribute, falling back to localStorage for the auto option.

4. Update the layout's user dropdown in the navbar to show the current user's name and include links to Profile and Sign Out.

5. Implement the notification bell functionality (REQ-NOTIF-004/005):
   - Add to Task model: countDueToday($userId) and countOverdue($userId).
   - In the layout navbar, the bell icon shows a badge with (overdue + due today) count. If count is 0, hide the badge.
   - Clicking the bell opens a Bootstrap dropdown showing two sections: "Overdue" (list of overdue tasks) and "Due Today" (list of today's tasks). Each item is a link that opens the task detail modal.
   - The dropdown content is loaded via hx-get="/notifications" hx-trigger="click" on the bell button.
   - Create a simple NotificationController with an index() method that returns this fragment.

6. Security hardening — verify and add the following across the ENTIRE application:
   - CSRF: verify that every POST/PUT/PATCH/DELETE route validates the CSRF token. Verify the global hx-headers on <body> sends it with every HTMX request. Return 403 for invalid tokens.
   - XSS: audit every view file to ensure ALL user-generated content (task titles, descriptions, category names, user names) is escaped with htmlspecialchars($var, ENT_QUOTES, 'UTF-8'). Create a helper function e($string) as a shortcut.
   - SQL Injection: verify every query uses prepared statements. No string concatenation in SQL.
   - Authorization: verify every controller method that touches tasks, sub-tasks, or categories checks user_id ownership at the query level.
   - Headers: add security headers in public/index.php or via .htaccess: X-Content-Type-Options: nosniff, X-Frame-Options: DENY, Referrer-Policy: strict-origin-when-cross-origin.

7. Create custom error pages:
   - src/views/errors/404.php — styled 404 page using the app layout with a "Go to Dashboard" link.
   - src/views/errors/500.php — styled 500 page with a "Something went wrong" message. No stack trace in production.
   - Update the router and a global error handler to use these pages.

8. Create the deploy.sh script per REQ-DEPLOY-003:
   - Runs composer install --no-dev --optimize-autoloader.
   - Runs php migrations/migrate.php.
   - Sets permissions: chown -R www-data:www-data logs/ and chmod 755 public/.
   - Clears any opcache if available.

9. Create a cleanup script (scripts/cleanup.php) that permanently deletes:
   - Soft-deleted tasks older than 30 days.
   - Soft-deleted user accounts older than 30 days (and their associated tasks, categories, etc.).
   - Expired password reset tokens.
   - Expired remember-me tokens.
   - Login attempts older than 90 days.
   This script is intended to be run via cron daily per REQ-DEPLOY-004.

10. Final quality checks:
    - Verify all sidebar nav links highlight correctly on their respective pages.
    - Verify the quick-add form in the navbar works from every page.
    - Verify toast notifications appear for all CRUD operations.
    - Verify the app looks correct in both light and dark modes.
    - Verify mobile responsiveness: sidebar collapses to offcanvas, tables are scrollable, kanban scrolls horizontally, calendar degrades to agenda view.

Test: Visit /profile, update name and timezone, verify changes persist. Change password, verify old password is rejected and new one works. Test theme preference saves to database and persists across sessions. Test the notification bell shows correct counts and opens the dropdown. Test CSRF by manually submitting a form without a token — should get 403. Navigate to a non-existent URL and verify the 404 page. Run the cleanup script and verify it purges old data. Test the entire app end-to-end in both light and dark modes on desktop and mobile viewports.
```

---

## Post-Build Verification Checklist

After completing all 10 prompts, perform a full walkthrough of these user flows:

1. Register → verify email → log in → see dashboard
2. Create tasks from quick-add, list view, kanban column, and calendar day
3. Edit tasks from list view modal and kanban card
4. Drag tasks across kanban columns
5. Navigate the calendar, click days, add tasks
6. Toggle task completion from list, kanban, and dashboard
7. Add and manage sub-tasks
8. Create, edit, and delete categories
9. Filter and sort tasks in list view
10. Soft-delete → view in trash → restore / permanent delete
11. Change password, update profile, change theme
12. Log out → use "Remember Me" → verify auto-login
13. Trigger account lockout (5 failed logins) → wait → unlock
14. Forgot password → reset → verify old sessions invalidated
15. Verify all the above in dark mode on a mobile viewport

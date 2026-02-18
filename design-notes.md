# Design Notes — Todo List Tracker Application

## Design Reference

All design components, patterns, and utility classes referenced in this document are sourced from the official Bootstrap 5.3 documentation at [https://getbootstrap.com/docs/5.3/](https://getbootstrap.com/docs/5.3/). Claude Code should consult this site directly when implementing any component described below. The Bootstrap 5.3 Dashboard example at [https://getbootstrap.com/docs/5.3/examples/dashboard/](https://getbootstrap.com/docs/5.3/examples/dashboard/) serves as the foundational layout reference.

---

## Global Layout

The application uses a fixed sidebar + top navbar layout modeled after the Bootstrap 5.3 Dashboard example. The overall page structure is:

```
┌──────────────────────────────────────────────────────┐
│  Top Navbar (fixed-top, bg-body-tertiary)             │
├────────────┬─────────────────────────────────────────┤
│            │                                         │
│  Sidebar   │         Main Content Area               │
│  (fixed,   │         (container-fluid, p-4)          │
│  offcanvas │                                         │
│  on mobile)│                                         │
│            │                                         │
│            │                                         │
│            │                                         │
├────────────┴─────────────────────────────────────────┤
│  (No footer — dashboard pattern uses full height)     │
└──────────────────────────────────────────────────────┘
```

### Top Navbar

Reference: [https://getbootstrap.com/docs/5.3/components/navbar/](https://getbootstrap.com/docs/5.3/components/navbar/)

The navbar should include:

- `.navbar` with `.navbar-expand-lg` and `.bg-body-tertiary` for theme-aware background.
- `.navbar-brand` on the left with the application name "TaskFlow" (or chosen app name).
- A color mode toggle dropdown (light/dark/auto) on the right using `data-bs-theme` attribute toggling — see Color Modes section below.
- A `.navbar-toggler` button that controls the sidebar offcanvas on mobile viewports.
- A quick-add button (`.btn.btn-primary.btn-sm`) for creating a new todo inline.
- A notification bell icon using Bootstrap Icons with a `.badge.rounded-pill.bg-danger` for unread count, positioned absolutely on the icon using `.position-relative` and `.position-absolute.top-0.start-100.translate-middle`.

```html
<!-- Badge on icon pattern from getbootstrap.com/docs/5.3/components/badge/ -->
<button type="button" class="btn btn-light position-relative">
  <i class="bi bi-bell"></i>
  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
    3
    <span class="visually-hidden">unread notifications</span>
  </span>
</button>
```

### Sidebar Navigation

Reference: [https://getbootstrap.com/docs/5.3/components/offcanvas/](https://getbootstrap.com/docs/5.3/components/offcanvas/)

On desktop (lg and above), the sidebar is a fixed-position column using the CSS pattern from the Dashboard example. On mobile, it converts to an `.offcanvas.offcanvas-start` component. The sidebar includes:

- Nav items using `.nav.nav-pills.flex-column` — each item is a `.nav-link` with a Bootstrap Icon and label.
- Primary navigation links: Dashboard, My Tasks, Kanban Board, Calendar, Categories/Tags.
- A "Saved Filters" section with a small heading and secondary nav links.
- A divider (`<hr>`) followed by Settings and Sign Out links at the bottom.
- The `.active` class applied to the current page's nav link.
- Use `.text-body-secondary` for non-active links and `.text-truncate` for long labels.

---

## Color Modes (Dark Mode / Light Mode)

Reference: [https://getbootstrap.com/docs/5.3/customize/color-modes/](https://getbootstrap.com/docs/5.3/customize/color-modes/)

Bootstrap 5.3 supports color modes via the `data-bs-theme` attribute on the `<html>` element.

### Implementation Rules

- Set `data-bs-theme="light"` as default on the `<html>` element.
- Provide a toggle in the navbar with three options: Light, Dark, Auto (system preference).
- Store the user's preference in `localStorage` and apply on page load before rendering to prevent flash-of-wrong-theme.
- Use Bootstrap's built-in theme-aware CSS variables throughout. Never hardcode colors — always use classes like `.bg-body`, `.bg-body-tertiary`, `.text-body`, `.text-body-secondary`, `.border-subtle`, etc.
- For contextual colors, use the adaptive variants: `.text-primary-emphasis`, `.bg-primary-subtle`, `.border-primary-subtle` — these automatically adjust for dark mode.
- Individual components can override the global theme using `data-bs-theme="dark"` or `data-bs-theme="light"` on the element itself (useful for always-dark sidebar, if desired).

### Theme Toggle Dropdown

```html
<!-- From Bootstrap docs color mode toggler pattern -->
<li class="nav-item dropdown">
  <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
    <i class="bi bi-circle-half"></i> Theme
  </a>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><button class="dropdown-item" data-bs-theme-value="light">Light</button></li>
    <li><button class="dropdown-item" data-bs-theme-value="dark">Dark</button></li>
    <li><button class="dropdown-item" data-bs-theme-value="auto">Auto</button></li>
  </ul>
</li>
```

---

## Page 1: Dashboard

The dashboard is the default landing page after login. It provides an at-a-glance summary of the user's tasks and productivity.

### Summary Stat Cards Row

Use a responsive grid row with four `.card` elements.

Reference: [https://getbootstrap.com/docs/5.3/components/card/](https://getbootstrap.com/docs/5.3/components/card/)

```html
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
  <!-- Repeat card pattern for each stat -->
  <div class="col">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-body-secondary mb-1 small">Total Tasks</p>
            <h3 class="card-title mb-0">42</h3>
          </div>
          <span class="bg-primary-subtle text-primary-emphasis rounded-3 p-2">
            <i class="bi bi-list-check fs-4"></i>
          </span>
        </div>
        <p class="text-success small mt-2 mb-0">
          <i class="bi bi-arrow-up"></i> 12% from last week
        </p>
      </div>
    </div>
  </div>
</div>
```

The four stat cards should display:

| Card              | Icon              | Color Scheme                                          |
|-------------------|-------------------|-------------------------------------------------------|
| Total Tasks       | `bi-list-check`   | `.bg-primary-subtle` / `.text-primary-emphasis`       |
| Completed         | `bi-check-circle` | `.bg-success-subtle` / `.text-success-emphasis`       |
| In Progress       | `bi-clock-history`| `.bg-warning-subtle` / `.text-warning-emphasis`       |
| Overdue           | `bi-exclamation-triangle` | `.bg-danger-subtle` / `.text-danger-emphasis` |

### Task Completion Progress Bar

Reference: [https://getbootstrap.com/docs/5.3/components/progress/](https://getbootstrap.com/docs/5.3/components/progress/)

Below the stat cards, show an overall completion progress bar inside a card:

```html
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="fw-semibold">Weekly Progress</span>
      <span class="text-body-secondary">28 of 42 tasks</span>
    </div>
    <div class="progress" role="progressbar" aria-label="Task completion"
         aria-valuenow="67" aria-valuemin="0" aria-valuemax="100">
      <div class="progress-bar bg-success" style="width: 67%">67%</div>
    </div>
  </div>
</div>
```

Use the v5.3 progress bar markup where the `role="progressbar"` and `aria-*` attributes are on the `.progress` wrapper, not the inner bar.

### Recent Tasks Table

Reference: [https://getbootstrap.com/docs/5.3/content/tables/](https://getbootstrap.com/docs/5.3/content/tables/)

Display a `.table.table-hover` inside a card showing the most recent tasks. Columns: checkbox, task title, priority badge, due date, status badge, actions dropdown.

- Priority badges use: `.badge.text-bg-danger` (High), `.badge.text-bg-warning` (Medium), `.badge.text-bg-info` (Low).
- Status badges use: `.badge.text-bg-success` (Done), `.badge.text-bg-primary` (In Progress), `.badge.text-bg-secondary` (Todo).
- The actions column uses a `.btn-group` with a small dropdown for Edit, Delete, and Move actions.
- Each row's checkbox should use HTMX to toggle completion status via `hx-post` without a full page reload.
- The table should be wrapped in `.table-responsive` for mobile.

### Upcoming Deadlines List

A `.list-group.list-group-flush` inside a card, showing the next 5 upcoming tasks with due dates. Each item uses:

```html
<li class="list-group-item d-flex justify-content-between align-items-center">
  <div>
    <span class="fw-medium">Task Title</span>
    <br><small class="text-body-secondary">Project Name</small>
  </div>
  <span class="badge text-bg-warning rounded-pill">Tomorrow</span>
</li>
```

---

## Page 2: Kanban Board

The Kanban board displays tasks organized into columns by status. This is a custom layout built on Bootstrap's grid and card components.

### Board Layout

Use a horizontal scrollable row on smaller screens with fixed-width columns:

```html
<div class="d-flex overflow-auto gap-3 pb-3 align-items-start">
  <!-- One column per status -->
  <div class="kanban-column flex-shrink-0" style="width: 320px;">
    <div class="card bg-body-secondary border-0">
      <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          Todo <span class="badge text-bg-secondary rounded-pill ms-1">8</span>
        </h6>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus"></i></button>
      </div>
      <div class="card-body p-2 kanban-cards" style="min-height: 200px; max-height: 70vh; overflow-y: auto;">
        <!-- Task cards go here -->
      </div>
    </div>
  </div>
</div>
```

### Kanban Columns

The default columns are:

| Column         | Header Color Class                                    |
|----------------|-------------------------------------------------------|
| Backlog        | Left border: `border-start border-secondary border-3` |
| Todo           | Left border: `border-start border-primary border-3`   |
| In Progress    | Left border: `border-start border-warning border-3`   |
| Review         | Left border: `border-start border-info border-3`      |
| Done           | Left border: `border-start border-success border-3`   |

### Kanban Task Card

Each task card within a column:

```html
<div class="card mb-2 shadow-sm border-0 kanban-card" draggable="true"
     data-task-id="123">
  <div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <span class="badge text-bg-danger">High</span>
      <div class="dropdown">
        <button class="btn btn-sm btn-link text-body-secondary p-0"
                data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#">Move to...</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="#">Delete</a></li>
        </ul>
      </div>
    </div>
    <h6 class="card-title mb-2">Task title here</h6>
    <p class="card-text small text-body-secondary mb-2">Brief description...</p>
    <div class="d-flex justify-content-between align-items-center">
      <small class="text-body-secondary"><i class="bi bi-calendar-event me-1"></i>Feb 20</small>
      <small class="text-body-secondary"><i class="bi bi-tag me-1"></i>Work</small>
    </div>
  </div>
</div>
```

### Drag-and-Drop Behavior

- Drag-and-drop is handled with Alpine.js for client-side state during the drag.
- On drop, HTMX fires an `hx-put` request to update the task's status column on the server.
- Use `x-data` on the board container to track the currently dragged task ID.
- Visual feedback during drag: apply `.opacity-50` to the card being dragged and `.border.border-primary.border-dashed` to the drop target column.

---

## Page 3: Calendar View

The calendar provides a monthly/weekly view of tasks by due date.

### Calendar Navigation Header

```html
<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="btn-group" role="group">
    <button class="btn btn-outline-secondary" hx-get="/calendar?nav=prev" hx-target="#calendar-grid">
      <i class="bi bi-chevron-left"></i>
    </button>
    <button class="btn btn-outline-secondary" hx-get="/calendar?nav=today" hx-target="#calendar-grid">
      Today
    </button>
    <button class="btn btn-outline-secondary" hx-get="/calendar?nav=next" hx-target="#calendar-grid">
      <i class="bi bi-chevron-right"></i>
    </button>
  </div>
  <h4 class="mb-0">February 2026</h4>
  <ul class="nav nav-pills" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-view="month">Month</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-view="week">Week</button>
    </li>
  </ul>
</div>
```

### Calendar Grid (Month View)

Build the calendar as a `.table.table-bordered` with 7 columns (Sun–Sat). Each cell represents a day:

```html
<td class="align-top p-1" style="height: 120px; width: 14.28%;">
  <div class="d-flex justify-content-between mb-1">
    <span class="small fw-medium">18</span>
    <span class="badge text-bg-primary rounded-pill">3</span>
  </div>
  <!-- Task pills -->
  <div class="d-grid gap-1">
    <a href="#" class="badge text-bg-success text-start text-truncate text-decoration-none">
      Complete report
    </a>
    <a href="#" class="badge text-bg-warning text-start text-truncate text-decoration-none">
      Team meeting
    </a>
  </div>
</td>
```

- Today's date cell should have `.bg-primary-subtle` background.
- Days outside the current month use `.text-body-tertiary` for dimmed text.
- Clicking a day opens a modal or offcanvas to show/add tasks for that day.
- Calendar navigation (prev/next month) should use HTMX to swap the `#calendar-grid` target.

### Calendar Grid (Week View)

The week view uses a similar table but with time slots on the vertical axis (hour rows) and 7 day columns. Tasks are displayed as absolutely-positioned colored blocks spanning their duration.

---

## Page 4: Task Detail / Edit View

When a user clicks a task from any view, it opens either in a modal or offcanvas panel depending on context.

### Modal Approach

Reference: [https://getbootstrap.com/docs/5.3/components/modal/](https://getbootstrap.com/docs/5.3/components/modal/)

Use `.modal.modal-lg` for the task detail/edit view:

```html
<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5">Edit Task</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Task form content -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger me-auto"><i class="bi bi-trash"></i> Delete</button>
        <button type="button" class="btn btn-primary"
                hx-put="/tasks/123" hx-target="#task-list">Save Changes</button>
      </div>
    </div>
  </div>
</div>
```

### Task Form Fields

Use Bootstrap's form components: [https://getbootstrap.com/docs/5.3/forms/overview/](https://getbootstrap.com/docs/5.3/forms/overview/)

The form should include:

- **Title** — `.form-control` text input.
- **Description** — `.form-control` textarea (3 rows).
- **Status** — `.form-select` dropdown: Backlog, Todo, In Progress, Review, Done.
- **Priority** — `.form-select` dropdown: Low, Medium, High. Alternatively, use `.btn-group` with radio-styled toggle buttons (`.btn-check` + `.btn.btn-outline-*`).
- **Due Date** — `.form-control` with `type="date"`.
- **Category/Tags** — `.form-control` text input with Alpine.js-powered tag pills.
- **Notes/Checklist** — A sub-task checklist using `.form-check` elements inside a `.list-group`.

All form sections should use `.mb-3` spacing with `.form-label` elements.

---

## Page 5: Categories / Tags Management

A simple settings-style page for managing task categories and tags.

### Category List

Use a `.list-group` with action items:

```html
<div class="list-group">
  <div class="list-group-item d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <span class="rounded-circle d-inline-block" style="width:12px; height:12px; background-color: var(--bs-primary);"></span>
      <span>Work</span>
    </div>
    <div class="btn-group btn-group-sm">
      <button class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></button>
      <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
    </div>
  </div>
</div>
```

### Add Category Form

An inline form using `.input-group`:

```html
<div class="input-group mb-3">
  <input type="color" class="form-control form-control-color" value="#0d6efd">
  <input type="text" class="form-control" placeholder="New category name...">
  <button class="btn btn-primary" hx-post="/categories" hx-target="#category-list">
    <i class="bi bi-plus-lg"></i> Add
  </button>
</div>
```

---

## Shared Components

### Toast Notifications

Reference: [https://getbootstrap.com/docs/5.3/components/toasts/](https://getbootstrap.com/docs/5.3/components/toasts/)

A toast container should be fixed at the bottom-right of the viewport for HTMX action feedback (task created, updated, deleted):

```html
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div class="toast align-items-center text-bg-success border-0" role="alert"
       aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle me-1"></i> Task updated successfully.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
              data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
```

Use color variants for different notification types:

| Event         | Toast Class          |
|---------------|----------------------|
| Success       | `.text-bg-success`   |
| Error         | `.text-bg-danger`    |
| Warning       | `.text-bg-warning`   |
| Info          | `.text-bg-info`      |

Toasts should auto-dismiss after 3 seconds. Trigger them via HTMX response headers using the `HX-Trigger` response header to fire a custom event that Alpine.js listens for.

### Confirmation Modal

A small reusable `.modal.modal-sm` for delete confirmations:

```html
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle text-danger fs-1 mb-3 d-block"></i>
        <h5>Delete Task?</h5>
        <p class="text-body-secondary mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger"
                hx-delete="/tasks/123" hx-target="#task-list"
                hx-confirm="unset">Delete</button>
      </div>
    </div>
  </div>
</div>
```

### Empty States

When a list or board column has no tasks, display an empty state illustration using Bootstrap text utilities:

```html
<div class="text-center py-5">
  <i class="bi bi-inbox text-body-tertiary" style="font-size: 3rem;"></i>
  <p class="text-body-secondary mt-2 mb-3">No tasks here yet.</p>
  <button class="btn btn-sm btn-outline-primary">
    <i class="bi bi-plus-lg me-1"></i> Add a task
  </button>
</div>
```

### Loading Indicator (HTMX)

Use the Bootstrap spinner as the HTMX loading indicator:

```html
<div id="loading-indicator" class="htmx-indicator">
  <div class="spinner-border spinner-border-sm text-primary" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>
```

Apply `hx-indicator="#loading-indicator"` on HTMX-powered elements.

---

## HTMX + Alpine.js Interaction Patterns

### Quick Add Task (Navbar)

The quick-add button in the navbar opens an inline form (toggled by Alpine.js `x-show`) that submits via HTMX:

```html
<div x-data="{ open: false }" class="d-inline-block">
  <button class="btn btn-primary btn-sm" @click="open = !open">
    <i class="bi bi-plus-lg"></i> New Task
  </button>
  <div x-show="open" x-transition @click.outside="open = false"
       class="position-absolute end-0 mt-2 p-3 card shadow-lg" style="width: 350px; z-index: 1050;">
    <form hx-post="/tasks" hx-target="#task-list" hx-swap="afterbegin"
          @htmx:after-request="open = false">
      <input type="text" class="form-control mb-2" name="title" placeholder="Task title..." required>
      <div class="d-flex gap-2">
        <select class="form-select form-select-sm" name="priority">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
        </select>
        <input type="date" class="form-control form-control-sm" name="due_date">
        <button type="submit" class="btn btn-primary btn-sm">Add</button>
      </div>
    </form>
  </div>
</div>
```

### Inline Task Editing

When clicking a task title in the list view, use Alpine.js to toggle between display and edit modes:

```html
<div x-data="{ editing: false }">
  <span x-show="!editing" @click="editing = true" class="cursor-pointer">
    Task Title Here
  </span>
  <input x-show="editing" x-ref="editInput"
         @keydown.enter="$refs.editInput.form.requestSubmit()"
         @keydown.escape="editing = false"
         @blur="editing = false"
         class="form-control form-control-sm"
         name="title" value="Task Title Here"
         hx-put="/tasks/123" hx-target="closest tr" hx-swap="outerHTML">
</div>
```

### Filter and Search

A filter bar at the top of the task list and kanban views:

```html
<div class="d-flex flex-wrap gap-2 mb-3" x-data="{ status: 'all', priority: 'all' }">
  <div class="input-group" style="max-width: 300px;">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="search" class="form-control" placeholder="Search tasks..."
           hx-get="/tasks" hx-trigger="input changed delay:300ms"
           hx-target="#task-list" name="q">
  </div>
  <select class="form-select form-select-sm" style="width: auto;"
          x-model="status" name="status"
          hx-get="/tasks" hx-target="#task-list" hx-include="[name='q']">
    <option value="all">All Statuses</option>
    <option value="todo">Todo</option>
    <option value="in_progress">In Progress</option>
    <option value="done">Done</option>
  </select>
  <select class="form-select form-select-sm" style="width: auto;"
          x-model="priority" name="priority"
          hx-get="/tasks" hx-target="#task-list" hx-include="[name='q'],[name='status']">
    <option value="all">All Priorities</option>
    <option value="high">High</option>
    <option value="medium">Medium</option>
    <option value="low">Low</option>
  </select>
</div>
```

---

## Icons

Use Bootstrap Icons throughout the application.

Reference: [https://icons.getbootstrap.com/](https://icons.getbootstrap.com/)

Include via CDN in the `<head>`:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
```

### Icon Mapping

| Context                | Icon Class                     |
|------------------------|--------------------------------|
| Dashboard              | `bi-speedometer2`              |
| My Tasks               | `bi-check2-square`             |
| Kanban Board           | `bi-kanban`                    |
| Calendar               | `bi-calendar3`                 |
| Categories/Tags        | `bi-tags`                      |
| Settings               | `bi-gear`                      |
| Add / Create           | `bi-plus-lg`                   |
| Edit                   | `bi-pencil`                    |
| Delete                 | `bi-trash`                     |
| Search                 | `bi-search`                    |
| Filter                 | `bi-funnel`                    |
| Sort                   | `bi-sort-down`                 |
| Notification           | `bi-bell`                      |
| High Priority          | `bi-exclamation-triangle-fill` |
| Due Date               | `bi-calendar-event`            |
| Completed              | `bi-check-circle-fill`         |
| In Progress            | `bi-clock-history`             |
| Drag Handle (Kanban)   | `bi-grip-vertical`             |
| Light Theme            | `bi-sun-fill`                  |
| Dark Theme             | `bi-moon-stars-fill`           |
| Auto Theme             | `bi-circle-half`               |
| Sign Out               | `bi-box-arrow-right`           |

---

## Typography & Spacing

Reference: [https://getbootstrap.com/docs/5.3/utilities/spacing/](https://getbootstrap.com/docs/5.3/utilities/spacing/)

### General Rules

- Use Bootstrap's default system font stack — no custom fonts needed.
- Page titles: `<h2>` or `.fs-4.fw-semibold`.
- Section headings: `<h5>` or `.fs-6.fw-semibold`.
- Body text: default size (1rem / 16px).
- Secondary/meta text: `.small` or `.fs-7` with `.text-body-secondary`.
- Standard spacing between sections: `.mb-4`.
- Standard spacing within card bodies: `.mb-2` or `.mb-3`.
- Card padding: default `.card-body` padding (1rem).

### Responsive Breakpoints

Follow Bootstrap's standard breakpoints:

| Breakpoint | Class infix | Min-width |
|------------|-------------|-----------|
| Extra small| (none)      | 0         |
| Small      | `sm`        | 576px     |
| Medium     | `md`        | 768px     |
| Large      | `lg`        | 992px     |
| Extra large| `xl`        | 1200px    |
| XXL        | `xxl`       | 1400px    |

- Sidebar visible from `lg` up.
- Stat cards: 1 column on mobile, 2 on `md`, 4 on `xl`.
- Kanban columns: horizontal scroll on all viewports; minimum 320px per column.
- Calendar: full grid on `md` up; list view on smaller screens.

---

## Custom CSS Guidelines

Minimize custom CSS. Prefer Bootstrap utility classes. When custom CSS is needed:

- Prefix all custom classes with `.todo-` to avoid conflicts (e.g., `.todo-kanban-column`, `.todo-calendar-cell`).
- Place all custom CSS in a single `public/css/app.css` file.
- Use CSS custom properties (variables) that reference Bootstrap's variables for theme compatibility:

```css
.todo-kanban-column {
  min-height: 200px;
  max-height: 70vh;
  overflow-y: auto;
}

.todo-calendar-cell {
  height: 120px;
  vertical-align: top;
}

.todo-calendar-cell.today {
  background-color: var(--bs-primary-bg-subtle);
}

/* Drag-and-drop visual feedback */
.todo-kanban-card.dragging {
  opacity: 0.5;
}

.todo-kanban-column.drag-over {
  border: 2px dashed var(--bs-primary);
  background-color: var(--bs-primary-bg-subtle);
}
```

---

## Accessibility Requirements

- All interactive elements must be keyboard accessible.
- Use semantic HTML: `<nav>`, `<main>`, `<section>`, `<header>`.
- All images and icons must have `alt` text or `aria-label`.
- Form inputs must have associated `<label>` elements or `aria-label`.
- Color is never the sole indicator of meaning — always pair with text or icons.
- Modals must trap focus and return focus to the trigger element on close.
- Toast notifications use `role="alert"` and `aria-live="assertive"`.
- Progress bars include `aria-valuenow`, `aria-valuemin`, and `aria-valuemax`.
- Use `.visually-hidden` class for screen-reader-only text where needed.
- Dropdowns must be navigable with arrow keys (Bootstrap handles this natively).

---

## Summary of Bootstrap 5.3 Components Used

| Component       | Documentation URL                                                              |
|-----------------|--------------------------------------------------------------------------------|
| Navbar          | https://getbootstrap.com/docs/5.3/components/navbar/                           |
| Offcanvas       | https://getbootstrap.com/docs/5.3/components/offcanvas/                        |
| Cards           | https://getbootstrap.com/docs/5.3/components/card/                             |
| Badges          | https://getbootstrap.com/docs/5.3/components/badge/                            |
| Progress        | https://getbootstrap.com/docs/5.3/components/progress/                         |
| Modals          | https://getbootstrap.com/docs/5.3/components/modal/                            |
| Toasts          | https://getbootstrap.com/docs/5.3/components/toasts/                           |
| Dropdowns       | https://getbootstrap.com/docs/5.3/components/dropdowns/                        |
| List Group      | https://getbootstrap.com/docs/5.3/components/list-group/                       |
| Nav / Pills     | https://getbootstrap.com/docs/5.3/components/navs-tabs/                        |
| Tooltips        | https://getbootstrap.com/docs/5.3/components/tooltips/                         |
| Buttons         | https://getbootstrap.com/docs/5.3/components/buttons/                          |
| Button Groups   | https://getbootstrap.com/docs/5.3/components/button-group/                     |
| Forms           | https://getbootstrap.com/docs/5.3/forms/overview/                              |
| Input Groups    | https://getbootstrap.com/docs/5.3/forms/input-group/                           |
| Spinners        | https://getbootstrap.com/docs/5.3/components/spinners/                         |
| Color Modes     | https://getbootstrap.com/docs/5.3/customize/color-modes/                       |
| Dashboard Example | https://getbootstrap.com/docs/5.3/examples/dashboard/                        |
| Bootstrap Icons | https://icons.getbootstrap.com/                                               |

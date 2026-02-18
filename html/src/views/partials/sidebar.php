<nav class="nav flex-column px-3 py-3" id="sidebar-nav">
    <!-- Primary Navigation -->
    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-1 text-body-secondary text-uppercase small" id="nav-heading">
        <span>Navigation</span>
    </h6>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item" id="nav-dashboard">
            <a class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : 'text-body-secondary' ?>"
               href="/dashboard" id="nav-dashboard-link">
                <i class="bi bi-speedometer2 me-2" id="nav-dashboard-icon"></i>
                Dashboard
            </a>
        </li>

        <li class="nav-item" id="nav-tasks">
            <a class="nav-link <?= $currentRoute === 'tasks' ? 'active' : 'text-body-secondary' ?>"
               href="/tasks" id="nav-tasks-link">
                <i class="bi bi-check2-square me-2" id="nav-tasks-icon"></i>
                My Tasks
            </a>
        </li>

        <li class="nav-item" id="nav-kanban">
            <a class="nav-link <?= $currentRoute === 'kanban' ? 'active' : 'text-body-secondary' ?>"
               href="/kanban" id="nav-kanban-link">
                <i class="bi bi-kanban me-2" id="nav-kanban-icon"></i>
                Kanban Board
            </a>
        </li>

        <li class="nav-item" id="nav-calendar">
            <a class="nav-link <?= $currentRoute === 'calendar' ? 'active' : 'text-body-secondary' ?>"
               href="/calendar" id="nav-calendar-link">
                <i class="bi bi-calendar3 me-2" id="nav-calendar-icon"></i>
                Calendar
            </a>
        </li>

        <li class="nav-item" id="nav-categories">
            <a class="nav-link <?= $currentRoute === 'categories' ? 'active' : 'text-body-secondary' ?>"
               href="/categories" id="nav-categories-link">
                <i class="bi bi-tags me-2" id="nav-categories-icon"></i>
                Categories
            </a>
        </li>

        <li class="nav-item" id="nav-trash">
            <a class="nav-link <?= $currentRoute === 'trash' ? 'active' : 'text-body-secondary' ?>"
               href="/trash" id="nav-trash-link">
                <i class="bi bi-trash me-2" id="nav-trash-icon"></i>
                Trash
            </a>
        </li>
    </ul>

    <!-- Divider -->
    <hr class="my-3" id="sidebar-divider">

    <!-- Secondary Navigation -->
    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-1 text-body-secondary text-uppercase small" id="saved-filters-heading">
        <span>Saved Filters</span>
        <button class="btn btn-link p-0 text-body-secondary" style="font-size: 0.75rem;"
                title="Add new filter" id="add-filter-btn">
            <i class="bi bi-plus"></i>
        </button>
    </h6>

    <ul class="nav nav-pills flex-column mb-3" id="saved-filters">
        <li class="nav-item" id="filter-high-priority">
            <a class="nav-link text-body-secondary small" href="/tasks?priority=high" id="filter-high-priority-link">
                <i class="bi bi-exclamation-triangle-fill text-danger me-2" id="filter-high-priority-icon"></i>
                High Priority
            </a>
        </li>

        <li class="nav-item" id="filter-due-today">
            <a class="nav-link text-body-secondary small" href="/tasks?due=today" id="filter-due-today-link">
                <i class="bi bi-calendar-check text-warning me-2" id="filter-due-today-icon"></i>
                Due Today
            </a>
        </li>

        <li class="nav-item" id="filter-overdue">
            <a class="nav-link text-body-secondary small" href="/tasks?overdue=true" id="filter-overdue-link">
                <i class="bi bi-clock-history text-danger me-2" id="filter-overdue-icon"></i>
                Overdue
            </a>
        </li>
    </ul>

    <!-- Footer Links -->
    <div class="nav flex-column" id="sidebar-footer">
        <a class="nav-link text-body-secondary small" href="/profile" id="sidebar-settings-link">
            <i class="bi bi-gear me-2" id="sidebar-settings-icon"></i>
            Settings
        </a>

        <div class="nav-link text-body-secondary small" id="sidebar-user-info">
            <i class="bi bi-person-circle me-2" id="sidebar-user-icon"></i>
            <div class="d-flex flex-column" id="sidebar-user-details">
                <span class="fw-medium" id="sidebar-username">
                    <?= htmlspecialchars($currentUser['first_name'] ?? 'User') ?>
                </span>
                <span class="text-muted" style="font-size: 0.7rem;" id="sidebar-user-email">
                    <?= htmlspecialchars($currentUser['email'] ?? '') ?>
                </span>
            </div>
        </div>
    </div>
</nav>

<style>
    /* Custom sidebar styles */
    .sidebar-heading {
        font-weight: 600;
        letter-spacing: 0.025em;
    }

    .nav-pills .nav-link {
        border-radius: 0.5rem;
        margin-bottom: 0.125rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.15s ease-in-out;
    }

    .nav-pills .nav-link:hover {
        background-color: var(--bs-secondary-bg);
        color: var(--bs-emphasis-color);
    }

    .nav-pills .nav-link.active {
        background-color: var(--bs-primary);
        color: var(--bs-white);
    }

    .nav-pills .nav-link.active i {
        color: var(--bs-white);
    }

    #sidebar-user-info {
        cursor: default;
        padding: 0.5rem 0.75rem;
        margin-top: 0.5rem;
        border-top: 1px solid var(--bs-border-color);
    }

    @media (max-width: 991.98px) {
        .offcanvas-body .nav-pills .nav-link {
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
        }
    }
</style>
<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4" id="dashboard-header">
    <div>
        <h2 class="mb-1" id="dashboard-title">Welcome back, <?= htmlspecialchars($currentUser['first_name']) ?>!</h2>
        <p class="text-body-secondary mb-0" id="dashboard-subtitle">Here's what's happening with your tasks today.</p>
    </div>
    <div class="d-flex gap-2" id="dashboard-actions">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#taskModal" id="new-task-btn">
            <i class="bi bi-plus-lg me-1"></i> New Task
        </button>
        <div class="dropdown" id="dashboard-menu">
            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" id="dashboard-menu-btn">
                <i class="bi bi-three-dots"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/tasks?view=export" id="export-tasks">
                    <i class="bi bi-download me-2"></i>Export Tasks
                </a></li>
                <li><a class="dropdown-item" href="/profile" id="dashboard-settings">
                    <i class="bi bi-gear me-2"></i>Settings
                </a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4" id="stats-cards">
    <div class="col" id="stat-total">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-body-secondary mb-1 small">Total Tasks</p>
                        <h3 class="card-title mb-0" id="total-tasks-count"><?= $stats['total_tasks'] ?></h3>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary-emphasis">
                        <i class="bi bi-list-check"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-body-secondary">
                        <i class="bi bi-calendar me-1"></i>All time
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col" id="stat-completed">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-body-secondary mb-1 small">Completed</p>
                        <h3 class="card-title mb-0" id="completed-tasks-count"><?= $stats['completed_tasks'] ?></h3>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success-emphasis">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-success">
                        <i class="bi bi-arrow-up me-1"></i><?= $completionRate ?>% completion rate
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col" id="stat-progress">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-body-secondary mb-1 small">In Progress</p>
                        <h3 class="card-title mb-0" id="in-progress-count"><?= $stats['in_progress_tasks'] ?></h3>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-body-secondary">
                        <i class="bi bi-play me-1"></i>Active tasks
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col" id="stat-overdue">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-body-secondary mb-1 small">Overdue</p>
                        <h3 class="card-title mb-0 <?= $stats['overdue_tasks'] > 0 ? 'text-danger' : '' ?>" id="overdue-count">
                            <?= $stats['overdue_tasks'] ?>
                        </h3>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger-emphasis">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <?php if ($stats['overdue_tasks'] > 0): ?>
                        <small class="text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Needs attention
                        </small>
                    <?php else: ?>
                        <small class="text-success">
                            <i class="bi bi-check me-1"></i>All caught up!
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Overview -->
<div class="row mb-4" id="progress-section">
    <div class="col-md-8" id="progress-chart">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-1">Task Progress</h5>
                <p class="text-body-secondary small mb-0">Overall completion status</p>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-medium">Completion Rate</span>
                    <span class="text-body-secondary"><?= $stats['completed_tasks'] ?> of <?= $stats['total_tasks'] ?> tasks</span>
                </div>
                <div class="progress mb-3" style="height: 12px;" role="progressbar"
                     aria-label="Task completion" aria-valuenow="<?= $completionRate ?>"
                     aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: <?= $completionRate ?>%"></div>
                </div>
                <div class="row text-center">
                    <div class="col">
                        <div class="text-muted small">Completed</div>
                        <div class="fw-semibold text-success"><?= $stats['completed_tasks'] ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">In Progress</div>
                        <div class="fw-semibold text-warning"><?= $stats['in_progress_tasks'] ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Remaining</div>
                        <div class="fw-semibold"><?= $stats['total_tasks'] - $stats['completed_tasks'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4" id="quick-actions">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-1">Quick Actions</h5>
                <p class="text-body-secondary small mb-0">Common tasks</p>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="d-grid gap-2 flex-grow-1">
                    <a href="/tasks" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list-ul me-2"></i>View All Tasks
                    </a>
                    <a href="/kanban" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-kanban me-2"></i>Kanban Board
                    </a>
                    <a href="/calendar" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-calendar3 me-2"></i>Calendar View
                    </a>
                    <a href="/categories" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-tags me-2"></i>Manage Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tasks and Upcoming Deadlines -->
<div class="row" id="dashboard-content">
    <div class="col-lg-7" id="recent-tasks-section">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Tasks</h5>
                <a href="/tasks" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentTasks)): ?>
                    <div class="text-center py-5" id="no-recent-tasks">
                        <i class="bi bi-inbox text-body-tertiary" style="font-size: 3rem;"></i>
                        <p class="text-body-secondary mt-2 mb-3">No tasks yet. Create your first task to get started!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal">
                            <i class="bi bi-plus-lg me-1"></i> Create Task
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="recent-tasks-table">
                            <tbody>
                                <?php foreach ($recentTasks as $task): ?>
                                    <tr class="task-row" data-task-id="<?= $task['id'] ?>">
                                        <td class="ps-3" style="width: 40px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       <?= $task['status'] === 'done' ? 'checked' : '' ?>
                                                       hx-patch="/tasks/<?= $task['id'] ?>/toggle"
                                                       hx-target="closest tr"
                                                       hx-swap="outerHTML">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($task['category_color']): ?>
                                                    <span class="rounded-circle me-2"
                                                          style="width:8px; height:8px; background-color: <?= htmlspecialchars($task['category_color']) ?>;"></span>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-medium <?= $task['status'] === 'done' ? 'text-decoration-line-through text-muted' : '' ?>">
                                                        <?= htmlspecialchars($task['title']) ?>
                                                    </div>
                                                    <?php if ($task['category_name']): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($task['category_name']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="width: 100px;">
                                            <span class="badge priority-<?= $task['priority'] ?>">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </td>
                                        <td style="width: 120px;">
                                            <?php if ($task['due_date']): ?>
                                                <small class="text-body-secondary">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?= date('M j', strtotime($task['due_date'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 100px;">
                                            <span class="badge status-<?= $task['status'] ?>">
                                                <?= ucwords(str_replace('_', ' ', $task['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mt-3 mt-lg-0" id="upcoming-deadlines-section">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Upcoming Deadlines</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingDeadlines)): ?>
                    <div class="text-center py-4" id="no-deadlines">
                        <i class="bi bi-calendar-check text-body-tertiary" style="font-size: 2rem;"></i>
                        <p class="text-body-secondary mt-2 mb-0">No upcoming deadlines</p>
                        <small class="text-muted">You're all caught up!</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" id="deadlines-list">
                        <?php foreach ($upcomingDeadlines as $task): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0"
                                 data-task-id="<?= $task['id'] ?>">
                                <div class="d-flex align-items-center">
                                    <?php if ($task['category_color']): ?>
                                        <span class="rounded-circle me-2"
                                              style="width:8px; height:8px; background-color: <?= htmlspecialchars($task['category_color']) ?>;"></span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($task['title']) ?></div>
                                        <?php if ($task['category_name']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($task['category_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $dueDate = new DateTime($task['due_date']);
                                    $today = new DateTime();
                                    $diff = $today->diff($dueDate);
                                    $isOverdue = $dueDate < $today;
                                    $isToday = $dueDate->format('Y-m-d') === $today->format('Y-m-d');
                                    $isTomorrow = $diff->days === 1 && !$isOverdue;

                                    if ($isOverdue) {
                                        $badgeClass = 'text-bg-danger';
                                        $badgeText = 'Overdue';
                                    } elseif ($isToday) {
                                        $badgeClass = 'text-bg-warning';
                                        $badgeText = 'Today';
                                    } elseif ($isTomorrow) {
                                        $badgeClass = 'text-bg-info';
                                        $badgeText = 'Tomorrow';
                                    } else {
                                        $badgeClass = 'text-bg-secondary';
                                        $badgeText = $diff->days . ' days';
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?> rounded-pill">
                                        <?= $badgeText ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
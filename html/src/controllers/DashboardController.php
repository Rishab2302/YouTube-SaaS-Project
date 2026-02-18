<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Dashboard';

        // Get dashboard data
        $dashboardData = $this->getDashboardData();

        $this->render('dashboard/index', [
            'pageTitle' => $pageTitle,
            'stats' => $dashboardData['stats'],
            'recentTasks' => $dashboardData['recentTasks'],
            'upcomingDeadlines' => $dashboardData['upcomingDeadlines'],
            'completionRate' => $dashboardData['completionRate']
        ]);
    }

    private function getDashboardData(): array
    {
        $userId = $this->currentUser['id'];

        // Get task statistics
        $statsQuery = $this->db->prepare("
            SELECT
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN due_date < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) as overdue_tasks
            FROM tasks
            WHERE user_id = ? AND deleted_at IS NULL
        ");
        $statsQuery->execute([$userId]);
        $stats = $statsQuery->fetch();

        // Get recent tasks (last 10 updated)
        $recentQuery = $this->db->prepare("
            SELECT t.*, c.name as category_name, c.color as category_color
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            ORDER BY t.updated_at DESC
            LIMIT 10
        ");
        $recentQuery->execute([$userId]);
        $recentTasks = $recentQuery->fetchAll();

        // Get upcoming deadlines (next 5 by due date)
        $deadlinesQuery = $this->db->prepare("
            SELECT t.*, c.name as category_name, c.color as category_color
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            AND t.due_date IS NOT NULL AND t.status != 'done'
            ORDER BY t.due_date ASC
            LIMIT 5
        ");
        $deadlinesQuery->execute([$userId]);
        $upcomingDeadlines = $deadlinesQuery->fetchAll();

        // Calculate completion rate
        $completionRate = $stats['total_tasks'] > 0
            ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1)
            : 0;

        return [
            'stats' => $stats,
            'recentTasks' => $recentTasks,
            'upcomingDeadlines' => $upcomingDeadlines,
            'completionRate' => $completionRate
        ];
    }
}
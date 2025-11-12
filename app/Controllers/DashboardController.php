<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\TimeLog;

class DashboardController {
    private Task $taskModel;
    private TimeLog $timeLogModel;

    public function __construct() {
        require_auth();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * Show dashboard
     */
    public function index(): void {
        $userId = auth()['id'];

        // Get or create today's focus
        $focus = $this->getTodaysFocus($userId);

        // Get 3 upcoming deadlines
        $upcomingDeadlines = $this->taskModel->getUpcomingDeadlines(3);

        // Get tasks in progress (should be max 1)
        $tasksInProgress = $this->taskModel->getInProgress();

        // Get recent time logs (last 7 days)
        $recentTimeLogs = $this->timeLogModel->getRecent(7);

        view('dashboard.index', [
            'focus' => $focus,
            'upcomingDeadlines' => $upcomingDeadlines,
            'tasksInProgress' => $tasksInProgress,
            'recentTimeLogs' => $recentTimeLogs,
        ]);
    }

    /**
     * Save today's focus
     */
    public function saveFocus(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $userId = auth()['id'];
        $focus = trim($_POST['focus'] ?? '');

        if (empty($focus)) {
            json_response(['error' => 'Il focus non può essere vuoto'], 400);
        }

        $db = get_db();

        // Insert or update
        $sql = "INSERT INTO daily_focus (user_id, date, focus)
                VALUES (?, CURDATE(), ?)
                ON DUPLICATE KEY UPDATE focus = VALUES(focus)";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $focus]);

        json_response(['success' => true, 'message' => 'Focus salvato ✅']);
    }

    /**
     * Get today's focus for user
     */
    private function getTodaysFocus(int $userId): ?string {
        $db = get_db();

        $sql = "SELECT focus FROM daily_focus
                WHERE user_id = ? AND date = CURDATE()
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ? $result['focus'] : null;
    }
}

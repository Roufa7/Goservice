<?php
require_once dirname(__DIR__, 2) . '/config.php';

class DashboardController {
    private PDO $db;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    public function handleRequest(): void {
        $stats = [];

        $queries = [
            'users' => 'SELECT COUNT(*) FROM user',
            'services' => 'SELECT COUNT(*) FROM service',
            'posts' => 'SELECT COUNT(*) FROM post',
            'reclamations' => 'SELECT COUNT(*) FROM reclamation',
        ];

        foreach ($queries as $key => $sql) {
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $stats[$key] = (int) $stmt->fetchColumn();
            } catch (Throwable $e) {
                $stats[$key] = 0;
            }
        }

        $GLOBALS['adminStats'] = $stats;

        try {
            $chartSql = "SELECT 
                            DATE_FORMAT(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY), '%d %b') AS week_start,
                            COUNT(*) AS count
                         FROM reclamation
                         WHERE status = 'resolved'
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                         GROUP BY week_start
                         ORDER BY MIN(created_at) ASC";
            $stmt = $this->db->prepare($chartSql);
            $stmt->execute();
            $GLOBALS['reclamationChartData'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $GLOBALS['reclamationChartData'] = [];
        }
    }
}

$controller = new DashboardController();
$controller->handleRequest();
?>
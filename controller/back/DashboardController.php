<?php
require_once dirname(__DIR__, 2) . '/config.php';

class DashboardController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest() {
        // Fetch stats for the top cards
        $stats = [];
        
        $queries = [
            'users' => "SELECT COUNT(*) FROM users",
            'services' => "SELECT COUNT(*) FROM service",
            'posts' => "SELECT COUNT(*) FROM post",
            'reclamations' => "SELECT COUNT(*) FROM reclamation"
        ];

        foreach ($queries as $key => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats[$key] = $stmt->fetchColumn();
        }
        $GLOBALS['adminStats'] = $stats;

        // Fetch data for the chart: resolved reclamations per week (last 8 weeks)
        $chartSql = "SELECT 
                        DATE_FORMAT(DATE_SUB(created_at, INTERVAL (WEEKDAY(created_at)) DAY), '%d %b') as week_start,
                        COUNT(*) as count
                     FROM reclamation 
                     WHERE status = 'resolved' 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                     GROUP BY week_start
                     ORDER BY MIN(created_at) ASC";
        
        $stmt = $this->db->prepare($chartSql);
        $stmt->execute();
        $GLOBALS['reclamationChartData'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$controller = new DashboardController();
$controller->handleRequest();
?>

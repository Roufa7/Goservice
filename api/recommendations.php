<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/RecommendationController.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function extractUserIdFromRequest(): int {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#/api/recommendations/(\d+)$#', $path, $m)) {
        return (int) $m[1];
    }
    if (isset($_GET['userId']) && is_numeric($_GET['userId'])) {
        return (int) $_GET['userId'];
    }
    return 0;
}

$userId = extractUserIdFromRequest();
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing userId']);
    exit;
}

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;

try {
    $controller = new RecommendationController();
    $recommendations = $controller->recommendForUser($userId, $limit);
    echo json_encode($recommendations, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

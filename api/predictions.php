<?php
/**
 * API Endpoint: /api/predictions
 * 
 * Provides prediction data based on offers and candidatures
 * Usage:
 *   GET /api/predictions.php
 *   GET /api/predictions.php?days=30
 * 
 * Returns: JSON response with topService, trendingOffers, and insight
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Include required files
    require_once(__DIR__ . '/../config.php');
    require_once(__DIR__ . '/../controller/PredictionController.php');
    
    // Get days parameter (default 30)
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    $days = max(1, min($days, 365)); // Clamp between 1 and 365 days
    
    // Instantiate controller and get predictions
    $predictionController = new PredictionController();
    $predictions = $predictionController->getPredictions($days);
    
    // Return JSON response
    http_response_code(200);
    echo json_encode($predictions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

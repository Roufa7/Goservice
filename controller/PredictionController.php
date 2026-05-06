<?php
/**
 * PredictionController
 * 
 * Handles prediction logic for service offers and candidatures.
 * Provides trending analysis without machine learning - purely statistical.
 */

include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../model/Offer.php');
include_once(__DIR__ . '/../model/Candidature.php');

if (!class_exists('PredictionController')) {
    class PredictionController {
        
        private $db;
        private $lookbackDays = 30; // Default analysis window
        
        public function __construct() {
            $this->db = config::getConnexion();
        }

        /**
         * Get comprehensive predictions based on current data
         * 
         * @return array Prediction data with topService, trendingOffers, and insight
         */
        public function getPredictions($days = 30) {
            $this->lookbackDays = (int)$days;
            
            try {
                $topService = $this->getTopService();
                $trendingOffers = $this->getTrendingOffers();
                $insight = $this->generateInsight($topService, $trendingOffers);
                
                return [
                    'success' => true,
                    'topService' => $topService,
                    'trendingOffers' => $trendingOffers,
                    'insight' => $insight,
                    'lookbackDays' => $this->lookbackDays,
                    'timestamp' => (new DateTime('now'))->format('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        /**
         * Find the most requested service by counting total candidatures
         * 
         * @return array|null Service data with name, count, and trend
         */
        private function getTopService() {
            $recentDate = (new DateTime('now'))->sub(new DateInterval('P' . $this->lookbackDays . 'D'))->format('Y-m-d');
            
            // SQL: Count candidatures per service type (last 30 days)
            $sql = "
                SELECT 
                    o.type_service,
                    COUNT(c.id_candidature) as candidature_count,
                    COUNT(DISTINCT c.id_candidature) as unique_candidatures
                FROM offre o
                LEFT JOIN candidature c ON c.id_offre = o.id_offre
                WHERE c.date_candidature >= :recentDate
                GROUP BY o.type_service
                ORDER BY candidature_count DESC
                LIMIT 1
            ";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['recentDate' => $recentDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    return [
                        'name' => $result['type_service'] ?: 'Service',
                        'candidatureCount' => (int)$result['candidature_count'],
                        'uniqueCandidatures' => (int)$result['unique_candidatures']
                    ];
                }
                
                return null;
            } catch (Exception $e) {
                error_log("Error fetching top service: " . $e->getMessage());
                return null;
            }
        }

        /**
         * Find trending offers based on candidatures per day since posting
         * Trending Score = number_of_candidatures / days_since_post (avoids division by zero)
         * 
         * @return array Array of trending offers with scores, sorted by score DESC
         */
        private function getTrendingOffers() {
            $recentDate = (new DateTime('now'))->sub(new DateInterval('P' . $this->lookbackDays . 'D'))->format('Y-m-d');
            
            // SQL: Calculate trending score for each offer
            // Trending Score = candidatures per day
            $sql = "
                SELECT 
                    o.id_offre,
                    o.titre,
                    o.type_service,
                    o.date_publication,
                    COUNT(c.id_candidature) as candidature_count,
                    DATEDIFF(CURDATE(), DATE(o.date_publication)) as days_since_post,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), DATE(o.date_publication)) <= 0 THEN NULL
                        ELSE ROUND(COUNT(c.id_candidature) / DATEDIFF(CURDATE(), DATE(o.date_publication)), 2)
                    END as trending_score
                FROM offre o
                LEFT JOIN candidature c ON c.id_offre = o.id_offre
                WHERE c.date_candidature >= :recentDate OR DATE(o.date_publication) >= :recentDate
                GROUP BY o.id_offre, o.titre, o.type_service, o.date_publication
                HAVING candidature_count > 0
                ORDER BY trending_score DESC, candidature_count DESC
                LIMIT 5
            ";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['recentDate' => $recentDate]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $trendingOffers = [];
                foreach ($results as $row) {
                    $score = $row['trending_score'] !== null ? (float)$row['trending_score'] : 0;
                    $trendingOffers[] = [
                        'id' => (int)$row['id_offre'],
                        'title' => $row['titre'],
                        'service' => $row['type_service'],
                        'candidatureCount' => (int)$row['candidature_count'],
                        'daysSincePost' => (int)$row['days_since_post'],
                        'trendingScore' => $score
                    ];
                }
                
                return $trendingOffers;
            } catch (Exception $e) {
                error_log("Error fetching trending offers: " . $e->getMessage());
                return [];
            }
        }

        /**
         * Generate a human-readable insight from prediction data
         * 
         * @param array $topService Top service data
         * @param array $trendingOffers Array of trending offers
         * @return string Insight message
         */
        private function generateInsight($topService, $trendingOffers) {
            if (empty($topService)) {
                return "Insufficient data to generate predictions.";
            }
            
            $service = $topService['name'] ?? 'Services';
            $count = $topService['candidatureCount'] ?? 0;
            
            // Build insight message
            $messages = [
                "Demand for {$service} services is strong with {$count} candidatures in the last {$this->lookbackDays} days.",
                "{$service} is the most requested service ({$count} applications).",
                "The {$service} market shows {$count} candidatures over the past month.",
                "High interest in {$service} services: {$count} applications received."
            ];
            
            // Add trend analysis if we have trending offers
            if (!empty($trendingOffers)) {
                $topOffer = $trendingOffers[0];
                $score = $topOffer['trendingScore'];
                $title = $topOffer['title'];
                
                if ($score > 3) {
                    $messages[] = "🔥 '{$title}' is trending with {$score} applications per day!";
                } elseif ($score > 1) {
                    $messages[] = "'{$title}' shows steady interest at {$score} applications per day.";
                } else {
                    $messages[] = "'{$title}' is gaining traction ({$score} applications per day).";
                }
            }
            
            // Return a random message for variety
            return $messages[array_rand($messages)];
        }

        /**
         * Get service comparison over time periods
         * Useful for detecting growth or decline
         * 
         * @return array Comparison of services between recent and previous periods
         */
        public function getServiceComparison() {
            $recentStart = (new DateTime('now'))->sub(new DateInterval('P' . ($this->lookbackDays / 2) . 'D'))->format('Y-m-d');
            $recentEnd = (new DateTime('now'))->format('Y-m-d');
            $previousStart = (new DateTime('now'))->sub(new DateInterval('P' . $this->lookbackDays . 'D'))->format('Y-m-d');
            $previousEnd = (new DateTime('now'))->sub(new DateInterval('P' . ($this->lookbackDays / 2) . 'D'))->format('Y-m-d');
            
            $sql = "
                SELECT 
                    o.type_service,
                    SUM(CASE WHEN c.date_candidature >= :recentStart THEN 1 ELSE 0 END) as recent_count,
                    SUM(CASE WHEN c.date_candidature BETWEEN :previousStart AND :previousEnd THEN 1 ELSE 0 END) as previous_count,
                    CASE 
                        WHEN SUM(CASE WHEN c.date_candidature BETWEEN :previousStart AND :previousEnd THEN 1 ELSE 0 END) = 0 THEN NULL
                        ELSE ROUND(
                            (SUM(CASE WHEN c.date_candidature >= :recentStart THEN 1 ELSE 0 END) - 
                             SUM(CASE WHEN c.date_candidature BETWEEN :previousStart AND :previousEnd THEN 1 ELSE 0 END)) / 
                            SUM(CASE WHEN c.date_candidature BETWEEN :previousStart AND :previousEnd THEN 1 ELSE 0 END) * 100, 
                            2
                        )
                    END as growth_percent
                FROM offre o
                LEFT JOIN candidature c ON c.id_offre = o.id_offre
                GROUP BY o.type_service
                ORDER BY recent_count DESC
            ";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'recentStart' => $recentStart,
                    'previousStart' => $previousStart,
                    'previousEnd' => $previousEnd
                ]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error fetching service comparison: " . $e->getMessage());
                return [];
            }
        }
    }
}
?>

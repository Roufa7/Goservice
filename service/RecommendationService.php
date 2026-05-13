<?php
/**
 * RecommendationService.php — GoService v3
 * IA : Recommandation (KNN) + Prédiction prix (Régression linéaire)
 * Bibliothèque : php-ai/php-ml (installée via Composer)
 *
 * Chemin : GoService_v3/service/RecommendationService.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Phpml\Classification\KNearestNeighbors;
use Phpml\Regression\LeastSquares;

class RecommendationService
{
    // ════════════════════════════════════════════════════════
    //  RECOMMANDATION — Services similaires via KNN
    //  Appelé dans serviceDetails.php
    //  Trouve les K services les plus proches du service
    //  courant en vectorisant : catégorie, prix, disponibilité
    // ════════════════════════════════════════════════════════
    public static function getRecommandations(array $serviceCourant, array $tousLesServices, int $k = 3): array
    {
        // Exclure le service courant et les non-validés
        $autres = array_values(array_filter($tousLesServices, function($s) use ($serviceCourant) {
            return (int)$s['id_service'] !== (int)$serviceCourant['id_service']
                && $s['statut'] === 'Validé';
        }));

        if (count($autres) < 2) return [];

        $prixMax = max(array_column($tousLesServices, 'prix')) ?: 1;

        // Calculer la distance euclidienne pour chaque service
        $vecteurRef = self::vectorize($serviceCourant, $prixMax);
        $distances  = [];
        foreach ($autres as $i => $s) {
            $distances[$i] = self::euclideanDistance($vecteurRef, self::vectorize($s, $prixMax));
        }
        asort($distances);

        // Prendre les K plus proches
        $recommandations = [];
        foreach (array_slice(array_keys($distances), 0, $k, true) as $i) {
            $s = $autres[$i];
            $recommandations[] = array_merge($s, [
                'score_similarite' => self::calculerScore($serviceCourant, $s)
            ]);
        }

        usort($recommandations, fn($a, $b) => $b['score_similarite'] <=> $a['score_similarite']);
        return $recommandations;
    }

    // ════════════════════════════════════════════════════════
    //  PRÉDICTION DE PRIX — Régression linéaire (Least Squares)
    //  Appelé dans categories.php (back office)
    //  Prédit le prix du prochain service pour chaque catégorie
    // ════════════════════════════════════════════════════════
    public static function predirePrixParCategorie(array $services): array
    {
        // Grouper par catégorie
        $parCategorie = [];
        foreach ($services as $s) {
            $cat = $s['nom_categorie'] ?? 'Autre';
            $parCategorie[$cat][] = (float)$s['prix'];
        }

        $predictions = [];

        foreach ($parCategorie as $categorie => $prix) {
            $n       = count($prix);
            $moyenne = round(array_sum($prix) / $n, 2);

            if ($n < 2) {
                $predictions[$categorie] = [
                    'moyenne_actuelle' => $moyenne,
                    'prediction'       => $moyenne,
                    'tendance'         => 'stable',
                    'nb_services'      => $n,
                    'confiance'        => 'faible',
                ];
                continue;
            }

            try {
                // X = index (1,2,3...), Y = prix
                $samples = array_map(fn($i) => [$i + 1], range(0, $n - 1));
                $targets = $prix;

                $regression = new LeastSquares();
                $regression->train($samples, $targets);

                $prediction = max(0, round($regression->predict([$n + 1]), 2));

                $tendance = $prediction > $moyenne * 1.05 ? 'hausse'
                          : ($prediction < $moyenne * 0.95 ? 'baisse' : 'stable');

                $predictions[$categorie] = [
                    'moyenne_actuelle' => $moyenne,
                    'prediction'       => $prediction,
                    'tendance'         => $tendance,
                    'nb_services'      => $n,
                    'confiance'        => $n >= 3 ? 'bonne' : 'moyenne',
                ];
            } catch (\Exception $e) {
                error_log('[GoService][IA] ' . $e->getMessage());
                $predictions[$categorie] = [
                    'moyenne_actuelle' => $moyenne,
                    'prediction'       => $moyenne,
                    'tendance'         => 'stable',
                    'nb_services'      => $n,
                    'confiance'        => 'faible',
                ];
            }
        }

        uasort($predictions, fn($a, $b) => $b['nb_services'] <=> $a['nb_services']);
        return $predictions;
    }

    // ── Helpers privés ───────────────────────────────────────

    private static function vectorize(array $s, float $prixMax): array
    {
        return [
            (float)($s['id_categorie'] ?? 0) / 20.0,
            (float)($s['prix']         ?? 0) / $prixMax,
            $s['disponibilite'] === 'Disponible' ? 1.0 : 0.0,
        ];
    }

    private static function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $v) $sum += ($v - ($b[$i] ?? 0)) ** 2;
        return sqrt($sum);
    }

    private static function calculerScore(array $ref, array $candidat): int
    {
        $score = 0;
        if ((int)$ref['id_categorie'] === (int)$candidat['id_categorie']) $score += 50;
        $prixRef = (float)($ref['prix'] ?? 0);
        if ($prixRef > 0) {
            $diff  = abs($prixRef - (float)($candidat['prix'] ?? 0)) / $prixRef;
            $score += (int)(30 * max(0, 1 - $diff));
        }
        if ($ref['disponibilite'] === $candidat['disponibilite']) $score += 20;
        return min(100, $score);
    }
}
?>

<?php
/**
 * GeocoderService.php
 * Service de géocodage utilisant Nominatim (OpenStreetMap) — 100% gratuit, sans clé API
 * Utilisation : convertir une adresse textuelle en coordonnées latitude/longitude
 * 
 * Chemin : GoService_v3/service/GeocoderService.php
 */

class GeocoderService
{
    /**
     * Convertit une adresse en coordonnées GPS via Nominatim (OpenStreetMap)
     * 
     * @param string $adresse  Ex: "Avenue Habib Bourguiba, Tunis"
     * @return array|null      ['lat' => 36.819, 'lng' => 10.165] ou null si non trouvé
     */
    public static function geocode(string $adresse): ?array
    {
        if (empty(trim($adresse))) {
            return null;
        }

        // Nominatim API — gratuit, pas de clé API requise
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q'              => $adresse,
            'format'         => 'json',
            'limit'          => 1,
            'addressdetails' => 0,
        ]);

        $opts = [
            'http' => [
                'method'  => 'GET',
                // Nominatim exige un User-Agent identifiable
                'header'  => "User-Agent: GoService/3.0 (contact@goservice.tn)\r\n",
                'timeout' => 5,
            ],
        ];

        $context  = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data) || !isset($data[0]['lat'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon'],
        ];
    }

    /**
     * Sauvegarde les coordonnées d'un service en base de données
     * 
     * @param int    $id_service
     * @param string $adresse
     * @return array|null  Les coordonnées sauvegardées, ou null
     */
    public static function geocodeAndSave(int $id_service, string $adresse): ?array
    {
        $coords = self::geocode($adresse);

        if ($coords === null) {
            return null;
        }

        try {
            $db  = config::getConnexion();
            $sql = "UPDATE service 
                    SET adresse   = :adresse,
                        latitude  = :lat,
                        longitude = :lng
                    WHERE id_service = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'adresse' => $adresse,
                'lat'     => $coords['lat'],
                'lng'     => $coords['lng'],
                'id'      => $id_service,
            ]);
        } catch (Exception $e) {
            // Log silencieux — ne pas bloquer l'utilisateur si le géocodage échoue
            error_log('GeocoderService::geocodeAndSave error: ' . $e->getMessage());
        }

        return $coords;
    }
}

<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../model/Service.php';
require_once __DIR__ . '/../../../service/GeocoderService.php';

$serviceController = new ServiceController();
$id_provider = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre         = trim($_POST['titre']         ?? '');
    $description   = trim($_POST['description']   ?? '');
    $prix          = trim($_POST['prix']          ?? '');
    $disponibilite = trim($_POST['disponibilite'] ?? '');
    $id_categorie  = trim($_POST['id_categorie']  ?? '');
    $adresse       = trim($_POST['adresse']       ?? '');
    $statut        = 'En attente';

    $errors = [];

    if ($titre === '' || !preg_match('/^[A-Za-zÀ-ÿ\s]{3,}$/u', $titre))
        $errors[] = "Le titre doit contenir uniquement des lettres (min. 3 caractères).";
    if ($description === '' || strlen($description) < 5)
        $errors[] = "La description doit contenir au moins 5 caractères.";
    if ($prix === '' || !is_numeric($prix) || (float)$prix <= 0)
        $errors[] = "Le prix doit être un nombre positif.";
    if ($disponibilite === '')
        $errors[] = "Veuillez choisir la disponibilité.";
    if ($id_categorie === '' || !is_numeric($id_categorie))
        $errors[] = "Veuillez choisir une catégorie valide.";

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Format non autorisé.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/../../../assets/images/services/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = uniqid('service_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/services/' . $filename;
            } else {
                $errors[] = "Erreur lors de l'upload.";
            }
        }
    } else {
        $errors[] = "Veuillez choisir une image.";
    }

    if (empty($errors)) {
        $lat = null; $lng = null;
        if (!empty($adresse)) {
            $coords = GeocoderService::geocode($adresse);
            if ($coords) { $lat = $coords['lat']; $lng = $coords['lng']; }
        }

        $service = new Service(
            $titre, $description, (float)$prix,
            $disponibilite, $statut, $image_path,
            $id_provider, (int)$id_categorie,
            $adresse ?: null, $lat, $lng
        );

        $serviceController->addService($service);
        header('Location: index.php?page=myServices&added=1');
        exit;
    } else {
        $_SESSION['service_errors'] = $errors;
        $_SESSION['old_service']    = $_POST;
        header('Location: index.php?page=addService');
        exit;
    }
}
header('Location: index.php?page=addService');
exit;
<?php
class ApplicationController {
    private $applicationModel;

    public function __construct(Application $applicationModel) {
        $this->applicationModel = $applicationModel;
    }

    public function submitApplication(array $data): int {
        return $this->applicationModel->create($data);
    }

    public function getApplicationsByOffer(int $offerId): array {
        return $this->applicationModel->findByOffer($offerId);
    }

    public function getAllApplications(): array {
        return $this->applicationModel->findAll();
    }

    public function getApplicationStats(): array {
        return $this->applicationModel->getStats();
    }

    public function updateApplicationStatus(int $id, string $status): bool {
        return $this->applicationModel->updateStatus($id, $status);
    }

    public function deleteApplication(int $id): bool {
        return $this->applicationModel->delete($id);
    }

    public function uploadCV(array $file): string {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('Aucun fichier CV téléchargé');
        }

        $uploadDir = __DIR__ . '/../assets/uploads/cv/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Format de fichier non autorisé. PDF, DOC, DOCX seulement.');
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('Fichier trop volumineux (max 5MB)');
        }

        $fileName = 'cv_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Erreur lors du téléchargement du fichier');
        }

        return 'assets/uploads/cv/' . $fileName;
    }
}

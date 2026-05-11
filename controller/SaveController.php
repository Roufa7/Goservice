<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Save.php';

class SaveController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function addSave(Save $save): bool
    {
        $sql = "INSERT INTO saved_post (id_post, id_user) VALUES (:id_post, :id_user)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_post' => $save->getIdPost(),
            'id_user' => $save->getIdUser()
        ]);
    }

    public function removeSave(int $id_post, int $id_user): bool
    {
        $sql = "DELETE FROM saved_post WHERE id_post = :id_post AND id_user = :id_user";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_post' => $id_post,
            'id_user' => $id_user
        ]);
    }

    public function isSaved(int $id_post, int $id_user): bool
    {
        $sql = "SELECT COUNT(*) FROM saved_post WHERE id_post = :id_post AND id_user = :id_user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_post' => $id_post,
            'id_user' => $id_user
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function countSaves(int $id_post): int
    {
        $sql = "SELECT COUNT(*) FROM saved_post WHERE id_post = :id_post";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_post' => $id_post]);

        return (int)$stmt->fetchColumn();
    }

    public function getSavedPostsByUser(int $id_user): array
    {
        $sql = "SELECT p.*, sp.date_saved
                FROM saved_post sp
                INNER JOIN post p ON p.id_post = sp.id_post
                WHERE sp.id_user = :id_user
                ORDER BY sp.date_saved DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_user' => $id_user]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
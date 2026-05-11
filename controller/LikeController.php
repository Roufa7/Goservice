<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Like.php';

class LikeController
{
    public function addLike(Like $like)
    {
        $sql = "INSERT INTO like_post (id_post, id_user, date_like)
                VALUES (:id_post, :id_user, :date_like)
                ON DUPLICATE KEY UPDATE date_like = VALUES(date_like)";  // Pour éviter les doublons

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id_post' => $like->getIdPost(),
            'id_user' => $like->getIdUser(),
            'date_like' => $like->getDateLike() ?? date('Y-m-d H:i:s')
        ]);
    }

    public function removeLike($id_post, $id_user)
    {
        $sql = "DELETE FROM like_post WHERE id_post = :id_post AND id_user = :id_user";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post, 'id_user' => $id_user]);
    }

    public function isLiked($id_post, $id_user): bool
    {
        $sql = "SELECT COUNT(*) FROM like_post WHERE id_post = :id_post AND id_user = :id_user";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post, 'id_user' => $id_user]);
        return $query->fetchColumn() > 0;
    }

    public function countLikes($id_post): int
    {
        $sql = "SELECT COUNT(*) FROM like_post WHERE id_post = :id_post";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post]);
        return (int)$query->fetchColumn();
    }
}
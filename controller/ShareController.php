<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Share.php';

class ShareController
{
    // =========================
    // ADD SHARE
    // =========================
    public function addShare(Share $share)
    {
        $sql = "INSERT INTO share_post
                (id_post, id_user, description_share, emoji_share, date_share)
                VALUES
                (:id_post, :id_user, :description_share, :emoji_share, :date_share)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'id_post' => $share->getIdPost(),
            'id_user' => $share->getIdUser(),
            'description_share' => $share->getDescriptionShare(),
            'emoji_share' => $share->getEmojiShare(),
            'date_share' => $share->getDateShare() ?? date('Y-m-d H:i:s')
        ]);
    }

    // =========================
    // COUNT SHARES
    // =========================
    public function countShares($id_post): int
    {
        $sql = "SELECT COUNT(*) 
                FROM share_post
                WHERE id_post = :id_post";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'id_post' => $id_post
        ]);

        return (int)$query->fetchColumn();
    }

    // =========================
    // UPDATE SHARE
    // =========================
    public function updateShare($id_share, $description_share, $emoji_share)
    {
        $sql = "UPDATE share_post
                SET description_share = :description_share,
                    emoji_share = :emoji_share
                WHERE id_share = :id_share";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        return $query->execute([
            'description_share' => $description_share,
            'emoji_share' => $emoji_share,
            'id_share' => $id_share
        ]);
    }

    // =========================
    // DELETE SHARE
    // =========================
    public function deleteShare($id_share)
    {
        $sql = "DELETE FROM share_post
                WHERE id_share = :id_share";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        return $query->execute([
            'id_share' => $id_share
        ]);
    }

    // =========================
    // GET SHARES BY POST
    // =========================
    public function getSharesByPost($id_post)
    {
        $sql = "SELECT s.*, 
                       COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                       COALESCE(pr.prenom, '') AS prenom
                FROM share_post s
                LEFT JOIN `user` u ON s.id_user = u.id_user
                LEFT JOIN profile pr ON pr.id_user = u.id_user
                WHERE s.id_post = :id_post
                ORDER BY s.date_share DESC";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'id_post' => $id_post
        ]);

        return $query->fetchAll();
    }
}
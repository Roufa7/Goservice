<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Share.php';

class ShareController
{
    public function addShare(Share $share)
    {
        $sql = "INSERT INTO share_post (id_post, id_user, date_share)
                VALUES (:id_post, :id_user, :date_share)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id_post' => $share->getIdPost(),
            'id_user' => $share->getIdUser(),
            'date_share' => $share->getDateShare() ?? date('Y-m-d H:i:s')
        ]);
    }

    public function countShares($id_post): int
    {
        $sql = "SELECT COUNT(*) FROM share_post WHERE id_post = :id_post";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post]);
        return (int)$query->fetchColumn();
    }
}
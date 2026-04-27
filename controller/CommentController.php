<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Comment.php';

class CommentController
{
    public function listCommentsByPost($id_post)
    {
        $sql = "SELECT c.*, u.nom, u.prenom
                FROM commentaire c
                LEFT JOIN users u ON c.id_user = u.id_user
                WHERE c.id_post = :id_post
                ORDER BY c.date_commentaire ASC";

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post]);
        return $query->fetchAll();
    }

    public function addComment(Comment $comment)
    {
        $sql = "INSERT INTO commentaire (contenu_commentaire, date_commentaire, id_post, id_user, id_parent_commentaire, image_commentaire, emoji_commentaire)
                VALUES (:contenu, :date, :id_post, :id_user, :id_parent, :image, :emoji)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'contenu' => $comment->getContenuCommentaire(),
            'date' => $comment->getDateCommentaire() ?? date('Y-m-d H:i:s'),
            'id_post' => $comment->getIdPost(),
            'id_user' => $comment->getIdUser(),
            'id_parent' => $comment->getIdParentCommentaire(),
            'image' => $comment->getImageCommentaire(),
            'emoji' => $comment->getEmojiCommentaire()
        ]);
    }

    public function deleteComment($id)
    {
        $sql = "DELETE FROM commentaire WHERE id_commentaire = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }

    public function getCommentById($id)
    {
        $sql = "SELECT * FROM commentaire WHERE id_commentaire = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }
}
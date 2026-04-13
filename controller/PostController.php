<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Post.php';

class PostController
{
    public function listPosts()
{
    $sql = "SELECT p.*, u.nom, u.prenom
            FROM post p
            LEFT JOIN users u ON p.id_user = u.id_user
            ORDER BY p.id_post DESC";

    $db = config::getConnexion();
    return $db->query($sql);
}

    public function addPost(Post $post)
    {
        $sql = "INSERT INTO post (titre, contenu, image, type_post, statut_post, id_user)
                VALUES (:titre, :contenu, :image, :type_post, :statut_post, :id_user)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'titre' => $post->getTitre(),
            'contenu' => $post->getContenu(),
            'image' => $post->getImage(),
            'type_post' => $post->getTypePost(),
            'statut_post' => $post->getStatutPost(),
            'id_user' => $post->getIdUser()
        ]);
    }

    public function getPostById($id)
    {
        $sql = "SELECT * FROM post WHERE id_post = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    public function deletePost($id)
    {
        $sql = "DELETE FROM post WHERE id_post = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }
}
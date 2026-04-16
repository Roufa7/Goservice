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
        $query = $db->query($sql);
        return $query->fetchAll();
    }

    public function addPost(Post $post)
    {
        $sql = "INSERT INTO post (titre, contenu, image, video, type_post, statut_post, id_user)
                VALUES (:titre, :contenu, :image, :video, :type_post, :statut_post, :id_user)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'titre' => $post->getTitre(),
            'contenu' => $post->getContenu(),
            'image' => $post->getImage(),
            'video' => $post->getVideo(),
            'type_post' => $post->getTypePost(),
            'statut_post' => $post->getStatutPost(),
            'id_user' => $post->getIdUser()
        ]);
    }

    public function updatePost(Post $post)
    {
        $db = config::getConnexion();

        $fields = [
            "titre = :titre",
            "contenu = :contenu",
            "type_post = :type_post",
            "statut_post = :statut_post"
        ];

        $params = [
            'id_post' => $post->getIdPost(),
            'titre' => $post->getTitre(),
            'contenu' => $post->getContenu(),
            'type_post' => $post->getTypePost(),
            'statut_post' => $post->getStatutPost()
        ];

        if ($post->getImage() !== null) {
            $fields[] = "image = :image";
            $params['image'] = $post->getImage();
        }

        if ($post->getVideo() !== null) {
            $fields[] = "video = :video";
            $params['video'] = $post->getVideo();
        }

        $sql = "UPDATE post SET " . implode(', ', $fields) . " WHERE id_post = :id_post";
        $query = $db->prepare($sql);
        $query->execute($params);
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
        $post = $this->getPostById($id);

        if ($post) {
            if (!empty($post['image'])) {
                $imgPath = __DIR__ . '/../' . $post['image'];
                if (file_exists($imgPath)) {
                    @unlink($imgPath);
                }
            }

            if (!empty($post['video'])) {
                $videoPath = __DIR__ . '/../' . $post['video'];
                if (file_exists($videoPath)) {
                    @unlink($videoPath);
                }
            }
        }

        $sql = "DELETE FROM post WHERE id_post = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }

    public function getTopContributors($limit = 5)
    {
        $sql = "SELECT u.id_user, u.nom, u.prenom, COUNT(p.id_post) AS total_posts
                FROM users u
                INNER JOIN post p ON u.id_user = p.id_user
                GROUP BY u.id_user, u.nom, u.prenom
                ORDER BY total_posts DESC, u.nom ASC
                LIMIT :lim";

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }
}
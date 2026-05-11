<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Post.php';

class PostController
{
    // =========================
    // LIST POSTS
    // =========================
    public function listPosts()
    {
        $sql = "SELECT p.*, 
                       COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                       COALESCE(pr.prenom, '') AS prenom,
                (SELECT COUNT(*) FROM commentaire c WHERE c.id_post = p.id_post) as comments_count,
                (SELECT COUNT(*) FROM like_post l WHERE l.id_post = p.id_post) as likes_count,
                (SELECT COUNT(*) FROM share_post s WHERE s.id_post = p.id_post) as shares_count,
                (SELECT COUNT(*) FROM report_post r WHERE r.id_post = p.id_post) as reports_count
                FROM post p
                LEFT JOIN `user` u ON p.id_user = u.id_user
                LEFT JOIN profile pr ON pr.id_user = u.id_user
                ORDER BY p.id_post DESC";

        $db = config::getConnexion();
        $query = $db->query($sql);

        return $query->fetchAll();
    }

    // =========================
    // ADD POST
    // =========================
    public function addPost(Post $post)
    {
        $sql = "INSERT INTO post 
                (titre, contenu, image, video, type_post, statut_post, id_user)
                VALUES 
                (:titre, :contenu, :image, :video, :type_post, :statut_post, :id_user)";

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

    // =========================
    // UPDATE POST
    // =========================
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

        $sql = "UPDATE post 
                SET " . implode(', ', $fields) . "
                WHERE id_post = :id_post";

        $query = $db->prepare($sql);

        $query->execute($params);
    }

    // =========================
    // GET POST BY ID
    // =========================
    public function getPostById($id)
    {
        $sql = "SELECT * FROM post WHERE id_post = :id";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->execute([
            'id' => $id
        ]);

        return $query->fetch();
    }

    // =========================
    // DELETE POST
    // =========================
    public function deletePost($id_post)
    {
        $db = config::getConnexion();

        // supprimer likes
        $sqlLike = "DELETE FROM like_post
                    WHERE id_post = :id_post";

        $queryLike = $db->prepare($sqlLike);

        $queryLike->execute([
            'id_post' => $id_post
        ]);

        // supprimer commentaires
        $sqlComment = "DELETE FROM commentaire
                       WHERE id_post = :id_post";

        $queryComment = $db->prepare($sqlComment);

        $queryComment->execute([
            'id_post' => $id_post
        ]);

        // supprimer partages
        $sqlShare = "DELETE FROM share_post
                     WHERE id_post = :id_post";

        $queryShare = $db->prepare($sqlShare);

        $queryShare->execute([
            'id_post' => $id_post
        ]);

        // supprimer signalements
        $sqlReport = "DELETE FROM report_post
                      WHERE id_post = :id_post";

        $queryReport = $db->prepare($sqlReport);

        $queryReport->execute([
            'id_post' => $id_post
        ]);

        // supprimer post
        $sqlPost = "DELETE FROM post
                    WHERE id_post = :id_post";

        $queryPost = $db->prepare($sqlPost);

        return $queryPost->execute([
            'id_post' => $id_post
        ]);
    }

    // =========================
    // SIGNAL POST
    // =========================
    public function signalPost($id_post)
    {
        $sql = "UPDATE post
                SET statut_post = 'Signalé'
                WHERE id_post = :id_post";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        return $query->execute([
            'id_post' => $id_post
        ]);
    }

    // =========================
    // COMMENTS ADMIN
    // =========================
    public function listCommentsForAdmin()
    {
        $sql = "SELECT c.*, 
                       p.titre as post_title,
                       COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                       COALESCE(pr.prenom, '') AS prenom
                FROM commentaire c
                LEFT JOIN post p ON c.id_post = p.id_post
                LEFT JOIN `user` u ON c.id_user = u.id_user
                LEFT JOIN profile pr ON pr.id_user = u.id_user
                ORDER BY c.date_commentaire DESC";

        $db = config::getConnexion();
        $query = $db->query($sql);

        return $query->fetchAll();
    }

    // =========================
    // TOP CONTRIBUTORS
    // =========================
    public function getTopContributors($limit = 5)
    {
        $sql = "SELECT u.id_user,
                       COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                       COALESCE(pr.prenom, '') AS prenom,
                       COUNT(p.id_post) AS total_posts
                FROM `user` u
                LEFT JOIN profile pr ON pr.id_user = u.id_user
                INNER JOIN post p ON u.id_user = p.id_user
                GROUP BY u.id_user, nom, prenom
                ORDER BY total_posts DESC, nom ASC
                LIMIT :lim";

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        $query->bindValue(':lim', (int)$limit, PDO::PARAM_INT);

        $query->execute();

        return $query->fetchAll();
    }
}
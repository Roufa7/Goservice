<?php

class Comment
{
    private ?int $id_commentaire;
    private string $contenu_commentaire;
    private ?string $date_commentaire;
    private int $id_post;
    private int $id_user;
    private ?int $id_parent_commentaire;
    private ?string $image_commentaire;
    private ?string $emoji_commentaire;

    public function __construct(
        ?int $id_commentaire,
        string $contenu_commentaire,
        ?string $date_commentaire,
        int $id_post,
        int $id_user,
        ?int $id_parent_commentaire = null,
        ?string $image_commentaire = null,
        ?string $emoji_commentaire = null
    ) {
        $this->id_commentaire = $id_commentaire;
        $this->contenu_commentaire = $contenu_commentaire;
        $this->date_commentaire = $date_commentaire;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->id_parent_commentaire = $id_parent_commentaire;
        $this->image_commentaire = $image_commentaire;
        $this->emoji_commentaire = $emoji_commentaire;
    }

    public function getIdCommentaire(): ?int { return $this->id_commentaire; }
    public function getContenuCommentaire(): string { return $this->contenu_commentaire; }
    public function getDateCommentaire(): ?string { return $this->date_commentaire; }
    public function getIdPost(): int { return $this->id_post; }
    public function getIdUser(): int { return $this->id_user; }
    public function getIdParentCommentaire(): ?int { return $this->id_parent_commentaire; }
    public function getImageCommentaire(): ?string { return $this->image_commentaire; }
    public function getEmojiCommentaire(): ?string { return $this->emoji_commentaire; }
}
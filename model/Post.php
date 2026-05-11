<?php

class Post
{
    private ?int $id_post;
    private string $titre;
    private string $contenu;
    private ?string $image;
    private ?string $video;
    private string $type_post;
    private string $statut_post;
    private int $id_user;

    public function __construct(
        ?int $id_post,
        string $titre,
        string $contenu,
        ?string $image,
        ?string $video,
        string $type_post,
        string $statut_post,
        int $id_user
    ) {
        $this->id_post = $id_post;
        $this->titre = $titre;
        $this->contenu = $contenu;
        $this->image = $image;
        $this->video = $video;
        $this->type_post = $type_post;
        $this->statut_post = $statut_post;
        $this->id_user = $id_user;
    }

    public function getIdPost(): ?int { return $this->id_post; }
    public function getTitre(): string { return $this->titre; }
    public function getContenu(): string { return $this->contenu; }
    public function getImage(): ?string { return $this->image; }
    public function getVideo(): ?string { return $this->video; }
    public function getTypePost(): string { return $this->type_post; }
    public function getStatutPost(): string { return $this->statut_post; }
    public function getIdUser(): int { return $this->id_user; }
}
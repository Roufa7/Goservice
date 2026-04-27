<?php

class Like
{
    private ?int $id_like;
    private int $id_post;
    private int $id_user;
    private ?string $date_like;

    public function __construct(?int $id_like, int $id_post, int $id_user, ?string $date_like = null)
    {
        $this->id_like = $id_like;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->date_like = $date_like;
    }

    public function getIdLike(): ?int { return $this->id_like; }
    public function getIdPost(): int { return $this->id_post; }
    public function getIdUser(): int { return $this->id_user; }
    public function getDateLike(): ?string { return $this->date_like; }
}
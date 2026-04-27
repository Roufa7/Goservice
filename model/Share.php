<?php

class Share
{
    private ?int $id_share;
    private int $id_post;
    private int $id_user;
    private ?string $date_share;

    public function __construct(?int $id_share, int $id_post, int $id_user, ?string $date_share = null)
    {
        $this->id_share = $id_share;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->date_share = $date_share;
    }

    public function getIdShare(): ?int { return $this->id_share; }
    public function getIdPost(): int { return $this->id_post; }
    public function getIdUser(): int { return $this->id_user; }
    public function getDateShare(): ?string { return $this->date_share; }
}
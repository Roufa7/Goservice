<?php

class Save
{
    private ?int $id_saved;
    private int $id_post;
    private int $id_user;
    private ?string $date_saved;

    public function __construct(?int $id_saved, int $id_post, int $id_user, ?string $date_saved = null)
    {
        $this->id_saved = $id_saved;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->date_saved = $date_saved;
    }

    public function getIdSaved(): ?int
    {
        return $this->id_saved;
    }

    public function getIdPost(): int
    {
        return $this->id_post;
    }

    public function getIdUser(): int
    {
        return $this->id_user;
    }

    public function getDateSaved(): ?string
    {
        return $this->date_saved;
    }

    public function setIdSaved(?int $id_saved): void
    {
        $this->id_saved = $id_saved;
    }

    public function setIdPost(int $id_post): void
    {
        $this->id_post = $id_post;
    }

    public function setIdUser(int $id_user): void
    {
        $this->id_user = $id_user;
    }

    public function setDateSaved(?string $date_saved): void
    {
        $this->date_saved = $date_saved;
    }
}
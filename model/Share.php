<?php

class Share
{
    private ?int $id_share;
    private int $id_post;
    private int $id_user;
    private ?string $description_share;
    private ?string $emoji_share;
    private ?string $date_share;

    public function __construct(
        ?int $id_share,
        int $id_post,
        int $id_user,
        ?string $description_share = null,
        ?string $emoji_share = null,
        ?string $date_share = null
    ) {
        $this->id_share = $id_share;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->description_share = $description_share;
        $this->emoji_share = $emoji_share;
        $this->date_share = $date_share;
    }

    public function getIdShare(): ?int
    {
        return $this->id_share;
    }

    public function getIdPost(): int
    {
        return $this->id_post;
    }

    public function getIdUser(): int
    {
        return $this->id_user;
    }

    public function getDescriptionShare(): ?string
    {
        return $this->description_share;
    }

    public function getEmojiShare(): ?string
    {
        return $this->emoji_share;
    }

    public function getDateShare(): ?string
    {
        return $this->date_share;
    }
}
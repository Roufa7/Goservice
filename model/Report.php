<?php

class Report
{
    private ?int $id_report;
    private int $id_post;
    private int $id_user;
    private ?string $reason;
    private ?string $date_report;

    public function __construct(?int $id_report, int $id_post, int $id_user, ?string $reason = null, ?string $date_report = null)
    {
        $this->id_report = $id_report;
        $this->id_post = $id_post;
        $this->id_user = $id_user;
        $this->reason = $reason;
        $this->date_report = $date_report;
    }

    public function getIdReport(): ?int { return $this->id_report; }
    public function getIdPost(): int { return $this->id_post; }
    public function getIdUser(): int { return $this->id_user; }
    public function getReason(): ?string { return $this->reason; }
    public function getDateReport(): ?string { return $this->date_report; }
}
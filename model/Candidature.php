<?php
if (!class_exists('Candidature')) {
class Candidature {
    private ?int $id_candidature;
    private ?DateTime $date_candidature;
    private ?string $statut;
    private ?string $experience;
    private ?string $competences;
    private ?string $cv;
    private ?string $message;
    private ?int $id_user;
    private ?int $id_offre;

    public function __construct(
        ?int $id_candidature,
        ?DateTime $date_candidature,
        ?string $statut,
        ?string $experience,
        ?string $competences,
        ?string $cv,
        ?string $message,
        ?int $id_user,
        ?int $id_offre
    ) {
        $this->id_candidature = $id_candidature;
        $this->date_candidature = $date_candidature;
        $this->statut = $statut;
        $this->experience = $experience;
        $this->competences = $competences;
        $this->cv = $cv;
        $this->message = $message;
        $this->id_user = $id_user;
        $this->id_offre = $id_offre;
    }

    public function show(): void {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Date candidature</th><th>Statut</th><th>Experience</th><th>Competences</th><th>CV</th><th>Message</th><th>ID user</th><th>ID offre</th></tr>";
        echo "<tr>";
        echo "<td>{$this->id_candidature}</td>";
        echo "<td>" . ($this->date_candidature ? $this->date_candidature->format('Y-m-d H:i:s') : '') . "</td>";
        echo "<td>{$this->statut}</td>";
        echo "<td>{$this->experience}</td>";
        echo "<td>{$this->competences}</td>";
        echo "<td>{$this->cv}</td>";
        echo "<td>{$this->message}</td>";
        echo "<td>{$this->id_user}</td>";
        echo "<td>{$this->id_offre}</td>";
        echo "</tr>";
        echo "</table>";
    }

    public function getIdCandidature(): ?int { return $this->id_candidature; }
    public function setIdCandidature(?int $id_candidature): void { $this->id_candidature = $id_candidature; }

    public function getDateCandidature(): ?DateTime { return $this->date_candidature; }
    public function setDateCandidature(?DateTime $date_candidature): void { $this->date_candidature = $date_candidature; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): void { $this->statut = $statut; }

    public function getExperience(): ?string { return $this->experience; }
    public function setExperience(?string $experience): void { $this->experience = $experience; }

    public function getCompetences(): ?string { return $this->competences; }
    public function setCompetences(?string $competences): void { $this->competences = $competences; }

    public function getCv(): ?string { return $this->cv; }
    public function setCv(?string $cv): void { $this->cv = $cv; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): void { $this->message = $message; }

    public function getIdUser(): ?int { return $this->id_user; }
    public function setIdUser(?int $id_user): void { $this->id_user = $id_user; }

    public function getIdOffre(): ?int { return $this->id_offre; }
    public function setIdOffre(?int $id_offre): void { $this->id_offre = $id_offre; }
}
}

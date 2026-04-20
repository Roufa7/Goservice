<?php
if (!class_exists('Offer')) {
class Offer {
    private ?int $id_offre;
    private ?string $titre;
    private ?string $description;
    private ?string $localisation;
    private ?DateTime $date_publication;
    private ?DateTime $date_expiration;
    private ?string $statut;
    private ?string $type_service;
    private ?int $id_admin;
    private ?float $prix;

    public const TYPE_SERVICE_OPTIONS = [
        'cuisine',
        'juridique',
        'plomberie',
        'design',
        'nettoyage',
        'evenementiel',
    ];

    public function __construct(
        ?int $id_offre = null,
        ?string $titre = null,
        ?string $description = null,
        ?string $localisation = null,
        ?DateTime $date_publication = null,
        ?DateTime $date_expiration = null,
        ?string $statut = null,
        ?string $type_service = null,
        ?int $id_admin = null,
        ?float $prix = null
    ) {
        $this->id_offre = $id_offre;
        $this->titre = $titre;
        $this->description = $description;
        $this->localisation = $localisation;
        $this->date_publication = $date_publication;
        $this->date_expiration = $date_expiration;
        $this->statut = $statut;
        $this->type_service = $type_service;
        $this->id_admin = $id_admin;
        $this->prix = $prix;
    }

    public static function getTypeServiceOptions(): array {
        return self::TYPE_SERVICE_OPTIONS;
    }

    public function show(): void {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Titre</th><th>Description</th><th>Localisation</th><th>Date publication</th><th>Date expiration</th><th>Statut</th><th>Type service</th><th>Prix</th><th>ID admin</th></tr>";
        echo "<tr>";
        echo "<td>{$this->id_offre}</td>";
        echo "<td>{$this->titre}</td>";
        echo "<td>{$this->description}</td>";
        echo "<td>{$this->localisation}</td>";
        echo "<td>" . ($this->date_publication ? $this->date_publication->format('Y-m-d H:i:s') : '') . "</td>";
        echo "<td>" . ($this->date_expiration ? $this->date_expiration->format('Y-m-d H:i:s') : '') . "</td>";
        echo "<td>{$this->statut}</td>";
        echo "<td>{$this->type_service}</td>";
        echo "<td>{$this->prix}</td>";
        echo "<td>{$this->id_admin}</td>";
        echo "</tr>";
        echo "</table>";
    }

    public function getIdOffre(): ?int {
        return $this->id_offre;
    }


    public function setIdOffre(?int $id_offre): void {
        $this->id_offre = $id_offre;

    public function findActive(): array { //récupère uniquement les offres ouvertes
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    o.id AS id_offre,
                    o.titre,
                    o.description,
                    NULL AS localisation,
                    COALESCE(o.created_at, o.date_debut) AS date_publication,
                    o.date_fin AS date_expiration,
                    CASE
                        WHEN o.statut = "active" THEN "ouverte"
                        WHEN o.statut = "inactive" THEN "fermee"
                        ELSE "fermee"
                    END AS statut,
                    LOWER(COALESCE(s.categorie, "cuisine")) AS type_service,
                    o.prix,
                    o.creator_id AS id_admin,
                    u.nom AS admin_nom,
                    u.prenom AS admin_prenom
                 FROM offers o
                 LEFT JOIN services s ON o.service_id = s.id
                 LEFT JOIN users u ON o.creator_id = u.' . $this->userPk . '
                 WHERE o.statut = :statut
                 ORDER BY COALESCE(o.created_at, o.date_debut) DESC'
            );
            $stmt->execute(['statut' => 'active']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom AS admin_nom, u.prenom AS admin_prenom
             FROM offre o
             LEFT JOIN users u ON o.id_admin = u.id_user
             WHERE o.statut = :statut
             ORDER BY o.date_publication DESC'
        );
        $stmt->execute(['statut' => 'ouverte']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    }

    public function getTitre(): ?string {
        return $this->titre;
    }

    public function setTitre(?string $titre): void {
        $this->titre = $titre;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function getLocalisation(): ?string {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): void {
        $this->localisation = $localisation;
    }

    public function getDatePublication(): ?DateTime {
        return $this->date_publication;
    }

    public function setDatePublication(?DateTime $date_publication): void {
        $this->date_publication = $date_publication;
    }

    public function getDateExpiration(): ?DateTime {
        return $this->date_expiration;
    }

    public function setDateExpiration(?DateTime $date_expiration): void {
        $this->date_expiration = $date_expiration;
    }

    public function getStatut(): ?string {
        return $this->statut;
    }

    public function setStatut(?string $statut): void {
        $this->statut = $statut;
    }

    public function getTypeService(): ?string {
        return $this->type_service;
    }

    public function setTypeService(?string $type_service): void {
        $this->type_service = $type_service;
    }

    public function getIdAdmin(): ?int {
        return $this->id_admin;
    }

    public function setIdAdmin(?int $id_admin): void {
        $this->id_admin = $id_admin;
    }

    public function getPrix(): ?float {
        return $this->prix;
    }

    public function setPrix(?float $prix): void {
        $this->prix = $prix;
    }
}
}
}

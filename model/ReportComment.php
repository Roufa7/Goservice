<?php
class ReportComment {
    private ?int $id_report_comment;
    private int $id_commentaire;
    private int $id_user;
    private ?string $reason;
    private ?string $date_report;

    public function __construct($id=null, $id_commentaire, $id_user, $reason=null, $date=null) {
        $this->id_report_comment = $id;
        $this->id_commentaire = $id_commentaire;
        $this->id_user = $id_user;
        $this->reason = $reason;
        $this->date_report = $date;
    }

    public function getIdCommentaire(){ return $this->id_commentaire; }
    public function getIdUser(){ return $this->id_user; }
    public function getReason(){ return $this->reason; }
}
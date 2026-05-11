<?php
require_once '../../model/ReportComment.php';
require_once '../../config.php';

class ReportCommentController {

    public function addReport($report) {
        $db = config::getConnexion();

        $sql = "INSERT INTO report_comment (id_commentaire, id_user, reason, date_report)
                VALUES (:id_commentaire, :id_user, :reason, NOW())";

        $query = $db->prepare($sql);
        $query->execute([
            'id_commentaire' => $report->getIdCommentaire(),
            'id_user' => $report->getIdUser(),
            'reason' => $report->getReason()
        ]);
    }

    public function getReportsByComment($id_commentaire) {
        $db = config::getConnexion();

        $sql = "SELECT * FROM report_comment WHERE id_commentaire = :id";
        $query = $db->prepare($sql);
        $query->execute(['id'=>$id_commentaire]);

        return $query->fetchAll();
    }
}
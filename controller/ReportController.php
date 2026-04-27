<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Report.php';

class ReportController
{
    public function addReport(Report $report)
    {
        $sql = "INSERT INTO report_post (id_post, id_user, reason, date_report)
                VALUES (:id_post, :id_user, :reason, :date_report)";

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id_post' => $report->getIdPost(),
            'id_user' => $report->getIdUser(),
            'reason' => $report->getReason(),
            'date_report' => $report->getDateReport() ?? date('Y-m-d H:i:s')
        ]);
    }

    public function countReports($id_post): int
    {
        $sql = "SELECT COUNT(*) FROM report_post WHERE id_post = :id_post";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id_post' => $id_post]);
        return (int)$query->fetchColumn();
    }
}
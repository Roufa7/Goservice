<?php
/**
 * Export PDF — Liste des services
 * Page autonome (pas de layout back office), appelée directement :
 * index.php?page=exportServicesPdf
 * ou en standalone : view/back/pages/exportServicesPdf.php
 */
require_once __DIR__ . '/../../../controller/ServiceController.php';

$controller = new ServiceController();
$services   = $controller->listServicesWithCategories();

// Filtre optionnel par statut passé en GET
$filtreStatut = trim($_GET['statut'] ?? '');
if ($filtreStatut !== '') {
    $services = array_filter($services, fn($s) => trim($s['statut'] ?? '') === $filtreStatut);
    $services = array_values($services);
}

$total       = count($services);
$dateGenere  = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des services — GoService</title>
<style>
  /* ── Reset ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    background: #fff;
    padding: 36px 40px;
  }

  /* ── Header ── */
  .pdf-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
  }

  .pdf-logo {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .pdf-title {
    text-align: center;
    flex: 1;
  }

  .pdf-title h1 {
    font-size: 28px;
    font-weight: 900;
    color: #ee5828;
    letter-spacing: -0.5px;
  }

  .pdf-meta {
    text-align: right;
    font-size: 12px;
    color: #666;
    margin-bottom: 22px;
    margin-top: 8px;
  }

  .pdf-divider {
    height: 2px;
    background: linear-gradient(to right, #ee5828, #1a1a2e);
    border-radius: 2px;
    margin-bottom: 22px;
  }

  /* ── Stats résumé ── */
  .pdf-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 22px;
  }
  .pdf-stat {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px 20px;
    text-align: center;
  }
  .pdf-stat strong { display: block; font-size: 20px; color: #ee5828; font-weight: 900; }
  .pdf-stat span   { font-size: 11px; color: #888; }

  /* ── Tableau ── */
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
  }

  thead tr {
    background: #1a1a2e;
  }

  thead th {
    color: #fff;
    padding: 11px 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  tbody tr:nth-child(even) { background: #f7f7f9; }
  tbody tr:nth-child(odd)  { background: #ffffff; }
  tbody tr:hover           { background: #fff3ef; }

  tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #ececec;
    font-size: 12.5px;
    vertical-align: middle;
  }

  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
  }
  .b-dispo   { background: #e8f5e9; color: #2e7d32; }
  .b-indispo { background: #fce4ec; color: #c62828; }
  .b-valide  { background: #e3f2fd; color: #1565c0; }
  .b-attente { background: #fff8e1; color: #e65100; }
  .b-autre   { background: #f5f5f5; color: #555; }

  .prix { font-weight: 700; color: #ee5828; }

  /* ── Footer ── */
  .pdf-footer {
    margin-top: 28px;
    text-align: center;
    font-size: 11px;
    color: #aaa;
    border-top: 1px solid #eee;
    padding-top: 12px;
  }

  /* ── Bouton imprimer (masqué à l'impression) ── */
  .btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ee5828;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 24px;
    font-family: inherit;
    text-decoration: none;
  }
  .btn-print:hover { background: #d44a1e; }

  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 24px;
    font-family: inherit;
    text-decoration: none;
  }
  .btn-back:hover { background: #2d2d4e; }

  /* ── Print ── */
  @media print {
    .btn-print, .btn-back, .no-print { display: none !important; }
    body { padding: 20px 24px; }
    tbody tr:hover { background: inherit; }
    @page { margin: 1.5cm; size: A4 landscape; }
  }
</style>
</head>
<body>

<!-- Bouton imprimer (disparaît à l'impression) -->
<div class="no-print" style="margin-bottom:16px;display:flex;align-items:center;gap:14px;">
  <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer PDF</button>
  <a href="index.php?page=services" class="btn-back">← Retour aux services</a>
</div>

<!-- Header -->
<div class="pdf-header">
  <div class="pdf-logo">
    <img src="../../assets/images/logo-pdf.png"
         alt="GoService"
         style="height:54px; width:auto; object-fit:contain; display:block;">
  </div>

  <div class="pdf-title">
    <h1>Liste des services</h1>
  </div>
  <div style="width:160px;"></div>
</div>

<div class="pdf-meta">Généré le : <?php echo $dateGenere; ?><?php if ($filtreStatut): ?> &nbsp;|&nbsp; Filtre : <?php echo htmlspecialchars($filtreStatut); ?><?php endif; ?></div>
<div class="pdf-divider"></div>

<!-- Stats -->
<?php
$nbDispo    = count(array_filter($services, fn($s) => trim($s['disponibilite'] ?? '') === 'Disponible'));
$nbValides  = count(array_filter($services, fn($s) => trim($s['statut'] ?? '') === 'Validé'));
$nbAttente  = count(array_filter($services, fn($s) => trim($s['statut'] ?? '') === 'En attente'));
$cats       = array_unique(array_filter(array_map(fn($s) => $s['nom_categorie'] ?? '', $services)));
?>
<div class="pdf-stats">
  <div class="pdf-stat"><strong><?php echo $total; ?></strong><span>Services</span></div>
  <div class="pdf-stat"><strong><?php echo count($cats); ?></strong><span>Catégories</span></div>
  <div class="pdf-stat"><strong><?php echo $nbDispo; ?></strong><span>Disponibles</span></div>
  <div class="pdf-stat"><strong><?php echo $nbValides; ?></strong><span>Validés</span></div>
  <div class="pdf-stat"><strong><?php echo $nbAttente; ?></strong><span>En attente</span></div>
</div>

<!-- Tableau -->
<table>
  <thead>
    <tr>
      <th style="width:40px;">ID</th>
      <th>Titre</th>
      <th>Catégorie</th>
      <th style="width:90px;">Prix</th>
      <th style="width:115px;">Disponibilité</th>
      <th style="width:105px;">Statut</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($services)): ?>
      <?php foreach ($services as $s): ?>
        <?php
        $dispo  = trim($s['disponibilite'] ?? '');
        $statut = trim($s['statut'] ?? '');
        $dispoBadge  = $dispo  === 'Disponible'  ? 'b-dispo'   : 'b-indispo';
        $statutBadge = $statut === 'Validé'       ? 'b-valide'  : ($statut === 'En attente' ? 'b-attente' : 'b-autre');
        ?>
        <tr>
          <td><?php echo (int)$s['id_service']; ?></td>
          <td><strong><?php echo htmlspecialchars($s['titre'] ?? ''); ?></strong></td>
          <td><?php echo htmlspecialchars($s['nom_categorie'] ?? ''); ?></td>
          <td class="prix"><?php echo number_format((float)($s['prix'] ?? 0), 2, ',', ' '); ?> €</td>
          <td><span class="badge <?php echo $dispoBadge; ?>"><?php echo htmlspecialchars($dispo); ?></span></td>
          <td><span class="badge <?php echo $statutBadge; ?>"><?php echo htmlspecialchars($statut); ?></span></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="6" style="text-align:center;padding:20px;color:#aaa;">Aucun service trouvé.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="pdf-footer">
  GoService — Document généré automatiquement le <?php echo $dateGenere; ?> &nbsp;|&nbsp; <?php echo $total; ?> service(s) au total
</div>

<script>
// Auto-print si on arrive via le bouton export
if (window.location.search.includes('autoprint=1')) {
    window.addEventListener('load', function () { window.print(); });
}
</script>
</body>
</html>

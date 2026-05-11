<?php
/**
 * Export PDF — Liste des catégories
 * Page autonome appelée directement.
 */
require_once __DIR__ . '/../../../controller/CategorieController.php';

$controller = new CategorieController();
$categories = $controller->listCategoriesWithCount();

$total      = count($categories);
$dateGenere = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des catégories — GoService</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    background: #fff;
    padding: 36px 40px;
  }

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

  .pdf-title { text-align: center; flex: 1; }
  .pdf-title h1 { font-size: 28px; font-weight: 900; color: #ee5828; }

  .pdf-meta {
    text-align: right; font-size: 12px; color: #666;
    margin-bottom: 22px; margin-top: 8px;
  }

  .pdf-divider {
    height: 2px;
    background: linear-gradient(to right, #ee5828, #1a1a2e);
    border-radius: 2px; margin-bottom: 22px;
  }

  .pdf-stats { display: flex; gap: 16px; margin-bottom: 22px; }
  .pdf-stat {
    border: 1px solid #e0e0e0; border-radius: 10px;
    padding: 10px 28px; text-align: center;
  }
  .pdf-stat strong { display: block; font-size: 22px; color: #ee5828; font-weight: 900; }
  .pdf-stat span   { font-size: 11px; color: #888; }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #1a1a2e; }
  thead th {
    color: #fff; padding: 11px 14px; text-align: left;
    font-size: 12px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
  }
  tbody tr:nth-child(even) { background: #f7f7f9; }
  tbody tr:nth-child(odd)  { background: #ffffff; }
  tbody td {
    padding: 11px 14px; border-bottom: 1px solid #ececec;
    font-size: 12.5px; vertical-align: middle;
  }

  .icone-cell { font-size: 20px; text-align: center; }

  .nb-badge {
    display: inline-block; padding: 3px 12px; border-radius: 99px;
    background: #fff3ef; color: #ee5828; font-weight: 700;
    font-size: 12px; border: 1px solid #f8cfc4;
  }

  .pdf-footer {
    margin-top: 28px; text-align: center; font-size: 11px;
    color: #aaa; border-top: 1px solid #eee; padding-top: 12px;
  }

  @media print {
    .btn-print, .btn-back, .no-print { display: none !important; }
    body { padding: 20px 24px; }
    @page { margin: 1.5cm; size: A4; }
  }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;display:flex;align-items:center;gap:14px;">
  <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer PDF</button>
  <a href="index.php?page=categories" class="btn-back">← Retour aux catégories</a>
</div>

<!-- Header -->
<div class="pdf-header">
  <div class="pdf-logo">
    <img src="../../assets/images/logo-pdf.png"
         alt="GoService"
         style="height:54px; width:auto; object-fit:contain; display:block;">
  </div>

  <div class="pdf-title">
    <h1>Liste des catégories</h1>
  </div>
  <div style="width:160px;"></div>
</div>

<div class="pdf-meta">Généré le : <?php echo $dateGenere; ?></div>
<div class="pdf-divider"></div>

<!-- Stats -->
<?php $totalServices = array_sum(array_column($categories, 'nb_services')); ?>
<div class="pdf-stats">
  <div class="pdf-stat"><strong><?php echo $total; ?></strong><span>Catégories</span></div>
  <div class="pdf-stat"><strong><?php echo $totalServices; ?></strong><span>Services liés</span></div>
  <div class="pdf-stat"><strong><?php echo $total > 0 ? round($totalServices / $total, 1) : 0; ?></strong><span>Moy. services/cat.</span></div>
</div>

<!-- Tableau -->
<table>
  <thead>
    <tr>
      <th style="width:40px;">ID</th>
      <th style="width:50px;text-align:center;">Icône</th>
      <th>Nom</th>
      <th>Description</th>
      <th style="width:110px;text-align:center;">Services liés</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?php echo (int)$cat['id_categorie']; ?></td>
          <td class="icone-cell"><?php echo htmlspecialchars($cat['icone'] ?? '🗂️'); ?></td>
          <td><strong><?php echo htmlspecialchars($cat['nom']); ?></strong></td>
          <td style="color:#555;"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
          <td style="text-align:center;">
            <span class="nb-badge"><?php echo (int)$cat['nb_services']; ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="5" style="text-align:center;padding:20px;color:#aaa;">Aucune catégorie trouvée.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="pdf-footer">
  GoService — Document généré automatiquement le <?php echo $dateGenere; ?> &nbsp;|&nbsp; <?php echo $total; ?> catégorie(s) au total
</div>

<script>
if (window.location.search.includes('autoprint=1')) {
    window.addEventListener('load', function () { window.print(); });
}
</script>
</body>
</html>

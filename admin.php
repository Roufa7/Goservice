<?php
// GoService - Accueil rapide
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoService - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        .card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .card a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .card a:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }
        .emoji {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        .status {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            color: #2e7d32;
        }
        .status strong {
            color: #1b5e20;
        }
    </style>
</head>
<body>
    <div class="status">
        <strong>✅ Configuration complète !</strong> Tous les services sont actifs et prêts.
    </div>

    <div class="header">
        <h1>🚀 GoService</h1>
        <p>Plateforme de services - Centre de contrôle</p>
    </div>

    <div class="grid">
        <div class="card">
            <div class="emoji">💻</div>
            <h2>Application Web</h2>
            <p>Accédez à l'interface principale de GoService pour voir l'application en action.</p>
            <a href="http://127.0.0.1:8000/view/front/index.php?page=home" target="_blank">Ouvrir l'app</a>
        </div>

        <div class="card">
            <div class="emoji">🔐</div>
            <h2>Admin Panel</h2>
            <p>Accédez au tableau de bord administrateur pour gérer les utilisateurs et contenus.</p>
            <a href="http://127.0.0.1:8000/view/back/index.php?page=dashboard" target="_blank">Aller au panel</a>
        </div>

        <div class="card">
            <div class="emoji">💾</div>
            <h2>Base de données</h2>
            <p>Gérez les données avec phpMyAdmin. Vérifiez les tables, utilisateurs et services.</p>
            <a href="http://localhost:8888/phpMyAdmin5/index.php?db=goservice" target="_blank">Ouvrir phpMyAdmin</a>
        </div>

        <div class="card">
            <div class="emoji">👥</div>
            <h2>Comptes de test</h2>
            <p>
                <strong>Admin :</strong> emna@goservice.com / 123456<br>
                <strong>Provider :</strong> amina@goservice.com / 123456<br>
                <strong>User :</strong> roufa@goservice.com / 123456
            </p>
        </div>

        <div class="card">
            <div class="emoji">📊</div>
            <h2>Statistiques BD</h2>
            <p>
                8 tables créées<br>
                8 utilisateurs en test<br>
                5 services disponibles<br>
                3 événements programmés
            </p>
        </div>

        <div class="card">
            <div class="emoji">📖</div>
            <h2>Documentation</h2>
            <p>Consultez le guide de configuration et les bonnes pratiques développement.</p>
            <a href="./README.md" target="_blank">Lire la doc</a>
        </div>
    </div>
</body>
</html>

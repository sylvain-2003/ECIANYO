<?php
require_once 'connexion.php';

// Traitement de l'ajout d'une opération
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $type = $_POST['type'];
    $montant = floatval($_POST['montant']);
    $libelle = trim($_POST['libelle']);
    $date_operation = $_POST['date_operation'];

    if (!empty($type) && $montant > 0 && !empty($date_operation)) {
        $stmt = $pdo->prepare("INSERT INTO transactions (type, montant, libelle, date_operation) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $montant, $libelle, $date_operation]);
        header("Location: index.php");
        exit();
    }
}

// Récupération des filtres de date
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Construction de la requête SQL avec filtres optionnels
$sql = "SELECT * FROM transactions WHERE 1=1";
$params = [];

if (!empty($date_debut)) {
    $sql .= " AND date_operation >= ?";
    $params[] = $date_debut;
}
if (!empty($date_fin)) {
    $sql .= " AND date_operation <= ?";
    $params[] = $date_fin;
}
$sql .= " ORDER BY date_operation DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques globales / filtrées
$capital_initial = 0;
$total_recettes = 0;
$total_depenses = 0;

// Pour le capital initial, on peut soit prendre tout le cumul, soit le filtrer. 
// Ici, on calcule les totaux en fonction des données affichées ou globales selon vos préférences.
foreach ($transactions as $t) {
    if ($t['type'] === 'capital') {
        $capital_initial += $t['montant'];
    } elseif ($t['type'] === 'recette') {
        $total_recettes += $t['montant'];
    } elseif ($t['type'] === 'depense') {
        $total_depenses += $t['montant'];
    }
}
$solde_actuel = ($capital_initial + $total_recettes) - $total_depenses;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Frigo ECIANYO - Gestion du projet</title>
    <style>
        :root {
            --primary: #800020; /* Bordeaux */
            --secondary: #1a252f; /* Bleu marine */
            --light: #f8f9fa;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .card {
            background: #fff;
            border-left: 5px solid var(--primary);
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 4px;
        }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .card p { margin: 0; font-size: 20px; font-weight: bold; color: var(--secondary); }
        form {
            background: #fdfdfd;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }
        button:hover { background-color: #600018; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th { background-color: var(--secondary); color: white; }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }
        .badge-capital { background-color: #2980b9; }
        .badge-recette { background-color: #27ae60; }
        .badge-depense { background-color: #c0392b; }
    </style>
</head>
<body>

<div class="container">
    <h1>GESTION DU PROJET ECIANYO</h1>
    <h2 style="text-align: center"><b>Tableau de Bord Financier</b></h2>
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="card">
            <h3>Capital Initial</h3>
            <p><?= number_format($capital_initial, 2, ',', ' ') ?> FCFA</p>
        </div>
        <div class="card">
            <h3>Total Recettes</h3>
            <p style="color: #27ae60;"><?= number_format($total_recettes, 2, ',', ' ') ?> FCFA</p>
        </div>
        <div class="card">
            <h3>Total Dépenses</h3>
            <p style="color: #c0392b;"><?= number_format($total_depenses, 2, ',', ' ') ?> FCFA</p>
        </div>
        <div class="card" style="border-left-color: var(--secondary);">
            <h3>Solde Actuel</h3>
            <p><?= number_format($solde_actuel, 2, ',', ' ') ?> FCFA</p>
        </div>
    </div>

    <!-- Filtre par date -->
    <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>Du :</label>
            <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
        </div>
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>Au :</label>
            <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
        </div>
        <div>
            <button type="submit">Filtrer</button>
            <a href="index.php" style="margin-left: 10px; text-decoration: none; color: #666;">Réinitialiser</a>
        </div>
    </form>

    <!-- Formulaire d'ajout -->
    <h2>Ajouter une opération</h2>
    <form method="POST">
        <input type="hidden" name="action" value="ajouter">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Type d'opération :</label>
                <select name="type" id="typeSelect" required onchange="verifierType()">
                    <option value="depense">Dépense</option>
                    <option value="capital">Capital</option>
                    <option value="recette">Recette</option>
                </select>
            </div>
            <div class="form-group">
                <label>Montant (FCFA) :</label>
                <input type="number" step="0.01" name="montant" required>
            </div>
            <div class="form-group">
                <label>Libellé / Description :</label>
                <input type="text" name="libelle" id="libelleInput" placeholder="Ex:Achat de machandises..." required>
            </div>
            <div class="form-group">
                <label>Date de l'opération :</label>
                <input type="date" name="date_operation" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <button type="submit">Enregistrer l'opération</button>
    </form>

    <!-- Historique des opérations -->
    <h2>Historique des Opérations</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Libellé</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($transactions) > 0): ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['date_operation']) ?></td>
                        <td>
                            <span class="badge badge-<?= $t['type'] ?>">
                                <?= ucfirst($t['type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($t['libelle']) ?></td>
                        <td><strong><?= number_format($t['montant'], 2, ',', ' ') ?> FCFA</strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #777;">Aucune opération trouvée pour cette période.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Petite fonction JavaScript optionnelle pour adapter dynamiquement le libellé si besoin
    function verifierType() {
        const type = document.getElementById('typeSelect').value;
        const libelle = document.getElementById('libelleInput');
        if (type === 'capital') {
            libelle.value = 'Capital initial du projet';
        } else if (libelle.value === 'Capital initial du projet') {
            libelle.value = '';
        }
    }
</script>

</body>
</html>
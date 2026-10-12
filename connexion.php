<?php
$host = 'localhost';
$dbname = 'frigo_ecianyo';
$username = 'root'; // Remplacez par votre utilisateur
$password = ''; // Remplacez par votre mot de passe

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Création de la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // Création de la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('capital', 'recette', 'depense') NOT NULL,
        montant DECIMAL(10, 2) NOT NULL,
        libelle VARCHAR(255) NOT NULL,
        date_operation DATE NOT NULL
    )");
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
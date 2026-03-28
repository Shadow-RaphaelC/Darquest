<?php
require_once 'include/session.php';
require_once 'BD/bd.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Inventaire</title>
</head>
<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Inventaire</h1>
        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <p class="locked-message">Accès réservé aux utilisateurs connectés. <a href="index.php">Retour à l'accueil</a></p>
            <p><a class="btnAutre" href="index.php">Accueil</a></p>
        <?php else: ?>
            <div class="btnBox" style="justify-content: space-evenly; padding-left: 100px; padding-right: 100px;">
                <a class="btnAutre" href="magasin.php">Magasin</a>
                <a class="btnAutre" href="panier.php">Panier</a>
                <a class="btnAutre" href="index.php">Accueil</a>
            </div>
            <p>Contenu de l'inventaire à venir.</p>
        <?php endif; ?>
    </main>
    <?php require 'include/footer.php'; ?>
</body>
</html>
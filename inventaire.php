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
        <?php
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            echo '<p class="locked-message">Accès réservé aux utilisateurs connectés. <a href="index.php">Retour à l\'accueil</a></p>';
            echo '<p><a class="btnAutre" href="index.php">Accueil</a></p>';
            require_once 'include/footer.php';
            exit;
        }
        ?>
        <p>Voici votre inventaire.</p>
        <div class="btnBox" style="justify-content: space-evenly; padding-left: 100px; padding-right: 100px;">
            <a class="btnAutre" href="magasin.php">Magasin</a>
            <a class="btnAutre" href="panier.php">Panier</a>
            <a class="btnAutre" href="index.php">Accueil</a>
        </div>
        <?php

        ?>

        <div class="item-grid-3">
            <?php foreach ($products as $p) :
                render_item_card(
                    $p[0] ?? null,
                    $p[1] ?? '',
                    $p[2] ?? 0,
                    $p[3] ?? '',
                    isset($p[4]) ? $p[4] : 0,
                    $p[5] ?? '',
                    $p[6] ?? true
                );
            endforeach;
            ?>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
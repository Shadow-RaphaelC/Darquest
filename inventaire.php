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
        require_once 'item.php';

        $inventory = [
            [101, 'Casque du dragon', 'Réduit les dégâts subis de 8%', '95', 'img/placeholder.webp'],
            [102, 'Bottes du vent', 'Augmente la vitesse de déplacement', '75', 'img/placeholder.webp'],
            [103, 'Gantelets runiques', '+20 force', '80', 'img/placeholder.webp'],
            [104, 'Potion Zen', 'Restaure 150 PV', '25', 'img/placeholder.webp'],
            [105, 'Bague de vie', '+50 PV', '60', 'img/placeholder.webp'],
            [106, 'Bouclier éthéré', '+55 défense', '115', 'img/placeholder.webp'],
            [107, 'Corde d\'arc', 'Convocation rapide', '65', 'img/placeholder.webp'],
            [108, 'Plastron du conquérant', '+90 défense', '145', 'img/placeholder.webp'],
            [109, 'Talisman mystique', '+30 magie', '88', 'img/placeholder.webp'],
        ];
        ?>

        <div class="item-grid-3">
            <?php foreach ($inventory as $item) :
                render_item_card($item[0], $item[1], $item[2], $item[3] . ' gold', $item[4]);
            endforeach;
            ?>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
<?php
require_once 'BD/bd.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Magasin</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Magasin</h1>
        <p>Bienvenue dans le magasin.</p>

        <?php
        require_once 'item.php';

        $products = [
            [1, 'Overlord\'s bloodmail', 'Armure mythique, +120 vie', '120', 'img/placeholder.webp'],
            [2, 'Bottes du vent', 'Augmente la vitesse de déplacement', '75', 'img/placeholder.webp'],
            [3, 'Arc de l\'aube', '+40 de dégâts à distance', '110', 'img/placeholder.webp'],
            [4, 'Casque du dragon', 'Réduit les dégâts subis de 8%', '95', 'img/placeholder.webp'],
            [5, 'Gantelets runiques', '+20 force', '80', 'img/placeholder.webp'],
            [6, 'Cape spectrale', 'Immunité aux effets de peur', '130', 'img/placeholder.webp'],
            [7, 'Bague de vie', '+50 PV', '60', 'img/placeholder.webp'],
            [8, 'Potion Zen', 'Restaure 150 PV', '25', 'img/placeholder.webp'],
            [9, 'Sceptre du chaos', '+70 magie', '140', 'img/placeholder.webp'],
            [10, 'Grelot de l\'ombre', '+25 invisibilité', '90', 'img/placeholder.webp'],
            [11, 'Pierre d\'énergie', 'Recharge les sorts 30% plus vite', '105', 'img/placeholder.webp'],
            [12, 'Bouclier éthéré', '+55 défense', '115', 'img/placeholder.webp'],
        ];
        ?>

        <div class="item-grid-4">
            <?php foreach ($products as $p) :
                render_item_card($p[0], $p[1], $p[2], $p[3] . ' gold', $p[4]);
            endforeach;
            ?>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
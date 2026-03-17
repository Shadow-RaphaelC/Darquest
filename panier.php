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
    <title>DarQuest Panier</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Panier</h1>
        <?php
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            echo '<p class="locked-message">Accès réservé aux utilisateurs connectés. <a href="index.php">Retour à l\'accueil</a></p>';
            echo '<p><a class="btnAutre" href="index.php">Accueil</a></p>';
            require_once 'include/footer.php';
            exit;
        }
        ?>
        <div class="btnBox" style="justify-content: space-evenly; padding-left: 100px; padding-right: 100px;">
            <a class="btnAutre" href="magasin.php">Magasin</a>
            <a class="btnAutre" href="inventaire.php">Inventaire</a>
            <a class="btnAutre" href="index.php">Accueil</a>
        </div>
        <?php
        require_once 'item.php';

        $cart_items = [
            [201, 'Overlord\'s bloodmail', 'Armure mythique', 120.00, 'img/placeholder.webp'],
            [202, 'Arc de l\'aube', 'Dégâts à distance +40', 110.00, 'img/placeholder.webp'],
        ];

        $subtotal = 0;
        foreach ($cart_items as $ci) {
            $subtotal += $ci[3];
        }

        $total = $subtotal;
        ?>

        <div class="panier-layout">
            <div class="panier-items">
                <?php foreach ($cart_items as $ci) :
                    render_item_card($ci[0], $ci[1], $ci[2], number_format($ci[3], 2) . ' gold', $ci[4]);
                endforeach;
                ?>
            </div>
            <div class="panier-recap">
                <h2>Récapitulatif du panier</h2>
                <p>Articles: <?php echo count($cart_items); ?></p>
                <p>Sous-total: <?php echo number_format($subtotal, 2) . ' gold'; ?></p>
                <p><strong>Total: <?php echo number_format($total, 2) . ' gold'; ?></strong></p>
                <p>Livraison estimée:
                    <select>
                        <option>Standard</option>
                        <option>Express</option>
                    </select>
                </p>
            </div>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>

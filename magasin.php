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
    <title>DarQuest Magasin</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Magasin</h1>
        <p>Bienvenue dans le magasin.</p>

        <?php
        $products = AfficherItems();

        if (!is_array($products)) {
            $products = [];
        }
        ?>

        <div class="item-grid-4">
            <?php foreach ($products as $p) :
                $id = $p[0] ?? null;
                $nom = $p[1] ?? '';
                $quantity = $p[2] ?? 0;
                $typeItem = $p[3] ?? '';
                $price = $p[4] ?? 0;
                $image = $p[5] ?? '';
                $isDisponible = $p[6] ?? true;

                // If quantity looks like price (e.g. 000000...19), prefer actual numeric parse.
                $quantity = (int)$quantity;
                $price = (int)$price;

                render_item_card($id, $nom, $quantity, $typeItem, $price, $image, $isDisponible);
            endforeach;
            ?>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
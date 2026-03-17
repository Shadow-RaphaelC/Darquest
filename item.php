<?php
function render_item_card($id, $title, $description, $price, $image = 'img/placeholder.webp') {
    echo "<div class=\"itemBox\">\n";
    echo "  <img src=\"$image\" alt=\"Product Image\" class=\"item-img\">\n";
    echo "  <div class=\"item-info\">\n";
    echo "    <h2 class=\"prixOr\">$price</h2>\n";
    echo "    <h3 class=\"titre\">$title</h3>\n";
    echo "    <p class=\"description\">$description</p>\n";
    echo "    <div class=\"btnPanier\">\n";
    echo "      <a class=\"btnPanierBtn\" href=\"panier.php?action=add&id=$id\">Ajouter au panier</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
}

if (basename($_SERVER['SCRIPT_NAME']) === 'item.php') {
    require_once 'BD/bd.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Item component test</title>
        <link rel="stylesheet" href="css/styles_dark.css">
    </head>
    <body>
    <?php require 'include/header.php'; ?>
    <div class="item-grid-1">
        <?php render_item_card(1, 'Overlord\'s bloodmail', 'Armure mythique, +120 vie', '$120', 'img/placeholder.webp'); ?>
    </div>
    <?php require 'include/footer.php'; ?>
    </body>
    </html>
    <?php
}

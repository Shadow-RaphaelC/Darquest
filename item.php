<?php

function render_item_card($id, $title, $description, $price, $image = 'img/placeholder.webp') {
    echo "<div class=\"itemBox\">\n";
    echo "  <img src=\"" . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . "\" alt=\"Product Image\" class=\"item-img\">\n";
    echo "  <div class=\"item-info\">\n";
    echo "    <h2 class=\"prixOr\">" . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . "</h2>\n";
    echo "    <h3 class=\"titre\">" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h3>\n";
    echo "    <p class=\"description\">" . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "</p>\n";
    echo "    <div class=\"btnPanier\">\n";
    echo "      <a class=\"btnPanierBtn\" href=\"panier.php?action=add&id=" . intval($id) . "\">Ajouter au panier</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
}

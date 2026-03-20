<?php

// -------------------------
// Database connection
// -------------------------
function get_pdo(): PDO|false
{
    $host = '158.69.48.109';
    $db = 'dbdarquest5';
    $user = 'equipe5';
    $pass = 'd8kv94h6';
    $charset = 'utf8';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        error_log('get_pdo error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Afficher Items
// -------------------------
function AfficherItems(): array
{
    $pdo = get_pdo();
    if ($pdo === false) {
        return [];
    }

    try {
        $stmt = $pdo->prepare('CALL AfficherItems()');
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        while ($stmt->nextRowset()){ }

        return $rows;
    } catch (PDOException $e) {
        error_log('AfficherItems error: ' . $e->getMessage());
        return [];
    }
}

function render_item_card($id, $nom, $quantity, $typeItem, $price, $image, $isDisponible) {
    $isDisponibleNormalized = filter_var($isDisponible, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($isDisponibleNormalized === false) {
        return;
    }

    $typeItemCode = strtoupper(trim((string)$typeItem));
    if ($typeItemCode === 'A' || $typeItemCode === 'ARME') {
        $typeLabel = 'Arme';
    } elseif ($typeItemCode === 'R' || $typeItemCode === 'ARMURE') {
        $typeLabel = 'Armure';
    } elseif ($typeItemCode === 'P' || $typeItemCode === 'POTION') {
        $typeLabel = 'Potion';
    } elseif ($typeItemCode === 'S' || $typeItemCode === 'SORT') {
        $typeLabel = 'Sort';
    } elseif ($typeItemCode !== '') {
        $typeLabel = ucfirst(strtolower($typeItemCode));
    } else {
        $typeLabel = 'Autre';
    }

    $quantityValue = (int)$quantity;
    $priceValue = (int)$price;
    $priceDisplay = number_format($priceValue, 0, '', '');

    echo "<div class=\"itemBox\">\n";
    echo "  <div class=\"item-img-wrapper\">\n";
    echo "    <img class=\"item-img\" src=\"" . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . "\" alt=\"" . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "  </div>\n";
    echo "  <div class=\"item-info\">\n";
    echo "    <h3 class=\"titre\">" . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . "</h3>\n";
    echo "    <p class=\"item-type\">Type: " . htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') . "</p>\n";
    echo "    <p class=\"description\">Quantité: " . htmlspecialchars($quantityValue, ENT_QUOTES, 'UTF-8') . "</p>\n";
    echo "    <p class=\"prixOr\">" . htmlspecialchars($priceDisplay, ENT_QUOTES, 'UTF-8') . " gold</p>\n";
    echo "    <div class=\"btnPanier\">\n";
    echo "      <a class=\"btnPanierBtn\" href=\"panier.php?action=add&id=" . intval($id) . "\">Ajouter au panier</a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
}
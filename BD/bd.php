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
    echo "      <a href=\"panier.php?action=add&id=" . intval($id) . "\">\n";
    echo "        <img src=\"img/addToCart.png\" class=\"btnPanierImg\" alt=\"Ajouter au panier\">\n";
    echo "      </a>\n";
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
}

// -------------------------
// Ajouter au Panier
// -------------------------
function AjouterPanier(int $idJoueur, int $idItem, int $quantite): array
{
    $pdo = get_pdo();
    if ($pdo === false) return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $stmt = $pdo->prepare('CALL AjouterPanier(:idJoueur, :idItem, :quantite)');
        $stmt->execute([
            ':idJoueur'  => $idJoueur,
            ':idItem'    => $idItem,
            ':quantite'  => $quantite,
        ]);
        while ($stmt->nextRowset()) {}
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('AjouterPanier error: ' . $e->getMessage());
        $msg = str_contains($e->getMessage(), 'Stock insuffisant')
            ? 'Stock insuffisant.'
            : 'Erreur lors de l\'ajout au panier.';
        return ['success' => false, 'message' => $msg];
    }
}

// -------------------------
// Afficher le Panier
// -------------------------
function AfficherPanier(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false) return [];
    try {
        $stmt = $pdo->prepare(
            'SELECT p.idPanier, p.idItem, p.quantitePanier, i.nomItem, i.prix, i.image
             FROM Panier p
             JOIN Items i ON i.idItem = p.idItem
             WHERE p.idJoueur = :idJoueur'
        );
        $stmt->execute([':idJoueur' => $idJoueur]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('AfficherPanier error: ' . $e->getMessage());
        return [];
    }
}

// -------------------------
// Retirer du Panier
// -------------------------
function RetirerPanier(int $idPanier, int $idJoueur): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $stmt = $pdo->prepare(
            'DELETE FROM Panier WHERE idPanier = :idPanier AND idJoueur = :idJoueur'
        );
        $stmt->execute([':idPanier' => $idPanier, ':idJoueur' => $idJoueur]);
        return true;
    } catch (PDOException $e) {
        error_log('RetirerPanier error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Modifier Quantité Panier
// -------------------------
function ModifierQuantitePanier(int $idPanier, int $idJoueur, int $nouvelleQte): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $stmt = $pdo->prepare(
            'UPDATE Panier SET quantitePanier = :qte
             WHERE idPanier = :idPanier AND idJoueur = :idJoueur'
        );
        $stmt->execute([
            ':qte'      => $nouvelleQte,
            ':idPanier' => $idPanier,
            ':idJoueur' => $idJoueur,
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('ModifierQuantitePanier error: ' . $e->getMessage());
        return false;
    }
}
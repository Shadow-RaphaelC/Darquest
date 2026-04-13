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

        while ($stmt->nextRowset()) {
        }

        return $rows;
    } catch (PDOException $e) {
        error_log('AfficherItems error: ' . $e->getMessage());
        return [];
    }
}

// -------------------------
// Get All Items Sub-Details
// -------------------------
function GetAllItemsSubDetails(): array
{
    $pdo = get_pdo();
    if ($pdo === false) return [];

    $details = [];
    $details['__sortsError'] = null;

    try {
        $stmt = $pdo->query(
            'SELECT s.idItem, ts.Description AS typeDescription, ts.ptVie, ts.ptDegat,
                    s.estInstantane, s.rarete
             FROM Sorts s
             JOIN TypeSorts ts ON ts.typeSorts = s.typeSorts'
        );
        foreach ($stmt->fetchAll() as $row) {
            $details[(int)$row['idItem']] = [
                'category'        => 'sort',
                'typeDescription' => $row['typeDescription'],
                'ptVie'           => (int)$row['ptVie'],
                'ptDegat'         => (int)$row['ptDegat'],
                'estInstantane'    => (bool)$row['estInstantane'],
                'rarete'          => (int)$row['rarete'],
            ];
        }
    } catch (PDOException $e) {
        error_log('GetAllItemsSubDetails Sorts error: ' . $e->getMessage());
        $details['__sortsError'] = $e->getMessage();
    }

    try {
        $stmt = $pdo->query('SELECT idItem, efficacite, genre, description FROM Armes');
        foreach ($stmt->fetchAll() as $row) {
            $details[(int)$row['idItem']] = [
                'category'    => 'arme',
                'efficacite'  => $row['efficacite'],
                'genre'       => $row['genre'],
                'description' => $row['description'],
            ];
        }
    } catch (PDOException $e) {
        error_log('GetAllItemsSubDetails Armes error: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query('SELECT idItem, effet, duree FROM Potions');
        foreach ($stmt->fetchAll() as $row) {
            $details[(int)$row['idItem']] = [
                'category' => 'potion',
                'effet'    => $row['effet'],
                'duree'    => (int)$row['duree'],
            ];
        }
    } catch (PDOException $e) {
        error_log('GetAllItemsSubDetails Potions error: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query('SELECT idItem, matiere, taille FROM Armures');
        foreach ($stmt->fetchAll() as $row) {
            $details[(int)$row['idItem']] = [
                'category' => 'armure',
                'matiere'  => $row['matiere'],
                'taille'   => $row['taille'],
            ];
        }
    } catch (PDOException $e) {
        error_log('GetAllItemsSubDetails Armures error: ' . $e->getMessage());
    }

    return $details;
}

// -------------------------
// Get Shop Stock Map
// -------------------------
function GetItemsStockMap(): array
{
    $rows = AfficherItems();
    $map = [];
    foreach ($rows as $row) {
        $idItem = (int) ($row[0] ?? 0);
        $stock = (int) ($row[2] ?? 0);
        if ($idItem > 0) {
            $map[$idItem] = $stock;
        }
    }
    return $map;
}

function render_item_card($id, $nom, $quantity, $typeItem, $price, $image, $isDisponible)
{
    $isDisponibleNormalized = filter_var($isDisponible, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($isDisponibleNormalized === false) {
        return;
    }

    $typeItemCode = strtoupper(trim((string) $typeItem));
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

    $quantityValue = (int) $quantity;
    $priceValue = (int) $price;
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
    if ($quantityValue > 0) {
        echo "      <form method=\"GET\" action=\"panier.php\" target=\"panier-frame\">\n";
        echo "        <input type=\"hidden\" name=\"action\" value=\"add\">\n";
        echo "        <input type=\"hidden\" name=\"id\" value=\"" . intval($id) . "\">\n";
        echo "        <button type=\"submit\" class=\"btnPanierImg-btn\">\n";
        echo "          <img src=\"img/addToCart.png\" class=\"btnPanierImg\" alt=\"Ajouter au panier\">\n";
        echo "        </button>\n";
        echo "      </form>\n";
    } else {
        echo "      <span class=\"btnPanierImg btnPanierImg--disabled\">Rupture de stock</span>\n";
    }
    echo "    </div>\n";
    echo "  </div>\n";
    echo "</div>\n";
}

// -------------------------
// Ajouter au Panier
// -------------------------
function AjouterPanier(string $alias, int $idItem, int $quantite): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $stmt = $pdo->prepare('CALL AjouterPanier(:alias, :idItem, :quantite)');
        $stmt->execute([
            ':alias' => $alias,
            ':idItem' => $idItem,
            ':quantite' => $quantite,
        ]);
        while ($stmt->nextRowset()) {
        }
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
    if ($pdo === false)
        return [];
    try {
        $stmt = $pdo->prepare(
            'SELECT p.idItem, p.quantitePanier, i.nom, i.prix, i.image
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
function RetirerPanier(int $idItem, int $idJoueur): bool
{
    $pdo = get_pdo();
    if ($pdo === false)
        return false;
    try {
        $stmt = $pdo->prepare(
            'DELETE FROM Panier WHERE idItem = :idItem AND idJoueur = :idJoueur'
        );
        $stmt->execute([':idItem' => $idItem, ':idJoueur' => $idJoueur]);
        return true;
    } catch (PDOException $e) {
        error_log('RetirerPanier error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Modifier Quantité Panier
// -------------------------
function ModifierQuantitePanier(int $idItem, int $idJoueur, int $nouvelleQte): bool
{
    $pdo = get_pdo();
    if ($pdo === false)
        return false;
    try {
        $stmt = $pdo->prepare(
            'UPDATE Panier SET quantitePanier = :qte
             WHERE idItem = :idItem AND idJoueur = :idJoueur'
        );
        $stmt->execute([
            ':qte' => $nouvelleQte,
            ':idItem' => $idItem,
            ':idJoueur' => $idJoueur,
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('ModifierQuantitePanier error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Fetch player coins
// -------------------------
function GetJoueurCoins(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['gold' => 0, 'argent' => 0, 'bronze' => 0];
    try {
        $stmt = $pdo->prepare(
            'SELECT gold, argent, bronze FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return ['gold' => 0, 'argent' => 0, 'bronze' => 0];
        return [
            'gold'   => (int)$row['gold'],
            'argent' => (int)$row['argent'],
            'bronze' => (int)$row['bronze'],
        ];
    } catch (PDOException $e) {
        error_log('GetJoueurCoins error: ' . $e->getMessage());
        return ['gold' => 0, 'argent' => 0, 'bronze' => 0];
    }
}

// -------------------------
// Payer le Panier
// -------------------------
function PayerPanier(string $alias): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $stmt = $pdo->prepare('CALL PayerPanier(:alias)');
        $stmt->execute([':alias' => $alias]);
        while ($stmt->nextRowset()) {
        }
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('PayerPanier error: ' . $e->getMessage());
        $msg = $e->getMessage();
        return ['success' => false, 'message' => $msg];
    }
}

// -------------------------
// Afficher Inventaire
// -------------------------
function AfficherInventaire(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return [];
    try {
        $stmt = $pdo->prepare(
            'SELECT inv.idItem, inv.quantiteInvenatire, i.nom, i.prix, i.image, i.typeItem
             FROM Inventaire inv
             JOIN Items i ON i.idItem = inv.idItem
             WHERE inv.idJoueur = :idJoueur'
        );
        $stmt->execute([':idJoueur' => $idJoueur]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('AfficherInventaire error: ' . $e->getMessage());
        return [];
    }
}

// -------------------------
// Get Item Type by ID
// -------------------------
function GetItemType(int $idItem): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['typeItem' => ''];
    try {
        $stmt = $pdo->prepare(
            'SELECT typeItem FROM Items WHERE idItem = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idItem]);
        $row = $stmt->fetch();
        return $row ?: ['typeItem' => ''];
    } catch (PDOException $e) {
        error_log('GetItemType error: ' . $e->getMessage());
        return ['typeItem' => ''];
    }
}

// -------------------------
// Vendre un item de l'inventaire
// -------------------------
function VendreItem(int $idJoueur, int $idItem, int $quantite): array
{
    if ($quantite <= 0) {
        return ['success' => false, 'message' => 'Quantité invalide.'];
    }

    $pdo = get_pdo();
    if ($pdo === false) {
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    }

    try {
        $pdo->beginTransaction();

        // Verify inventory ownership and available quantity
        $stmt = $pdo->prepare(
            'SELECT inv.quantiteInvenatire, i.prix, i.typeItem
             FROM Inventaire inv
             JOIN Items i ON i.idItem = inv.idItem
             WHERE inv.idJoueur = :idJoueur AND inv.idItem = :idItem'
        );
        $stmt->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Item introuvable dans l\'inventaire.'];
        }

        $qteDisponible = (int) $row['quantiteInvenatire'];
        if ($quantite > $qteDisponible) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Quantité insuffisante dans l\'inventaire.'];
        }

        // Calculate resell price (spells: +10%, others: -40%)
        $prix     = (int) $row['prix'];
        $typeCode = strtoupper(trim((string) $row['typeItem']));
        $resellRate = ($typeCode === 'S' || $typeCode === 'SORT') ? 1.10 : 0.60;
        $goldGagne  = (int) round($prix * $resellRate * $quantite);

        // Remove from inventory (delete row if quantity reaches 0)
        $newQte = $qteDisponible - $quantite;
        if ($newQte <= 0) {
            $stmt = $pdo->prepare(
                'DELETE FROM Inventaire WHERE idJoueur = :idJoueur AND idItem = :idItem'
            );
            $stmt->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE Inventaire SET quantiteInvenatire = :qte
                 WHERE idJoueur = :idJoueur AND idItem = :idItem'
            );
            $stmt->execute([':qte' => $newQte, ':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        }

        // Add gold to player
        $stmt = $pdo->prepare(
            'UPDATE Joueurs SET gold = gold + :gold WHERE idJoueur = :idJoueur'
        );
        $stmt->execute([':gold' => $goldGagne, ':idJoueur' => $idJoueur]);

        $pdo->commit();

        // Restore quantity back to shop stock (outside transaction — best effort)
        try {
            $stmt = $pdo->prepare(
                'UPDATE Items SET quantite = quantite + :quantite WHERE idItem = :idItem'
            );
            $stmt->execute([':quantite' => $quantite, ':idItem' => $idItem]);
        } catch (PDOException $stockErr) {
            error_log('VendreItem: stock restore failed (check Items column name): ' . $stockErr->getMessage());
        }

        return ['success' => true, 'gold' => $goldGagne];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('VendreItem error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la vente.'];
    }
}

// -------------------------
// Fetch Player HP
// -------------------------
function GetJoueurHP(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['pointDeVie' => 0, 'maxHP' => 100];
    try {
        $stmt = $pdo->prepare(
            'SELECT pointDeVie, maxHP FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return ['pointDeVie' => 0, 'maxHP' => 100];
        return [
            'pointDeVie' => (int)($row['pointDeVie'] ?? 0),
            'maxHP'       => (int)($row['maxHP']       ?? 100),
        ];
    } catch (PDOException $e) {
        error_log('GetJoueurHP error: ' . $e->getMessage());
        // maxHP column may not exist — try fetching only pointDeVie
        try {
            $stmt = $pdo->prepare('SELECT pointDeVie FROM Joueurs WHERE idJoueur = :id LIMIT 1');
            $stmt->execute([':id' => $idJoueur]);
            $row = $stmt->fetch();
            return ['pointDeVie' => (int)($row['pointDeVie'] ?? 0), 'maxHP' => 100];
        } catch (PDOException $e2) {
            error_log('GetJoueurHP fallback error: ' . $e2->getMessage());
            return ['pointDeVie' => 0, 'maxHP' => 100];
        }
    }
}

// -------------------------
// Fetch Mage Status
// -------------------------
function GetMageStatus(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['isMage' => false];
    try {
        $stmt = $pdo->prepare(
            'SELECT estMage FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        return $row ?: ['estMage' => 0];
    } catch (PDOException $e) {
        error_log('GetMageStatus error: ' . $e->getMessage());
        return ['estMage' => 0];
    }
}
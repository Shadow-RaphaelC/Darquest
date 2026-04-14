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
// Cart item count
// -------------------------
function GetCartCount(int $idJoueur): int
{
    $pdo = get_pdo();
    if ($pdo === false) return 0;
    try {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(quantitePanier), 0) FROM Panier WHERE idJoueur = :id'
        );
        $stmt->execute([':id' => $idJoueur]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
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
            'SELECT inv.idItem, inv.quantiteInvenatire, i.nom, i.prix, i.image, i.typeItem,
                    s.rarete, arm.taille, arm.matiere, arme.efficacite, arme.genre, pot.effet
             FROM Inventaire inv
             JOIN Items i          ON i.idItem    = inv.idItem
             LEFT JOIN Sorts   s   ON s.idItem    = inv.idItem
             LEFT JOIN Armures arm ON arm.idItem  = inv.idItem
             LEFT JOIN Armes   arme ON arme.idItem = inv.idItem
             LEFT JOIN Potions pot ON pot.idItem  = inv.idItem
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
            'SELECT inv.quantiteInvenatire, i.prix, i.typeItem, s.rarete
             FROM Inventaire inv
             JOIN Items i ON i.idItem = inv.idItem
             LEFT JOIN Sorts s ON s.idItem = inv.idItem
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

        // Calculate resell price (sorts: rate by rarete; others: -40%)
        $prix     = (int) $row['prix'];
        $typeCode = strtoupper(trim((string) $row['typeItem']));
        if ($typeCode === 'S' || $typeCode === 'SORT') {
            $rarete = (int) $row['rarete'];
            if ($rarete === 2)      $resellRate = 0.95;
            elseif ($rarete === 3)  $resellRate = 0.90;
            else                    $resellRate = 1.00;
        } else {
            $resellRate = 0.60;
        }
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

// -------------------------
// Armor stat helper
// -------------------------
function getArmorStats(string $taille, string $matiere): array
{
    $tailleStats = [
        'EXTRA LARGE' => ['maxHP' => 30, 'heal' => -5],
        'LARGE'       => ['maxHP' => 20, 'heal' =>  0],
        'MOYEN'       => ['maxHP' => 10, 'heal' =>  5],
        'PETIT'       => ['maxHP' =>  5, 'heal' => 10],
    ];
    $matiereStats = [
        'PLAQUES' => ['maxHP' => 20, 'heal' => -5],
        'MAILLE'  => ['maxHP' => 15, 'heal' =>  0],
        'CUIR'    => ['maxHP' => 10, 'heal' =>  5],
        'TISSU'   => ['maxHP' =>  5, 'heal' => 10],
    ];

    $t = $tailleStats[strtoupper(trim($taille))]   ?? ['maxHP' => 0, 'heal' => 0];
    $m = $matiereStats[strtoupper(trim($matiere))] ?? ['maxHP' => 0, 'heal' => 0];

    return ['maxHP' => $t['maxHP'] + $m['maxHP'], 'heal' => $t['heal'] + $m['heal']];
}

// -------------------------
// Get Equipped Armor
// -------------------------
function GetArmureEquipee(int $idJoueur): ?array
{
    $pdo = get_pdo();
    if ($pdo === false) return null;
    try {
        $stmt = $pdo->prepare(
            'SELECT i.idItem, i.nom, i.image, a.taille, a.matiere
             FROM Joueurs j
             JOIN Items   i ON i.idItem = j.idArmureEquipee
             JOIN Armures a ON a.idItem = j.idArmureEquipee
             WHERE j.idJoueur = :id AND j.idArmureEquipee IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $stats = getArmorStats($row['taille'], $row['matiere']);
        return array_merge($row, ['maxHPBonus' => $stats['maxHP'], 'healBonus' => $stats['heal']]);
    } catch (PDOException $e) {
        error_log('GetArmureEquipee error: ' . $e->getMessage());
        return null;
    }
}

// -------------------------
// Equip Armor
// -------------------------
function EquiperArmure(int $idJoueur, int $idItem): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $pdo->beginTransaction();

        // Verify the armor exists in the player's inventory
        $stmt = $pdo->prepare(
            'SELECT a.taille, a.matiere
             FROM Armures a
             JOIN Inventaire inv ON inv.idItem = a.idItem
             WHERE a.idItem = :idItem AND inv.idJoueur = :idJoueur'
        );
        $stmt->execute([':idItem' => $idItem, ':idJoueur' => $idJoueur]);
        $newArmor = $stmt->fetch();

        if (!$newArmor) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Armure introuvable dans l\'inventaire.'];
        }

        // Get current player state
        $stmt = $pdo->prepare(
            'SELECT maxHP, pointDeVie, idArmureEquipee FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Joueur introuvable.'];
        }

        $maxHP      = (int) $player['maxHP'];
        $pointDeVie = (int) $player['pointDeVie'];
        $idOld      = $player['idArmureEquipee'];

        // Already wearing this exact armor
        if ($idOld !== null && (int) $idOld === $idItem) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Vous portez déjà cette armure.'];
        }

        // Strip old armor bonuses from maxHP
        if ($idOld !== null) {
            $stmt = $pdo->prepare('SELECT taille, matiere FROM Armures WHERE idItem = :id LIMIT 1');
            $stmt->execute([':id' => $idOld]);
            $oldArmor = $stmt->fetch();
            if ($oldArmor) {
                $oldStats = getArmorStats($oldArmor['taille'], $oldArmor['matiere']);
                $maxHP   -= $oldStats['maxHP'];
            }
        }

        // Apply new armor bonuses
        $newStats = getArmorStats($newArmor['taille'], $newArmor['matiere']);
        $newMaxHP = $maxHP + $newStats['maxHP'];
        $newHeal  = $newStats['heal'];
        $newPV    = min($pointDeVie, $newMaxHP);

        $stmt = $pdo->prepare(
            'UPDATE Joueurs
             SET maxHP = :maxHP, pointDeVie = :pv, healBonus = :heal, idArmureEquipee = :idArmure
             WHERE idJoueur = :idJoueur'
        );
        $stmt->execute([
            ':maxHP'    => $newMaxHP,
            ':pv'       => $newPV,
            ':heal'     => $newHeal,
            ':idArmure' => $idItem,
            ':idJoueur' => $idJoueur,
        ]);

        $pdo->commit();

        return ['success' => true, 'maxHP' => $newMaxHP, 'pointDeVie' => $newPV, 'healBonus' => $newHeal];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('EquiperArmure error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de l\'équipement.'];
    }
}

// -------------------------
// Unequip Armor
// -------------------------
function DesequiperArmure(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT maxHP, pointDeVie, idArmureEquipee FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player || $player['idArmureEquipee'] === null) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Aucune armure équipée.'];
        }

        $maxHP      = (int) $player['maxHP'];
        $pointDeVie = (int) $player['pointDeVie'];
        $idOld      = (int) $player['idArmureEquipee'];

        $stmt = $pdo->prepare('SELECT taille, matiere FROM Armures WHERE idItem = :id LIMIT 1');
        $stmt->execute([':id' => $idOld]);
        $oldArmor = $stmt->fetch();

        if ($oldArmor) {
            $oldStats = getArmorStats($oldArmor['taille'], $oldArmor['matiere']);
            $maxHP   -= $oldStats['maxHP'];
        }

        $newPV = min($pointDeVie, $maxHP);

        $stmt = $pdo->prepare(
            'UPDATE Joueurs SET maxHP = :maxHP, pointDeVie = :pv, healBonus = 0, idArmureEquipee = NULL
             WHERE idJoueur = :idJoueur'
        );
        $stmt->execute([':maxHP' => $maxHP, ':pv' => $newPV, ':idJoueur' => $idJoueur]);

        $pdo->commit();

        return ['success' => true, 'maxHP' => $maxHP, 'pointDeVie' => $newPV];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('DesequiperArmure error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du déséquipement.'];
    }
}

// -------------------------
// Weapon stat helper
// -------------------------
function normalizeStatKey(string $s): string
{
    $s = strtoupper(trim($s));
    $s = str_replace(['É','È','Ê','Ë'], 'E', $s);
    $s = str_replace(['é','è','ê','ë'], 'E', $s);
    $s = str_replace(['À','Â'], 'A', $s);
    $s = str_replace(['à','â'], 'A', $s);
    return $s;
}

function getWeaponStats(string $efficacite, string $genre): array
{
    $efficaciteBonus = [
        'TRES TRES TRES EFFICACE' => 20,
        'TRES TRES EFFICACE'      => 15,
        'TRES EFFICACE'           => 10,
        'EFFICACE'                => 5,
        'PAS EFFICACE'            => 0,
    ];

    $genreStats = [
        'EPEE A DEUX MAIN'  => ['gold' => -10, 'damage' => 0.50],
        'EPEE'              => ['gold' =>   0, 'damage' => 1.00],
        'GLAIVE'            => ['gold' =>  10, 'damage' => 2.00],
        'BATON'             => ['gold' => -10, 'damage' => 0.50],
        'HACHE A DEUX MAIN' => ['gold' => -10, 'damage' => 0.50],
        'DAGUE'             => ['gold' =>   0, 'damage' => 1.00],
        'ARC'               => ['gold' =>  10, 'damage' => 2.00],
        'HACHE'             => ['gold' =>   0, 'damage' => 1.00],
    ];

    $effKey   = normalizeStatKey($efficacite);
    $genreKey = normalizeStatKey($genre);

    $goldFromEff = $efficaciteBonus[$effKey]  ?? 0;
    $genreData   = $genreStats[$genreKey]     ?? ['gold' => 0, 'damage' => 1.00];

    return [
        'goldBonus'      => $goldFromEff + $genreData['gold'],
        'damageModifier' => $genreData['damage'],
    ];
}

// -------------------------
// Get Equipped Weapon
// -------------------------
function GetArmeEquipee(int $idJoueur): ?array
{
    $pdo = get_pdo();
    if ($pdo === false) return null;
    try {
        $stmt = $pdo->prepare(
            'SELECT i.idItem, i.nom, i.image, arme.efficacite, arme.genre
             FROM Joueurs j
             JOIN Items i   ON i.idItem   = j.idArmeEquipee
             JOIN Armes arme ON arme.idItem = j.idArmeEquipee
             WHERE j.idJoueur = :id AND j.idArmeEquipee IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $stats = getWeaponStats($row['efficacite'], $row['genre']);
        return array_merge($row, $stats);
    } catch (PDOException $e) {
        error_log('GetArmeEquipee error: ' . $e->getMessage());
        return null;
    }
}

// -------------------------
// Equip Weapon
// -------------------------
function EquiperArme(int $idJoueur, int $idItem): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $pdo->beginTransaction();

        // Verify weapon exists in player's inventory
        $stmt = $pdo->prepare(
            'SELECT arme.efficacite, arme.genre
             FROM Armes arme
             JOIN Inventaire inv ON inv.idItem = arme.idItem
             WHERE arme.idItem = :idItem AND inv.idJoueur = :idJoueur'
        );
        $stmt->execute([':idItem' => $idItem, ':idJoueur' => $idJoueur]);
        $newWeapon = $stmt->fetch();

        if (!$newWeapon) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Arme introuvable dans l\'inventaire.'];
        }

        // Get current player state
        $stmt = $pdo->prepare(
            'SELECT idArmeEquipee FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Joueur introuvable.'];
        }

        $idOld = $player['idArmeEquipee'];

        if ($idOld !== null && (int) $idOld === $idItem) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Vous portez déjà cette arme.'];
        }

        $newStats = getWeaponStats($newWeapon['efficacite'], $newWeapon['genre']);

        $stmt = $pdo->prepare(
            'UPDATE Joueurs
             SET goldBonus = :gold, damageModifier = :dmg, idArmeEquipee = :idArme
             WHERE idJoueur = :idJoueur'
        );
        $stmt->execute([
            ':gold'     => $newStats['goldBonus'],
            ':dmg'      => $newStats['damageModifier'],
            ':idArme'   => $idItem,
            ':idJoueur' => $idJoueur,
        ]);

        $pdo->commit();

        return ['success' => true, 'goldBonus' => $newStats['goldBonus'], 'damageModifier' => $newStats['damageModifier']];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('EquiperArme error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de l\'équipement.'];
    }
}

// -------------------------
// Unequip Weapon
// -------------------------
function DesequiperArme(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $stmt = $pdo->prepare(
            'SELECT idArmeEquipee FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player || $player['idArmeEquipee'] === null) {
            return ['success' => false, 'message' => 'Aucune arme équipée.'];
        }

        $stmt = $pdo->prepare(
            'UPDATE Joueurs SET goldBonus = 0, damageModifier = 1.00, idArmeEquipee = NULL
             WHERE idJoueur = :idJoueur'
        );
        $stmt->execute([':idJoueur' => $idJoueur]);

        return ['success' => true];

    } catch (PDOException $e) {
        error_log('DesequiperArme error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du déséquipement.'];
    }
}

// -------------------------
// Potion heal % by effet
// -------------------------
function getPotionHealPct(string $effet): int
{
    $map = [
        'SOINS'                                      => 20,
        'SOINS AMELIORE'                             => 40,
        'RESISTANCE AU FEU'                          => 25,
        'ATTAQUE PLUS FORTE'                         => 20,
        'RAGE AMELIORE'                              => 25,
        'RECUPERATION DE MANA'                       => 15,
        'RECUPERATION DE MANA AMELIORE'              => 30,
        'RECUPERATION DE MANA AMELIORE EN COMBAT'    => 35,
        'VITESSE'                                    => 10,
        'INVISIBILITE'                               => 15,
    ];
    return $map[normalizeStatKey($effet)] ?? 15;
}

// -------------------------
// Use Potion
// -------------------------
function UtiliserPotion(int $idJoueur, int $idItem): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $pdo->beginTransaction();

        // Get potion effet + inventory qty
        $stmt = $pdo->prepare(
            'SELECT p.effet, inv.quantiteInvenatire
             FROM Potions p
             JOIN Inventaire inv ON inv.idItem = p.idItem
             WHERE p.idItem = :idItem AND inv.idJoueur = :idJoueur'
        );
        $stmt->execute([':idItem' => $idItem, ':idJoueur' => $idJoueur]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Potion introuvable dans l\'inventaire.'];
        }

        // Get player HP + healBonus
        $stmt = $pdo->prepare(
            'SELECT pointDeVie, maxHP, healBonus FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Joueur introuvable.'];
        }

        $pv        = (int)   $player['pointDeVie'];
        $maxHP     = (int)   $player['maxHP'];
        $healBonus = (int)   $player['healBonus'];

        if ($pv >= $maxHP) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Vos HP sont déjà au maximum.'];
        }

        // Calculate heal
        $healPct    = getPotionHealPct((string) $row['effet']);
        $baseHeal   = (int) round($maxHP * $healPct / 100);
        $finalHeal  = (int) round($baseHeal * (1 + $healBonus / 100));
        $finalHeal  = max(1, $finalHeal);
        $newPV      = min($pv + $finalHeal, $maxHP);

        // Update HP
        $stmt = $pdo->prepare('UPDATE Joueurs SET pointDeVie = :pv WHERE idJoueur = :id');
        $stmt->execute([':pv' => $newPV, ':id' => $idJoueur]);

        // Consume one potion
        $newQte = (int) $row['quantiteInvenatire'] - 1;
        if ($newQte <= 0) {
            $stmt = $pdo->prepare('DELETE FROM Inventaire WHERE idJoueur = :idJoueur AND idItem = :idItem');
            $stmt->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        } else {
            $stmt = $pdo->prepare('UPDATE Inventaire SET quantiteInvenatire = :qte WHERE idJoueur = :idJoueur AND idItem = :idItem');
            $stmt->execute([':qte' => $newQte, ':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        }

        $pdo->commit();

        return [
            'success'   => true,
            'healed'    => $finalHeal,
            'newPV'     => $newPV,
            'maxHP'     => $maxHP,
            'consumed'  => $newQte <= 0,
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UtiliserPotion error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de l\'utilisation.'];
    }
}

// -------------------------
// Hourly potion restock (lazy cron)
// -------------------------
function checkPotionRestock(): void
{
    $pdo = get_pdo();
    if ($pdo === false) return;

    try {
        // Atomically claim the restock slot — only fires if 1+ hour has passed
        $stmt = $pdo->prepare(
            "UPDATE Config SET valeur = NOW()
             WHERE cle = 'last_potion_restock'
             AND valeur <= DATE_SUB(NOW(), INTERVAL 6 HOUR)"
        );
        $stmt->execute();

        if ($stmt->rowCount() === 0) return; // Not time yet, or another request beat us

        // Distribute 50 units randomly across potions
        $restock = $pdo->prepare(
            "UPDATE Items SET quantite = quantite + 1
             WHERE typeItem IN ('P', 'POTION')
             ORDER BY RAND() LIMIT 1"
        );
        for ($i = 0; $i < 50; $i++) {
            $restock->execute();
        }

    } catch (PDOException $e) {
        error_log('checkPotionRestock error: ' . $e->getMessage());
    }
}
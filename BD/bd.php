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

function render_item_card($id, $nom, $quantity, $typeItem, $price, $image, $estDisponible)
{
    $estDisponibleNormalized = filter_var($estDisponible, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($estDisponibleNormalized === false) {
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
                    s.rarete, ts.ptVie AS sortPtVie,
                    arm.taille, arm.matiere, arme.efficacite, arme.genre, pot.effet
             FROM Inventaire inv
             JOIN Items i              ON i.idItem      = inv.idItem
             LEFT JOIN Sorts   s       ON s.idItem      = inv.idItem
             LEFT JOIN TypeSorts ts    ON ts.typeSorts  = s.typeSorts
             LEFT JOIN Armures arm     ON arm.idItem    = inv.idItem
             LEFT JOIN Armes   arme    ON arme.idItem   = inv.idItem
             LEFT JOIN Potions pot     ON pot.idItem    = inv.idItem
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

        // Auto-unequip if this item is currently equipped
        if ($typeCode === 'R' || $typeCode === 'ARMURE') {
            $stmt = $pdo->prepare(
                'SELECT maxHP, pointDeVie, idArmureEquipee FROM Joueurs
                 WHERE idJoueur = :idJoueur AND idArmureEquipee = :idItem LIMIT 1'
            );
            $stmt->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
            $equipped = $stmt->fetch();
            if ($equipped) {
                $newMaxHP = (int) $equipped['maxHP'];
                $newPV    = (int) $equipped['pointDeVie'];
                $armorStmt = $pdo->prepare('SELECT taille, matiere FROM Armures WHERE idItem = :id LIMIT 1');
                $armorStmt->execute([':id' => $idItem]);
                $armorRow = $armorStmt->fetch();
                if ($armorRow) {
                    $oldStats  = getArmorStats($armorRow['taille'], $armorRow['matiere']);
                    $newMaxHP -= $oldStats['maxHP'];
                }
                $newPV = min($newPV, $newMaxHP);
                $stmt = $pdo->prepare(
                    'UPDATE Joueurs SET maxHP = :maxHP, pointDeVie = :pv, healBonus = 0, idArmureEquipee = NULL
                     WHERE idJoueur = :idJoueur'
                );
                $stmt->execute([':maxHP' => $newMaxHP, ':pv' => $newPV, ':idJoueur' => $idJoueur]);
            }
        } elseif ($typeCode === 'A' || $typeCode === 'ARME') {
            $stmt = $pdo->prepare(
                'SELECT idArmeEquipee FROM Joueurs
                 WHERE idJoueur = :idJoueur AND idArmeEquipee = :idItem LIMIT 1'
            );
            $stmt->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare(
                    'UPDATE Joueurs SET goldBonus = 0, damageModifier = 1.00, idArmeEquipee = NULL
                     WHERE idJoueur = :idJoueur'
                );
                $stmt->execute([':idJoueur' => $idJoueur]);
            }
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
            'SELECT estMage, quetesMagieComplete FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        return $row ?: ['estMage' => 0, 'quetesMagieComplete' => 0];
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

        // Ensure healPct column exists then fetch potion data
        _ensurePotionHealPct($pdo);
        $stmt = $pdo->prepare(
            'SELECT p.effet, COALESCE(p.healPct, 0) AS dbHealPct, inv.quantiteInvenatire
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

        // Use DB heal % if set by admin, otherwise fall back to PHP map
        $dbHealPct  = (int)$row['dbHealPct'];
        $healPct    = $dbHealPct > 0 ? $dbHealPct : getPotionHealPct((string)$row['effet']);
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
// Use Sort (flat HP heal from TypeSorts.ptVie)
// -------------------------
function UtiliserSort(int $idJoueur, int $idItem): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT ts.ptVie, inv.quantiteInvenatire
             FROM Sorts s
             JOIN TypeSorts ts    ON ts.typeSorts = s.typeSorts
             JOIN Inventaire inv  ON inv.idItem   = s.idItem
             WHERE s.idItem = :idItem AND inv.idJoueur = :idJoueur'
        );
        $stmt->execute([':idItem' => $idItem, ':idJoueur' => $idJoueur]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Sort introuvable dans l\'inventaire.'];
        }

        $stmt = $pdo->prepare(
            'SELECT pointDeVie, maxHP, healBonus FROM Joueurs WHERE idJoueur = :id LIMIT 1'
        );
        $stmt->execute([':id' => $idJoueur]);
        $player = $stmt->fetch();

        if (!$player) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Joueur introuvable.'];
        }

        $pv        = (int) $player['pointDeVie'];
        $maxHP     = (int) $player['maxHP'];
        $healBonus = (int) $player['healBonus'];

        if ($pv >= $maxHP) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Vos HP sont déjà au maximum.'];
        }

        $baseHeal  = (int) $row['ptVie'];
        $finalHeal = (int) round($baseHeal * (1 + $healBonus / 100));
        $finalHeal = max(1, $finalHeal);
        $newPV     = min($pv + $finalHeal, $maxHP);

        $pdo->prepare('UPDATE Joueurs SET pointDeVie = :pv WHERE idJoueur = :id')
            ->execute([':pv' => $newPV, ':id' => $idJoueur]);

        $newQte = (int) $row['quantiteInvenatire'] - 1;
        if ($newQte <= 0) {
            $pdo->prepare('DELETE FROM Inventaire WHERE idJoueur = :idJoueur AND idItem = :idItem')
                ->execute([':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        } else {
            $pdo->prepare('UPDATE Inventaire SET quantiteInvenatire = :qte WHERE idJoueur = :idJoueur AND idItem = :idItem')
                ->execute([':qte' => $newQte, ':idJoueur' => $idJoueur, ':idItem' => $idItem]);
        }

        $pdo->commit();

        return [
            'success'  => true,
            'healed'   => $finalHeal,
            'newPV'    => $newPV,
            'maxHP'    => $maxHP,
            'consumed' => $newQte <= 0,
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UtiliserSort error: ' . $e->getMessage());
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
             AND valeur <= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
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

// -------------------------
// Ajouter Enigme (admin)
// -------------------------
function AjouterEnigme(string $enigme, string $difficulte, string $idCategorie, array $reponses, int $bonneReponse): array
{
    $pdo = get_pdo();
    if ($pdo === false)
        return ['success' => false, 'message' => 'Erreur de connexion BD.'];

    try {
        // Insert the enigma via SP — expected to return a result set with the new idEnigma
        $stmt = $pdo->prepare('CALL AjouterEnigma(:enigme, :difficulte, :idCategorie)');
        $stmt->execute([
            ':enigme'      => trim($enigme),
            ':difficulte'  => $difficulte,
            ':idCategorie' => $idCategorie,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($stmt->nextRowset()) {}

        // SP uses START TRANSACTION without COMMIT — commit it from PHP
        $pdo->commit();

        // Try result set first, then LAST_INSERT_ID() on same connection
        $idEnigma = $row ? (int) reset($row) : 0;
        if (!$idEnigma) {
            $idEnigma = (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        }

        if (!$idEnigma) {
            return ['success' => false, 'message' => 'Impossible de récupérer l\'ID de l\'énigme créée.'];
        }
        // Insert each of the 4 responses via SP
        // Fresh connection per call + explicit commit — SP opens transaction but never commits
        foreach ($reponses as $i => $texte) {
            $pdoRep = get_pdo();
            if ($pdoRep === false) {
                throw new \RuntimeException('Erreur de connexion BD pour les réponses.');
            }
            $estBonne      = ($i == $bonneReponse) ? 1 : 0;
            $reponseQuoted = $pdoRep->quote(trim($texte));
            $pdoRep->exec("CALL AjouterReponse($estBonne, $reponseQuoted, $idEnigma)");
            $pdoRep->commit();
            $pdoRep = null;
        }

        return ['success' => true, 'idEnigma' => $idEnigma];

    } catch (PDOException $e) {
        error_log('AjouterEnigme error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'énigme.'];
    }
}

// -------------------------
// Get Random Enigma with Answers
// -------------------------
function GetEnigmeAleatoire(string $categorie = ''): array
{
    $pdo = get_pdo();
if ($pdo === false) return [];

    try {
        if ($categorie !== '' && in_array($categorie, ['F', 'M', 'D', 'G'], true)) {
            $stmt = $pdo->prepare('CALL EnigmeParCategorie(:cat)');
            $stmt->execute([':cat' => $categorie]);
        } else {
            $stmt = $pdo->query('CALL EnigmeAleatoire()');
        }

        $row = $stmt->fetch();
        while ($stmt->nextRowset()) {}
        try { $pdo->commit(); } catch (\Throwable $e) {}
        if (!$row) return [];

        $idEnigma = (int)$row['idEnigma'];

        $pdo2 = get_pdo();
        if ($pdo2 === false) return [];
        $stmt2 = $pdo2->prepare(
            'SELECT idReponses, reponse, estBonneReponse FROM Reponses WHERE idEnigma = :id'
        );
        $stmt2->execute([':id' => $idEnigma]);
        $reponses = $stmt2->fetchAll();

        if (empty($reponses)) return [];

        return [
            'idEnigma'    => $idEnigma,
            'enigme'      => $row['enigme'],
            'difficulte'  => $row['difficulte'] ?? $row['idCategorie'],
            'idCategorie' => $row['idCategorie'],
            'reponses'    => array_map(fn($r) => [
                'idReponse' => (int)$r['idReponses'],
                'reponse'   => $r['reponse'],
                'estBonne'  => (bool)$r['estBonneReponse'],
            ], $reponses),
        ];
    } catch (PDOException $e) {
        error_log('GetEnigmeAleatoire error: ' . $e->getMessage());
        return ['__error' => $e->getMessage()];
    }
}

// -------------------------
// Mage Progress (category G correct answers)
// -------------------------
function ProcessMageProgress(int $idJoueur): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $stmt = $pdo->prepare('SELECT quetesMagieComplete, estMage FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();

        if ((int)$row['estMage']) return false;

        $newCount = (int)$row['quetesMagieComplete'] + 1;
        $pdo->prepare('UPDATE Joueurs SET quetesMagieComplete = :c WHERE idJoueur = :id')
            ->execute([':c' => $newCount, ':id' => $idJoueur]);

        if ($newCount >= 3) {
            $pdo->prepare('UPDATE Joueurs SET estMage = 1 WHERE idJoueur = :id')
                ->execute([':id' => $idJoueur]);
            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log('ProcessMageProgress error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Flag Enigma as Seen (estPigee)
// -------------------------
function FlagEnigmePigee(int $idEnigma, string $idCategorie): void
{
    $pdo = get_pdo();
    if ($pdo === false) return;
    try {
        $pdo->prepare('UPDATE Enigma SET estPigee = 1 WHERE idEnigma = :id')
            ->execute([':id' => $idEnigma]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Enigma WHERE idCategorie = :cat AND estPigee = 0');
        $stmt->execute([':cat' => $idCategorie]);

        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->prepare('UPDATE Enigma SET estPigee = 0 WHERE idCategorie = :cat')
                ->execute([':cat' => $idCategorie]);
        }
    } catch (PDOException $e) {
        error_log('FlagEnigmePigee error: ' . $e->getMessage());
    }
}

// -------------------------
// Joueur Streak
// -------------------------
function GetJoueurStreak(int $idJoueur): int
{
    $pdo = get_pdo();
    if ($pdo === false) return 0;
    try {
        $stmt = $pdo->prepare('SELECT streak FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('GetJoueurStreak error: ' . $e->getMessage());
        return 0;
    }
}

function SetJoueurStreak(int $idJoueur, int $streak): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $val = max(0, $streak);
        $pdo->prepare('UPDATE Joueurs SET streak = :streak WHERE idJoueur = :id')
            ->execute([':streak' => $val, ':id' => $idJoueur]);
        try {
            $pdo->prepare('UPDATE Joueurs SET bestStreak = GREATEST(COALESCE(bestStreak, 0), :s) WHERE idJoueur = :id')
                ->execute([':s' => $val, ':id' => $idJoueur]);
        } catch (PDOException $e) { /* bestStreak column may not exist yet */ }
        return true;
    } catch (PDOException $e) {
        error_log('SetJoueurStreak error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Quest Statistics
// Requires: ALTER TABLE Joueurs ADD COLUMN IF NOT EXISTS bestStreak INT UNSIGNED NOT NULL DEFAULT 0;
// -------------------------
function GetQuestStats(int $idJoueur): array
{
    $pdo = get_pdo();
    $default = ['total' => 0, 'facile' => 0, 'moyen' => 0, 'difficile' => 0, 'magie' => 0, 'bestStreak' => 0];
    if (!$pdo) return $default;

    // Ensure career columns exist (profil.php may load before any enigma is played)
    static $colChecked2 = false;
    if (!$colChecked2) {
        foreach (['totalPlays', 'correctF', 'correctM', 'correctD', 'correctG'] as $col) {
            try {
                $exists = $pdo->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Joueurs'
                     AND COLUMN_NAME = '$col'"
                )->fetchColumn();
                if (!$exists) {
                    $pdo->exec("ALTER TABLE Joueurs ADD COLUMN $col INT UNSIGNED NOT NULL DEFAULT 0");
                }
            } catch (PDOException $e) { /* ignore */ }
        }
        $colChecked2 = true;
    }

    try {
        $s = $pdo->prepare('SELECT GREATEST(COALESCE(bestStreak, 0), COALESCE(streak, 0)) AS best FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $s->execute([':id' => $idJoueur]);
        $bestStreak = (int)($s->fetchColumn() ?: 0);
    } catch (PDOException $e) {
        try {
            $s = $pdo->prepare('SELECT COALESCE(streak, 0) FROM Joueurs WHERE idJoueur = :id LIMIT 1');
            $s->execute([':id' => $idJoueur]);
            $bestStreak = (int)($s->fetchColumn() ?: 0);
        } catch (PDOException $e2) {
            $bestStreak = 0;
        }
    }

    try {
        $s2 = $pdo->prepare(
            'SELECT COALESCE(correctF, 0) AS cF,
                    COALESCE(correctM, 0) AS cM,
                    COALESCE(correctD, 0) AS cD,
                    COALESCE(correctG, 0) AS cG
             FROM Joueurs WHERE idJoueur = :j LIMIT 1'
        );
        $s2->execute([':j' => $idJoueur]);
        $row = $s2->fetch();

        $facile    = (int)($row['cF'] ?? 0);
        $moyen     = (int)($row['cM'] ?? 0);
        $difficile = (int)($row['cD'] ?? 0);
        $magie     = (int)($row['cG'] ?? 0);
        $total     = $facile + $moyen + $difficile + $magie;

        return [
            'total'      => $total,
            'facile'     => $facile,
            'moyen'      => $moyen,
            'difficile'  => $difficile,
            'magie'      => $magie,
            'bestStreak' => $bestStreak,
        ];
    } catch (PDOException $e) {
        error_log('GetQuestStats error: ' . $e->getMessage());
        return array_merge($default, ['bestStreak' => $bestStreak]);
    }
}

// -------------------------
// Joueur Damage Modifier (weapon)
// -------------------------
function GetJoueurDamageModifier(int $idJoueur): float
{
    $pdo = get_pdo();
    if ($pdo === false) return 1.0;
    try {
        $stmt = $pdo->prepare('SELECT damageModifier FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 1.0;
    } catch (PDOException $e) {
        error_log('GetJoueurDamageModifier error: ' . $e->getMessage());
        return 1.0;
    }
}

// -------------------------
// Apply Enigma Damage (wrong answer)
// -------------------------
function PrendreDegatEnigme(int $idJoueur, int $degats): array
{
    $pdo = get_pdo();
    if ($pdo === false) return ['newHP' => 0, 'maxHP' => 100];
    try {
        $pdo->prepare(
            'UPDATE Joueurs SET pointDeVie = GREATEST(0, pointDeVie - :d) WHERE idJoueur = :id'
        )->execute([':d' => $degats, ':id' => $idJoueur]);
        $stmt = $pdo->prepare('SELECT pointDeVie, maxHP FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        return ['newHP' => (int)($row['pointDeVie'] ?? 0), 'maxHP' => (int)($row['maxHP'] ?? 100)];
    } catch (PDOException $e) {
        error_log('PrendreDegatEnigme error: ' . $e->getMessage());
        return ['newHP' => 0, 'maxHP' => 100];
    }
}

// -------------------------
// Joueur Gold Bonus (weapon %)
// -------------------------
function GetJoueurGoldBonus(int $idJoueur): int
{
    $pdo = get_pdo();
    if ($pdo === false) return 0;
    try {
        $stmt = $pdo->prepare('SELECT goldBonus FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('GetJoueurGoldBonus error: ' . $e->getMessage());
        return 0;
    }
}

// -------------------------
// Ajouter Pieces via SP (base reward)
// -------------------------
function AjouterPieces(string $difficulte, string $alias): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $stmt = $pdo->prepare('CALL AjouterPieces(:diff, :alias)');
        $stmt->execute([':diff' => $difficulte, ':alias' => $alias]);
        while ($stmt->nextRowset()) {}
        return true;
    } catch (PDOException $e) {
        error_log('AjouterPieces error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Ajouter Pieces Bonus (streak reward, direct)
// -------------------------
function AjouterPiecesBonus(int $idJoueur, int $montant, string $type): bool
{
    if (!in_array($type, ['gold', 'argent', 'bronze'], true)) return false;
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        $stmt = $pdo->prepare("UPDATE Joueurs SET `$type` = `$type` + :montant WHERE idJoueur = :id");
        $stmt->execute([':montant' => $montant, ':id' => $idJoueur]);
        return true;
    } catch (PDOException $e) {
        error_log('AjouterPiecesBonus error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Statistique: insert enigma result (INSERT IGNORE to skip duplicate attempts)
// -------------------------
function InsererStatistique(int $idJoueur, int $idQuestion, int $estReussi): bool
{
    $pdo = get_pdo();
    if ($pdo === false) return false;
    try {
        // Ensure totalPlays column exists
        static $colChecked = false;
        if (!$colChecked) {
            $exists = $pdo->query(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Joueurs'
                 AND COLUMN_NAME = 'totalPlays'"
            )->fetchColumn();
            if (!$exists) {
                $pdo->exec('ALTER TABLE Joueurs ADD COLUMN totalPlays INT UNSIGNED NOT NULL DEFAULT 0');
            }
            $colChecked = true;
        }

        // ON DUPLICATE KEY accumulates successes so repeated plays (after flag reset) are counted
        $stmt = $pdo->prepare('INSERT INTO Statistique (idJoueur, idQuestion, estReussi) VALUES (:j, :q, :r) ON DUPLICATE KEY UPDATE estReussi = estReussi + VALUES(estReussi)');
        $stmt->execute([':j' => $idJoueur, ':q' => $idQuestion, ':r' => $estReussi]);

        return true;
    } catch (PDOException $e) {
        error_log('InsererStatistique error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------
// Total enigmas in the game
// -------------------------
function GetTotalEnigmas(): int
{
    $pdo = get_pdo();
    if (!$pdo) return 0;
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM Enigma')->fetchColumn();
    } catch (PDOException $e) {
        error_log('GetTotalEnigmas error: ' . $e->getMessage());
        return 0;
    }
}

// -------------------------
// Statistique: get player enigma stats
// -------------------------
// Leaderboard: top N players by MMR then rang
// -------------------------
function GetLeaderboard(int $limit = 12): array
{
    $pdo = get_pdo();
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare(
            'SELECT alias, COALESCE(rang,0) AS rang, COALESCE(lp,0) AS lp, COALESCE(mmr,0) AS mmr
             FROM Joueurs
             ORDER BY mmr DESC, rang DESC, lp DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('GetLeaderboard error: ' . $e->getMessage());
        return [];
    }
}

// -------------------------
function GetEnigmaStats(int $idJoueur): array
{
    $pdo = get_pdo();
    if ($pdo === false) return ['total' => 0, 'reussies' => 0, 'ratees' => 0, 'taux' => 0, 'totalPlays' => 0];
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(estReussi > 0) AS reussies FROM Statistique WHERE idJoueur = :j');
        $stmt->execute([':j' => $idJoueur]);
        $row      = $stmt->fetch();
        $total    = (int) ($row['total']    ?? 0);
        $reussies = (int) ($row['reussies'] ?? 0);
        $ratees   = $total - $reussies;
        $taux     = $total > 0 ? (int) round($reussies / $total * 100) : 0;

        // Career play count (includes replays of the same question)
        $totalPlays = 0;
        try {
            $s2 = $pdo->prepare('SELECT COALESCE(totalPlays, 0) FROM Joueurs WHERE idJoueur = :id LIMIT 1');
            $s2->execute([':id' => $idJoueur]);
            $totalPlays = (int) $s2->fetchColumn();
        } catch (PDOException $e) { /* column may not exist yet */ }

        return compact('total', 'reussies', 'ratees', 'taux', 'totalPlays');
    } catch (PDOException $e) {
        error_log('GetEnigmaStats error: ' . $e->getMessage());
        return ['total' => 0, 'reussies' => 0, 'ratees' => 0, 'taux' => 0, 'totalPlays' => 0];
    }
}

function getRankName(int $rang): string
{
    // 0→14: low→high, drawing from R6, Valorant, LoL, Rocket League, CS2
    return [
        'Fer',           // 0  – Iron           (Valorant / LoL)        — absolute bottom
        'Cuivre',        // 1  – Copper         (Rainbow Six Siege)     — low
        'Bronze',        // 2  – Bronze         (all games)             — low
        'Argent',        // 3  – Silver         (all games)             — low-mid
        'Or',            // 4  – Gold           (all games)             — mid
        'Platine',       // 5  – Platinum       (Valorant / LoL / R6)   — mid
        'Émeraude',      // 6  – Emerald        (LoL)                   — mid-high
        'Diamant',       // 7  – Diamond        (all games)             — high
        'Champion',      // 8  – Champion       (R6 / Rocket League)    — high
        'Maître',        // 9  – Master         (LoL / Valorant)        — high
        'Grand Maître',  // 10 – Grandmaster    (LoL / R6 Champion)     — elite
        'Grand Champion',// 11 – Grand Champion (Rocket League)         — elite
        'Ascendant',     // 12 – Ascendant      (Valorant)              — near-top
        'Immortel',      // 13 – Immortal       (Valorant)              — near-top
        'Radiant',       // 14 – Radiant/Chall. (Valorant / LoL)        — absolute top
    ][$rang] ?? 'Radiant';
}

function getRankColor(int $rang): string
{
    return [
        '#7f8c8d', // Fer           – dull iron grey
        '#a0694a', // Cuivre        – copper brown
        '#cd7f32', // Bronze        – bronze
        '#bdc3c7', // Argent        – silver
        '#f1c40f', // Or            – gold
        '#4ecdc4', // Platine       – teal
        '#2ecc71', // Émeraude      – emerald green
        '#00bfff', // Diamant       – icy blue
        '#e67e22', // Champion      – warm orange (R6 champion vibe)
        '#9b59b6', // Maître        – purple
        '#d35400', // Grand Maître  – deep orange
        '#e74c3c', // Grand Champion– fiery red (RL Grand Champ)
        '#ff85c8', // Ascendant     – Valorant pink
        '#c0392b', // Immortel      – deep crimson
        '#ffe066', // Radiant       – bright gold/white-gold
    ][$rang] ?? '#ffe066';
}

function _ensureRankedColumns(PDO $pdo): void
{
    try {
        $existing = $pdo->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Joueurs'
             AND COLUMN_NAME IN ('rang','lp','mmr')"
        )->fetchAll(PDO::FETCH_COLUMN);

        $missing = array_diff(['rang', 'lp', 'mmr'], $existing);
        if (empty($missing)) return;

        $defs = [
            'rang' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
            'lp'   => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            'mmr'  => 'INT UNSIGNED NOT NULL DEFAULT 1000',
        ];
        foreach ($missing as $col) {
            $pdo->exec("ALTER TABLE Joueurs ADD COLUMN $col {$defs[$col]}");
        }
    } catch (PDOException $e) {
        error_log('_ensureRankedColumns error: ' . $e->getMessage());
    }
}

function GetRankedData(int $idJoueur): array
{
    $pdo = get_pdo();
    if (!$pdo) return ['rang' => 0, 'lp' => 0, 'mmr' => 1000];
    _ensureRankedColumns($pdo);
    try {
        $s = $pdo->prepare('SELECT rang, lp, mmr FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $s->execute([':id' => $idJoueur]);
        $row = $s->fetch();
        return [
            'rang' => (int)($row['rang'] ?? 0),
            'lp'   => (int)($row['lp']   ?? 0),
            'mmr'  => (int)($row['mmr']  ?? 1000),
        ];
    } catch (PDOException $e) {
        error_log('GetRankedData error: ' . $e->getMessage());
        return ['rang' => 0, 'lp' => 0, 'mmr' => 1000];
    }
}

// LP/MMR per difficulty — harder = more LP per correct answer.
// ~50 F correct = rank up | ~25 M | ~15 D | ~10 G
// At max rank (Radiant) LP is frozen at 100; only MMR keeps climbing.
function _rankedDiffStats(string $categorie): array
{
    return match(strtoupper($categorie)) {
        'M'     => ['lpGain' => 4,  'lpLoss' => 3, 'mmrGain' => 7,  'mmrLoss' => 5],
        'D'     => ['lpGain' => 7,  'lpLoss' => 4, 'mmrGain' => 11, 'mmrLoss' => 7],
        'G'     => ['lpGain' => 10, 'lpLoss' => 5, 'mmrGain' => 15, 'mmrLoss' => 10],
        default => ['lpGain' => 2,  'lpLoss' => 2, 'mmrGain' => 4,  'mmrLoss' => 3],
    };
}

function ProcessRanked(int $idJoueur, bool $correct, string $categorie, int $winStreak, int $lossStreak): array
{
    $pdo = get_pdo();
    if (!$pdo) return [];
    _ensureRankedColumns($pdo);
    try {
        $s = $pdo->prepare('SELECT rang, lp, mmr FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $s->execute([':id' => $idJoueur]);
        $row  = $s->fetch();
        $rang = (int)($row['rang'] ?? 0);
        $lp   = (int)($row['lp']   ?? 0);
        $mmr  = (int)($row['mmr']  ?? 1000);

        $diff = _rankedDiffStats($categorie);

        $isMaxRank = ($rang >= 14);

        if ($correct) {
            $streakBonus = min(3, (int) floor($winStreak / 5));
            $lpChange    = $diff['lpGain'] + $streakBonus;
            $mmrChange   = $diff['mmrGain'];
            $mmr         = min(99999, $mmr + $mmrChange);
        } else {
            $lpChange  = -$diff['lpLoss'];
            $mmrChange = -$diff['mmrLoss'];
            $mmr       = max(500, $mmr + $mmrChange);
        }

        $rankChange = null;

        if ($isMaxRank) {
            // At max rank LP is locked at 100 — only MMR matters
            $lp      = 100;
            $lpChange = 0;
        } else {
            $lp += $lpChange;

            if ($lp >= 100) {
                $rang++;
                $lp = 0;
                $rankChange = 'up';
            }

            if ($lp < 0) {
                if ($rang > 0) {
                    $rang--;
                    $lp = 75;
                    $rankChange = 'down';
                } else {
                    $lp = 0;
                }
            }
        }

        $pdo->prepare('UPDATE Joueurs SET rang = :rang, lp = :lp, mmr = :mmr WHERE idJoueur = :id')
            ->execute([':rang' => $rang, ':lp' => $lp, ':mmr' => $mmr, ':id' => $idJoueur]);

        return compact('rang', 'lp', 'mmr', 'lpChange', 'mmrChange', 'rankChange');
    } catch (PDOException $e) {
        error_log('ProcessRanked error: ' . $e->getMessage());
        return [];
    }
}

// -------------------------
// Admin: all items (quantity + price)
// -------------------------
function GetAllItemsForAdmin(): array
{
    $pdo = get_pdo();
    if (!$pdo) return [];
    try {
        return $pdo->query(
            'SELECT idItem, nom, quantite, prix, typeItem FROM Items ORDER BY typeItem, nom'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('GetAllItemsForAdmin: ' . $e->getMessage());
        return [];
    }
}

function UpdateItemShop(int $idItem, int $quantite, int $prix): array
{
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur BD.'];
    try {
        $pdo->prepare('UPDATE Items SET quantite = :q, prix = :p WHERE idItem = :id')
            ->execute([':q' => $quantite, ':p' => $prix, ':id' => $idItem]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('UpdateItemShop: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// -------------------------
// Admin: sort types (heal editing)
// -------------------------
function GetSortTypesForAdmin(): array
{
    $pdo = get_pdo();
    if (!$pdo) return [];
    try {
        return $pdo->query(
            'SELECT ts.typeSorts, ts.Description, ts.ptVie, COUNT(s.idItem) AS nbItems
             FROM TypeSorts ts
             LEFT JOIN Sorts s ON s.typeSorts = ts.typeSorts
             GROUP BY ts.typeSorts, ts.Description, ts.ptVie
             ORDER BY ts.typeSorts'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('GetSortTypesForAdmin: ' . $e->getMessage());
        return [];
    }
}

function UpdateSortHeal(string $typeSorts, int $ptVie): array
{
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur BD.'];
    try {
        $pdo->prepare('UPDATE TypeSorts SET ptVie = :v WHERE typeSorts = :t')
            ->execute([':v' => $ptVie, ':t' => $typeSorts]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('UpdateSortHeal: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// -------------------------
// Admin: potions (heal % editing) — auto-migrates healPct column
// -------------------------
function _ensurePotionHealPct(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $exists = $pdo->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Potions' AND COLUMN_NAME = 'healPct'"
    )->fetchColumn();
    if (!$exists) {
        $pdo->exec('ALTER TABLE Potions ADD COLUMN healPct INT UNSIGNED NOT NULL DEFAULT 0');
        $init = $pdo->prepare('UPDATE Potions SET healPct = :h WHERE idItem = :id');
        foreach ($pdo->query('SELECT idItem, effet FROM Potions')->fetchAll() as $row) {
            $init->execute([':h' => getPotionHealPct((string)$row['effet']), ':id' => $row['idItem']]);
        }
    }
    $done = true;
}

function GetPotionsForAdmin(): array
{
    $pdo = get_pdo();
    if (!$pdo) return [];
    try {
        _ensurePotionHealPct($pdo);
        return $pdo->query(
            'SELECT p.idItem, i.nom, p.effet, COALESCE(NULLIF(p.healPct,0), 15) AS healPct
             FROM Potions p JOIN Items i ON i.idItem = p.idItem ORDER BY i.nom'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('GetPotionsForAdmin: ' . $e->getMessage());
        return [];
    }
}

function UpdatePotionHeal(int $idItem, int $healPct): array
{
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur BD.'];
    try {
        _ensurePotionHealPct($pdo);
        $pdo->prepare('UPDATE Potions SET healPct = :h WHERE idItem = :id')
            ->execute([':h' => $healPct, ':id' => $idItem]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('UpdatePotionHeal: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// -------------------------
// Admin: add new item (Items + subtype table, in a transaction)
// -------------------------
function AddItemToShop(string $nom, int $quantite, int $prix, string $typeItem, string $image, bool $estDisponible, array $sub): array
{
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur BD.'];
    try {
        $pdo->beginTransaction();

        $pdo->prepare(
            'INSERT INTO Items (nom, quantite, prix, typeItem, image, estDisponible)
             VALUES (:nom, :q, :p, :type, :img, :disp)'
        )->execute([
            ':nom'  => $nom,
            ':q'    => $quantite,
            ':p'    => $prix,
            ':type' => $typeItem,
            ':img'  => $image,
            ':disp' => $estDisponible ? 1 : 0,
        ]);
        $idItem = (int) $pdo->lastInsertId();

        switch ($typeItem) {
            case 'A':
                $pdo->prepare('INSERT INTO Armes (idItem, efficacite, genre, description) VALUES (:id, :e, :g, :d)')
                    ->execute([':id' => $idItem, ':e' => $sub['efficacite'], ':g' => $sub['genre'], ':d' => $sub['description']]);
                break;
            case 'R':
                $pdo->prepare('INSERT INTO Armures (idItem, matiere, taille) VALUES (:id, :m, :t)')
                    ->execute([':id' => $idItem, ':m' => $sub['matiere'], ':t' => $sub['taille']]);
                break;
            case 'P':
                _ensurePotionHealPct($pdo);
                $pdo->prepare('INSERT INTO Potions (idItem, effet, duree, healPct) VALUES (:id, :e, :d, :h)')
                    ->execute([':id' => $idItem, ':e' => $sub['effet'], ':d' => (int)$sub['duree'], ':h' => (int)$sub['healPct']]);
                break;
            case 'S':
                $pdo->prepare('INSERT INTO Sorts (idItem, typeSorts, rarete, estInstantane) VALUES (:id, :t, :r, :inst)')
                    ->execute([':id' => $idItem, ':t' => $sub['typeSorts'], ':r' => (int)$sub['rarete'], ':inst' => $sub['estInstantane'] ? 1 : 0]);
                break;
        }

        $pdo->commit();
        return ['success' => true, 'idItem' => $idItem];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('AddItemToShop: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// -------------------------
// Vérifier le mot de passe actuel + valider le nouveau (sans appliquer le changement)
// -------------------------
function ChangerMotDePasseVerify(int $idJoueur, string $motDePasseActuel, string $nouveauMDP): array
{
    if (strlen($nouveauMDP) < 6) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.'];
    }
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $stmt = $pdo->prepare('SELECT motDePasse FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return ['success' => false, 'message' => 'Joueur introuvable.'];
        if (!password_verify($motDePasseActuel, $row['motDePasse'])) {
            return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
        }
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('ChangerMotDePasseVerify error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur de vérification.'];
    }
}

// -------------------------
// Changer le mot de passe (utilisateur connecté, mot de passe actuel requis)
// -------------------------
function ChangerMotDePasse(int $idJoueur, string $motDePasseActuel, string $nouveauMDP): array
{
    if (strlen($nouveauMDP) < 6) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.'];
    }
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $stmt = $pdo->prepare('SELECT motDePasse FROM Joueurs WHERE idJoueur = :id LIMIT 1');
        $stmt->execute([':id' => $idJoueur]);
        $row = $stmt->fetch();
        if (!$row) return ['success' => false, 'message' => 'Joueur introuvable.'];
        if (!password_verify($motDePasseActuel, $row['motDePasse'])) {
            return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
        }
        $hash = password_hash($nouveauMDP, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE Joueurs SET motDePasse = :mdp WHERE idJoueur = :id')
            ->execute([':mdp' => $hash, ':id' => $idJoueur]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('ChangerMotDePasse error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe.'];
    }
}

// -------------------------
// Réinitialiser le mot de passe (flux mot de passe oublié, sans mot de passe actuel)
// -------------------------
function ResetMotDePasse(int $idJoueur, string $nouveauMDP): array
{
    if (strlen($nouveauMDP) < 6) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    }
    $pdo = get_pdo();
    if (!$pdo) return ['success' => false, 'message' => 'Erreur de connexion BD.'];
    try {
        $hash = password_hash($nouveauMDP, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE Joueurs SET motDePasse = :mdp WHERE idJoueur = :id')
            ->execute([':mdp' => $hash, ':id' => $idJoueur]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('ResetMotDePasse error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la réinitialisation.'];
    }
}
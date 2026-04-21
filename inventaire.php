<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

// Handle sell action (AJAX POST — returns JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sell') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        echo json_encode(['success' => false, 'stage' => 'auth', 'message' => 'Non connecté']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $idItem = (int) ($_POST['idItem'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 1);

    if ($idItem <= 0 || $quantite <= 0) {
        echo json_encode([
            'success' => false,
            'stage' => 'validation',
            'message' => 'Paramètres invalides',
            'idItem' => $idItem,
            'quantite' => $quantite,
        ]);
        exit;
    }

    $result = VendreItem($userId, $idItem, $quantite);

    if ($result['success']) {
        $coins = GetJoueurCoins($userId);
        $_SESSION['gold'] = $coins['gold'];
        $_SESSION['argent'] = $coins['argent'];
        $_SESSION['bronze'] = $coins['bronze'];
    }

    echo json_encode(array_merge($result, [
        'stage' => 'complete',
        'userId' => $userId,
        'idItem' => $idItem,
        'quantite' => $quantite,
        'newGold' => (int) ($_SESSION['gold'] ?? 0),
    ]));
    exit;
}

// Handle use action (AJAX POST — returns JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'use') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        echo json_encode(['success' => false, 'message' => 'Non connecté']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $idItem = (int) ($_POST['idItem'] ?? 0);

    if ($idItem <= 0) {
        echo json_encode(['success' => false, 'message' => 'Paramètre invalide']);
        exit;
    }

    $type = GetItemType($idItem);
    $typeCode = strtoupper(trim((string) ($type['typeItem'] ?? '')));

    if ($typeCode === 'R' || $typeCode === 'ARMURE') {
        $result = EquiperArmure($userId, $idItem);
        if ($result['success']) {
            $_SESSION['maxHP'] = $result['maxHP'];
            $_SESSION['pointDeVie'] = $result['pointDeVie'];
        }
        echo json_encode(array_merge($result, [
            'itemType' => 'armure',
            'newMaxHP' => (int) ($_SESSION['maxHP'] ?? 100),
            'newPV' => (int) ($_SESSION['pointDeVie'] ?? 0),
        ]));
    } elseif ($typeCode === 'A' || $typeCode === 'ARME') {
        $result = EquiperArme($userId, $idItem);
        echo json_encode(array_merge($result, ['itemType' => 'arme']));
    } elseif ($typeCode === 'P' || $typeCode === 'POTION') {
        $result = UtiliserPotion($userId, $idItem);
        if ($result['success']) {
            $_SESSION['pointDeVie'] = $result['newPV'];
            $_SESSION['maxHP'] = $result['maxHP'];
        }
        echo json_encode(array_merge($result, [
            'itemType' => 'potion',
            'newMaxHP' => (int) ($_SESSION['maxHP'] ?? 100),
            'newPV' => (int) ($_SESSION['pointDeVie'] ?? 0),
        ]));
    } else {
        echo json_encode(['success' => false, 'message' => 'Cet item n\'est pas encore utilisable.']);
    }
    exit;
}

$flashMsg = null;
$flashType = null;
if (isset($_SESSION['inv_flash'])) {
    $flashMsg = $_SESSION['inv_flash']['msg'];
    $flashType = $_SESSION['inv_flash']['type'];
    unset($_SESSION['inv_flash']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Inventaire</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Inventaire</h1>

        <?php if ($flashMsg): ?>
            <div class="inv-flash inv-flash--<?= $flashType ?>">
                <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <p class="locked-message">Accès réservé aux utilisateurs connectés.
                <a href="index.php">Retour à l'accueil</a>
            </p>
            <p><a class="btnAutre" href="index.php">Accueil</a></p>

        <?php else: ?>
            <?php
            $userId = (int) $_SESSION['user_id'];
            $items = AfficherInventaire($userId);
            $armureEquipee = GetArmureEquipee($userId);
            $equippedArmorId = $armureEquipee ? (int) $armureEquipee['idItem'] : null;
            $armeEquipee = GetArmeEquipee($userId);
            $equippedWeaponId = $armeEquipee ? (int) $armeEquipee['idItem'] : null;
            $playerHP = GetJoueurHP($userId);
            $playerMaxHP = $playerHP['maxHP'];
            $playerHealBonus = 0;
            $playerPV = $playerHP['pointDeVie'];
            $pdoTmp = get_pdo();
            if ($pdoTmp) {
                try {
                    $sTmp = $pdoTmp->prepare('SELECT healBonus FROM Joueurs WHERE idJoueur = :id LIMIT 1');
                    $sTmp->execute([':id' => $userId]);
                    $rTmp = $sTmp->fetch();
                    $playerHealBonus = (int) ($rTmp['healBonus'] ?? 0);
                } catch (PDOException $e) {
                }
            }
            ?>

            <?php if (empty($items)): ?>
                <p class="panier-empty">Votre inventaire est vide.
                    <a href="magasin.php">Visiter le magasin</a>
                </p>

            <?php else: ?>
                <div class="shop-filters">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Rechercher un item" class="search-input">
                    </div>
                    <div class="checkboxes">
                        <label class="filter-checkbox"><input type="checkbox" name="type" value="Arme"
                                onchange="applyFilters()"> Arme</label>
                        <label class="filter-checkbox"><input type="checkbox" name="type" value="Armure"
                                onchange="applyFilters()"> Armure</label>
                        <label class="filter-checkbox"><input type="checkbox" name="type" value="Potion"
                                onchange="applyFilters()"> Potion</label>
                        <label class="filter-checkbox"><input type="checkbox" name="type" value="Sort"
                                onchange="applyFilters()"> Sort</label>
                    </div>
                    <div class="radioButtons">
                        <label class="filter-radio"><input type="radio" name="sort" value="no_sort" onchange="applyFilters()"
                                checked> Aucun tri</label>
                        <label class="filter-radio"><input type="radio" name="sort" value="price_asc" onchange="applyFilters()">
                            Prix croissant</label>
                        <label class="filter-radio"><input type="radio" name="sort" value="price_desc"
                                onchange="applyFilters()"> Prix décroissant</label>
                    </div>
                </div>

                <div class="item-grid-4" id="inventaireGrid"
                    style="display:flex; flex-wrap:wrap; gap:16px; justify-content:center;">
                    <?php foreach ($items as $item):
                        $idItem = (int) $item['idItem'];
                        $nom = htmlspecialchars($item['nom']);
                        $qte = (int) $item['quantiteInvenatire'];
                        $prix = (int) $item['prix'];
                        $image = htmlspecialchars($item['image']);
                        $typeCode = strtoupper(trim((string) $item['typeItem']));
                        $isEquipped = (($typeCode === 'R' || $typeCode === 'ARMURE') && $equippedArmorId === $idItem)
                            || (($typeCode === 'A' || $typeCode === 'ARME') && $equippedWeaponId === $idItem);

                        if ($typeCode === 'A' || $typeCode === 'ARME')
                            $typeLabel = 'Arme';
                        elseif ($typeCode === 'R' || $typeCode === 'ARMURE')
                            $typeLabel = 'Armure';
                        elseif ($typeCode === 'P' || $typeCode === 'POTION')
                            $typeLabel = 'Potion';
                        elseif ($typeCode === 'S' || $typeCode === 'SORT')
                            $typeLabel = 'Sort';
                        elseif ($typeCode !== '')
                            $typeLabel = ucfirst(strtolower($typeCode));
                        else
                            $typeLabel = 'Autre';

                        if ($typeCode === 'S' || $typeCode === 'SORT') {
                            $rarete = (int) $item['rarete'];
                            if ($rarete === 2) {
                                $resellRate = 0.95;
                                $resellLabel = '(-5%)';
                                $resellColor = '#f3c9ad';
                            } elseif ($rarete === 3) {
                                $resellRate = 0.90;
                                $resellLabel = '(-10%)';
                                $resellColor = '#f3c9ad';
                            } else {
                                $resellRate = 1.00;
                                $resellLabel = '(100%)';
                                $resellColor = '#adf3ad';
                            }
                        } else {
                            $resellRate = 0.60;
                            $resellLabel = '(-40%)';
                            $resellColor = '#f3c9ad';
                        }
                        $resellPrix = (int) round($prix * $resellRate);

                        $isUsable = in_array($typeCode, ['A', 'ARME', 'R', 'ARMURE', 'P', 'POTION', 'S', 'SORT']);

                        // Armor sub-stats
                        $armorStats = null;
                        if (($typeCode === 'R' || $typeCode === 'ARMURE') && !empty($item['taille']) && !empty($item['matiere'])) {
                            $armorStats = getArmorStats($item['taille'], $item['matiere']);
                        }

                        // Weapon sub-stats
                        $weaponStats = null;
                        if (($typeCode === 'A' || $typeCode === 'ARME') && !empty($item['efficacite']) && !empty($item['genre'])) {
                            $weaponStats = getWeaponStats($item['efficacite'], $item['genre']);
                        }

                        // Potion sub-stats
                        $potionHealPct = null;
                        if (($typeCode === 'P' || $typeCode === 'POTION') && !empty($item['effet'])) {
                            $potionHealPct = getPotionHealPct($item['effet']);
                        }
                        ?>
                        <div class="itemBox" id="item-<?= $idItem ?>" data-type="<?= htmlspecialchars($typeLabel, ENT_QUOTES) ?>"
                            data-price="<?= $prix ?>" data-name="<?= htmlspecialchars(strtolower($item['nom']), ENT_QUOTES) ?>"
                            data-order="<?= $idItem ?>">
                            <div class="item-img-wrapper">
                                <img class="item-img" src="<?= $image ?>" alt="<?= $nom ?>">
                            </div>
                            <div class="item-info">
                                <h3 class="titre">
                                    <?= $nom ?>
                                    <?php if ($isEquipped): ?>
                                        <span class="armor-badge-equipped">Équipée</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="item-type">Type : <?= htmlspecialchars($typeLabel) ?></p>
                                <?php if ($armorStats !== null): ?>
                                    <p class="armor-stats">
                                        <?= htmlspecialchars(ucfirst(strtolower($item['taille']))) ?>
                                        · <?= htmlspecialchars(ucfirst(strtolower($item['matiere']))) ?>
                                    </p>
                                    <p class="armor-stats">
                                        +<?= $armorStats['maxHP'] ?> HP max
                                        &nbsp;|&nbsp;
                                        <?= $armorStats['heal'] >= 0 ? '+' : '' ?>                <?= $armorStats['heal'] ?>% soin
                                    </p>
                                <?php endif; ?>
                                <?php if ($weaponStats !== null): ?>
                                    <p class="armor-stats">
                                        <?= htmlspecialchars(ucfirst(strtolower($item['genre']))) ?>
                                        · <?= htmlspecialchars(ucfirst(strtolower($item['efficacite']))) ?>
                                    </p>
                                    <p class="armor-stats">
                                        <?= $weaponStats['goldBonus'] >= 0 ? '+' : '' ?>                <?= $weaponStats['goldBonus'] ?>% gold
                                        &nbsp;|&nbsp;
                                        <?php
                                        $dmg = (float) $weaponStats['damageModifier'];
                                        if ($dmg < 1.0)
                                            echo 'Dégâts Prit /2';
                                        elseif ($dmg > 1.0)
                                            echo 'Dégâts Prit ×2';
                                        else
                                            echo 'Dégâts Prit normaux';
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($potionHealPct !== null): ?>
                                    <?php
                                    $baseHeal = (int) round($playerMaxHP * $potionHealPct / 100);
                                    $realHeal = (int) round($baseHeal * (1 + $playerHealBonus / 100));
                                    $realHeal = max(1, $realHeal);
                                    ?>
                                    <p class="armor-stats">
                                        <?= htmlspecialchars(ucfirst(strtolower($item['effet']))) ?>
                                    </p>
                                    <p class="armor-stats">
                                        Restaure ~<?= $realHeal ?> HP
                                        <?php if ($playerHealBonus !== 0): ?>
                                            <span style="color:<?= $playerHealBonus > 0 ? '#adf3ad' : '#f3adad' ?>;">
                                                (<?= $playerHealBonus > 0 ? '+' : '' ?><?= $playerHealBonus ?>% soin)
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <p class="description">Quantité : <?= $qte ?></p>
                                <p class="prixOr"><?= number_format($prix, 0, '', '') ?> gold</p>
                                <p class="item-resell" style="color: <?= $resellColor ?>;">
                                    Revente : <?= number_format($resellPrix, 0, '', '') ?> gold / unité
                                    <?= $resellLabel ?>
                                </p>
                            </div>
                            <?php if ($isUsable): ?>
                                <button type="button" class="btn-utiliser use-btn" data-id="<?= $idItem ?>" <?= $isEquipped ? 'disabled title="Déjà équipée"' : '' ?>>
                                    <?= in_array($typeCode, ['R', 'ARMURE', 'A', 'ARME']) ? 'Équiper' : 'Utiliser' ?>
                                </button>
                            <?php endif; ?>
                            <div class="btnPanier">
                                <form method="POST" action="inventaire.php" class="sell-form">
                                    <input type="hidden" name="action" value="sell">
                                    <input type="hidden" name="idItem" value="<?= $idItem ?>">
                                    <button type="submit" class="btnVendreImg-btn">
                                        <img src="img/removeFromInv.png" class="btnVendreImg" alt="Vendre">
                                    </button>
                                    <div class="sell-qty-control">
                                        <button type="button" class="qty-btn qty-minus">−</button>
                                        <input type="text" inputmode="numeric" name="quantite" class="qty-input" value="1"
                                            data-min="1" data-max="<?= $qte ?>">
                                        <button type="button" class="qty-btn qty-plus">+</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p id="noResults" style="display:none; opacity:0.5; margin-top:2rem;">Aucun objet trouvé.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php require 'include/footer.php'; ?>
    <script>
        // +/- quantity controls
        document.querySelectorAll('.sell-qty-control').forEach(function (ctrl) {
            const input = ctrl.querySelector('.qty-input');
            ctrl.querySelector('.qty-minus').addEventListener('click', function () {
                const min = parseInt(input.dataset.min) || 1;
                input.value = Math.max(min, (parseInt(input.value) || 1) - 1);
            });
            ctrl.querySelector('.qty-plus').addEventListener('click', function () {
                const max = parseInt(input.dataset.max) || 999;
                input.value = Math.min(max, (parseInt(input.value) || 1) + 1);
            });
        });

        // Sell form — fetch-based
        document.querySelectorAll('.sell-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                fetch('inventaire.php', { method: 'POST', body: new FormData(form) })
                    .then(function (res) {
                        if (!res.ok) {
                            return res.text().then(function (t) {
                                throw new Error('HTTP ' + res.status + ': ' + t.slice(0, 200));
                            });
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            const goldEl = document.querySelector('.coins .gold');
                            if (goldEl) goldEl.textContent = parseInt(data.newGold, 10);
                            showFlash('Vendu ! +' + data.gold + ' gold', 'success');
                            setTimeout(function () { saveFilterState(); location.reload(); }, 1400);
                        } else {
                            showFlash(data.message || 'Erreur inconnue', 'error');
                        }
                    })
                    .catch(function () {
                        showFlash('Erreur réseau', 'error');
                    });
            });
        });

        // Use / Equip button
        document.querySelectorAll('.use-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const idItem = btn.dataset.id;
                const fd = new FormData();
                fd.append('action', 'use');
                fd.append('idItem', idItem);

                btn.disabled = true;

                fetch('inventaire.php', { method: 'POST', body: fd })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            const hpBar = document.querySelector('.hpBar');
                            const hpText = document.querySelector('.hpText');
                            if (data.itemType === 'armure') {
                                if (hpBar && hpText && data.newMaxHP > 0) {
                                    const pct = Math.round(data.newPV / data.newMaxHP * 100);
                                    hpBar.style.width = pct + '%';
                                    hpText.textContent = 'PV: ' + data.newPV + '/' + data.newMaxHP;
                                }
                                showFlash('Armure équipée !', 'success');
                            } else if (data.itemType === 'arme') {
                                showFlash('Arme équipée !', 'success');
                            } else if (data.itemType === 'potion') {
                                if (hpBar && hpText && data.newMaxHP > 0) {
                                    const pct = Math.round(data.newPV / data.newMaxHP * 100);
                                    hpBar.style.width = pct + '%';
                                    hpText.textContent = 'PV: ' + data.newPV + '/' + data.newMaxHP;
                                }
                                showFlash('+' + data.healed + ' HP restaurés !', 'success');
                            } else {
                                showFlash('Utilisé !', 'success');
                            }
                            setTimeout(function () { saveFilterState(); location.reload(); }, 1400);
                        } else {
                            btn.disabled = false;
                            showFlash(data.message || 'Erreur inconnue', 'error');
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        showFlash('Erreur réseau', 'error');
                    });
            });
        });

        // Filter state persistence
        function saveFilterState() {
            const state = {
                search: document.getElementById('searchInput')?.value ?? '',
                types: [...document.querySelectorAll('input[name="type"]:checked')].map(cb => cb.value),
                sort: document.querySelector('input[name="sort"]:checked')?.value ?? 'no_sort',
            };
            sessionStorage.setItem('inv_filters', JSON.stringify(state));
        }

        function restoreFilterState() {
            const raw = sessionStorage.getItem('inv_filters');
            if (!raw) return;
            sessionStorage.removeItem('inv_filters');
            try {
                const state = JSON.parse(raw);
                const searchEl = document.getElementById('searchInput');
                if (searchEl && state.search) searchEl.value = state.search;
                (state.types || []).forEach(function (val) {
                    const cb = document.querySelector('input[name="type"][value="' + val + '"]');
                    if (cb) cb.checked = true;
                });
                if (state.sort) {
                    const radio = document.querySelector('input[name="sort"][value="' + state.sort + '"]');
                    if (radio) radio.checked = true;
                }
                applyFilters();
            } catch (e) { }
        }

        // Search & filter
        function applyFilters() {
            const query = document.getElementById('searchInput')?.value.toLowerCase().trim() ?? '';
            const checkedTypes = [...document.querySelectorAll('input[name="type"]:checked')].map(cb => cb.value);
            const sortValue = document.querySelector('input[name="sort"]:checked')?.value ?? 'no_sort';
            const grid = document.getElementById('inventaireGrid');
            if (!grid) return;
            const cards = [...grid.querySelectorAll('.itemBox')];

            cards.forEach(card => {
                const matchesSearch = !query || card.dataset.name.includes(query);
                const matchesType = checkedTypes.length === 0 || checkedTypes.includes(card.dataset.type);
                card.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });

            const visible = cards.filter(c => c.style.display !== 'none');
            if (sortValue !== 'no_sort') {
                visible.sort((a, b) => sortValue === 'price_asc'
                    ? a.dataset.price - b.dataset.price
                    : b.dataset.price - a.dataset.price
                ).forEach(card => grid.appendChild(card));
            } else {
                visible.sort((a, b) => parseInt(a.dataset.order) - parseInt(b.dataset.order))
                    .forEach(card => grid.appendChild(card));
            }

            const noResults = document.getElementById('noResults');
            if (noResults) noResults.style.display = visible.length === 0 ? 'block' : 'none';
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.addEventListener('input', applyFilters);

        restoreFilterState();

        function showFlash(msg, type) {
            let el = document.getElementById('inv-flash-msg');
            if (!el) {
                el = document.createElement('div');
                el.id = 'inv-flash-msg';
                document.querySelector('main h1').after(el);
            }
            el.className = 'inv-flash inv-flash--' + type;
            el.textContent = msg;
            el.style.display = '';
        }
    </script>
</body>

</html>
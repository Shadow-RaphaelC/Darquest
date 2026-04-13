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

    $userId   = (int) $_SESSION['user_id'];
    $idItem   = (int) ($_POST['idItem'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 1);

    if ($idItem <= 0 || $quantite <= 0) {
        echo json_encode([
            'success'  => false,
            'stage'    => 'validation',
            'message'  => 'Paramètres invalides',
            'idItem'   => $idItem,
            'quantite' => $quantite,
        ]);
        exit;
    }

    $result = VendreItem($userId, $idItem, $quantite);

    if ($result['success']) {
        $coins = GetJoueurCoins($userId);
        $_SESSION['gold']   = $coins['gold'];
        $_SESSION['argent'] = $coins['argent'];
        $_SESSION['bronze'] = $coins['bronze'];
    }

    echo json_encode(array_merge($result, [
        'stage'    => 'complete',
        'userId'   => $userId,
        'idItem'   => $idItem,
        'quantite' => $quantite,
        'newGold'  => $_SESSION['gold'] ?? 0,
    ]));
    exit;
}

$flashMsg  = null;
$flashType = null;
if (isset($_SESSION['inv_flash'])) {
    $flashMsg  = $_SESSION['inv_flash']['msg'];
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
            $items  = AfficherInventaire($userId);
            ?>

            <?php if (empty($items)): ?>
                <p class="panier-empty">Votre inventaire est vide.
                    <a href="magasin.php">Visiter le magasin</a>
                </p>

            <?php else: ?>
                <div class="item-grid-4" id="inventaireGrid" style="display:flex; flex-wrap:wrap; gap:16px; justify-content:center;">
                    <?php foreach ($items as $item):
                        $nom      = htmlspecialchars($item['nom']);
                        $qte      = (int) $item['quantiteInvenatire'];
                        $prix     = (int) $item['prix'];
                        $image    = htmlspecialchars($item['image']);
                        $typeCode = strtoupper(trim((string) $item['typeItem']));

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

                        $resellRate  = ($typeCode === 'S' || $typeCode === 'SORT') ? 1.10 : 0.60;
                        $resellPrix  = (int) round($prix * $resellRate);
                        $resellColor = ($typeCode === 'S' || $typeCode === 'SORT') ? '#adf3ad' : '#f3c9ad';
                        ?>
                        <div class="itemBox">
                            <div class="item-img-wrapper">
                                <img class="item-img" src="<?= $image ?>" alt="<?= $nom ?>">
                            </div>
                            <div class="item-info">
                                <h3 class="titre"><?= $nom ?></h3>
                                <p class="item-type">Type : <?= htmlspecialchars($typeLabel) ?></p>
                                <p class="description">Quantité : <?= $qte ?></p>
                                <p class="prixOr"><?= number_format($prix, 0, '', '') ?> gold</p>
                                <p class="item-resell" style="color: <?= $resellColor ?>;">
                                    Revente : <?= number_format($resellPrix, 0, '', '') ?> gold / unité
                                    <?= ($typeCode === 'S' || $typeCode === 'SORT') ? '(+10%)' : '(-40%)' ?>
                                </p>
                            </div>
                            <div class="btnPanier">
                                <form method="POST" action="inventaire.php" class="sell-form">
                                    <input type="hidden" name="action" value="sell">
                                    <input type="hidden" name="idItem" value="<?= (int) $item['idItem'] ?>">
                                    <div class="sell-qty-control">
                                        <button type="button" class="qty-btn qty-minus">−</button>
                                        <input type="text" inputmode="numeric" name="quantite" class="qty-input"
                                               value="1" data-min="1" data-max="<?= $qte ?>">
                                        <button type="button" class="qty-btn qty-plus">+</button>
                                    </div>
                                    <button type="submit" class="btnVendreImg-btn">
                                        <img src="img/removeFromInv.png" class="btnVendreImg" alt="Vendre">
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                            if (goldEl) goldEl.textContent = data.newGold;
                            showFlash('Vendu ! +' + data.gold + ' gold', 'success');
                            setTimeout(function () { location.reload(); }, 1200);
                        } else {
                            showFlash(data.message || 'Erreur inconnue', 'error');
                        }
                    })
                    .catch(function (err) {
                        showFlash('Erreur réseau', 'error');
                    });
            });
        });

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

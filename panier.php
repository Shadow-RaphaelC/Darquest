<?php
require_once __DIR__ . '/include/session.php';
require_once __DIR__ . '/BD/bd.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $userId = (int) $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    if ($action === 'add' && isset($_GET['id'])) {
        $idItem = (int) $_GET['id'];
        $quantite = max(1, (int) ($_GET['qte'] ?? 1));
        $alias = $_SESSION['username'];
        $result = AjouterPanier($alias, $idItem, $quantite);
        die(json_encode([
            'alias' => $alias,
            'idItem' => $idItem,
            'quantite' => $quantite,
            'result' => $result,
        ]));
    }

    if ($action === 'remove' && isset($_GET['idItem'])) {
        RetirerPanier((int) $_GET['idItem'], $userId);
        header('Location: panier.php');
        exit;
    }

    if ($action === 'update' && isset($_GET['idItem'], $_GET['qte'])) {
        $newQte = (int) $_GET['qte'];
        if ($newQte <= 0) {
            RetirerPanier((int) $_GET['idItem'], $userId);
        } else {
            ModifierQuantitePanier((int) $_GET['idItem'], $userId, $newQte);
        }
        header('Location: panier.php');
        exit;
    }

    if ($action === 'pay') {
        $alias = $_SESSION['username'];
        $result = PayerPanier($alias);

        if ($result['success']) {
            
            $coins = GetJoueurCoins($userId);
            $_SESSION['gold'] = $coins['gold'];
            $_SESSION['argent'] = $coins['argent'];
            $_SESSION['bronze'] = $coins['bronze'];
        }

        $_SESSION['panier_toast'] = [
            'type' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Achat effectué !' : $result['message'],
        ];
        header('Location: panier.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Panier</title>
</head>

<body>
    <?php require 'include/header.php'; ?>

    <?php if (!empty($_SESSION['panier_toast'])): ?>
        <?php
        $toastStyle = $_SESSION['panier_toast']['type'] === 'success'
            ? 'background:rgba(43,143,43,0.25); border-color:rgba(100,220,100,0.5); color:#adfaad;'
            : '';
        ?>
        <p class="auth-error-banner" style="<?= $toastStyle ?>">
            <?= htmlspecialchars($_SESSION['panier_toast']['message']) ?>
        </p>
        <?php unset($_SESSION['panier_toast']); ?>
    <?php endif; ?>

    <main>
        <h1>Panier</h1>

        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <p class="locked-message">Accès réservé aux utilisateurs connectés.
                <a href="index.php">Retour à l'accueil</a>
            </p>
            <p><a class="btnAutre" href="index.php">Accueil</a></p>

        <?php else: ?>

            <?php if (!empty($_SESSION['panier_error'])): ?>
                <p class="auth-error-banner"><?= htmlspecialchars($_SESSION['panier_error']) ?></p>
                <?php unset($_SESSION['panier_error']); ?>
            <?php endif; ?>

            <?php
            $userId = (int) $_SESSION['user_id'];
            $items = AfficherPanier($userId);
            $total = 0;
            ?>

            <?php if (empty($items)): ?>
                <p class="panier-empty">Votre panier est vide. <a href="magasin.php">Visiter le magasin</a></p>

            <?php else: ?>
                <div class="panier-layout">

                    <!-- item -->
                    <div class="panier-list">
                        <?php foreach ($items as $item):
                            $idItem = (int) $item['idItem'];
                            $nom = htmlspecialchars($item['nom']);
                            $prix = (int) $item['prix'];
                            $qte = (int) $item['quantitePanier'];
                            $image = htmlspecialchars($item['image']);
                            $sous = $prix * $qte;
                            $total += $sous;
                            ?>
                            <div class="panier-item">
                                <div class="panier-item-left">
                                    <img src="<?= $image ?>" alt="<?= $nom ?>" class="panier-item-img">
                                    <div class="panier-item-left-info">
                                        <h3><?= $nom ?></h3>
                                        <h3><?= number_format($prix, 0, '', '') ?> gold</h3>
                                    </div>
                                </div>
                                <div class="panier-item-right">
                                    <div class="panier-item-quantity">
                                        <a href="panier.php?action=update&idItem=<?= $idItem ?>&qte=<?= $qte - 1 ?>"
                                            class="panier-item-btn">−</a>
                                        <span><?= $qte ?></span>
                                        <a href="panier.php?action=update&idItem=<?= $idItem ?>&qte=<?= $qte + 1 ?>"
                                            class="panier-item-btn">+</a>
                                    </div>
                                    <a href="panier.php?action=remove&idItem=<?= $idItem ?>" class="panier-remove-btn">Retirer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- summary panel -->
                    <div class="panier-recap">
                        <h2>Résumé</h2>
                        <div class="panier-summary-lines">
                            <?php foreach ($items as $item):
                                $nom = htmlspecialchars($item['nom']);
                                $prix = (int) $item['prix'];
                                $qte = (int) $item['quantitePanier'];
                                $sous = $prix * $qte;
                                ?>
                                <div class="panier-summary-line">
                                    <span><?= $nom ?> ×<?= $qte ?></span>
                                    <span><?= number_format($sous, 0, '', '') ?> gold</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="panier-summary-total">
                            <span>Total</span>
                            <span><?= number_format($total, 0, '', '') ?> gold</span>
                        </div>
                        <form method="GET" action="panier.php">
                            <input type="hidden" name="action" value="pay">
                            <button type="submit" class="panier-buy-btn">Payer</button>
                        </form>
                    </div>

                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
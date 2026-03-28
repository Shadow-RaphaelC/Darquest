<?php ob_start();
require_once __DIR__ . '/include/session.php';
require_once __DIR__ . '/BD/bd.php';

// ── Cart action
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $userId = (int)$_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    if ($action === 'add' && isset($_GET['id'])) {
        $idItem   = (int)$_GET['id'];
        $quantite = max(1, (int)($_GET['qte'] ?? 1));
        $result   = AjouterPanier($userId, $idItem, $quantite);
        if (!$result['success']) {
            $_SESSION['panier_error'] = $result['message'];
        }
        header('Location: panier.php');
        exit;
    }

    if ($action === 'remove' && isset($_GET['idPanier'])) {
        RetirerPanier((int)$_GET['idPanier'], $userId);
        header('Location: panier.php');
        exit;
    }

    if ($action === 'update' && isset($_GET['idPanier'], $_GET['qte'])) {
        $newQte = (int)$_GET['qte'];
        if ($newQte <= 0) {
            RetirerPanier((int)$_GET['idPanier'], $userId);
        } else {
            ModifierQuantitePanier((int)$_GET['idPanier'], $userId, $newQte);
        }
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
<main>
    <h1>Panier</h1>

    <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
        <p class="locked-message">Accès réservé aux utilisateurs connectés.
            <a href="index.php">Retour à l'accueil</a>
        </p>
        <p><a class="btnAutre" href="index.php">Accueil</a></p>

    <?php else: ?>
        <div class="btnBox" style="justify-content: space-evenly; padding-left: 100px; padding-right: 100px;">
            <a class="btnAutre" href="magasin.php">Magasin</a>
            <a class="btnAutre" href="inventaire.php">Inventaire</a>
            <a class="btnAutre" href="index.php">Accueil</a>
        </div>

        <?php if (!empty($_SESSION['panier_error'])): ?>
            <p class="auth-error-banner"><?= htmlspecialchars($_SESSION['panier_error']) ?></p>
            <?php unset($_SESSION['panier_error']); ?>
        <?php endif; ?>

        <?php
        $userId = (int)$_SESSION['user_id'];
        $items  = AfficherPanier($userId);
        $total  = 0;
        ?>

        <?php if (empty($items)): ?>
            <p style="text-align:center; margin-top: 2rem;">Votre panier est vide.</p>

        <?php else: ?>
            <div class="panier-list">
                <?php foreach ($items as $item):
                    $idPanier = (int)$item['idPanier'];
                    $nom      = htmlspecialchars($item['nomItem']);
                    $prix     = (int)$item['prix'];
                    $qte      = (int)$item['quantitePanier'];
                    $image    = htmlspecialchars($item['image']);
                    $sous     = $prix * $qte;
                    $total   += $sous;
                ?>
                <div class="panier-item">
                    <div class="panier-item-left">
                        <img src="<?= $image ?>" alt="<?= $nom ?>" class="panier-item-img">
                        <div class="panier-item-left-info">
                            <h3><?= $nom ?></h3>
                            <h3>Prix : <?= number_format($prix, 0, '', '') ?> gold</h3>
                            <p>Sous-total : <?= number_format($sous, 0, '', '') ?> gold</p>
                        </div>
                    </div>
                    <div class="panier-item-right">
                        <div class="panier-item-quantity">
                            <a href="panier.php?action=update&idPanier=<?= $idPanier ?>&qte=<?= $qte - 1 ?>"
                               class="panier-item-btn">−</a>
                            <span><?= $qte ?></span>
                            <a href="panier.php?action=update&idPanier=<?= $idPanier ?>&qte=<?= $qte + 1 ?>"
                               class="panier-item-btn">+</a>
                        </div>
                        <a href="panier.php?action=remove&idPanier=<?= $idPanier ?>"
                           class="btnAutre" style="margin-top: 0.5rem;">Retirer</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="panier-total">
                <h2>Total : <?= number_format($total, 0, '', '') ?> gold</h2>
                <a class="btnAutre" href="#">Payer (bientôt disponible)</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require 'include/footer.php'; ?>
</body>
</html>
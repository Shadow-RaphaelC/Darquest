<?php
require_once 'include/session.php';
require_once 'BD/bd.php';
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

        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <p class="locked-message">Accès réservé aux utilisateurs connectés.
                <a href="index.php">Retour à l'accueil</a>
            </p>
            <p><a class="btnAutre" href="index.php">Accueil</a></p>

        <?php else: ?>
            <?php
            $userId = (int) $_SESSION['user_id'];
            $items = AfficherInventaire($userId);
            ?>

            <?php if (empty($items)): ?>
                <p class="panier-empty">Votre inventaire est vide.
                    <a href="magasin.php">Visiter le magasin</a>
                </p>

            <?php else: ?>
                <div class="item-grid-4" id="inventaireGrid" style="display:flex; flex-wrap:wrap; gap:16px; justify-content:center;">
                    <?php foreach ($items as $item):
                        $nom = htmlspecialchars($item['nom']);
                        $qte = (int) $item['quantiteInvenatire'];
                        $prix = (int) $item['prix'];
                        $image = htmlspecialchars($item['image']);
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
                                <?php
                                $resellRate = ($typeCode === 'S' || $typeCode === 'SORT') ? 1.10 : 0.60;
                                $resellPrix = (int) round($prix * $resellRate);
                                $resellColor = ($typeCode === 'S' || $typeCode === 'SORT') ? '#adf3ad' : '#f3c9ad';
                                ?>
                                <p class="item-resell" style="color: <?= $resellColor ?>;">
                                    Revente : <?= number_format($resellPrix, 0, '', '') ?> gold
                                    <?= ($typeCode === 'S' || $typeCode === 'SORT') ? '(+10%)' : '(-40%)' ?>
                                </p>
                            </div>
                            <div class="btnPanier">
                                <a href="inventaire.php?action=sell&idItem=<?= (int) $item['idItem'] ?>" class="btnVendreImg-btn">
                                    <img src="img/addToCart.png" class="btnVendreImg" alt="Vendre">
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
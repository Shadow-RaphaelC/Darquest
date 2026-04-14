<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

// Handle unequip actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unequip_armure') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        echo json_encode(['success' => false, 'message' => 'Non connecté']);
        exit;
    }
    $userId = (int) $_SESSION['user_id'];
    $result = DesequiperArmure($userId);
    if ($result['success']) {
        $_SESSION['maxHP']      = $result['maxHP'];
        $_SESSION['pointDeVie'] = $result['pointDeVie'];
    }
    echo json_encode(array_merge($result, [
        'newMaxHP' => (int)($_SESSION['maxHP']      ?? 100),
        'newPV'    => (int)($_SESSION['pointDeVie'] ?? 0),
    ]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unequip_arme') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        echo json_encode(['success' => false, 'message' => 'Non connecté']);
        exit;
    }
    $userId = (int) $_SESSION['user_id'];
    $result = DesequiperArme($userId);
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Profil</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Profil</h1>

        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <p class="locked-message">Accès réservé aux utilisateurs connectés.</p>
            <p><a class="btnAutre" href="index.php">Accueil</a></p>
        <?php else: ?>
            <?php
            $userId        = (int) $_SESSION['user_id'];
            $userName      = htmlspecialchars($_SESSION['username'] ?? 'Joueur', ENT_QUOTES, 'UTF-8');
            $hp            = GetJoueurHP($userId);
            $pv            = $hp['pointDeVie'];
            $maxHP         = $hp['maxHP'];
            $pvPct         = $maxHP > 0 ? min(100, (int)round($pv / $maxHP * 100)) : 0;
            $armureEquipee = GetArmureEquipee($userId);
            $armeEquipee   = GetArmeEquipee($userId);

            // Fetch bonuses from DB
            $healBonus      = 0;
            $goldBonus      = 0;
            $damageModifier = 1.00;
            $pdo = get_pdo();
            if ($pdo) {
                try {
                    $s = $pdo->prepare('SELECT healBonus, goldBonus, damageModifier FROM Joueurs WHERE idJoueur = :id LIMIT 1');
                    $s->execute([':id' => $userId]);
                    $row = $s->fetch();
                    $healBonus      = (int)   ($row['healBonus']      ?? 0);
                    $goldBonus      = (int)   ($row['goldBonus']      ?? 0);
                    $damageModifier = (float) ($row['damageModifier'] ?? 1.00);
                } catch (PDOException $e) {}
            }
            ?>

            <div class="profil-container">

                <!-- Stats -->
                <div class="profil-stat-box">
                    <h2><?= $userName ?></h2>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Points de vie</span>
                        <span class="profil-stat-value"><?= $pv ?> / <?= $maxHP ?></span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">HP max</span>
                        <span class="profil-stat-value"><?= $maxHP ?></span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Bonus de soin</span>
                        <span class="profil-stat-value" style="color:<?= $healBonus >= 0 ? '#adf3ad' : '#f3adad' ?>;">
                            <?= $healBonus >= 0 ? '+' : '' ?><?= $healBonus ?>%
                        </span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Bonus gold</span>
                        <span class="profil-stat-value" style="color:<?= $goldBonus >= 0 ? '#ffd700' : '#f3adad' ?>;">
                            <?= $goldBonus >= 0 ? '+' : '' ?><?= $goldBonus ?>%
                        </span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Modificateur de dégâts</span>
                        <span class="profil-stat-value" style="color:<?= $damageModifier < 1.0 ? '#adf3ad' : ($damageModifier > 1.0 ? '#f3adad' : '#fff') ?>;">
                            <?php
                            if ($damageModifier < 1.0)     echo 'Dégâts /2';
                            elseif ($damageModifier > 1.0) echo 'Dégâts ×2';
                            else                           echo 'Normal';
                            ?>
                        </span>
                    </div>
                    <div class="profil-hp-bar-wrap">
                        <div class="profil-hp-bar" style="width:<?= $pvPct ?>%;"></div>
                    </div>
                </div>

                <!-- Equipped armor -->
                <div class="profil-stat-box">
                    <h2>Armure équipée</h2>
                    <?php if ($armureEquipee): ?>
                        <?php $aStats = getArmorStats($armureEquipee['taille'], $armureEquipee['matiere']); ?>
                        <div class="profil-armor-card">
                            <img class="profil-armor-img"
                                 src="<?= htmlspecialchars($armureEquipee['image'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($armureEquipee['nom'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="profil-armor-info">
                                <h3><?= htmlspecialchars($armureEquipee['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p>
                                    <?= htmlspecialchars(ucfirst(strtolower($armureEquipee['taille'])), ENT_QUOTES, 'UTF-8') ?>
                                    &middot;
                                    <?= htmlspecialchars(ucfirst(strtolower($armureEquipee['matiere'])), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p>+<?= $aStats['maxHP'] ?> HP max</p>
                                <p><?= $aStats['heal'] >= 0 ? '+' : '' ?><?= $aStats['heal'] ?>% bonus de soin</p>
                            </div>
                        </div>
                        <button type="button" class="btn-desequiper" id="unequipArmureBtn">Déséquiper</button>
                    <?php else: ?>
                        <p class="profil-no-armor">Aucune armure équipée.</p>
                        <p><a class="btnAutre" href="inventaire.php" style="font-size:0.95rem;">Voir l'inventaire</a></p>
                    <?php endif; ?>
                </div>

                <!-- Equipped weapon -->
                <div class="profil-stat-box">
                    <h2>Arme équipée</h2>
                    <?php if ($armeEquipee): ?>
                        <?php $wStats = getWeaponStats($armeEquipee['efficacite'], $armeEquipee['genre']); ?>
                        <div class="profil-armor-card">
                            <img class="profil-armor-img"
                                 src="<?= htmlspecialchars($armeEquipee['image'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($armeEquipee['nom'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="profil-armor-info">
                                <h3><?= htmlspecialchars($armeEquipee['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p>
                                    <?= htmlspecialchars(ucfirst(strtolower($armeEquipee['genre'])), ENT_QUOTES, 'UTF-8') ?>
                                    &middot;
                                    <?= htmlspecialchars(ucfirst(strtolower($armeEquipee['efficacite'])), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p><?= $wStats['goldBonus'] >= 0 ? '+' : '' ?><?= $wStats['goldBonus'] ?>% bonus gold</p>
                                <p>
                                    <?php
                                    $d = $wStats['damageModifier'];
                                    if ($d < 1.0)     echo 'Dégâts /2 (réduction)';
                                    elseif ($d > 1.0) echo 'Dégâts ×2 (pris)';
                                    else              echo 'Dégâts normaux';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-desequiper" id="unequipArmeBtn">Déséquiper</button>
                    <?php else: ?>
                        <p class="profil-no-armor">Aucune arme équipée.</p>
                        <p><a class="btnAutre" href="inventaire.php" style="font-size:0.95rem;">Voir l'inventaire</a></p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </main>
    <?php require 'include/footer.php'; ?>
    <script>
        function unequipAction(btnId, action, onSuccess) {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            btn.addEventListener('click', function () {
                btn.disabled = true;
                const fd = new FormData();
                fd.append('action', action);
                fetch('profil.php', { method: 'POST', body: fd })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            if (onSuccess) onSuccess(data);
                            setTimeout(function () { location.reload(); }, 800);
                        } else {
                            btn.disabled = false;
                            alert(data.message || 'Erreur inconnue');
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        alert('Erreur réseau');
                    });
            });
        }

        unequipAction('unequipArmureBtn', 'unequip_armure', function (data) {
            const hpBar  = document.querySelector('.hpBar');
            const hpText = document.querySelector('.hpText');
            if (hpBar && hpText && data.newMaxHP > 0) {
                const pct = Math.round(data.newPV / data.newMaxHP * 100);
                hpBar.style.width = pct + '%';
                hpText.textContent = 'PV: ' + data.newPV + '/' + data.newMaxHP;
            }
        });

        unequipAction('unequipArmeBtn', 'unequip_arme', null);
    </script>
</body>

</html>

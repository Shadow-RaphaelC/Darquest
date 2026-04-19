<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

$idJoueur = (int)$_SESSION['user_id'];

// Always fetch fresh HP for threshold checks
$hpData    = GetJoueurHP($idJoueur);
$currentHP = $hpData['pointDeVie'];

// Sync streak from DB on first load
if (!isset($_SESSION['enigme_streak'])) {
    $_SESSION['enigme_streak'] = GetJoueurStreak($idJoueur);
}

// HP minimums per difficulty
function hpMinForDiff(string $diff): int {
    return match($diff) { 'D', 'G' => 7, 'M' => 5, default => 3 };
}

// --- POST: back to menu ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'back_to_menu') {
    unset($_SESSION['enigme_difficulte'], $_SESSION['enigme_current'], $_SESSION['enigme_answered']);
    header('Location: enigma.php');
    exit;
}

// --- POST: next question ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'next_question') {
    unset($_SESSION['enigme_current'], $_SESSION['enigme_answered']);
    header('Location: enigma.php');
    exit;
}

// --- POST: difficulty selection ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_diff'])) {
    $pick = $_POST['select_diff'];
    if (in_array($pick, ['F', 'M', 'D', 'aleatoire'], true)) {
        $minHP = hpMinForDiff($pick === 'aleatoire' ? 'F' : $pick);
        if ($currentHP < $minHP) {
            $_SESSION['enigme_hp_error'] = match($pick) {
                'M' => 'Vous avez besoin d\'au moins 5 HP pour jouer en Moyen.',
                'D' => 'Vous avez besoin d\'au moins 7 HP pour jouer en Difficile.',
                default => 'Vous avez besoin d\'au moins 3 HP pour jouer.',
            };
            header('Location: enigma.php');
            exit;
        }
        $_SESSION['enigme_difficulte'] = $pick;
    } else {
        unset($_SESSION['enigme_difficulte']);
    }
    unset($_SESSION['enigme_current'], $_SESSION['enigme_answered']);
    header('Location: enigma.php');
    exit;
}

// --- POST: answer submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idReponse'], $_POST['idEnigma']) && !isset($_SESSION['enigme_answered'])) {
    $idReponse = (int)$_POST['idReponse'];
    $idEnigma  = (int)$_POST['idEnigma'];
    $stored    = $_SESSION['enigme_current'] ?? null;

    if ($stored && $stored['idEnigma'] === $idEnigma) {
        $correct     = false;
        $correctId   = null;
        $idCategorie = $stored['idCategorie'];

        foreach ($stored['reponses'] as $rep) {
            if ($rep['estBonne'])                                     $correctId = $rep['idReponse'];
            if ($rep['idReponse'] === $idReponse && $rep['estBonne']) $correct   = true;
        }

        $coinType    = match($idCategorie) { 'M' => 'argent', 'D', 'G' => 'gold', default => 'bronze' };
        $coinsEarned = [];
        $damage      = 0;

        if ($correct) {
            $newStreak = $_SESSION['enigme_streak'] + 1;
            $_SESSION['enigme_streak'] = $newStreak;
            SetJoueurStreak($idJoueur, $newStreak);

            $diffForSP = $idCategorie === 'G' ? 'D' : $idCategorie;
            AjouterPieces($diffForSP, $_SESSION['username']);
            $coinsEarned[] = ['type' => $coinType, 'montant' => 10, 'label' => 'base'];

            $goldBonus = GetJoueurGoldBonus($idJoueur);

            if ($goldBonus > 0) {
                $weaponBonus = (int) round(10 * $goldBonus / 100);
                if ($weaponBonus > 0) {
                    AjouterPiecesBonus($idJoueur, $weaponBonus, $coinType);
                    $coinsEarned[] = ['type' => $coinType, 'montant' => $weaponBonus, 'label' => 'arme'];
                }
            }

            if ($newStreak >= 3) {
                $streakBase  = 100;
                $streakTotal = $streakBase + (int) round($streakBase * max(0, $goldBonus ?? 0) / 100);
                AjouterPiecesBonus($idJoueur, $streakTotal, $coinType);
                $coinsEarned[] = ['type' => $coinType, 'montant' => $streakTotal, 'label' => 'streak'];
            }

            $mageStatus = $idCategorie === 'G' ? ProcessMageProgress($idJoueur) : null;

            $coins = GetJoueurCoins($idJoueur);
            $_SESSION['gold']   = $coins['gold'];
            $_SESSION['argent'] = $coins['argent'];
            $_SESSION['bronze'] = $coins['bronze'];
        } else {
            $_SESSION['enigme_streak'] = 0;
            SetJoueurStreak($idJoueur, 0);

            $baseDmg  = match($idCategorie) { 'M' => 5, 'D', 'G' => 7, default => 3 };
            $dmgMod   = GetJoueurDamageModifier($idJoueur);
            $damage   = max(1, (int) round($baseDmg * $dmgMod));
            PrendreDegatEnigme($idJoueur, $damage);
        }

        $_SESSION['enigme_answered'] = [
            'correct'     => $correct,
            'selected_id' => $idReponse,
            'correct_id'  => $correctId,
            'coins'       => $coinsEarned,
            'streak'      => $_SESSION['enigme_streak'],
            'damage'      => $damage,
            'mage_status' => $mageStatus ?? null,
        ];
    }

    header('Location: enigma.php');
    exit;
}

// --- Load enigma if needed ---
$selectedDiff = $_SESSION['enigme_difficulte'] ?? null;
$debugError   = null;

if ($selectedDiff !== null && !isset($_SESSION['enigme_current'])) {
    $minHP = hpMinForDiff($selectedDiff === 'aleatoire' ? 'F' : $selectedDiff);

    if ($currentHP < $minHP) {
        $debugError = 'HP insuffisant pour continuer.';
    } else {
        $cat     = in_array($selectedDiff, ['F', 'M', 'D'], true) ? $selectedDiff : '';
        $fetched = GetEnigmeAleatoire($cat);
        if (isset($fetched['__error'])) {
            $debugError = $fetched['__error'];
        } elseif (!empty($fetched)) {
            $enigmaMinHP = hpMinForDiff($fetched['idCategorie']);
            if ($currentHP < $enigmaMinHP) {
                $debugError = 'HP insuffisant pour cette difficulte.';
            } else {
                shuffle($fetched['reponses']);
                $_SESSION['enigme_current'] = $fetched;
                FlagEnigmePigee($fetched['idEnigma'], $fetched['idCategorie']);
            }
        }
    }
}

$enigma   = $_SESSION['enigme_current'] ?? null;
$answered = $_SESSION['enigme_answered'] ?? null;
$streak   = (int)$_SESSION['enigme_streak'];

$diffLabels  = ['F' => 'Facile', 'M' => 'Moyen', 'D' => 'Difficile', 'G' => 'Difficile'];
$diffClass   = ['F' => 'facile', 'M' => 'moyen',  'D' => 'difficile', 'G' => 'difficile'];
$coinLabels  = ['gold' => 'or',  'argent' => 'argent', 'bronze' => 'bronze'];
$coinColors  = ['gold' => '#ffd700', 'argent' => '#c0c0c0', 'bronze' => '#cd7f32'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Enigma</title>
</head>
<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Enigma</h1>

        <?php if ($selectedDiff === null): ?>

        <?php
            $hpError = $_SESSION['enigme_hp_error'] ?? null;
            unset($_SESSION['enigme_hp_error']);
        ?>
        <?php if ($hpError): ?>
            <p style="color:#e88080; margin-bottom:12px;"><?= htmlspecialchars($hpError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <div class="btnBox">
            <form method="POST" action="enigma.php" style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
                <button type="submit" class="btnEnigmaDiff facile <?= $currentHP < 3 ? 'unavailable' : '' ?>"
                    name="select_diff" value="F">Facile</button>
                <button type="submit" class="btnEnigmaDiff moyen <?= $currentHP < 5 ? 'unavailable' : '' ?>"
                    name="select_diff" value="M">Moyen</button>
                <button type="submit" class="btnEnigmaDiff difficile <?= $currentHP < 7 ? 'unavailable' : '' ?>"
                    name="select_diff" value="D">Difficile</button>
            </form>
        </div>
        <div class="btnBox" style="margin-top:20px;">
            <form method="POST" action="enigma.php" style="display:flex; justify-content:center;">
                <button type="submit" class="btnEnigmaDiff aleatoire" name="select_diff" value="aleatoire">Aleatoire</button>
            </form>
        </div>

        <?php else: ?>

        <div style="text-align:center; margin-bottom:12px;">
            <form method="POST" action="enigma.php" style="display:inline;">
                <input type="hidden" name="action" value="back_to_menu">
                <button type="submit" class="btnChangerDiff">Changer de difficulte</button>
            </form>
        </div>

        <?php if ($enigma): ?>
        <?php
            $isMage  = $enigma['idCategorie'] === 'G';
            $diffKey = $enigma['difficulte'] ?? $enigma['idCategorie'];
            $diffLbl = $diffLabels[$diffKey] ?? $diffKey;
            $diffCls = $diffClass[$diffKey]  ?? '';
        ?>

        <div class="enigmeInfoRow">
            <div class="statBox enigmeStatBox <?= $isMage ? 'mage' : $diffCls ?>">
                <?= $isMage ? '★ ' : '' ?><?= htmlspecialchars($diffLbl, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="statBox enigmeStatBox enigmeStreakBox <?= $streak >= 3 ? 'streak-hot' : '' ?>">
                Serie: <?= $streak ?>
            </div>
        </div>

        <div class="enigmeBox enigmeBoxMulti">
            <?= htmlspecialchars($enigma['enigme'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <?php if ($answered !== null): ?>

        <div class="enigmeFeedback <?= $answered['correct'] ? 'correct' : 'wrong' ?>">
            <?php if ($answered['correct']): ?>
                Bonne reponse!
                <?php foreach ($answered['coins'] as $coin):
                    $col = $coinColors[$coin['type']];
                    $lbl = $coinLabels[$coin['type']];
                ?>
                    <?php if ($coin['label'] === 'streak'): ?>
                        &nbsp;|&nbsp; Bonus serie: <span style="color:<?= $col ?>">+<?= $coin['montant'] ?> <?= $lbl ?></span>
                    <?php elseif ($coin['label'] === 'arme'): ?>
                        &nbsp;|&nbsp; Bonus arme: <span style="color:<?= $col ?>">+<?= $coin['montant'] ?> <?= $lbl ?></span>
                    <?php else: ?>
                        &nbsp;<span style="color:<?= $col ?>">+<?= $coin['montant'] ?> <?= $lbl ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($answered['streak'] >= 3): ?>&nbsp;| Serie x<?= $answered['streak'] ?>!<?php endif; ?>
                <?php if ($answered['mage_status']): ?>
                    <div style="margin-top:8px; color:#c8b0ff; font-size:1.1em;">★ Vous etes maintenant un Mage! ★</div>
                <?php endif; ?>
            <?php else: ?>
                Mauvaise reponse. Serie reinitalisee.&nbsp;
                <?php if ($answered['damage'] > 0): ?>
                    <span style="color:#e88080;">-<?= $answered['damage'] ?> HP</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="enigmeBtnGrid">
            <?php foreach ($enigma['reponses'] as $rep):
                $cls = 'enigmaBtn';
                if ($rep['idReponse'] === $answered['correct_id'])       $cls .= ' answer-correct';
                elseif ($rep['idReponse'] === $answered['selected_id'])  $cls .= ' answer-wrong';
            ?>
                <button type="button" class="<?= $cls ?>" disabled>
                    <?= htmlspecialchars($rep['reponse'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="enigmeActionRow">
            <?php
                $nextDiff    = $enigma['idCategorie'];
                $canContinue = $currentHP >= hpMinForDiff($nextDiff);
            ?>
            <?php if ($canContinue): ?>
            <form method="POST" action="enigma.php" style="display:inline;">
                <input type="hidden" name="action" value="next_question">
                <button type="submit" class="btnEnigmaNext">Question suivante</button>
            </form>
            <?php endif; ?>
            <form method="POST" action="enigma.php" style="display:inline;">
                <input type="hidden" name="action" value="back_to_menu">
                <button type="submit" class="btnEnigmaMenu">Retour au menu</button>
            </form>
        </div>

        <?php else: ?>

        <form method="POST" action="enigma.php">
            <input type="hidden" name="idEnigma" value="<?= $enigma['idEnigma'] ?>">
            <div class="enigmeBtnGrid">
                <?php foreach ($enigma['reponses'] as $rep): ?>
                    <button type="submit" class="enigmaBtn" name="idReponse" value="<?= $rep['idReponse'] ?>">
                        <?= htmlspecialchars($rep['reponse'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>

        <?php endif; ?>

        <?php else: ?>
            <?php if ($debugError): ?>
                <p style="color:#ff8080;"><?= htmlspecialchars($debugError, ENT_QUOTES, 'UTF-8') ?></p>
                <div style="text-align:center; margin-top:12px;">
                    <form method="POST" action="enigma.php" style="display:inline;">
                        <input type="hidden" name="action" value="back_to_menu">
                        <button type="submit" class="btnEnigmaMenu">Retour au menu</button>
                    </form>
                </div>
            <?php else: ?>
                <p>Aucune enigme disponible pour cette difficulte.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php endif; ?>

    </main>
    <?php require 'include/footer.php'; ?>
</body>
</html>

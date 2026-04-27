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
if (!isset($_SESSION['enigme_loss_streak'])) {
    $_SESSION['enigme_loss_streak'] = 0;
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
    if (in_array($pick, ['F', 'M', 'D', 'G', 'aleatoire'], true)) {
        $minHP = hpMinForDiff($pick === 'aleatoire' ? 'F' : $pick);
        if ($currentHP < $minHP) {
            $_SESSION['enigme_hp_error'] = match($pick) {
                'M' => 'Vous avez besoin d\'au moins 5 HP pour jouer en Moyen.',
                'D' => 'Vous avez besoin d\'au moins 7 HP pour jouer en Difficile.',
                'G' => 'Vous avez besoin d\'au moins 7 HP pour jouer en Mage.',
                default => 'Vous avez besoin d\'au moins 3 HP pour jouer.',
            };
            header('Location: enigma.php');
            exit;
        }
        // Reset streak when switching category
        if (($pick !== ($_SESSION['enigme_difficulte'] ?? null))) {
            $_SESSION['enigme_streak'] = 0;
            $_SESSION['enigme_loss_streak'] = 0;
            SetJoueurStreak($idJoueur, 0);
        }
        $_SESSION['enigme_difficulte'] = $pick;
    } else {
        unset($_SESSION['enigme_difficulte']);
        $_SESSION['enigme_streak'] = 0;
        $_SESSION['enigme_loss_streak'] = 0;
        SetJoueurStreak($idJoueur, 0);
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

            FlagEnigmePigee($idEnigma, $idCategorie);

            $_SESSION['enigme_loss_streak'] = 0;
            $rankedResult = ProcessRanked($idJoueur, true, $idCategorie, $newStreak, 0);

            $coins = GetJoueurCoins($idJoueur);
            $_SESSION['gold']   = $coins['gold'];
            $_SESSION['argent'] = $coins['argent'];
            $_SESSION['bronze'] = $coins['bronze'];
        } else {
            $_SESSION['enigme_streak'] = 0;
            SetJoueurStreak($idJoueur, 0);

            $baseDmg  = match($idCategorie) { 'M' => 5, 'D', 'G' => 10, default => 3 };
            $dmgMod   = GetJoueurDamageModifier($idJoueur);
            $damage   = max(1, (int) round($baseDmg * $dmgMod));
            PrendreDegatEnigme($idJoueur, $damage);

            $lossStreak = $_SESSION['enigme_loss_streak'] + 1;
            $_SESSION['enigme_loss_streak'] = $lossStreak;
            $rankedResult = ProcessRanked($idJoueur, false, $idCategorie, 0, $lossStreak);
        }

        InsererStatistique($idJoueur, $idEnigma, $correct ? 1 : 0);

        // Career counters — always use session difficulty (guaranteed F/M/D/G by form buttons)
        $sessionDiff = $_SESSION['enigme_difficulte'] ?? '';
        $careerCat   = ($sessionDiff !== '' && $sessionDiff !== 'aleatoire')
            ? $sessionDiff
            : strtoupper(substr((string)$idCategorie, 0, 1));
        $catColMap   = ['F' => 'correctF', 'M' => 'correctM', 'D' => 'correctD', 'G' => 'correctG'];
        $catCol      = $catColMap[$careerCat] ?? null;
        $pdo = get_pdo();
        if ($pdo) {
            try {
                // totalPlays always goes up (win or lose)
                $pdo->prepare('UPDATE Joueurs SET totalPlays = totalPlays + 1 WHERE idJoueur = :id')
                    ->execute([':id' => $idJoueur]);
                // per-category only on correct
                if ($catCol && $correct) {
                    $pdo->prepare("UPDATE Joueurs SET $catCol = $catCol + 1 WHERE idJoueur = :id")
                        ->execute([':id' => $idJoueur]);
                }
            } catch (\PDOException $e) {
                error_log('career counter error: ' . $e->getMessage());
            }
        }

        $_SESSION['enigme_answered'] = [
            'correct'     => $correct,
            'selected_id' => $idReponse,
            'correct_id'  => $correctId,
            'coins'       => $coinsEarned,
            'streak'      => $_SESSION['enigme_streak'],
            'damage'      => $damage,
            'mage_status' => $mageStatus ?? null,
            'ranked'      => $rankedResult ?? [],
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
        $cat     = in_array($selectedDiff, ['F', 'M', 'D', 'G'], true) ? $selectedDiff : '';
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
            <form method="POST" action="enigma.php" style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
                <button type="submit" class="btnEnigmaDiff aleatoire" name="select_diff" value="aleatoire">Aleatoire</button>
                <button type="submit" class="btnEnigmaDiff mage <?= $currentHP < 7 ? 'unavailable' : '' ?>"
                    name="select_diff" value="G">★ Mage</button>
            </form>
        </div>

        <?php
            $enigmaStats  = GetEnigmaStats($idJoueur);
            $totalEnigmas = GetTotalEnigmas();
            $menuRanked   = GetRankedData($idJoueur);
            $menuRkColor  = getRankColor($menuRanked['rang']);
            $menuRkName   = getRankName($menuRanked['rang']);
            $allRanks    = [];
            for ($r = 14; $r >= 0; $r--) {
                $allRanks[] = ['rang' => $r, 'name' => getRankName($r), 'color' => getRankColor($r)];
            }
            $leaderboard = GetLeaderboard(12);
        ?>

        <div style="display:flex; gap:40px; align-items:flex-start; justify-content:center; flex-wrap:wrap; margin-top:28px; padding:0 40px;">

            <!-- Left: rank ladder -->
            <div class="profil-stat-box" style="min-width:180px; flex:0 0 auto;">
                <h2 style="margin-bottom:10px;">Rangs</h2>
                <?php foreach ($allRanks as $rk): ?>
                <div style="display:flex; align-items:center; gap:8px; padding:4px 0;
                    <?= $rk['rang'] === $menuRanked['rang'] ? 'background:' . $rk['color'] . '22; border-radius:6px; padding:4px 6px;' : '' ?>">
                    <span style="width:10px; height:10px; border-radius:50%; background:<?= $rk['color'] ?>; display:inline-block; flex-shrink:0;"></span>
                    <span style="color:<?= $rk['color'] ?>; font-weight:<?= $rk['rang'] === $menuRanked['rang'] ? '700' : '400' ?>;">
                        <?= htmlspecialchars($rk['name']) ?>
                    </span>
                    <?php if ($rk['rang'] === $menuRanked['rang']): ?>
                        <span style="color:#aaa; font-size:0.75em;">◀ vous</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Center: rank + stats -->
            <div style="flex:0 0 auto; min-width:280px;">
                <div class="profil-stat-box" style="border-color:<?= $menuRkColor ?>;">
                    <h2 style="color:<?= $menuRkColor ?>;">&#9733; <?= htmlspecialchars($menuRkName) ?></h2>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">LP</span>
                        <span class="profil-stat-value">
                            <?php if ($menuRanked['rang'] >= 14): ?>
                                <span style="color:<?= $menuRkColor ?>; font-weight:700;">MAX</span>
                            <?php else: ?>
                                <?= $menuRanked['lp'] ?> / 100
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">MMR</span>
                        <span class="profil-stat-value" style="color:#aaa;"><?= $menuRanked['mmr'] ?></span>
                    </div>
                    <div class="profil-hp-bar-wrap" style="margin-top:10px;">
                        <div class="profil-hp-bar" style="width:<?= $menuRanked['lp'] ?>%; background:<?= $menuRkColor ?>;"></div>
                    </div>
                </div>

                <div class="profil-stat-box" style="margin-top:16px;">
                    <h2>Mes statistiques</h2>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Enigmes dans le jeu</span>
                        <span class="profil-stat-value" style="color:#aaa;"><?= $totalEnigmas ?></span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Complétées</span>
                        <span class="profil-stat-value">
                            <?= $enigmaStats['reussies'] ?> / <?= $totalEnigmas ?>
                            <?php if ($totalEnigmas > 0 && $enigmaStats['reussies'] >= $totalEnigmas): ?>
                                <span style="color:#ffd700; font-weight:700;"> ★ Toutes!</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Jouées (uniques)</span>
                        <span class="profil-stat-value"><?= $enigmaStats['total'] ?></span>
                    </div>
                    <div class="profil-stat-row">
                        <span class="profil-stat-label">Ratées</span>
                        <span class="profil-stat-value" style="color:#f3adad;"><?= $enigmaStats['ratees'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Right: top 12 leaderboard -->
            <div class="profil-stat-box" style="min-width:260px; flex:0 0 auto;">
                <h2 style="margin-bottom:12px;">&#127942; Top 12</h2>
                <?php if (empty($leaderboard)): ?>
                    <p style="color:#aaa; font-size:0.85em;">Aucun joueur classé.</p>
                <?php else: ?>
                <?php
                $posColors = ['#ffe066', '#c0c0c0', '#cd7f32'];
                foreach ($leaderboard as $i => $p):
                    $pos     = $i + 1;
                    $pkColor = getRankColor((int)$p['rang']);
                    $posCol  = $posColors[$i] ?? '#aaa';
                    $isMe    = (isset($_SESSION['username']) && $p['alias'] === $_SESSION['username']);
                ?>
                <div style="display:flex; align-items:center; gap:8px; padding:5px 4px; border-bottom:1px solid #333;<?= $isMe ? ' background:rgba(255,255,255,0.04); border-radius:4px;' : '' ?>">
                    <span style="width:22px; text-align:right; font-weight:700; color:<?= $posCol ?>; font-size:0.9em; flex-shrink:0;">#<?= $pos ?></span>
                    <span style="width:10px; height:10px; border-radius:50%; background:<?= $pkColor ?>; display:inline-block; flex-shrink:0;"></span>
                    <span style="flex:1; font-size:0.88em;<?= $isMe ? ' color:#fff; font-weight:600;' : ' color:#ccc;' ?>"><?= htmlspecialchars($p['alias'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="color:<?= $pkColor ?>; font-size:0.78em; white-space:nowrap;"><?= htmlspecialchars(getRankName((int)$p['rang'])) ?></span>
                    <span style="color:#aaa; font-size:0.75em; white-space:nowrap; margin-left:4px;"><?= (int)$p['mmr'] ?> MMR</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

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

        <?php
            $ranked = GetRankedData($idJoueur);
        ?>
        <?php
            $mageData     = $isMage ? GetMageStatus($idJoueur) : null;
            $mageProgress = $mageData ? (int)$mageData['quetesMagieComplete'] : 0;
            $alreadyMage  = $mageData ? (int)$mageData['estMage'] : 0;
        ?>
        <div class="enigmeInfoRow">
            <div class="statBox enigmeStatBox <?= $isMage ? 'mage' : $diffCls ?>">
                <?= $isMage ? '★ ' : '' ?><?= htmlspecialchars($diffLbl, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php if ($isMage && !$alreadyMage): ?>
            <div class="statBox enigmeStatBox" style="color:#c8b0ff; border-color:#c8b0ff; letter-spacing:4px;">
                <?= str_repeat('★', $mageProgress) . str_repeat('☆', 3 - $mageProgress) ?>
            </div>
            <?php endif; ?>
            <div class="statBox enigmeStatBox enigmeStreakBox <?= $streak >= 3 ? 'streak-hot' : '' ?>">
                Serie: <?= $streak ?>
            </div>
            <div class="statBox enigmeStatBox" style="border-color:<?= getRankColor($ranked['rang']) ?>; color:<?= getRankColor($ranked['rang']) ?>; background:<?= getRankColor($ranked['rang']) ?>22;">
                <?= htmlspecialchars(getRankName($ranked['rang'])) ?> &middot; <?= $ranked['lp'] ?> LP
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
            <?php if (!empty($answered['ranked'])): ?>
                <?php
                    $rk = $answered['ranked'];
                    $lpChange   = $rk['lpChange'];
                    $rankChange = $rk['rankChange'] ?? null;
                    $lpCol      = $lpChange >= 0 ? '#adf3ad' : '#f3adad';
                    $lpSign     = $lpChange >= 0 ? '+' : '';
                    $rkColor    = getRankColor($rk['rang']);
                    $rkName     = getRankName($rk['rang']);
                ?>
                <div class="ranked-feedback-row">
                    <?php
                        $mmrChange  = (int)($rk['mmrChange'] ?? 0);
                        $mmrCol     = $mmrChange >= 0 ? '#adf3ad' : '#f3adad';
                        $mmrSign    = $mmrChange >= 0 ? '+' : '';
                    ?>
                    <span style="color:<?= $lpCol ?>; font-weight:700;"><?= $lpSign ?><?= $lpChange ?> LP</span>
                    &nbsp;&middot;&nbsp;
                    <span style="color:<?= $mmrCol ?>;"><?= $mmrSign ?><?= $mmrChange ?> MMR</span>
                    <span style="color:#aaa;"> (<?= $rk['mmr'] ?>)</span>
                    &nbsp;&middot;&nbsp;
                    <span style="color:<?= $rkColor ?>; font-weight:700;"><?= htmlspecialchars($rkName) ?></span>
                    <?php if ($rk['rang'] >= 14): ?>
                        <span style="color:<?= getRankColor(14) ?>; font-weight:700;"> &mdash; MAX</span>
                    <?php else: ?>
                        <span style="color:#aaa;"> &mdash; <?= $rk['lp'] ?>/100 LP</span>
                    <?php endif; ?>
                    <?php if ($rankChange === 'up'): ?>
                        &nbsp;<span style="color:#ffd700; font-weight:700;">&#9650; Promotion!</span>
                    <?php elseif ($rankChange === 'down'): ?>
                        &nbsp;<span style="color:#f3adad; font-weight:700;">&#9660; Rétrogradation</span>
                    <?php endif; ?>
                </div>
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

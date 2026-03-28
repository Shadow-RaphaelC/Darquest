<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $loggedIn ? ($_SESSION['username'] ?? 'Joueur') : 'Invité';
$isAdmin = $loggedIn && !empty($_SESSION['is_admin']);

$authError = $_SESSION['auth_error'] ?? '';
$authMode = $_SESSION['auth_mode'] ?? 'signin';
unset($_SESSION['auth_error'], $_SESSION['auth_mode']);

// Define nav buttons per page
$navLinks = [];
if ($currentPage === 'panier.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
        'inventaire.php' => 'Inventaire',
        'enigma.php' => 'Enigma',
    ];
} elseif ($currentPage === 'magasin.php') {
    $navLinks = [
        'panier.php' => 'Panier',
        'inventaire.php' => 'Inventaire',
        'enigma.php' => 'Enigma',
    ];
} elseif ($currentPage === 'inventaire.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
        'panier.php' => 'Panier',
    ];
} elseif ($currentPage === 'enigma.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
        'panier.php' => 'Panier',
    ];
}
?>
<header>
    <div class="headerLogo">
        <a href="index.php">
            <img style="height: 220px;" src="img/DarQuestTitle_WHITE.png" alt="DarQuest Logo" class="logo">
        </a>
    </div>
    <div class="separatorBox">
        <img src="img/sep/qq_01_01.png" alt="" class="sep">
        <?php for ($i = 0; $i < 6; $i++): ?>
            <img src="img/sep/qq_01_02.png" alt="" class="sep">
        <?php endfor; ?>
        <img src="img/sep/qq_01_03.png" alt="" class="sep">
    </div>

    <div class="headerBox">

        <!-- Left: page nav buttons -->
        <nav class="headerNavLeft">
            <?php if ($loggedIn && !empty($navLinks)): ?>
                <?php foreach ($navLinks as $href => $label): ?>
                    <a class="headerBtn" href="<?= $href ?>"><?= $label ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <!-- Right: coins, auth, profile -->
        <nav class="headerNav">
            <?php if (!$loggedIn): ?>
                <button class="headerBtn" id="openAuthModalBtn" type="button">
                    Connexion / Inscription
                </button>
            <?php else: ?>
                <form action="auth.php" method="POST" style="display:inline;">
                    <input type="hidden" name="mode" value="logout">
                    <button type="submit" class="headerBtn">Déconnexion</button>
                </form>
                <?php if ($isAdmin): ?>
                    <a class="headerBtn" href="admin.php">Admin</a>
                <?php endif; ?>

                <a class="user-profile" href="profil.php">
                    <div class="coins">
                        <?php
                        $gold = (int) ($_SESSION['gold'] ?? 0);
                        $argent = (int) ($_SESSION['argent'] ?? 0);
                        $bronze = (int) ($_SESSION['bronze'] ?? 0);
                        ?>
                        <span class="gold"><?= $gold ?></span>
                        <span class="silver"><?= $argent ?></span>
                        <span class="bronze"><?= $bronze ?></span>
                    </div>
                    <span class="user-profile-divider"></span>
                    <span class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                    <img class="user-avatar" src="img/placeholder.webp" alt="Avatar">
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$mode = $_POST['mode'] ?? '';

// ─────────────────────────────────────────────
//  CONNEXION
//  Calls: ConnexionDeJoueur(identifiant, motDePasse)
// ─────────────────────────────────────────────
if ($mode === 'signin') {
    $identifier = trim($_POST['authUser']     ?? '');
    $password   = trim($_POST['authPassword'] ?? '');

    if ($identifier === '' || $password === '') {
        $_SESSION['auth_error'] = 'Veuillez remplir tous les champs.';
        $_SESSION['auth_mode']  = 'signin';
        header('Location: index.php');
        exit;
    }

    $pdo = get_pdo();
    if (!$pdo) {
        $_SESSION['auth_error'] = 'Erreur de connexion à la base de données.';
        $_SESSION['auth_mode']  = 'signin';
        header('Location: index.php');
        exit;
    }

try {
    $stmt = $pdo->prepare(
        'SELECT idJoueur, alias, motDePasse, estAdmin
         FROM Joueurs
         WHERE alias = :identifiant1 OR courriel = :identifiant2
         LIMIT 1'
    );
    $stmt->execute([
        ':identifiant1' => $identifier,
        ':identifiant2' => $identifier,
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('ConnexionDeJoueur error: ' . $e->getMessage());
    $_SESSION['auth_error'] = 'Erreur lors de la connexion.';
    $_SESSION['auth_mode']  = 'signin';
    header('Location: index.php');
    exit;
}

if (!$user || !password_verify($password, $user['motDePasse'])) {
        $_SESSION['auth_error'] = 'Identifiant ou mot de passe incorrect.';
        $_SESSION['auth_mode']  = 'signin';
        header('Location: index.php');
        exit;
    }

    session_regenerate_id(false);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id']   = $user['idJoueur'];
    $_SESSION['username']  = $user['alias'];
    $_SESSION['is_admin']  = (bool)$user['estAdmin'];

    // Load coins into session
    $coins = GetJoueurCoins((int)$_SESSION['user_id']);
    $_SESSION['gold']   = $coins['gold'];
    $_SESSION['argent'] = $coins['argent'];
    $_SESSION['bronze'] = $coins['bronze'];

    header('Location: index.php');
    exit;
}

// ─────────────────────────────────────────────
//  INSCRIPTION
//  Calls: CreationDeJoueur(alias, nom, prenom, courriel, motDePasse)
// ─────────────────────────────────────────────
if ($mode === 'signup') {
    $alias    = trim($_POST['signupAlias']    ?? '');
    $prenom   = trim($_POST['signupPrenom']   ?? '');
    $nom      = trim($_POST['signupNom']      ?? '');
    $email    = trim($_POST['signupEmail']    ?? '');
    $password = trim($_POST['signupPassword'] ?? '');

    // ── Basic validation ──────────────────────────────────────────────────────
    if ($alias === '' || $prenom === '' || $nom === '' || $email === '' || $password === '') {
        $_SESSION['auth_error'] = 'Veuillez remplir tous les champs.';
        $_SESSION['auth_mode']  = 'signup';
        header('Location: index.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Adresse courriel invalide.';
        $_SESSION['auth_mode']  = 'signup';
        header('Location: index.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['auth_error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        $_SESSION['auth_mode']  = 'signup';
        header('Location: index.php');
        exit;
    }

    $pdo = get_pdo();
    if (!$pdo) {
        $_SESSION['auth_error'] = 'Erreur de connexion à la base de données.';
        $_SESSION['auth_mode']  = 'signup';
        header('Location: index.php');
        exit;
    }

    // ── Hash the password before sending to DB ────────────────────────────────
    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare(
            'CALL CreationDeJoueur(:alias, :nom, :prenom, :courriel, :motDePasse)'
        );
        $stmt->execute([
            ':alias'      => $alias,
            ':nom'        => $nom,
            ':prenom'     => $prenom,
            ':courriel'   => $email,
            ':motDePasse' => $hash,
        ]);

        $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($stmt->nextRowset()) {}

    } catch (PDOException $e) {
        error_log('CreationDeJoueur error: ' . $e->getMessage());

        if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
            $_SESSION['auth_error'] = 'Cet alias ou courriel est déjà utilisé.';
        } else {
            $_SESSION['auth_error'] = 'Erreur lors de la création du compte.';
        }

        $_SESSION['auth_mode'] = 'signup';
        header('Location: index.php');
        exit;
    }

    // ── Log the new user in immediately ──────────────────────────────────────
    session_regenerate_id(false);
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = $alias;
    $_SESSION['is_admin']  = false;
    
    // Load coins into session
    $coins = GetJoueurCoins((int)$_SESSION['user_id']);
    $_SESSION['gold']   = $coins['gold'];
    $_SESSION['argent'] = $coins['argent'];
    $_SESSION['bronze'] = $coins['bronze'];

    if (!empty($newUser['idJoueur'])) {
        $_SESSION['user_id'] = $newUser['idJoueur'];
    } else {
        $idStmt = $pdo->prepare(
            'SELECT idJoueur FROM Joueurs WHERE alias = :alias LIMIT 1'
        );
        $idStmt->execute([':alias' => $alias]);
        $row = $idStmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $row['idJoueur'] ?? null;
    }

    header('Location: index.php');
    exit;
}

// ─────────────────────────────────────────────
//  DÉCONNEXION
// ─────────────────────────────────────────────
if ($mode === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Fallback
header('Location: index.php');
exit;
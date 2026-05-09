<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$action = $_POST['action'] ?? '';

// ── Étape 1 : validation des champs et envoi du code ─────────────────────────
if ($action === 'request') {
    $alias    = trim($_POST['signupAlias']    ?? '');
    $prenom   = trim($_POST['signupPrenom']   ?? '');
    $nom      = trim($_POST['signupNom']      ?? '');
    $email    = trim($_POST['signupEmail']    ?? '');
    $password = trim($_POST['signupPassword'] ?? '');

    if ($alias === '' || $prenom === '' || $nom === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse courriel invalide.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
        exit;
    }

    // Vérifier que l'alias et le courriel ne sont pas déjà pris
    $pdo = get_pdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion BD.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Joueurs WHERE alias = :alias OR courriel = :email');
        $stmt->execute([':alias' => $alias, ':email' => $email]);
        if ((int) $stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Cet alias ou courriel est déjà utilisé.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log('signup_verify request: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur interne.']);
        exit;
    }

    // Hacher le mot de passe et stocker les données en session
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code  = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }

    $_SESSION['signup_code']    = $code;
    $_SESSION['signup_expires'] = time() + 600;
    $_SESSION['signup_data']    = compact('alias', 'nom', 'prenom', 'email', 'hash');

    $configFile = __DIR__ . '/include/mail_config.php';
    if (!file_exists($configFile)) {
        echo json_encode(['success' => false, 'message' => 'Configuration courriel manquante sur le serveur.']);
        exit;
    }
    require_once __DIR__ . '/include/mailer.php';

    if (!sendVerificationCode($email, $code, 'signup')) {
        echo json_encode(['success' => false, 'message' => 'Impossible d\'envoyer le courriel. Vérifiez l\'adresse saisie.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── Étape 2 : validation du code et création du compte ───────────────────────
if ($action === 'confirm') {
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if (!isset($_SESSION['signup_code'], $_SESSION['signup_expires'], $_SESSION['signup_data'])) {
        echo json_encode(['success' => false, 'message' => 'Aucune inscription en cours. Recommencez.']);
        exit;
    }
    if (time() > (int) $_SESSION['signup_expires']) {
        unset($_SESSION['signup_code'], $_SESSION['signup_expires'], $_SESSION['signup_data']);
        echo json_encode(['success' => false, 'message' => 'Le code a expiré. Recommencez.']);
        exit;
    }
    if ($code !== $_SESSION['signup_code']) {
        echo json_encode(['success' => false, 'message' => 'Code incorrect.']);
        exit;
    }

    $data = $_SESSION['signup_data'];
    $pdo  = get_pdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion BD.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('CALL CreationDeJoueur(:alias, :nom, :prenom, :courriel, :motDePasse)');
        $stmt->execute([
            ':alias'      => $data['alias'],
            ':nom'        => $data['nom'],
            ':prenom'     => $data['prenom'],
            ':courriel'   => $data['email'],
            ':motDePasse' => $data['hash'],
        ]);
        $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($stmt->nextRowset()) {}
    } catch (PDOException $e) {
        error_log('signup_verify confirm: ' . $e->getMessage());
        if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
            echo json_encode(['success' => false, 'message' => 'Cet alias ou courriel est déjà utilisé.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du compte.']);
        }
        exit;
    }

    unset($_SESSION['signup_code'], $_SESSION['signup_expires'], $_SESSION['signup_data']);

    // Connexion automatique
    session_regenerate_id(false);
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = $data['alias'];
    $_SESSION['is_admin']  = false;

    if (!empty($newUser['idJoueur'])) {
        $_SESSION['user_id'] = (int) $newUser['idJoueur'];
    } else {
        $idStmt = $pdo->prepare('SELECT idJoueur FROM Joueurs WHERE alias = :alias LIMIT 1');
        $idStmt->execute([':alias' => $data['alias']]);
        $row = $idStmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = (int) ($row['idJoueur'] ?? 0);
    }

    $coins = GetJoueurCoins((int) $_SESSION['user_id']);
    $_SESSION['gold']       = $coins['gold'];
    $_SESSION['argent']     = $coins['argent'];
    $_SESSION['bronze']     = $coins['bronze'];
    $hp = GetJoueurHP((int) $_SESSION['user_id']);
    $_SESSION['pointDeVie'] = (int) ($hp['pointDeVie'] ?? 0);
    $_SESSION['maxHP']      = (int) ($hp['maxHP']      ?? 100);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
exit;

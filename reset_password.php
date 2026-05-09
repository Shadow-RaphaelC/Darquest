<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$action = $_POST['action'] ?? '';

// ── Étape 1 : envoi du code par courriel ─────────────────────────────────────
if ($action === 'request_reset') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse courriel invalide.']);
        exit;
    }

    $pdo = get_pdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion BD.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT idJoueur FROM Joueurs WHERE courriel = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('request_reset error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur interne.']);
        exit;
    }

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Aucun compte associé à ce courriel.']);
        exit;
    }

    // Code 6 caractères alphanumériques (sans symboles, sans caractères ambigus)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code  = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }

    $_SESSION['reset_code']    = $code;
    $_SESSION['reset_user_id'] = (int) $row['idJoueur'];
    $_SESSION['reset_expires'] = time() + 600; // 10 minutes

    $configFile = __DIR__ . '/include/mail_config.php';
    if (!file_exists($configFile)) {
        echo json_encode(['success' => false, 'message' => 'Configuration courriel manquante sur le serveur (mail_config.php).']);
        exit;
    }
    require_once __DIR__ . '/include/mailer.php';

    if (!sendVerificationCode($email, $code, 'reset')) {
        echo json_encode(['success' => false, 'message' => 'Impossible d\'envoyer le courriel. Réessayez.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── Étape 2 : validation du code et nouveau mot de passe ─────────────────────
if ($action === 'validate_reset') {
    $code     = strtoupper(trim($_POST['code']     ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (!isset($_SESSION['reset_code'], $_SESSION['reset_user_id'], $_SESSION['reset_expires'])) {
        echo json_encode(['success' => false, 'message' => 'Aucune réinitialisation en cours. Recommencez depuis le début.']);
        exit;
    }

    if (time() > (int) $_SESSION['reset_expires']) {
        unset($_SESSION['reset_code'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
        echo json_encode(['success' => false, 'message' => 'Le code a expiré. Recommencez.']);
        exit;
    }

    if ($code !== $_SESSION['reset_code']) {
        echo json_encode(['success' => false, 'message' => 'Code incorrect.']);
        exit;
    }

    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']);
        exit;
    }

    $userId = (int) $_SESSION['reset_user_id'];
    $result = ResetMotDePasse($userId, $password);

    if ($result['success']) {
        unset($_SESSION['reset_code'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
    }

    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
exit;

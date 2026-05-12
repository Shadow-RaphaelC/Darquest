<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

header('Content-Type: application/json');

$action     = $_POST['action'] ?? '';
$isLoggedIn = !empty($_SESSION['logged_in']);
$isAdmin    = !empty($_SESSION['is_admin']);
$userId     = (int)($_SESSION['user_id'] ?? 0);
$userAlias  = $_SESSION['username'] ?? '';

switch ($action) {
    case 'get':
        $idItem = (int)($_POST['idItem'] ?? 0);
        if ($idItem <= 0) {
            echo json_encode(['success' => false, 'message' => 'Item invalide.']);
            exit;
        }
        try {
            $comments = GetCommentairesItem($idItem);
            echo json_encode(['success' => true, 'comments' => $comments]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add':
        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
            exit;
        }
        $commentaire = trim($_POST['commentaire'] ?? '');
        $evaluation  = (int)($_POST['evaluation'] ?? 0);
        $idItem      = (int)($_POST['idItem'] ?? 0);
        if ($commentaire === '' || $evaluation < 1 || $evaluation > 5 || $idItem <= 0) {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
            exit;
        }
        if (mb_strlen($commentaire) > 200) {
            echo json_encode(['success' => false, 'message' => 'Commentaire trop long (200 caractères max).']);
            exit;
        }
        $result = AjouterCommentaireItem($commentaire, $evaluation, $userAlias, $idItem);
        echo json_encode($result);
        break;

    case 'delete':
        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
            exit;
        }
        $idCommentaire = (int)($_POST['idCommentaire'] ?? 0);
        if ($idCommentaire <= 0) {
            echo json_encode(['success' => false, 'message' => 'Commentaire invalide.']);
            exit;
        }
        $result = SupprimerCommentaireItem($idCommentaire, $userId, $isAdmin);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}

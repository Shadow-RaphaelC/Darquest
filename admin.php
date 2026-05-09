<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

$adminFeedback = null;

// ── Add enigma ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_enigma') {
    $enigme      = trim($_POST['enigme']      ?? '');
    $difficulte  = trim($_POST['difficulte']  ?? '');
    $idCategorie = trim($_POST['idCategorie'] ?? '');
    $reponses    = $_POST['reponse']          ?? [];
    $bonneRep    = (int) ($_POST['bonneReponse'] ?? 0);

    $typeEnigme    = ($_POST['type_enigme'] ?? '') === 'vrai_faux' ? 'vrai_faux' : 'choix_multiple';
    $expectedCount = $typeEnigme === 'vrai_faux' ? 2 : 4;

    $validDiff  = in_array($difficulte,  ['F', 'M', 'D'], true);
    $validCat   = in_array($idCategorie, ['F', 'M', 'D', 'G'], true);
    $validRep   = count($reponses) === $expectedCount && !in_array('', array_map('trim', $reponses), true);
    $validBonne = $bonneRep >= 1 && $bonneRep <= $expectedCount;

    if (!$enigme || !$validDiff || !$validCat || !$validRep || !$validBonne) {
        $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Veuillez remplir tous les champs correctement.'];
    } else {
        $result = AjouterEnigme($enigme, $difficulte, $idCategorie, $reponses, $bonneRep);
        $_SESSION['admin_feedback'] = $result['success']
            ? ['type' => 'success', 'message' => 'Énigme #' . $result['idEnigma'] . ' ajoutée avec succès.']
            : ['type' => 'error',   'message' => $result['message']];
    }

    header('Location: admin.php#enigmes');
    exit;
}

// ── Update item (quantity + price) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_item') {
    $idItem   = (int) ($_POST['idItem']   ?? 0);
    $quantite = max(0, (int) ($_POST['quantite'] ?? 0));
    $prix     = max(0, (int) ($_POST['prix']     ?? 0));
    if ($idItem > 0) {
        $result = UpdateItemShop($idItem, $quantite, $prix);
        $_SESSION['admin_feedback'] = $result['success']
            ? ['type' => 'success', 'message' => 'Item mis à jour.']
            : ['type' => 'error',   'message' => $result['message']];
    }
    header('Location: admin.php#magasin');
    exit;
}

// ── Add new item ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_item') {
    $nom      = trim($_POST['nom']      ?? '');
    $typeItem = trim($_POST['typeItem'] ?? '');
    $quantite = max(0, (int)($_POST['quantite'] ?? 0));
    $prix     = max(0, (int)($_POST['prix']     ?? 0));
    $image    = trim($_POST['image']    ?? '');
    $isDisp   = !empty($_POST['estDisponible']);

    $validTypes = ['A', 'R', 'P', 'S'];
    if ($nom === '' || !in_array($typeItem, $validTypes, true)) {
        $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Veuillez remplir tous les champs obligatoires.'];
        header('Location: admin.php#add-item');
        exit;
    }

    $sub = [];
    if ($typeItem === 'A') {
        $sub = [
            'efficacite'  => trim($_POST['efficacite'] ?? 'efficace'),
            'genre'       => trim($_POST['genre']                    ?? ''),
            'description' => trim($_POST['arme_description']         ?? ''),
        ];
    } elseif ($typeItem === 'R') {
        $sub = [
            'matiere' => trim($_POST['matiere'] ?? ''),
            'taille'  => trim($_POST['taille']  ?? ''),
        ];
    } elseif ($typeItem === 'P') {
        $sub = [
            'effet'   => trim($_POST['effet']  ?? ''),
            'duree'   => max(0,  (int)($_POST['duree']   ?? 0)),
            'healPct' => max(0, min(100, (int)($_POST['healPct'] ?? 15))),
        ];
    } elseif ($typeItem === 'S') {
        $sub = [
            'typeSorts'     => trim($_POST['typeSorts']    ?? ''),
            'rarete'        => max(1, (int)($_POST['rarete'] ?? 1)),
            'estInstantane' => !empty($_POST['estInstantane']),
        ];
        if ($sub['typeSorts'] === '') {
            $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Veuillez choisir un type de sort.'];
            header('Location: admin.php#add-item');
            exit;
        }
    }

    $result = AddItemToShop($nom, $quantite, $prix, $typeItem, $image, $isDisp, $sub);
    $_SESSION['admin_feedback'] = $result['success']
        ? ['type' => 'success', 'message' => 'Item #' . $result['idItem'] . ' créé avec succès.']
        : ['type' => 'error',   'message' => $result['message']];
    header('Location: admin.php#add-item');
    exit;
}

// ── Update sort heal ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_sort_heal') {
    $typeSorts = trim($_POST['typeSorts'] ?? '');
    $ptVie     = max(0, (int) ($_POST['ptVie'] ?? 0));
    if ($typeSorts !== '') {
        $result = UpdateSortHeal($typeSorts, $ptVie);
        $_SESSION['admin_feedback'] = $result['success']
            ? ['type' => 'success', 'message' => 'Soin du sort mis à jour.']
            : ['type' => 'error',   'message' => $result['message']];
    }
    header('Location: admin.php#sorts');
    exit;
}

// ── Update potion heal ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_potion_heal') {
    $idItem  = (int) ($_POST['idItem']  ?? 0);
    $healPct = max(0, min(100, (int) ($_POST['healPct'] ?? 0)));
    if ($idItem > 0) {
        $result = UpdatePotionHeal($idItem, $healPct);
        $_SESSION['admin_feedback'] = $result['success']
            ? ['type' => 'success', 'message' => 'Soin de la potion mis à jour.']
            : ['type' => 'error',   'message' => $result['message']];
    }
    header('Location: admin.php#potions');
    exit;
}

if (!empty($_SESSION['admin_feedback'])) {
    $adminFeedback = $_SESSION['admin_feedback'];
    unset($_SESSION['admin_feedback']);
}

// ── Fetch data for display ────────────────────────────────────────────────────
$allItems   = GetAllItemsForAdmin();
$sortTypes  = GetSortTypesForAdmin();
$potions    = GetPotionsForAdmin();

function adminTypeLabel(string $code): string {
    $c = strtoupper(trim($code));
    return match(true) {
        $c === 'A' || $c === 'ARME'   => 'Arme',
        $c === 'R' || $c === 'ARMURE' => 'Armure',
        $c === 'P' || $c === 'POTION' => 'Potion',
        $c === 'S' || $c === 'SORT'   => 'Sort',
        default                        => ucfirst(strtolower($code)),
    };
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Admin</title>
    <style>
        .admin-table { width:100%; border-collapse:collapse; font-size:0.9em; margin-top:10px; }
        .admin-table th { text-align:left; padding:7px 10px; border-bottom:2px solid #333; color:#aaa; font-weight:600; }
        .admin-table td { padding:6px 10px; border-bottom:1px solid #222; vertical-align:middle; }
        .admin-table tr:hover td { background:rgba(255,255,255,0.03); }
        .admin-num-input { width:80px; background:#1a1a1a; border:1px solid #444; color:#fff; padding:4px 7px; border-radius:4px; text-align:center; }
        .admin-num-input:focus { outline:none; border-color:#888; }
        .admin-save-btn { padding:4px 12px; background:#2a5c2a; border:1px solid #4a9c4a; color:#adfaad; border-radius:4px; cursor:pointer; font-size:0.85em; }
        .admin-save-btn:hover { background:#356835; }
        .admin-section { margin-bottom:40px; }
        .admin-tag { display:inline-block; padding:2px 7px; border-radius:3px; font-size:0.78em; font-weight:600; }
        .tag-arme    { background:#3a2a0a; color:#f0a040; }
        .tag-armure  { background:#0a2a3a; color:#40a0f0; }
        .tag-potion  { background:#2a0a3a; color:#c080f0; }
        .tag-sort    { background:#0a3a2a; color:#40f0a0; }
        .admin-layout { display:flex; gap:20px; align-items:flex-start; }
        .admin-tabs { display:flex; flex-direction:column; gap:4px; min-width:170px; position:sticky; top:20px; }
        .admin-tab-btn { padding:10px 16px; background:#1a1a1a; border:1px solid #333; color:#aaa; border-radius:6px; cursor:pointer; font-size:0.9em; text-align:left; transition:border-color .15s, color .15s; }
        .admin-tab-btn:hover { border-color:#666; color:#fff; }
        .admin-tab-btn.active { background:#1e1e38; border-color:#5555aa; color:#aaaaff; font-weight:600; }
        .admin-content { flex:1; min-width:0; }
        .admin-panel { display:none; }
        .admin-panel.active { display:block; }
    </style>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Panneau d'administration</h1>

        <?php if ($adminFeedback): ?>
            <p class="auth-error-banner" <?= $adminFeedback['type'] === 'success' ? 'style="background:rgba(43,143,43,0.25);border-color:rgba(100,220,100,0.5);color:#adfaad;"' : '' ?>>
                <?= htmlspecialchars($adminFeedback['message']) ?>
            </p>
        <?php endif; ?>

        <div class="admin-layout">
        <div class="admin-tabs">
            <button class="admin-tab-btn" data-tab="magasin">Magasin</button>
            <button class="admin-tab-btn" data-tab="sorts">Sorts</button>
            <button class="admin-tab-btn" data-tab="potions">Potions</button>
            <button class="admin-tab-btn" data-tab="enigmes">Ajouter une énigme</button>
            <button class="admin-tab-btn" data-tab="add-item">Ajouter un item</button>
        </div>
        <div class="admin-content">

        <!-- ── Magasin: stock & prix ──────────────────────────────────────── -->
        <div class="admin-panel admin-section" id="magasin">
            <h2>Magasin — Stock &amp; Prix</h2>
            <?php if (empty($allItems)): ?>
                <p style="color:#aaa;">Aucun item trouvé.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Prix (gold)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allItems as $item):
                    $label = adminTypeLabel((string)($item['typeItem'] ?? ''));
                    $tagClass = 'tag-' . strtolower($label);
                ?>
                    <tr>
                        <td style="color:#555;"><?= (int)$item['idItem'] ?></td>
                        <td><?= htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="admin-tag <?= htmlspecialchars($tagClass) ?>"><?= htmlspecialchars($label) ?></span></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:contents;">
                                <input type="hidden" name="action" value="update_item">
                                <input type="hidden" name="idItem" value="<?= (int)$item['idItem'] ?>">
                                <input type="number" name="quantite" class="admin-num-input" value="<?= (int)$item['quantite'] ?>" min="0" max="9999">
                        </td>
                        <td>
                                <input type="number" name="prix" class="admin-num-input" value="<?= (int)$item['prix'] ?>" min="0" max="999999">
                        </td>
                        <td>
                                <button type="submit" class="admin-save-btn">Sauvegarder</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Sorts: soin ───────────────────────────────────────────────── -->
        <div class="admin-panel admin-section" id="sorts">
            <h2>Sorts — HP soignés</h2>
            <p style="color:#aaa; font-size:0.85em; margin-bottom:6px;">Modifier le soin s'applique à tous les sorts de ce type.</p>
            <?php if (empty($sortTypes)): ?>
                <p style="color:#aaa;">Aucun type de sort trouvé.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Items</th>
                        <th>HP soignés</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sortTypes as $st): ?>
                    <tr>
                        <td style="color:#555;"><?= htmlspecialchars($st['typeSorts'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($st['Description'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="color:#aaa; text-align:center;"><?= (int)$st['nbItems'] ?></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:contents;">
                                <input type="hidden" name="action" value="update_sort_heal">
                                <input type="hidden" name="typeSorts" value="<?= htmlspecialchars($st['typeSorts'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="number" name="ptVie" class="admin-num-input" value="<?= (int)$st['ptVie'] ?>" min="0" max="9999">
                        </td>
                        <td>
                                <button type="submit" class="admin-save-btn">Sauvegarder</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Potions: soin ──────────────────────────────────────────────── -->
        <div class="admin-panel admin-section" id="potions">
            <h2>Potions — % de soins</h2>
            <p style="color:#aaa; font-size:0.85em; margin-bottom:6px;">Pourcentage des HP max restaurés à l'utilisation (0–100).</p>
            <?php if (empty($potions)): ?>
                <p style="color:#aaa;">Aucune potion trouvée.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Effet</th>
                        <th>% soins</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($potions as $pot): ?>
                    <tr>
                        <td><?= htmlspecialchars($pot['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="color:#aaa; font-size:0.85em;"><?= htmlspecialchars($pot['effet'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:contents;">
                                <input type="hidden" name="action" value="update_potion_heal">
                                <input type="hidden" name="idItem" value="<?= (int)$pot['idItem'] ?>">
                                <input type="number" name="healPct" class="admin-num-input" style="width:65px;" value="<?= (int)$pot['healPct'] ?>" min="0" max="100"> %
                        </td>
                        <td>
                                <button type="submit" class="admin-save-btn">Sauvegarder</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Ajouter une énigme ─────────────────────────────────────────── -->
        <div class="admin-panel admin-section" id="enigmes">
            <h2>Ajouter une Énigme</h2>

            <form class="admin-form" action="admin.php" method="POST">
                <input type="hidden" name="action" value="add_enigma">

                <div class="admin-form-field">
                    <label for="enigme">Texte de l'énigme</label>
                    <textarea id="enigme" name="enigme" maxlength="300" rows="4" placeholder="Entrez l'énigme..." required></textarea>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-field">
                        <label for="difficulte">Difficulté</label>
                        <select id="difficulte" name="difficulte" required>
                            <option value="" disabled selected>-- Choisir --</option>
                            <option value="F">F — Facile</option>
                            <option value="M">M — Moyen</option>
                            <option value="D">D — Difficile</option>
                        </select>
                    </div>

                    <div class="admin-form-field">
                        <label for="idCategorie">Catégorie</label>
                        <select id="idCategorie" name="idCategorie" required>
                            <option value="" disabled selected>-- Choisir --</option>
                            <option value="F">F — Facile</option>
                            <option value="M">M — Moyen</option>
                            <option value="D">D — Difficile</option>
                            <option value="G">G — Mage</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="type_enigme" id="type_enigme" value="choix_multiple">

                <fieldset class="admin-responses">
                    <legend>Réponses <span class="admin-responses-hint">(cochez la bonne réponse)</span></legend>

                    <div class="admin-type-toggle">
                        <button type="button" class="admin-toggle-btn active" id="btnChoix">Choix multiples</button>
                        <button type="button" class="admin-toggle-btn" id="btnVraiFaux">Vrai / Faux</button>
                    </div>

                    <div class="admin-response-row" id="response-row-1">
                        <input type="radio" name="bonneReponse" value="1" id="correct_1" required>
                        <label for="correct_1" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[1]" id="reponse_1" maxlength="45" placeholder="Réponse 1" required>
                    </div>
                    <div class="admin-response-row" id="response-row-2">
                        <input type="radio" name="bonneReponse" value="2" id="correct_2">
                        <label for="correct_2" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[2]" id="reponse_2" maxlength="45" placeholder="Réponse 2" required>
                    </div>
                    <div class="admin-response-row" id="response-row-3">
                        <input type="radio" name="bonneReponse" value="3" id="correct_3">
                        <label for="correct_3" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[3]" id="reponse_3" maxlength="45" placeholder="Réponse 3" required>
                    </div>
                    <div class="admin-response-row" id="response-row-4">
                        <input type="radio" name="bonneReponse" value="4" id="correct_4">
                        <label for="correct_4" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[4]" id="reponse_4" maxlength="45" placeholder="Réponse 4" required>
                    </div>
                </fieldset>

                <div class="admin-form-actions">
                    <button type="submit" class="btn-primary">Ajouter l'énigme</button>
                    <button type="reset" class="btn-reset">Réinitialiser</button>
                </div>

                <p id="adminFormError" class="auth-error-banner" style="display:none;margin-top:12px;"></p>
            </form>
        </div>

        <!-- ── Ajouter un item ────────────────────────────────────────────── -->
        <div class="admin-panel admin-section" id="add-item">
            <h2>Ajouter un item</h2>
            <form class="admin-form" action="admin.php" method="POST" id="addItemForm">
                <input type="hidden" name="action" value="add_item">

                <div class="admin-form-row">
                    <div class="admin-form-field">
                        <label for="ai_nom">Nom *</label>
                        <input type="text" id="ai_nom" name="nom" maxlength="100" required>
                    </div>
                    <div class="admin-form-field">
                        <label for="ai_type">Type *</label>
                        <select id="ai_type" name="typeItem" required>
                            <option value="" disabled selected>-- Choisir --</option>
                            <option value="A">Arme</option>
                            <option value="R">Armure</option>
                            <option value="P">Potion</option>
                            <option value="S">Sort</option>
                        </select>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-field">
                        <label for="ai_quantite">Quantité</label>
                        <input type="number" id="ai_quantite" name="quantite" class="admin-num-input" value="0" min="0" max="9999">
                    </div>
                    <div class="admin-form-field">
                        <label for="ai_prix">Prix (gold)</label>
                        <input type="number" id="ai_prix" name="prix" class="admin-num-input" value="0" min="0" max="999999">
                    </div>
                    <div class="admin-form-field">
                        <label for="ai_image">Image (chemin)</label>
                        <input type="text" id="ai_image" name="image" maxlength="200" placeholder="img/items/example.webp">
                    </div>
                    <div class="admin-form-field" style="justify-content:flex-end; padding-top:22px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="estDisponible" value="1" checked> Disponible
                        </label>
                    </div>
                </div>

                <!-- Arme -->
                <div id="sub_arme" class="admin-subtype-fields" style="display:none;">
                    <div class="admin-form-row">
                        <div class="admin-form-field">
                            <label for="ai_genre">Genre</label>
                            <select id="ai_genre" name="genre">
                                <option value="Epee" selected>Épée</option>
                                <option value="Epee a deux main">Épée à deux mains</option>
                                <option value="Glaive">Glaive</option>
                                <option value="Hache">Hache</option>
                                <option value="Hache a deux main">Hache à deux mains</option>
                                <option value="Dague">Dague</option>
                                <option value="Arc">Arc</option>
                                <option value="Baton">Bâton</option>
                            </select>
                        </div>
                        <div class="admin-form-field">
                            <label for="ai_efficacite">Efficacité</label>
                            <select id="ai_efficacite" name="efficacite">
                                <option value="pas efficace">Pas efficace</option>
                                <option value="efficace" selected>Efficace</option>
                                <option value="tres efficace">Très efficace</option>
                                <option value="tres tres efficace">Très très efficace</option>
                                <option value="tres tres tres efficace">Très très très efficace</option>
                            </select>
                        </div>
                    </div>
                    <div class="admin-form-field">
                        <label for="ai_arme_desc">Description</label>
                        <input type="text" id="ai_arme_desc" name="arme_description" maxlength="200">
                    </div>
                </div>

                <!-- Armure -->
                <div id="sub_armure" class="admin-subtype-fields" style="display:none;">
                    <div class="admin-form-row">
                        <div class="admin-form-field">
                            <label for="ai_matiere">Matière</label>
                            <select id="ai_matiere" name="matiere">
                                <option value="Tissu">Tissu</option>
                                <option value="Cuir" selected>Cuir</option>
                                <option value="Maille">Maille</option>
                                <option value="Plaques">Plaques</option>
                            </select>
                        </div>
                        <div class="admin-form-field">
                            <label for="ai_taille">Taille</label>
                            <select id="ai_taille" name="taille">
                                <option value="Petit">Petit</option>
                                <option value="Moyen" selected>Moyen</option>
                                <option value="Large">Large</option>
                                <option value="Extra Large">Extra Large</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Potion -->
                <div id="sub_potion" class="admin-subtype-fields" style="display:none;">
                    <div class="admin-form-row">
                        <div class="admin-form-field">
                            <label for="ai_effet">Effet</label>
                            <input type="text" id="ai_effet" name="effet" maxlength="100" placeholder="Soin mineur…">
                        </div>
                        <div class="admin-form-field">
                            <label for="ai_duree">Durée (tours)</label>
                            <input type="number" id="ai_duree" name="duree" class="admin-num-input" value="0" min="0">
                        </div>
                        <div class="admin-form-field">
                            <label for="ai_healPct">% soins (0–100)</label>
                            <input type="number" id="ai_healPct" name="healPct" class="admin-num-input" style="width:65px;" value="15" min="0" max="100">
                        </div>
                    </div>
                </div>

                <!-- Sort -->
                <div id="sub_sort" class="admin-subtype-fields" style="display:none;">
                    <div class="admin-form-row">
                        <div class="admin-form-field">
                            <label for="ai_typeSorts">Type de sort</label>
                            <select id="ai_typeSorts" name="typeSorts">
                                <option value="" disabled selected>-- Choisir --</option>
                                <?php foreach ($sortTypes as $st): ?>
                                    <option value="<?= htmlspecialchars($st['typeSorts'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($st['typeSorts'] . ' — ' . $st['Description'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form-field">
                            <label for="ai_rarete">Rareté</label>
                            <input type="number" id="ai_rarete" name="rarete" class="admin-num-input" value="1" min="1">
                        </div>
                        <div class="admin-form-field" style="justify-content:flex-end; padding-top:22px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="estInstantane" value="1"> Instantané
                            </label>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn-primary">Créer l'item</button>
                    <button type="reset" class="btn-reset">Réinitialiser</button>
                </div>
                <p id="addItemError" class="auth-error-banner" style="display:none; margin-top:12px;"></p>
            </form>
        </div>
        </div><!-- /admin-content -->
        </div><!-- /admin-layout -->

    </main>
    <?php require 'include/footer.php'; ?>

    <script>
    (function () {
        const btnChoix    = document.getElementById('btnChoix');
        const btnVraiFaux = document.getElementById('btnVraiFaux');
        const typeInput   = document.getElementById('type_enigme');
        const row3        = document.getElementById('response-row-3');
        const row4        = document.getElementById('response-row-4');
        const rep1        = document.getElementById('reponse_1');
        const rep2        = document.getElementById('reponse_2');
        const rep3        = document.getElementById('reponse_3');
        const rep4        = document.getElementById('reponse_4');

        function setChoixMultiples() {
            typeInput.value = 'choix_multiple';
            btnChoix.classList.add('active');
            btnVraiFaux.classList.remove('active');
            row3.style.display = '';
            row4.style.display = '';
            [rep1, rep2, rep3, rep4].forEach(function (r) {
                r.readOnly = false;
                r.required = true;
                r.placeholder = 'Réponse ' + r.name.match(/\[(\d)\]/)[1];
                r.value = '';
            });
        }

        function setVraiFaux() {
            typeInput.value = 'vrai_faux';
            btnVraiFaux.classList.add('active');
            btnChoix.classList.remove('active');
            row3.style.display = 'none';
            row4.style.display = 'none';
            rep3.required = false;
            rep3.removeAttribute('name');
            rep4.required = false;
            rep4.removeAttribute('name');
            rep1.value = 'Vrai'; rep1.readOnly = true; rep1.required = true;
            rep2.value = 'Faux'; rep2.readOnly = true; rep2.required = true;
            const checked = document.querySelector('input[name="bonneReponse"]:checked');
            if (checked && (checked.value === '3' || checked.value === '4')) {
                document.getElementById('correct_1').checked = true;
            }
        }

        btnChoix.addEventListener('click', function () {
            rep3.name = 'reponse[3]';
            rep4.name = 'reponse[4]';
            setChoixMultiples();
        });
        btnVraiFaux.addEventListener('click', setVraiFaux);

        // ── Add item: show/hide subtype fields on type change ─────────────────
        (function () {
            const typeSelect = document.getElementById('ai_type');
            const subPanels  = {
                A: document.getElementById('sub_arme'),
                R: document.getElementById('sub_armure'),
                P: document.getElementById('sub_potion'),
                S: document.getElementById('sub_sort'),
            };
            if (!typeSelect) return;
            typeSelect.addEventListener('change', function () {
                Object.values(subPanels).forEach(function (p) { if (p) p.style.display = 'none'; });
                const sel = subPanels[typeSelect.value];
                if (sel) sel.style.display = '';
            });
        })();

        document.querySelector('#enigmes .admin-form').addEventListener('submit', function (e) {
            const errorBox = document.getElementById('adminFormError');
            const errors   = [];
            const isVraiFaux = typeInput.value === 'vrai_faux';
            const enigme = document.getElementById('enigme').value;
            if (enigme.length > 300) errors.push('Le texte dépasse 300 caractères (' + enigme.length + '/300).');
            const activeReps = isVraiFaux ? [rep1, rep2] : [rep1, rep2, rep3, rep4];
            activeReps.forEach(function (input, idx) {
                if (input.value.trim() === '') errors.push('La réponse ' + (idx + 1) + ' est vide.');
                else if (input.value.length > 45) errors.push('La réponse ' + (idx + 1) + ' dépasse 45 caractères.');
            });
            if (!document.querySelector('input[name="bonneReponse"]:checked')) errors.push('Veuillez cocher la bonne réponse.');
            if (errors.length > 0) {
                e.preventDefault();
                errorBox.innerHTML = errors.join('<br>');
                errorBox.style.display = '';
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                errorBox.style.display = 'none';
            }
        });
    })();

    // ── Admin tabs ────────────────────────────────────────────────────────────
    (function () {
        const tabs   = document.querySelectorAll('.admin-tab-btn');
        const panels = document.querySelectorAll('.admin-panel');

        function activate(tabId) {
            tabs.forEach(function (t) {
                t.classList.toggle('active', t.dataset.tab === tabId);
            });
            panels.forEach(function (p) {
                p.classList.toggle('active', p.id === tabId);
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activate(tab.dataset.tab);
                history.replaceState(null, '', '#' + tab.dataset.tab);
            });
        });

        // Respect the URL hash (used by server-side redirects after save)
        const hash = window.location.hash.slice(1);
        const valid = Array.from(tabs).some(function (t) { return t.dataset.tab === hash; });
        activate(valid ? hash : 'magasin');
    })();
    </script>
</body>

</html>

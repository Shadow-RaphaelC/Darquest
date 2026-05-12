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
    <title>DarQuest Magasin</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Magasin</h1>
        <p>Bienvenue dans le magasin.</p>

        <div class="shop-filters">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Rechercher un item" class="search-input">
            </div>
            <div class="checkboxes">
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Arme"
                        onchange="applyFilters()"> Arme</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Armure"
                        onchange="applyFilters()"> Armure</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Potion"
                        onchange="applyFilters()"> Potions</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Sort"
                        onchange="applyFilters()"> Sorts</label>
            </div>
            <div class="radioButtons">
                <label class="filter-radio"><input type="radio" name="sort" value="no_sort" onchange="applyFilters()"
                        checked>
                    Aucun tri
                </label>

                <label class="filter-radio"><input type="radio" name="sort" value="price_asc" onchange="applyFilters()">
                    Prix croissant
                </label>
                <label class="filter-radio"><input type="radio" name="sort" value="price_desc"
                        onchange="applyFilters()"> Prix décroissant
                </label>
            </div>

        </div>

        <div class="item-grid-4" id="itemGrid"
            style="display:flex; flex-wrap:wrap; gap:16px; justify-content:space-around;">
            <?php
            $products = AfficherItems();
            if (!is_array($products))
                $products = [];
            $commentStats = GetAllItemsCommentStats();

            foreach ($products as $p):
                $id = $p[0] ?? null;
                $nom = $p[1] ?? '';
                $quantity = (int) ($p[2] ?? 0);
                $typeItem = $p[3] ?? '';
                $price = (int) ($p[4] ?? 0);
                $image = $p[5] ?? '';
                $isDisponible = $p[6] ?? true;

                if (filter_var($isDisponible) === false)
                    continue;

                $typeCode = strtoupper(trim((string) $typeItem));
                if ($typeCode === 'A' || $typeCode === 'ARME')
                    $typeLabel = 'Arme';
                else if ($typeCode === 'R' || $typeCode === 'ARMURE')
                    $typeLabel = 'Armure';
                else if ($typeCode === 'P' || $typeCode === 'POTION')
                    $typeLabel = 'Potion';
                else if ($typeCode === 'S' || $typeCode === 'SORT')
                    $typeLabel = 'Sort';
                else if ($typeCode !== '')
                    $typeLabel = ucfirst(strtolower($typeCode));
                else
                    $typeLabel = 'Autre';
                
                $isLogged = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
                $isMage = false;
                if ($isLogged && isset($_SESSION['user_id']) && $typeCode === 'S') {
                    $mageStatus = GetMageStatus((int)$_SESSION['user_id']);
                    $isMage = (int)$mageStatus['estMage'] === 1;
                }
                $isSpell = ($typeCode === 'S' || $typeCode === 'SORT');
                ?>
                <div class="itemBox" data-type="<?= htmlspecialchars($typeLabel) ?>" data-price="<?= $price ?>"
                    data-name="<?= htmlspecialchars(strtolower($nom)) ?>" data-order="<?= htmlspecialchars($p[0] ?? '') ?>">
                    <div class="item-img-wrapper" style="cursor:pointer;" onclick="openItemModal(
        '<?= htmlspecialchars($image, ENT_QUOTES) ?>',
        '<?= htmlspecialchars($nom, ENT_QUOTES) ?>',
        '<?= htmlspecialchars($typeLabel, ENT_QUOTES) ?>',
        <?= $quantity ?>,
        <?= $price ?>,
        <?= intval($id) ?>
    )">
                        <img class="item-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($nom) ?>">
                    </div>
                    <div class="item-info">
                        <h3 class="titre"><?= htmlspecialchars($nom) ?></h3>
                        <p class="item-type">Type : <?= htmlspecialchars($typeLabel) ?></p>
                        <p class="description">Quantité : <?= $quantity ?></p>
                        <p class="prixOr"><?= number_format($price, 0, '', '') ?> gold</p>
                        <?php $cs = $commentStats[$id] ?? null; if ($cs && $cs['total'] > 0): ?>
                        <p class="item-rating">
                            <span class="item-rating-stars"><?= str_repeat('★', (int)round($cs['moyenne'])) . str_repeat('☆', 5 - (int)round($cs['moyenne'])) ?></span>
                            <span class="item-rating-pct"><?= round(($cs['moyenne'] / 5) * 100) ?>%</span>
                            <span class="item-rating-count">(<?= $cs['total'] ?> avis)</span>
                        </p>
                        <?php endif; ?>
                        <div class="btnPanier">
                            <?php if ($quantity > 0): ?>
                                <?php if (!$isLogged): ?>
                                    <span class="btnPanierImg--disabled">Connectez-vous</span>
                                <?php elseif ($isSpell && !$isMage): ?>
                                    <span class="btnPanierImg--disabled">Vous n'etes pas Mage</span>
                                <?php else: ?>
                                    <form method="GET" action="panier.php" target="panier-frame">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="id" value="<?= intval($id) ?>">
                                        <button type="submit" class="btnPanierImg-btn">
                                            <img src="img/addToCart.png" class="btnPanierImg" alt="Ajouter au panier">
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="btnPanierImg--disabled">Rupture de stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p id="noResults" style="display:none; opacity:0.5; margin-top:2rem;">Aucun objet trouvé.</p>
    </main>

    <?php require 'include/footer.php'; ?>
    <iframe name="panier-frame" style="display:none;"></iframe>

    <!-- Item detail modal -->
    <div id="itemModalOverlay" class="modal-overlay" aria-hidden="true">
        <div class="modal">
            <button id="closeItemModalBtn" class="modal-close" type="button">&times;</button>
            <div id="itemModalContent" style="text-align:center;">
                <div class="item-img-wrapper" style="border-radius:10px; overflow:hidden; margin-bottom:16px;">
                    <img id="modalImg" src="" alt="" class="item-img" style="transform:scale(1);">
                </div>
                <h2 id="modalNom" class="titre" style="margin-bottom:8px;"></h2>
                <p id="modalType" class="item-type"></p>
                <p id="modalQte" class="description"></p>
                <p id="modalPrix" class="prixOr"></p>
                <div id="modalDetails"></div>
                <div id="modalBtn" class="btnPanier" style="margin-top:16px;"></div>
                <hr class="modal-divider">
                <div id="modalComments" class="modal-comments">
                    <p class="comment-section-title">Avis des joueurs</p>
                    <div id="commentStats" class="comment-stats-area"></div>
                    <div id="commentList" class="comment-list-area"></div>
                    <div id="commentForm" class="comment-form-area"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ---- ITEM SUB-DETAILS ----
        const itemDetails = <?= json_encode(GetAllItemsSubDetails()) ?>;

        function renderItemDetails(details) {
            if (!details || !details.category) return '';
            let rows = '';
            if (details.category === 'sort') {
                rows += `<tr><td>Type de sort</td><td>${details.typeDescription}</td></tr>`;
                rows += `<tr><td>Points de vie</td><td>${details.ptVie}</td></tr>`;
                rows += `<tr><td>Points de dégâts</td><td>${details.ptDegat}</td></tr>`;
                rows += `<tr><td>Instantané</td><td>${details.estInstantane ? 'Oui' : 'Non'}</td></tr>`;
                rows += `<tr><td>Rareté</td><td>${details.rarete}</td></tr>`;
            } else if (details.category === 'arme') {
                rows += `<tr><td>Efficacité</td><td>${details.efficacite}</td></tr>`;
                rows += `<tr><td>Genre</td><td>${details.genre}</td></tr>`;
                if (details.description) rows += `<tr><td>Description</td><td>${details.description}</td></tr>`;
            } else if (details.category === 'potion') {
                rows += `<tr><td>Effet</td><td>${details.effet}</td></tr>`;
                rows += `<tr><td>Durée</td><td>${details.duree} Secondes</td></tr>`;
            } else if (details.category === 'armure') {
                rows += `<tr><td>Matière</td><td>${details.matiere}</td></tr>`;
                rows += `<tr><td>Taille</td><td>${details.taille}</td></tr>`;
            }
            return rows ? `<table class="modal-details-table">${rows}</table>` : '';
        }

        // ---- USER STATE ----
        const isLoggedIn = <?= isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'true' : 'false' ?>;
        const userId = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : '0' ?>;
        let userIsMage = false;
        
        <?php
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_id'])) {
            $mageStatus = GetMageStatus((int)$_SESSION['user_id']);
            echo 'userIsMage = ' . ((int)$mageStatus['estMage'] === 1 ? 'true' : 'false') . ';';
        }
        ?>
        const isAdmin = <?= !empty($_SESSION['is_admin']) ? 'true' : 'false' ?>;
        const userAlias = <?= json_encode($_SESSION['username'] ?? '') ?>;
        let currentModalItemId = 0;

        // ---- CART BADGE ----
        function incrementCartBadge() {
            const cartBtn = document.querySelector('.cart-btn');
            if (!cartBtn) return;
            let countEl = cartBtn.querySelector('.cart-count');
            if (countEl) {
                countEl.textContent = parseInt(countEl.textContent, 10) + 1;
            } else {
                const divider = document.createElement('span');
                divider.className = 'user-profile-divider';
                const badge = document.createElement('span');
                badge.className = 'cart-count';
                badge.textContent = '1';
                cartBtn.appendChild(divider);
                cartBtn.appendChild(badge);
            }
        }

        // ---- CART FEEDBACK HELPER ----
        function attachCartFeedback(form) {
            form.addEventListener('submit', function (e) {
                if (!isLoggedIn) return;
                incrementCartBadge();

                const btn = this.querySelector('.btnPanierImg-btn');
                const img = this.querySelector('.btnPanierImg');

                if (img && btn) {
                    img.style.display = 'none';

                    const msg = document.createElement('span');
                    msg.textContent = 'Ajouté !';
                    msg.className = 'cart-feedback-msg';
                    btn.appendChild(msg);

                    setTimeout(() => {
                        img.style.display = '';
                        msg.remove();
                    }, 1500);
                }
            }, { once: true });
        }

        // Attach feedback to existing cart forms
        document.querySelectorAll('.btnPanier form').forEach(form => {
            attachCartFeedback(form);
        });

        // ---- FILTERS & SORTING ----
        function applyFilters() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const checkedTypes = [...document.querySelectorAll('input[name="type"]:checked')].map(cb => cb.value);
            const sortValue = document.querySelector('input[name="sort"]:checked')?.value ?? 'no_sort';

            const grid = document.getElementById('itemGrid');
            let cards = [...grid.querySelectorAll('.itemBox')];

            cards.forEach(card => {
                const matchesSearch = !query || card.dataset.name.includes(query);
                const matchesType = checkedTypes.length === 0 || checkedTypes.includes(card.dataset.type);
                card.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });

            if (sortValue === 'no_sort') {
                cards
                    .filter(c => c.style.display !== 'none')
                    .sort((a, b) => parseInt(a.dataset.order) - parseInt(b.dataset.order))
                    .forEach(card => grid.appendChild(card));
            } else {
                cards
                    .filter(c => c.style.display !== 'none')
                    .sort((a, b) => sortValue === 'price_asc'
                        ? a.dataset.price - b.dataset.price
                        : b.dataset.price - a.dataset.price)
                    .forEach(card => grid.appendChild(card));
            }

            document.getElementById('noResults').style.display =
                cards.some(c => c.style.display !== 'none') ? 'none' : 'block';
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);

        // ---- ITEM MODAL ----
        const itemOverlay = document.getElementById('itemModalOverlay');
        document.getElementById('closeItemModalBtn').addEventListener('click', () => {
            itemOverlay.classList.remove('visible');
            itemOverlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('blurred');
        });
        itemOverlay.addEventListener('click', function (e) {
            if (e.target === itemOverlay) {
                itemOverlay.classList.remove('visible');
                itemOverlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('blurred');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && itemOverlay.classList.contains('visible')) {
                itemOverlay.classList.remove('visible');
                itemOverlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('blurred');
            }
        });

        function openItemModal(image, nom, type, qte, prix, id) {
            document.getElementById('modalImg').src = image;
            document.getElementById('modalImg').alt = nom;
            document.getElementById('modalImg').style.cssText = 'height:50px; object-fit:contain; background:#151515; border-radius:10px; margin-bottom:16px;';
            document.getElementById('modalNom').textContent = nom;
            document.getElementById('modalType').textContent = 'Type : ' + type;
            document.getElementById('modalQte').textContent = 'Quantité : ' + qte;
            document.getElementById('modalPrix').textContent = prix.toLocaleString() + ' gold';
            document.getElementById('modalDetails').innerHTML = renderItemDetails(itemDetails[id] || {});

            const btnDiv = document.getElementById('modalBtn');
            if (qte > 0) {
                if (type === 'Sort') {
                    if (!isLoggedIn) {
                        btnDiv.innerHTML = `<span class="btnPanierImg--disabled">Connectez-vous pour acheter</span>`;
                    } else if (!userIsMage) {
                        btnDiv.innerHTML = `<span class="btnPanierImg--disabled">Seuls les Mages peuvent acheter des sorts</span>`;
                    } else {
                        btnDiv.innerHTML = `
                    <form method="GET" action="panier.php" target="panier-frame">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="${id}">
                        <button type="submit" class="btnPanierImg-btn">
                            <img src="img/addToCart.png" class="btnPanierImg" alt="Ajouter au panier">
                        </button>
                    </form>`;
                        const newForm = btnDiv.querySelector('form');
                        attachCartFeedback(newForm);
                    }
                } else {
                    if (!isLoggedIn) {
                        btnDiv.innerHTML = `<span class="btnPanierImg--disabled">Connectez-vous pour acheter</span>`;
                    } else {
                        btnDiv.innerHTML = `
                    <form method="GET" action="panier.php" target="panier-frame">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="${id}">
                        <button type="submit" class="btnPanierImg-btn">
                            <img src="img/addToCart.png" class="btnPanierImg" alt="Ajouter au panier">
                        </button>
                    </form>`;
                        const newForm = btnDiv.querySelector('form');
                        attachCartFeedback(newForm);
                    }
                }
            } else {
                btnDiv.innerHTML = `<span class="btnPanierImg--disabled">Rupture de stock</span>`;
            }

            loadComments(id);
            itemOverlay.classList.add('visible');
            itemOverlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('blurred');
        }

        // ---- COMMENTS ----
        function escapeHtml(text) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(String(text)));
            return d.innerHTML;
        }

        function renderStars(value) {
            let html = '<span class="stars-display">';
            for (let i = 1; i <= 5; i++) {
                html += `<span class="star-icon${i <= Math.round(value) ? ' filled' : ''}">★</span>`;
            }
            return html + '</span>';
        }

        function renderCommentStats(comments) {
            if (!comments || comments.length === 0) {
                return '<p class="no-comments-msg">Aucune évaluation pour cet item.</p>';
            }
            const total = comments.length;
            const avg = comments.reduce((s, c) => s + parseInt(c.evaluation), 0) / total;
            const pct = Math.round((avg / 5) * 100);
            return `<div class="comment-stats"><span class="comment-count">${total} avis</span>${renderStars(avg)}<span class="comment-avg">${avg.toFixed(1)}/5 &mdash; ${pct}%</span></div>`;
        }

        function renderCommentForm(idItem) {
            if (!isLoggedIn) {
                return '<p class="comment-login-msg">Connectez-vous pour laisser un avis.</p>';
            }
            return `<div class="add-comment-form">
                <p class="comment-form-title">Laisser un avis</p>
                <div id="starPicker" class="star-picker" onmouseleave="resetStarHover()">
                    ${[1,2,3,4,5].map(i => `<span class="star-pick" data-value="${i}" onclick="pickStar(${i})" onmouseover="hoverStar(${i})">★</span>`).join('')}
                </div>
                <input type="hidden" id="selectedRating" value="0">
                <textarea id="commentInput" class="comment-textarea" placeholder="Votre commentaire (max 200 caractères)" maxlength="200"></textarea>
                <button class="btn-submit-comment" onclick="submitComment(${idItem})">Publier</button>
                <p id="commentFormMsg" class="comment-form-msg"></p>
            </div>`;
        }

        function hoverStar(value) {
            document.querySelectorAll('#starPicker .star-pick').forEach(s => {
                s.style.color = parseInt(s.dataset.value) <= value ? '#d4af6f' : '';
            });
        }

        function resetStarHover() {
            const sel = parseInt(document.getElementById('selectedRating')?.value || '0');
            document.querySelectorAll('#starPicker .star-pick').forEach(s => {
                s.style.color = parseInt(s.dataset.value) <= sel ? '#d4af6f' : '';
            });
        }

        function pickStar(value) {
            document.getElementById('selectedRating').value = value;
            document.querySelectorAll('#starPicker .star-pick').forEach(s => {
                s.style.color = parseInt(s.dataset.value) <= value ? '#d4af6f' : '';
            });
        }

        function renderCommentList(comments, idItem) {
            const el = document.getElementById('commentList');
            if (!comments || comments.length === 0) {
                el.innerHTML = '<p class="no-comments-msg">Aucun commentaire pour cet item.</p>';
                return;
            }
            el.innerHTML = comments.map(c => {
                const canDel = isAdmin || (isLoggedIn && c.alias === userAlias);
                return `<div class="comment-item">
                    <div class="comment-header">
                        <span class="comment-author">${escapeHtml(c.alias)}</span>
                        ${renderStars(parseInt(c.evaluation))}
                        ${canDel ? `<button class="btn-delete-comment" onclick="deleteComment(${parseInt(c.idCommentaire)},${idItem})" title="Supprimer">&times;</button>` : ''}
                    </div>
                    <p class="comment-text">${escapeHtml(c.commentaire)}</p>
                </div>`;
            }).join('');
        }

        function loadComments(idItem) {
            currentModalItemId = idItem;
            document.getElementById('commentStats').innerHTML = '<p class="loading-comments">Chargement...</p>';
            document.getElementById('commentList').innerHTML = '';
            document.getElementById('commentForm').innerHTML = renderCommentForm(idItem);

            const fd = new FormData();
            fd.append('action', 'get');
            fd.append('idItem', idItem);
            fetch('commentaires.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('commentStats').innerHTML = renderCommentStats(data.comments);
                        renderCommentList(data.comments, idItem);
                    } else {
                        document.getElementById('commentStats').innerHTML = '';
                        document.getElementById('commentList').innerHTML = '<p class="comment-error">Impossible de charger les commentaires.</p>';
                    }
                })
                .catch(() => {
                    document.getElementById('commentStats').innerHTML = '';
                    document.getElementById('commentList').innerHTML = '<p class="comment-error">Erreur réseau.</p>';
                });
        }

        function submitComment(idItem) {
            const rating = parseInt(document.getElementById('selectedRating').value);
            const commentaire = document.getElementById('commentInput').value.trim();
            const msgEl = document.getElementById('commentFormMsg');
            if (rating < 1 || rating > 5) { msgEl.textContent = 'Veuillez sélectionner une note (1-5 étoiles).'; return; }
            if (commentaire === '') { msgEl.textContent = 'Veuillez écrire un commentaire.'; return; }
            msgEl.textContent = '';
            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('commentaire', commentaire);
            fd.append('evaluation', rating);
            fd.append('idItem', idItem);
            fetch('commentaires.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadComments(idItem);
                    } else {
                        msgEl.textContent = data.message || 'Erreur lors de la publication.';
                    }
                })
                .catch(() => { msgEl.textContent = 'Erreur réseau.'; });
        }

        function deleteComment(idCommentaire, idItem) {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('idCommentaire', idCommentaire);
            fetch('commentaires.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadComments(idItem);
                    } else {
                        alert(data.message || 'Erreur lors de la suppression.');
                    }
                })
                .catch(() => { alert('Erreur réseau.'); });
        }
    </script>
</body>

</html>
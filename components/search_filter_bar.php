<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <!-- Recherche texte -->
            <div class="col-md-6">
                <label for="search_input" class="form-label fw-semibold">
                    <i class="bi bi-search"></i> Rechercher
                </label>
                <div class="input-group">
                    <input 
                        type="text" 
                        id="search_input" 
                        class="form-control" 
                        placeholder="Tapez pour chercher..."
                        name="search"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    />
                    <button 
                        class="btn btn-outline-secondary" 
                        type="submit"
                        form="filter_form"
                    >
                        Chercher
                    </button>
                </div>
                <small class="text-muted d-block mt-1">
                    Cherche dans: <?= $search_hint_text ?? 'tous les champs' ?>
                </small>
            </div>

            <!-- Filtres additionnels (slot pour variantes par page) -->
            <?php if (!empty($filter_slots)): ?>
                <?php foreach ($filter_slots as $slot): ?>
                    <div class="col-md-3">
                        <?= $slot ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="col-md-3 d-flex gap-2">
                <button 
                    type="submit" 
                    class="btn btn-sm btn-primary flex-grow-1"
                    form="filter_form"
                >
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a 
                    href="<?= url_for(basename(__FILE__, '.php') . '.php') ?>" 
                    class="btn btn-sm btn-secondary"
                >
                    <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                </a>
            </div>
        </div>

        <!-- Résumé des filtres actifs -->
        <?php
        $activeFilters = [];
        if (!empty($_GET['search'])) $activeFilters['search'] = $_GET['search'];
        foreach (($_GET['filters'] ?? []) as $key => $val) {
            if (!empty($val)) $activeFilters[$key] = $val;
        }
        ?>
        <?php if (!empty($activeFilters)): ?>
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted d-block mb-2">Filtres actifs:</small>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($activeFilters as $label => $value): ?>
                        <span class="badge bg-info text-dark">
                            <?= ucfirst(str_replace('_', ' ', $label)): ?>
                            <strong><?= htmlspecialchars(substr($value, 0, 20)) ?><?= strlen($value) > 20 ? '...' : '' ?></strong>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

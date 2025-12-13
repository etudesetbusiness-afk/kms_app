document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('catalogue-app');
    const searchInput = document.getElementById('catalogue-search');
    const suggestionsBox = document.getElementById('catalogue-suggestions');

    /* ===== Autocomplete / Recherche ===== */
    if (app && searchInput && suggestionsBox) {
        const searchEndpoint = app.dataset.searchEndpoint;
        const detailBase = app.dataset.detailBase;
        let lastQuery = '';
        let abortCtrl = null;

        const renderSuggestions = (items) => {
            if (!items || !items.length) {
                suggestionsBox.classList.add('d-none');
                suggestionsBox.innerHTML = '';
                return;
            }
            const html = items.map(item => {
                const href = `${detailBase}?slug=${encodeURIComponent(item.slug)}`;
                const price = item.prix_unite !== null && item.prix_unite !== undefined
                    ? `${Number(item.prix_unite).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} FCFA`
                    : '—';
                return `
                    <a class="catalogue-suggestion-item" href="${href}">
                        <div>
                            <strong>${escapeHtml(item.designation || '')}</strong>
                            <small>${escapeHtml(item.code || '')}</small>
                        </div>
                        <span class="catalogue-suggestion-item__price">${price}</span>
                    </a>
                `;
            }).join('');
            suggestionsBox.innerHTML = html;
            suggestionsBox.classList.remove('d-none');
        };

        const fetchSuggestions = async (q) => {
            if (!searchEndpoint || q.length < 1) {
                suggestionsBox.classList.add('d-none');
                suggestionsBox.innerHTML = '';
                return;
            }
            if (q === lastQuery) return;
            lastQuery = q;

            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();
            try {
                const res = await fetch(`${searchEndpoint}?q=${encodeURIComponent(q)}`, {signal: abortCtrl.signal});
                if (!res.ok) throw new Error('Erreur réseau');
                const data = await res.json();
                renderSuggestions(data.items || []);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    suggestionsBox.classList.add('d-none');
                }
            }
        };

        searchInput.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            fetchSuggestions(q);
        });

        searchInput.addEventListener('focus', () => {
            const q = searchInput.value.trim();
            if (q.length >= 1) fetchSuggestions(q);
        });

        document.addEventListener('click', (e) => {
            if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
                suggestionsBox.classList.add('d-none');
            }
        });
    }

    /* ===== Carrousel produits associés ===== */
    const carousel = document.getElementById('catalogue-carousel');
    if (carousel) {
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');
        const scrollAmount = 280; // Largeur item + gap

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                carousel.scrollBy({left: -scrollAmount, behavior: 'smooth'});
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                carousel.scrollBy({left: scrollAmount, behavior: 'smooth'});
            });
        }
    }

    /* ===== Changement image principale via vignettes ===== */
    const hero = document.querySelector('.catalogue-hero img');
    const thumbs = document.querySelectorAll('.catalogue-thumb');
    if (hero && thumbs.length) {
        thumbs.forEach((thumb, idx) => {
            thumb.addEventListener('click', () => {
                const src = thumb.getAttribute('src');
                if (src) {
                    hero.setAttribute('src', src);
                    // Highlight active thumbnail
                    thumbs.forEach(t => t.style.opacity = '0.5');
                    thumb.style.opacity = '1';
                }
            });
        });
        // Première vignette active par défaut
        if (thumbs.length > 0) {
            thumbs[0].style.opacity = '1';
        }
    }

    /* ===== Smooth animations on load ===== */
    const cards = document.querySelectorAll('.catalogue-card');
    cards.forEach((card, idx) => {
        card.style.opacity = '0';
        card.style.animation = `fadeInUp 0.5s ease-out ${idx * 0.05}s forwards`;
    });
});

/* ===== Utilitaires ===== */
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

/* ===== Animations CSS injectées ===== */
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .catalogue-suggestion-item__price {
        white-space: nowrap;
        font-weight: 600;
        color: #0f3460;
        font-size: 0.9rem;
    }
`;
document.head.appendChild(style);

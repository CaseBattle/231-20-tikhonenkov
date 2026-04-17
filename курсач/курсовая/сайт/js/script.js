document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const targetId = link.getAttribute('href');
        if (!targetId || targetId === '#') return;
        const target = document.querySelector(targetId);
        if (!target) return;

        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.querySelectorAll('.js-demo-chat').forEach((demoChatForm) => {
    demoChatForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = demoChatForm.dataset.alertMessage || 'Функция чата будет доступна в следующей версии';
        alert(message);
        demoChatForm.reset();
    });
});

document.querySelectorAll('.js-demo-action, .js-demo-pay').forEach((button) => {
    button.addEventListener('click', () => {
        const message = button.dataset.alertMessage || 'Функция онлайн-оплаты будет доступна в следующей версии';
        alert(message);
    });
});

const LOCAL_FAVORITES_KEY = 'greenhome_favorites';

function getLocalFavorites() {
    try {
        const data = localStorage.getItem(LOCAL_FAVORITES_KEY);
        const parsed = data ? JSON.parse(data) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function setLocalFavorites(ids) {
    localStorage.setItem(LOCAL_FAVORITES_KEY, JSON.stringify(ids));
}

function updateFavoriteCounter(count) {
    document.querySelectorAll('.js-favorites-count').forEach((node) => {
        node.textContent = String(count);
    });
}

function setFavoriteButtonState(button, isActive) {
    button.classList.toggle('is-active', isActive);
    button.textContent = isActive ? '❤' : '♡';
}

async function toggleServerFavorite(propertyId, shouldAdd) {
    const url = shouldAdd ? 'php/add_favorite.php' : 'php/remove_favorite.php';
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ property_id: propertyId }),
    });

    if (!response.ok) {
        throw new Error('request_failed');
    }
    return response.json();
}

function setupFavorites() {
    const buttons = document.querySelectorAll('.js-favorite-btn');
    if (!buttons.length) return;

    const localIds = new Set(getLocalFavorites());
    const isAuthenticated = buttons[0].dataset.auth === '1';

    if (!isAuthenticated) {
        buttons.forEach((button) => {
            const propertyId = Number(button.dataset.propertyId);
            setFavoriteButtonState(button, localIds.has(propertyId));
        });
        updateFavoriteCounter(localIds.size);
    }

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const propertyId = Number(button.dataset.propertyId);
            const isActive = button.classList.contains('is-active');
            const shouldAdd = !isActive;

            if (button.dataset.auth === '1') {
                try {
                    const result = await toggleServerFavorite(propertyId, shouldAdd);
                    setFavoriteButtonState(button, shouldAdd);
                    updateFavoriteCounter(result.count ?? 0);
                    if (!shouldAdd && window.location.pathname.endsWith('/favorites.php')) {
                        const card = button.closest('.property-card');
                        if (card) {
                            card.remove();
                        }
                    }
                } catch (error) {
                    alert('Не удалось обновить избранное. Попробуйте еще раз.');
                }
                return;
            }

            if (shouldAdd) {
                localIds.add(propertyId);
            } else {
                localIds.delete(propertyId);
            }
            setLocalFavorites([...localIds]);
            setFavoriteButtonState(button, shouldAdd);
            updateFavoriteCounter(localIds.size);
        });
    });
}

function setupLoadMore() {
    const grid = document.querySelector('.js-properties-grid');
    const button = document.querySelector('.js-load-more');
    if (!grid || !button) return;

    const cards = Array.from(grid.querySelectorAll('.js-property-card'));
    const batchSize = Number(grid.dataset.batchSize || 8);
    let visible = 0;

    const render = () => {
        visible += batchSize;
        cards.forEach((card, index) => {
            card.classList.toggle('is-visible', index < visible);
        });

        if (visible >= cards.length) {
            button.style.display = 'none';
        }
    };

    render();
    button.addEventListener('click', render);
}

setupFavorites();
setupLoadMore();


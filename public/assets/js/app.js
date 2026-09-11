// Vanilla JS only - no framework, per project brief §4.

async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        ...options,
    });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new Error(body.error || `Request failed with status ${response.status}`);
    }

    return response.json();
}

// Example usage (API-01): fetchJson(`/api/products/${sku}/availability`)

// Mobile nav toggle (UI-01: navigation must stay usable at 360px).
(function () {
    const toggle = document.getElementById('nav-toggle');
    const header = document.getElementById('app-header');

    if (!toggle || !header) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = header.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    header.querySelectorAll('.app-nav a').forEach((link) => {
        link.addEventListener('click', () => {
            header.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
})();

// Dynamic item rows for Purchase Order / Sales Order "create" forms
// (PO-01/SO-01) - add/remove product lines without a page reload.
(function () {
    const addButton = document.getElementById('add-item-row');
    const body = document.getElementById('po-items-body');
    const template = document.getElementById('po-item-row-template');

    if (!addButton || !body || !template) {
        return;
    }

    addButton.addEventListener('click', () => {
        const row = template.content.firstElementChild.cloneNode(true);
        body.appendChild(row);
    });

    body.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-item-row');

        if (!removeButton) {
            return;
        }

        if (body.querySelectorAll('.po-item-row').length > 1) {
            removeButton.closest('.po-item-row').remove();
        }
    });
})();

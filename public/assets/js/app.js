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

// Auto-submit a list page's filter form the moment "Tampilkan" (rows per
// page) changes, instead of making the user also click Filter (FIND-01).
(function () {
    document.getElementById('per_page')?.addEventListener('change', (event) => {
        event.target.form?.submit();
    });
})();

// Mobile sidebar drawer (UI-01: navigation must stay usable at 360px).
(function () {
    const shell = document.querySelector('.app-shell');
    const toggle = document.getElementById('nav-toggle');
    const closeButton = document.getElementById('sidebar-close');
    const overlay = document.getElementById('sidebar-overlay');
    const sidebar = document.getElementById('app-sidebar');

    if (!shell || !toggle || !sidebar) {
        return;
    }

    const setOpen = (isOpen) => {
        shell.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-open')));
    closeButton?.addEventListener('click', () => setOpen(false));
    overlay?.addEventListener('click', () => setOpen(false));

    sidebar.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
})();

// Confirm before deactivating a record (soft-delete only, never a hard
// delete - see README "Known limitations") - a lightweight safety net so a
// stray click doesn't take a user/product/partner offline by accident.
(function () {
    document.querySelectorAll('form[action*="/toggle-active"]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');

        if (!button || !button.textContent.trim().startsWith('Nonaktifkan')) {
            return;
        }

        form.addEventListener('submit', (event) => {
            const confirmed = window.confirm(
                'Nonaktifkan data ini? Data tidak dihapus permanen dan bisa diaktifkan kembali kapan saja.'
            );

            if (!confirmed) {
                event.preventDefault();
            }
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

// Auto-fill a PO/SO item row's price from the selected product's own
// registered buy/sell price (data-buy-price or data-sell-price on each
// <option>). On PO the visible field is disabled (price always mirrors the
// product's master data) and a hidden twin (`.row-price-value`) carries the
// actual value to the server; on SO the field stays a normal editable input.
(function () {
    const body = document.getElementById('po-items-body');

    if (!body) {
        return;
    }

    body.addEventListener('change', (event) => {
        const select = event.target.closest('.row-product');

        if (!select) {
            return;
        }

        const option = select.selectedOptions[0];
        const price = option?.dataset.buyPrice ?? option?.dataset.sellPrice;
        const row = select.closest('.po-item-row');
        const priceInput = row?.querySelector('.row-price');
        const priceHidden = row?.querySelector('.row-price-value');

        if (price === undefined || !priceInput) {
            return;
        }

        priceInput.value = price;

        if (priceHidden) {
            priceHidden.value = price;
        }

        // SO's Harga Jual may only be raised from the product's own price,
        // never lowered - the server re-checks this regardless (never trust
        // the browser alone), but the min attribute gives instant feedback.
        if (option.dataset.sellPrice !== undefined) {
            priceInput.min = price;
        }

        priceInput.dispatchEvent(new Event('input', { bubbles: true }));
    });
})();

// Show each Sales Order item row's available stock at the selected
// warehouse (API-01: /api/products/{sku}/availability) - wires up the
// fetchJson() helper above. Informational only: ARCH-02's real oversell
// guard still runs server-side at goods issue, not here.
(function () {
    const body = document.getElementById('po-items-body');
    const warehouseSelect = document.getElementById('warehouse_id');

    if (!body || !warehouseSelect) {
        return;
    }

    const cache = new Map();

    const availabilityFor = (sku) => {
        if (!cache.has(sku)) {
            cache.set(sku, fetchJson(`/api/products/${encodeURIComponent(sku)}/availability`).catch(() => null));
        }

        return cache.get(sku);
    };

    const updateRow = async (row) => {
        const stockEl = row.querySelector('.row-stock');

        if (!stockEl) {
            return;
        }

        const sku = row.querySelector('.row-product')?.selectedOptions[0]?.dataset.sku;
        const warehouseId = warehouseSelect.value;

        if (!sku || !warehouseId) {
            stockEl.textContent = '-';
            stockEl.className = 'row-stock badge badge-muted';
            return;
        }

        const availability = await availabilityFor(sku);
        const entry = availability?.warehouses.find((w) => String(w.warehouse_id) === warehouseId);
        const quantity = entry ? entry.quantity : 0;
        const qty = parseFloat(row.querySelector('.row-qty')?.value) || 0;

        stockEl.textContent = String(quantity);
        stockEl.className = 'row-stock badge ' + (qty > quantity ? 'badge-danger' : 'badge-success');
    };

    const updateAllRows = () => {
        body.querySelectorAll('.po-item-row').forEach((row) => updateRow(row));
    };

    body.addEventListener('change', (event) => {
        if (event.target.matches('.row-product')) {
            updateRow(event.target.closest('.po-item-row'));
        }
    });

    body.addEventListener('input', (event) => {
        if (event.target.matches('.row-qty')) {
            updateRow(event.target.closest('.po-item-row'));
        }
    });

    warehouseSelect.addEventListener('change', updateAllRows);

    new MutationObserver(updateAllRows).observe(body, { childList: true });
})();

// Live order summary (item count / total qty / estimated total) for the
// same PO/SO "create" forms - recalculated from qty x price as the user
// types, and again whenever a row is added or removed.
(function () {
    const body = document.getElementById('po-items-body');
    const countEl = document.getElementById('order-summary-count');
    const qtyEl = document.getElementById('order-summary-qty');
    const totalEl = document.getElementById('order-summary-total');

    if (!body || !countEl || !qtyEl || !totalEl) {
        return;
    }

    const rupiah = (value) => 'Rp' + Math.round(value).toLocaleString('id-ID');

    const recalculate = () => {
        const rows = body.querySelectorAll('.po-item-row');
        let totalQty = 0;
        let totalValue = 0;

        rows.forEach((row) => {
            const qty = parseFloat(row.querySelector('.row-qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.row-price')?.value) || 0;
            const subtotal = qty * price;
            const subtotalEl = row.querySelector('.row-subtotal');

            if (subtotalEl) {
                subtotalEl.textContent = rupiah(subtotal);
            }

            totalQty += qty;
            totalValue += subtotal;
        });

        countEl.textContent = String(rows.length);
        qtyEl.textContent = String(totalQty);
        totalEl.textContent = rupiah(totalValue);
    };

    body.addEventListener('input', (event) => {
        if (event.target.matches('.row-qty, .row-price')) {
            recalculate();
        }
    });

    new MutationObserver(recalculate).observe(body, { childList: true });

    recalculate();
})();

// Live image preview for the product create/edit forms - shows the file
// the user just picked instead of only the browser's "1 file selected" text.
(function () {
    const input = document.getElementById('image');
    const image = document.getElementById('image-preview-img');
    const empty = document.getElementById('image-preview-empty');

    if (!input || !image) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            image.src = String(reader.result);
            image.hidden = false;
            empty?.setAttribute('hidden', 'hidden');
        };
        reader.readAsDataURL(file);
    });
})();

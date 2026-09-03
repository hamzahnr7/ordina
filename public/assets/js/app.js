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

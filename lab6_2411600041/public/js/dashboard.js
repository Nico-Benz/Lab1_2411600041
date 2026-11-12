// AJAX polling: refresh the dashboard every 30 seconds without reloading the page.
(function () {
    const REFRESH_MS = 30000;
    const api = window.dashboardApi;
    const $ = (id) => document.getElementById(id);

    const peso = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // Count up/down to the new number and flash the card so the change is visible
    function animateNumber(el, to, format) {
        const from = parseFloat(el.dataset.value ?? el.textContent.replace(/[^0-9.-]/g, '')) || 0;
        el.dataset.value = to;
        if (from === to) { return; }
        const steps = 20, started = performance.now(), duration = 600;
        el.classList.add('stat-flash');
        (function tick(now) {
            const t = Math.min((now - started) / duration, 1);
            el.textContent = format(from + (to - from) * t);
            if (t < 1) { requestAnimationFrame(tick); } else { el.textContent = format(to); setTimeout(() => el.classList.remove('stat-flash'), 400); }
        })(started);
    }

    function renderLowStock(items) {
        const body = $('lowStockBody');
        if (!items.length) { body.innerHTML = '<tr><td class="text-center text-muted">All products are well stocked.</td></tr>'; return; }
        body.innerHTML = items.slice(0, 5).map((p) => `
            <tr>
                <td><div class="fw-bold"><a href="${esc(p.url)}" class="text-decoration-none text-reset">${esc(p.name)}</a></div>
                    <div class="small text-muted">Reorder at ${esc(p.reorder_level)}</div></td>
                <td class="text-end">${p.quantity <= 0
                    ? '<span class="badge bg-danger">Out of stock</span>'
                    : `<span class="badge" style="background-color: var(--theme-accent);">${esc(p.quantity)} left</span>`}</td>
            </tr>`).join('');
    }

    function renderRecent(items) {
        const body = $('recentTxBody');
        if (!items.length) { body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No stock movements yet.</td></tr>'; return; }
        body.innerHTML = items.slice(0, 5).map((t) => `
            <tr>
                <td>${esc(t.date)}</td>
                <td>${esc(t.product ?? '—')}</td>
                <td><span class="badge bg-${esc(t.type_color)}">${esc(t.type)}</span></td>
                <td class="${t.change >= 0 ? 'text-success' : 'text-danger'}">${t.change > 0 ? '+' : ''}${esc(t.change)}</td>
                <td>—</td>
                <td>${esc(t.user ?? '—')}</td>
            </tr>`).join('');
    }

    async function getJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!res.ok) { throw new Error('HTTP ' + res.status); }
        return res.json();
    }

    async function refresh() {
        try {
            const [stats, low, recent] = await Promise.all([getJson(api.stats), getJson(api.lowStock), getJson(api.recent)]);

            animateNumber($('statTotal'), stats.stats.total_products, (n) => Math.round(n));
            animateNumber($('statLow'), stats.stats.low_stock, (n) => Math.round(n));
            animateNumber($('statOut'), stats.stats.out_of_stock, (n) => Math.round(n));
            animateNumber($('statValue'), stats.stats.inventory_value, peso);

            renderLowStock(low.data);
            renderRecent(recent.data);

            const bell = $('bellCount');
            if (bell) {
                bell.textContent = stats.unread_notifications;
                bell.classList.toggle('d-none', stats.unread_notifications === 0);
            }

            $('lastUpdated').textContent = new Date().toLocaleTimeString();
            $('pollError').classList.add('d-none');
        } catch (e) {
            // Handle errors gracefully: keep the old numbers and try again next time
            $('pollError').classList.remove('d-none');
            console.error('Dashboard refresh failed:', e);
        }
    }

    setInterval(refresh, REFRESH_MS);
})();

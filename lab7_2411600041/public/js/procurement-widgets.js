/*
 * Dashboard procurement widgets: refresh every 30 seconds with fetch() (AJAX).
 * The first render is done by Blade on the server; this file only keeps it fresh.
 * URLs come from window.procurementApi (set in dashboard.blade.php).
 */
(function () {
    const api = window.procurementApi;
    if (!api) return;

    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (text) => String(text === null || text === undefined ? '' : text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function getJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Request failed: ' + response.status);
        return response.json();
    }

    function setHtml(id, html) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html;
    }

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function orderRow(order, withDate) {
        return '<tr>' +
            '<td><a href="' + esc(order.url) + '">' + esc(order.reference_number) + '</a></td>' +
            '<td>' + esc(order.supplier || '—') + '</td>' +
            (withDate ? '<td><span class="badge bg-' + esc(order.status_color) + '">' + esc(order.status_label) + '</span></td>' : '') +
            '<td class="text-end">' + peso(order.total) + '</td>' +
            '</tr>';
    }

    async function refreshPending() {
        const data = await getJson(api.pending);
        setText('pendingCount', data.count);
        setText('pendingValue', peso(data.value));
        setHtml('pendingOrdersBody', data.orders.length
            ? data.orders.map((o) => orderRow(o, false)).join('')
            : '<tr><td colspan="3" class="text-center text-muted">Nothing is waiting for approval.</td></tr>');
    }

    async function refreshRecent() {
        const data = await getJson(api.recent);
        setHtml('recentOrdersBody', data.orders.length
            ? data.orders.map((o) => orderRow(o, true)).join('')
            : '<tr><td colspan="4" class="text-center text-muted">No purchase orders yet.</td></tr>');
    }

    async function refreshSuppliers() {
        const data = await getJson(api.suppliers);
        setHtml('supplierPerfBody', data.suppliers.length
            ? data.suppliers.map((s) => '<tr><td>' + esc(s.name) + '</td>' +
                '<td class="text-end">' + (s.on_time_rate !== null ? s.on_time_rate + '%' : '—') + '</td>' +
                '<td class="text-end">' + (s.accuracy_rate !== null ? s.accuracy_rate + '%' : '—') + '</td></tr>').join('')
            : '<tr><td colspan="3" class="text-center text-muted">No delivered orders yet.</td></tr>');
    }

    async function refreshReorder() {
        const data = await getJson(api.reorder);
        setHtml('reorderAlertsBody', data.products.length
            ? data.products.map((p) => '<tr><td>' + esc(p.name) + '<div class="small text-muted">' + esc(p.sku) + ' · ' + esc(p.supplier || 'No supplier') + '</div></td>' +
                '<td class="text-end">' + p.quantity + ' / ' + p.reorder_point + '</td>' +
                '<td class="text-end">' + p.suggested + '</td>' +
                '<td>' + (p.has_open_order ? '<span class="badge bg-info">On order</span>' : '<span class="badge bg-danger">Reorder</span>') + '</td></tr>').join('')
            : '<tr><td colspan="4" class="text-center text-muted">All products are above their reorder point.</td></tr>');
    }

    async function refreshAll() {
        // each widget on its own, so one failing request does not stop the others
        await Promise.allSettled([refreshPending(), refreshRecent(), refreshSuppliers(), refreshReorder()]);
    }

    setInterval(refreshAll, 30000);
})();

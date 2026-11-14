/*
 * Purchase order form: builds the line item rows, fills the unit price from the
 * supplier price, and shows the subtotal / tax / total while you type.
 * The server checks everything again, so this file is only for convenience.
 * Data comes from window.poConfig (set in purchase-orders/_form.blade.php).
 */
(function () {
    const config = window.poConfig;
    if (!config) return;

    const body = document.getElementById('itemsBody');
    const supplierSelect = document.getElementById('supplier_id');
    const orderDate = document.getElementById('order_date');
    const expectedDate = document.getElementById('expected_delivery_date');
    const leadHint = document.getElementById('leadHint');
    let rowIndex = 0;

    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function findProduct(id) {
        return config.products.find((p) => String(p.id) === String(id));
    }

    // Only the products this supplier can supply (all products while no supplier is chosen)
    function availableProducts() {
        const supplierId = supplierSelect.value;
        if (!supplierId) return config.products;
        return config.products.filter((p) => Object.prototype.hasOwnProperty.call(p.prices, supplierId));
    }

    function priceFor(product) {
        const supplierId = supplierSelect.value;
        if (supplierId && product.prices[supplierId] !== undefined) return product.prices[supplierId];
        return product.price;
    }

    function optionsHtml(selectedId) {
        let html = '<option value="">-- Select product --</option>';
        availableProducts().forEach((p) => {
            const selected = String(p.id) === String(selectedId) ? ' selected' : '';
            html += '<option value="' + p.id + '"' + selected + '>' + escapeHtml(p.label) + '</option>';
        });
        return html;
    }

    function recalc() {
        let subtotal = 0;
        body.querySelectorAll('tr').forEach((row) => {
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const line = qty * price;
            row.querySelector('.line-total').textContent = peso(line);
            subtotal += line;
        });
        const tax = Math.round(subtotal * config.taxRate * 100) / 100;
        document.getElementById('subtotalText').textContent = peso(subtotal);
        document.getElementById('taxText').textContent = peso(tax);
        document.getElementById('totalText').textContent = peso(subtotal + tax);
    }

    function addRow(item) {
        item = item || {};
        const i = rowIndex++;
        const row = document.createElement('tr');
        row.innerHTML =
            '<td><select name="items[' + i + '][product_id]" class="form-select product" required>' + optionsHtml(item.product_id) + '</select></td>' +
            '<td><input type="number" min="1" step="1" name="items[' + i + '][quantity]" class="form-control qty" value="' + (item.quantity || '') + '" required></td>' +
            '<td><input type="number" min="0" step="0.01" name="items[' + i + '][unit_price]" class="form-control price" value="' + (item.unit_price !== undefined ? item.unit_price : '') + '" required></td>' +
            '<td class="text-end line-total">' + peso(0) + '</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove">&times;</button></td>';
        body.appendChild(row);

        row.querySelector('.product').addEventListener('change', function () {
            const product = findProduct(this.value);
            if (!product) return;
            row.querySelector('.price').value = priceFor(product).toFixed(2);
            const qtyInput = row.querySelector('.qty');
            if (!qtyInput.value) qtyInput.value = product.min_order_quantity;
            recalc();
        });
        row.querySelector('.qty').addEventListener('input', recalc);
        row.querySelector('.price').addEventListener('input', recalc);
        row.querySelector('.remove').addEventListener('click', function () {
            row.remove();
            recalc();
        });
    }

    // Supplier changed: refresh the product lists and suggest the delivery date
    supplierSelect.addEventListener('change', function () {
        body.querySelectorAll('tr').forEach((row) => {
            const select = row.querySelector('.product');
            const current = select.value;
            select.innerHTML = optionsHtml(current);
            if (select.value !== current) {
                row.querySelector('.price').value = '';
            } else if (current) {
                const product = findProduct(current);
                if (product) row.querySelector('.price').value = priceFor(product).toFixed(2);
            }
        });

        const option = supplierSelect.options[supplierSelect.selectedIndex];
        const lead = option ? parseInt(option.dataset.lead, 10) : NaN;
        if (!isNaN(lead) && orderDate.value) {
            const date = new Date(orderDate.value + 'T00:00:00');
            date.setDate(date.getDate() + lead);
            const pad = (n) => String(n).padStart(2, '0');
            expectedDate.value = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
            leadHint.textContent = 'Lead time of this supplier: ' + lead + ' day(s).';
        }
        recalc();
    });

    document.getElementById('addLine').addEventListener('click', () => addRow());

    // Start with the existing / old() lines, or one empty line
    if (config.items && config.items.length) {
        config.items.forEach((item) => addRow(item));
    } else {
        addRow();
    }
    recalc();
})();

(function () {
    const api = window.dashboardApi;
    if (!api) return;

    const $ = function (id) {
        return document.getElementById(id);
    };

    const fmt2 = function (v) {
        return v.toFixed(2);
    };
    const fmtInt = function (v) {
        return String(Math.round(v));
    };
    const fmt1 = function (v) {
        return String(parseFloat(v.toFixed(1)));
    };

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function animateNumber(el, target, format) {
        if (!el) return;
        const start = parseFloat(el.textContent.replace(/,/g, "")) || 0;
        if (start === target) {
            el.textContent = format(target);
            return;
        }
        const duration = 600;
        const startTime = performance.now();
        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            el.textContent = format(start + (target - start) * progress);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function typeBadge(type) {
        const colors = {
            increase: "success",
            decrease: "danger",
            adjustment: "warning",
        };
        const label = type.charAt(0).toUpperCase() + type.slice(1);
        return (
            '<span class="badge bg-' +
            (colors[type] || "secondary") +
            '">' +
            escapeHtml(label) +
            "</span>"
        );
    }

    function updateStats(stats) {
        animateNumber($("stat-average"), stats.average_grade, fmt2);
        animateNumber($("stat-total"), stats.total_subjects, fmtInt);
        animateNumber($("stat-completion"), stats.completion_rate, fmt1);
        animateNumber($("stat-passed"), stats.passed_count, fmtInt);

        $("atRiskCount").textContent = stats.at_risk_count;
        $("atRiskAlert").classList.toggle("d-none", stats.at_risk_count === 0);

        const charts = window.dashboardCharts || {};
        if (charts.performance) {
            charts.performance.data.datasets[0].data = [
                stats.average_grade,
                stats.total_subjects,
                stats.completion_rate,
                stats.passed_count,
            ];
            charts.performance.update();
        }
        if (charts.status) {
            charts.status.data.datasets[0].data = [
                stats.passed_count,
                stats.pending_count,
                stats.at_risk_count,
            ];
            charts.status.update();
        }
    }

    function renderAtRisk(items) {
        const list = $("atRiskList");
        if (!list) return;
        if (!items.length) {
            list.innerHTML =
                '<li class="list-group-item text-muted">No subjects at risk. Keep it up!</li>';
            return;
        }
        list.innerHTML = items
            .map(function (s) {
                return (
                    '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<div><a href="' +
                    escapeHtml(s.url) +
                    '" class="fw-semibold text-decoration-none">' +
                    escapeHtml(s.subject_code) +
                    "</a> " +
                    '<span class="text-muted">' +
                    escapeHtml(s.subject_name) +
                    "</span></div>" +
                    '<div><span class="me-2">' +
                    s.current_grade.toFixed(2) +
                    ' <span class="small text-muted">(threshold ' +
                    s.low_grade_threshold.toFixed(2) +
                    ")</span></span>" +
                    '<span class="badge bg-' +
                    escapeHtml(s.status_color) +
                    '">' +
                    escapeHtml(s.status) +
                    "</span></div></li>"
                );
            })
            .join("");
    }

    function renderTransactions(rows) {
        const body = $("recentTransactionsBody");
        if (!body) return;
        if (!rows.length) {
            body.innerHTML =
                '<tr><td colspan="5" class="text-center text-muted">No transactions yet.</td></tr>';
            return;
        }
        body.innerHTML = rows
            .map(function (t) {
                return (
                    "<tr>" +
                    '<td class="text-nowrap">' +
                    escapeHtml(t.created_at) +
                    "</td>" +
                    '<td><a href="' +
                    escapeHtml(t.url) +
                    '" class="text-decoration-none">' +
                    escapeHtml(t.subject_code) +
                    "</a> " +
                    '<span class="small text-muted">' +
                    escapeHtml(t.subject_name) +
                    "</span></td>" +
                    "<td>" +
                    typeBadge(t.type) +
                    "</td>" +
                    '<td class="text-end text-nowrap">' +
                    t.previous_grade.toFixed(2) +
                    " &rarr; " +
                    t.new_grade.toFixed(2) +
                    "</td>" +
                    "<td>" +
                    escapeHtml(t.user) +
                    "</td>" +
                    "</tr>"
                );
            })
            .join("");
    }

    function getJson(url) {
        return fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) {
                window.location.reload();
                throw new Error("Session expired");
            }
            if (!res.ok) throw new Error("Request failed (" + res.status + ")");
            return res.json();
        });
    }

    function refresh() {
        const label = $("lastUpdated");
        Promise.all([
            getJson(api.stats),
            getJson(api.atRisk),
            getJson(api.transactions),
        ])
            .then(function (results) {
                updateStats(results[0].stats);
                renderAtRisk(results[1].data);
                renderTransactions(results[2].data);
                label.className = "text-muted";
                label.textContent =
                    "Last updated: " + new Date().toLocaleTimeString();
            })
            .catch(function (err) {
                console.error(err);
                label.className = "text-danger";
                label.textContent =
                    "Update failed. Retrying in " +
                    api.interval / 1000 +
                    " seconds...";
            });
    }

    refresh();
    setInterval(refresh, api.interval);
})();

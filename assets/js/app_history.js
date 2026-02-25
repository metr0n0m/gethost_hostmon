(function () {
    const T = window.I18N || {};
    const tr = (k, d) => (Object.prototype.hasOwnProperty.call(T, k) ? T[k] : d);
    const HISTORY_REFRESH_SECONDS = 10;

    function el(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value == null ? '' : value);
        return div.innerHTML;
    }

    function formatDateTime(value) {
        if (!value) return '-';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return escapeHtml(value);
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        const hh = String(date.getHours()).padStart(2, '0');
        const mm = String(date.getMinutes()).padStart(2, '0');
        const ss = String(date.getSeconds()).padStart(2, '0');
        return d + '.' + m + '.' + y + ' ' + hh + ':' + mm + ':' + ss;
    }

    function canTrackHost(value) {
        if (!value) return false;
        const s = String(value).trim();
        return /^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(s);
    }

    function addToMonitoring(host, button) {
        if (button) {
            button.disabled = true;
        }
        fetch('ajax_add_site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ name: host, url: host })
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw new Error(String((data && data.error) || tr('request_failed', 'Request failed')));
                }
                return data;
            })
            .then(data => {
                if (!data.success) {
                    alert(data.error || tr('msg_add_failed', 'Failed to add site'));
                    return;
                }
                if (data.message) {
                    alert(String(data.message));
                }
            })
            .catch((err) => {
                let message = tr('request_failed', 'Request failed');
                if (err && err.message) {
                    message = String(err.message);
                }
                alert(message);
            })
            .finally(() => {
                if (button) {
                    button.disabled = false;
                }
            });
    }

    function renderTopCounters(rows) {
        const body = el('top-counters-body');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '<tr><td colspan="4" class="text-muted">' + escapeHtml(tr('no_data', 'No data')) + '</td></tr>';
            return;
        }

        body.innerHTML = rows.map(function (row) {
            return '<tr>' +
                '<td>' + escapeHtml(row.query_type) + '</td>' +
                '<td>' + escapeHtml(row.query_value_norm) + '</td>' +
                '<td><strong>' + escapeHtml(row.counter) + '</strong></td>' +
                '<td>' + formatDateTime(row.last_requested_at) + '</td>' +
            '</tr>';
        }).join('');
    }

    function renderHistory(rows) {
        const body = el('query-history-body');
        if (!body) return;

        if (!rows || rows.length === 0) {
            body.innerHTML = '<tr><td colspan="10" class="text-muted">' + escapeHtml(tr('no_data', 'No data')) + '</td></tr>';
            return;
        }

        body.innerHTML = rows.map(function (row) {
            const source = String(row.client_provider || '').trim();
            const addBtn = canTrackHost(source)
                ? '<button class="btn btn-sm btn-outline-success btn-add-monitor" data-host="' + escapeHtml(source) + '">' + escapeHtml(tr('action_add_tracking', 'Add tracking')) + '</button>'
                : '-';

            return '<tr>' +
                '<td>' + formatDateTime(row.requested_at) + '</td>' +
                '<td><div><strong>' + escapeHtml(row.query_type) + '</strong></div><div>' + escapeHtml(row.raw_query) + '</div></td>' +
                '<td>' + escapeHtml(row.result_summary || '-') + '</td>' +
                '<td><strong>' + escapeHtml(row.total_counter || row.counter_snapshot || 1) + '</strong></td>' +
                '<td>' + escapeHtml(row.client_ip || '-') + '</td>' +
                '<td>' + escapeHtml((row.client_os || '-') + ' / ' + (row.client_browser || '-')) + '</td>' +
                '<td>' + (Number(row.is_tor) === 1 ? escapeHtml(tr('yes', 'yes')) : escapeHtml(tr('no', 'no'))) + '</td>' +
                '<td>' + (Number(row.is_proxy) === 1 ? escapeHtml(tr('yes', 'yes')) : escapeHtml(tr('no', 'no'))) + '</td>' +
                '<td>' + escapeHtml(source || '-') + '</td>' +
                '<td>' + addBtn + '</td>' +
            '</tr>';
        }).join('');
    }

    async function loadHistory() {
        const topBody = el('top-counters-body');
        const historyBody = el('query-history-body');
        if (!topBody || !historyBody) return;

        try {
            const response = await fetch('ajax_get_query_history.php', { method: 'GET', headers: { Accept: 'application/json' }, cache: 'no-store' });
            const data = await response.json();

            if (!data.success) {
                topBody.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(data.error || tr('msg_unknown_error', 'Error')) + '</td></tr>';
                historyBody.innerHTML = '<tr><td colspan="10" class="text-danger">' + escapeHtml(data.error || tr('msg_unknown_error', 'Error')) + '</td></tr>';
                return;
            }

            renderTopCounters(data.top || []);
            renderHistory(data.history || []);
        } catch (e) {
            topBody.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(tr('request_failed', 'Request failed')) + '</td></tr>';
            historyBody.innerHTML = '<tr><td colspan="10" class="text-danger">' + escapeHtml(tr('request_failed', 'Request failed')) + '</td></tr>';
        }
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.btn-add-monitor');
        if (!button) return;
        const host = String(button.getAttribute('data-host') || '').trim();
        if (!host) return;
        addToMonitoring(host, button);
    });

    document.addEventListener('DOMContentLoaded', function () {
        loadHistory();
        setInterval(loadHistory, HISTORY_REFRESH_SECONDS * 1000);
    });
})();

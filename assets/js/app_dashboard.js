$(document).ready(function () {
    const T = window.I18N || {};
    const tr = (k, d) => (Object.prototype.hasOwnProperty.call(T, k) ? T[k] : d);

    if (!$('#dashboard-table-body').length) return;

    let timer = null;

    function startLoop() {
        if (timer) clearInterval(timer);
        const seconds = Number(DASHBOARD_REFRESH_SECONDS || 0);
        if (seconds > 0) {
            timer = setInterval(function () {
                recheckEnabledAndReload();
            }, seconds * 1000);
        }
    }

    function recheckEnabledAndReload() {
        $.ajax({
            url: 'ajax_recheck_sites.php',
            method: 'POST',
            data: { only_enabled: 1 },
            dataType: 'json',
            complete: function () {
                loadDashboard();
            }
        });
    }

    function loadDashboard() {
        $.ajax({
            url: 'ajax_get_sites.php',
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    $('#dashboard-table-body').html('<tr><td colspan="7" class="text-danger">' + escapeHtml(tr('load_failed', 'Load failed')) + '</td></tr>');
                    return;
                }
                renderDashboard(resp.data || []);
            },
            error: function () {
                $('#dashboard-table-body').html('<tr><td colspan="7" class="text-danger">' + escapeHtml(tr('request_failed', 'Request failed')) + '</td></tr>');
            }
        });
    }

    $('#force-recheck').on('click', function () {
        recheckEnabledAndReload();
    });

    $('#save-refresh').on('click', function () {
        const value = Number($('#refresh-seconds-input').val() || 0);

        $.ajax({
            url: 'ajax_update_refresh.php',
            method: 'POST',
            data: { refresh_seconds: value },
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    alert(resp.error || tr('msg_save_refresh_failed', 'Failed to save refresh'));
                    return;
                }
                DASHBOARD_REFRESH_SECONDS = Number(resp.refresh_seconds);
                startLoop();
            },
            error: function () {
                alert(tr('request_failed', 'Request failed'));
            }
        });
    });

    $('#dashboard-table-body').on('click', '.btn-site-action', function () {
        const id = Number($(this).closest('tr').data('site-id') || 0);
        const action = String($(this).data('action') || '');
        if (!id || !action) return;

        if (action === 'delete' && !confirm(tr('confirm_delete', 'Delete site from list?'))) {
            return;
        }

        $.ajax({
            url: 'ajax_site_action.php',
            method: 'POST',
            data: { id, action },
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    alert(resp.error || tr('msg_action_failed', 'Action failed'));
                    return;
                }
                loadDashboard();
            },
            error: function () {
                alert(tr('request_failed', 'Request failed'));
            }
        });
    });

    function getStatusBadge(site) {
        if (Number(site.is_enabled) !== 1) return '<span class="badge text-bg-secondary">' + escapeHtml(tr('status_monitor_off', 'Monitoring off')) + '</span>';
        const httpCode = Number(site.http_code || 0);
        if (site.status === 'active' && httpCode >= 400 && httpCode <= 599) {
            return '<span class="badge text-bg-warning text-dark">' +
                escapeHtml(tr('status_active_http_error', 'Active (HTTP error)')) +
                ' ' + escapeHtml(String(httpCode)) +
                '</span>';
        }
        if (site.status === 'active') return '<span class="badge text-bg-success">' + escapeHtml(tr('status_active', 'Active')) + '</span>';
        if (site.status === 'no_access') return '<span class="badge text-bg-danger">' + escapeHtml(tr('status_no_access', 'No access')) + '</span>';
        return '<span class="badge text-bg-warning text-dark">' + escapeHtml(tr('status_inactive', 'Inactive')) + '</span>';
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

    function renderDashboard(sites) {
        if (!sites.length) {
            $('#dashboard-table-body').html('<tr><td colspan="7" class="text-muted">' + escapeHtml(tr('no_sites', 'No sites yet')) + '</td></tr>');
            return;
        }

        let html = '';
        sites.forEach(function (site) {
            const enabled = Number(site.is_enabled) === 1;
            const toggleAction = enabled ? 'deactivate' : 'activate';
            const toggleLabel = enabled ? tr('action_deactivate', 'Deactivate') : tr('action_activate', 'Activate');
            const toggleClass = enabled ? 'btn-outline-warning' : 'btn-outline-success';
            const httpCode = Number(site.http_code || 0);
            const rowClass = (enabled && site.status === 'active' && httpCode >= 400 && httpCode <= 599) ? ' class="monitor-row-http-error"' : '';

            html += '<tr data-site-id="' + Number(site.id) + '"' + rowClass + '>' +
                '<td>' + escapeHtml(site.name || '') + '</td>' +
                '<td class="url-cell"><a href="' + escapeHtml(site.url || '') + '" title="' + escapeHtml(site.url || '') + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(site.url || '') + '</a></td>' +
                '<td>' + getStatusBadge(site) + '</td>' +
                '<td>' + (site.http_code ? escapeHtml(site.http_code) : '-') + '</td>' +
                '<td>' + (site.response_time_ms ? escapeHtml(site.response_time_ms) : '-') + '</td>' +
                '<td>' + formatDateTime(site.last_check) + '</td>' +
                '<td class="text-end action-cell"><div class="action-buttons">' +
                    '<button class="btn btn-sm ' + toggleClass + ' btn-site-action" data-action="' + toggleAction + '" title="' + escapeHtml(toggleLabel) + '">' +
                        '<i class="fa fa-power-off"></i><span class="action-label">' + escapeHtml(toggleLabel) + '</span>' +
                    '</button>' +
                    '<button class="btn btn-sm btn-outline-danger btn-site-action" data-action="delete" title="' + escapeHtml(tr('action_delete', 'Delete')) + '">' +
                        '<i class="fa fa-trash"></i><span class="action-label">' + escapeHtml(tr('action_delete', 'Delete')) + '</span>' +
                    '</button>' +
                '</div></td>' +
                '</tr>';
        });

        $('#dashboard-table-body').html(html);
    }

    function escapeHtml(text) {
        return $('<div>').text(String(text || '')).html();
    }

    loadDashboard();
    startLoop();
});

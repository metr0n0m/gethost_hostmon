$(document).ready(function () {
    const T = window.I18N || {};
    const tr = (k, d) => (Object.prototype.hasOwnProperty.call(T, k) ? T[k] : d);

    let recheckTimer = null;
    const monitoredHostKeys = new Set();

    if ($('#sites-table-body').length) {
        loadSites();
    }

    startAutoRecheck();

    $('#reload-sites').on('click', function () {
        recheckAll(false);
    });

    $('#resolve-form').on('submit', function (e) {
        e.preventDefault();
        const query = String($('#resolve-input').val() || '').trim();

        if (!query) {
            renderResolveResult({ success: false, message: tr('error_enter_ip_host', 'Please enter IP or host') });
            return;
        }

        $.ajax({
            url: 'ajax_resolve.php',
            method: 'POST',
            data: { query },
            dataType: 'json',
            success: renderResolveResult,
            error: function () {
                renderResolveResult({ success: false, message: tr('request_failed', 'Request failed') });
            }
        });
    });

    $('#resolve-result').on('click', '#add-tracking-btn', function () {
        const host = String($(this).data('host') || '').trim();
        const url = String($(this).data('url') || '').trim();
        if (!host) return;
        addHostToMonitoring(host, url || host, this);
    });

    $('#sites-table-body').on('click', '.btn-site-action', function () {
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
                loadSites();
            },
            error: function () {
                alert(tr('request_failed', 'Request failed'));
            }
        });
    });

    function startAutoRecheck() {
        if (recheckTimer) clearTimeout(recheckTimer);
        scheduleNextRecheck();
    }

    function scheduleNextRecheck() {
        fetchRefreshSeconds(function (seconds) {
            recheckTimer = setTimeout(function () {
                recheckAll(true);
                scheduleNextRecheck();
            }, seconds * 1000);
        });
    }

    function fetchRefreshSeconds(done) {
        const fallback = normalizeRefreshSeconds(window.INDEX_RECHECK_SECONDS);
        $.ajax({
            url: 'ajax_get_refresh.php',
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    done(normalizeRefreshSeconds(resp.refresh_seconds));
                    return;
                }
                done(fallback);
            },
            error: function () {
                done(fallback);
            }
        });
    }

    function normalizeRefreshSeconds(value) {
        const n = Number(value || 0);
        if (n >= 5 && n <= 3600) return n;
        return 30;
    }

    function addHostToMonitoring(name, url, buttonEl) {
        const $btn = buttonEl ? $(buttonEl) : null;
        if ($btn && $btn.prop('disabled')) return;
        if ($btn) {
            $btn.prop('disabled', true);
        }

        $.ajax({
            url: 'ajax_add_site.php',
            method: 'POST',
            data: { name, url },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    const msg = String(resp.message || '');
                    const status = String(resp.status || '');
                    const alertType = (status === 'exists' || status === 'exists_unreachable')
                        ? 'info'
                        : (status === 'added_unreachable' ? 'warning' : 'success');
                    renderInlineNotice(msg || tr('msg_site_added', 'Site added to monitor'), alertType);
                    loadSites();
                } else {
                    renderInlineNotice(resp.error || tr('msg_add_failed', 'Failed to add site'), 'danger');
                }
            },
            error: function (xhr) {
                let message = tr('msg_add_request_failed', 'Request failed while adding monitor target');
                if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                    message = String(xhr.responseJSON.error);
                }
                renderInlineNotice(message, 'danger');
            },
            complete: function () {
                if ($btn) {
                    $btn.prop('disabled', false);
                }
            }
        });
    }

    function recheckAll(onlyEnabled) {
        $.ajax({
            url: 'ajax_recheck_sites.php',
            method: 'POST',
            data: { only_enabled: onlyEnabled ? 1 : 0 },
            dataType: 'json',
            complete: function () {
                loadSites();
            },
            error: function () {
                if (!onlyEnabled) {
                    alert(tr('msg_recheck_failed', 'Failed to recheck sites'));
                }
            }
        });
    }

    function renderResolveResult(resp) {
        const container = $('#resolve-result');
        container.removeClass('d-none');

        if (!resp || !resp.success) {
            const msg = escapeHtml((resp && (resp.message || resp.error)) || tr('error_invalid_data', 'Invalid data. Please provide valid IP or host.'));
            container.html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            return;
        }

        const typeLabel = resp.query_type === 'ip_to_host' ? tr('type_ip_host', 'IP -> Host') : tr('type_host_ip', 'Host -> IP');
        const host = resp.resolved_host ? String(resp.resolved_host) : '';
        const ips = Array.isArray(resp.resolved_ips) ? resp.resolved_ips : [];
        const protocolLabel = getProtocolLabel(resp);

        const details = resp.query_type === 'ip_to_host'
            ? '<div><strong>' + escapeHtml(tr('label_host', 'Host')) + ':</strong> ' + (host ? escapeHtml(host) : '<span class="text-muted">' + escapeHtml(tr('not_found', 'not found')) + '</span>') + '</div>'
            : '<div><strong>' + escapeHtml(tr('label_ip', 'IP')) + ':</strong> ' + (ips.length ? escapeHtml(ips.join(', ')) : '<span class="text-muted">' + escapeHtml(tr('not_found', 'not found')) + '</span>') + '</div>';
        const protocolLine = resp.query_type === 'host_to_ip'
            ? '<div><strong>' + escapeHtml(tr('label_protocol', 'Protocol')) + ':</strong> ' + escapeHtml(protocolLabel) + '</div>'
            : '';

        const trackUrl = resp.track_url ? String(resp.track_url) : '';
        const trackHostKey = normalizeHostKey(extractHostFromUrl(trackUrl || host) || host);
        const alreadyMonitored = !!trackHostKey && monitoredHostKeys.has(trackHostKey);
        const trackButton = resp.can_track && host && !alreadyMonitored
            ? '<button id="add-tracking-btn" class="btn btn-sm btn-success mt-3" data-host="' + escapeHtml(host) + '" data-url="' + escapeHtml(trackUrl) + '"><i class="fa fa-heart-pulse"></i> ' + escapeHtml(tr('action_add_tracking', 'Add tracking')) + '</button>'
            : '';

        container.html('<div class="alert alert-light border mb-0"><div class="mb-2"><strong>' + escapeHtml(tr('label_type', 'Type')) + ':</strong> ' + escapeHtml(typeLabel) + '</div>' + protocolLine + details + '<div class="mt-2 text-muted">' + escapeHtml(resp.message || '') + '</div>' + trackButton + '</div>');
    }

    function renderInlineNotice(text, type) {
        $('#resolve-result').append('<div class="alert alert-' + type + ' mt-3 mb-0">' + escapeHtml(text) + '</div>');
    }

    function loadSites() {
        if (!$('#sites-table-body').length) return;

        $.ajax({
            url: 'ajax_get_sites.php',
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    $('#sites-table-body').html('<tr><td colspan="5" class="text-danger">' + escapeHtml(tr('load_failed', 'Load failed')) + '</td></tr>');
                    return;
                }
                renderSites(resp.data || []);
            },
            error: function () {
                $('#sites-table-body').html('<tr><td colspan="5" class="text-danger">' + escapeHtml(tr('request_failed', 'Request failed')) + '</td></tr>');
            }
        });
    }

    function getStatusBadge(site) {
        if (Number(site.is_enabled) !== 1) {
            return '<span class="badge text-bg-secondary">' + escapeHtml(tr('status_monitor_off', 'Monitoring off')) + '</span>';
        }

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

    function renderSites(sites) {
        monitoredHostKeys.clear();

        if (!sites.length) {
            $('#sites-table-body').html('<tr><td colspan="5" class="text-muted">' + escapeHtml(tr('no_sites', 'No sites yet')) + '</td></tr>');
            return;
        }

        let html = '';
        sites.forEach(function (site) {
            const monitoredHost = extractHostFromUrl(String(site.url || ''));
            const monitoredKey = normalizeHostKey(monitoredHost);
            if (monitoredKey) {
                monitoredHostKeys.add(monitoredKey);
            }

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

        $('#sites-table-body').html(html);
    }

    function escapeHtml(text) {
        return $('<div>').text(String(text || '')).html();
    }

    function normalizeHostKey(value) {
        const v = String(value || '').trim().toLowerCase();
        return v || '';
    }

    function extractHostFromUrl(value) {
        const input = String(value || '').trim();
        if (!input) return '';

        const withoutScheme = input.replace(/^https?:\/\//i, '');
        const hostPortPath = withoutScheme.split('/')[0];
        const host = hostPortPath.split(':')[0].trim();
        return host;
    }

    function getProtocolLabel(resp) {
        const rawInput = String((resp && resp.input) || '').trim().toLowerCase();
        if (rawInput.startsWith('https://')) return tr('protocol_https', 'HTTPS');
        if (rawInput.startsWith('http://')) return tr('protocol_http', 'HTTP');
        const probe = (resp && resp.protocol_probe) ? resp.protocol_probe : null;
        if (probe && typeof probe === 'object') {
            const hasHttp = Number(probe.http || 0) >= 100;
            const hasHttps = Number(probe.https || 0) >= 100;
            if (hasHttp && hasHttps) return tr('protocol_both', 'HTTP + HTTPS');
            if (hasHttp) return tr('protocol_http', 'HTTP');
            if (hasHttps) return tr('protocol_https', 'HTTPS');
            return tr('protocol_no_response', 'No response on HTTP/HTTPS');
        }
        return tr('protocol_not_specified', 'Not specified');
    }
});

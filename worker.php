<?php
/**
 * 管理员专属 Cloudflare Worker 实时状态页。
 *
 * @package Argon
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    argon_worker_status_render_404();
    return;
}

nocache_headers();
if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow', true);
}

require_once get_template_directory() . '/inc/special-page-surface.php';
get_header();
argon_special_page_surface_open('worker-page');
?>

<?php
argon_special_page_surface_header(array(
    'icon' => 'cloud',
    'title' => __('友链 Worker 实时状态', 'argon'),
    'subtitle' => __('自动巡检、队列与实时探测状态，每秒更新两次', 'argon'),
    'stats' => array(
        array(
            'icon' => 'circle',
            'text' => __('正在连接', 'argon'),
            'id' => 'argon-worker-live',
            'text_id' => 'argon-worker-live-text',
            'class' => 'argon-special-page-badge argon-special-page-live',
            'aria_live' => 'polite',
        ),
    ),
));
?>

<div id="argon-worker-status-page" class="argon-special-page-list">

    <div class="argon-special-page-stats argon-special-page-meta">
        <span class="argon-special-page-stat"><?php esc_html_e('上次更新', 'argon'); ?>：<strong id="argon-worker-updated-at">—</strong></span>
        <span class="argon-special-page-stat"><?php esc_html_e('请求耗时', 'argon'); ?>：<strong id="argon-worker-latency">—</strong></span>
        <span class="argon-special-page-stat"><?php esc_html_e('服务时间', 'argon'); ?>：<strong id="argon-worker-server-time">—</strong></span>
    </div>

    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section" aria-labelledby="argon-worker-summary-title">
        <div class="argon-special-page-section-header">
            <h2 class="argon-special-page-section-title" id="argon-worker-summary-title"><i class="fa fa-dashboard" aria-hidden="true"></i><?php esc_html_e('状态汇总', 'argon'); ?></h2>
            <span class="argon-special-page-section-count"><i class="fa fa-bolt" aria-hidden="true"></i><?php esc_html_e('实时', 'argon'); ?></span>
        </div>
        <section class="argon-special-page-grid" id="argon-worker-summary" aria-label="<?php echo esc_attr__('Worker 状态汇总', 'argon'); ?>">
            <article class="argon-special-page-metric">
                <div class="argon-special-page-metric-label"><?php esc_html_e('等待数据', 'argon'); ?></div>
                <div class="argon-special-page-metric-value">—</div>
            </article>
        </section>
    </article>

    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section" aria-labelledby="argon-worker-jobs-title">
        <div class="argon-special-page-section-header">
            <h2 class="argon-special-page-section-title" id="argon-worker-jobs-title"><i class="fa fa-tasks" aria-hidden="true"></i><?php esc_html_e('最近任务', 'argon'); ?></h2>
            <span class="argon-special-page-section-count"><i class="fa fa-refresh" aria-hidden="true"></i><?php esc_html_e('实时', 'argon'); ?></span>
        </div>
        <div class="argon-special-page-table-wrap">
            <table class="argon-special-page-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('任务', 'argon'); ?></th>
                        <th><?php esc_html_e('目标', 'argon'); ?></th>
                        <th><?php esc_html_e('状态', 'argon'); ?></th>
                        <th><?php esc_html_e('互链', 'argon'); ?></th>
                        <th><?php esc_html_e('回调', 'argon'); ?></th>
                        <th><?php esc_html_e('更新时间', 'argon'); ?></th>
                        <th><?php esc_html_e('详细信息', 'argon'); ?></th>
                    </tr>
                </thead>
                <tbody id="argon-worker-jobs">
                    <tr><td colspan="7" class="argon-special-page-empty"><?php esc_html_e('正在读取 Worker 任务…', 'argon'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </article>

    <article class="post card shadow-sm bg-white border-0 argon-special-page-card argon-special-page-section" aria-labelledby="argon-worker-details-title">
        <div class="argon-special-page-section-header">
            <h2 class="argon-special-page-section-title" id="argon-worker-details-title"><i class="fa fa-code" aria-hidden="true"></i><?php esc_html_e('完整状态详情', 'argon'); ?></h2>
        </div>
        <pre class="argon-special-page-details" id="argon-worker-details"><?php esc_html_e('正在读取…', 'argon'); ?></pre>
    </article>
</div>

<script data-pjax>
(function() {
    'use strict';

    var page = document.getElementById('argon-worker-status-page');
    if (!page) return;
    if (typeof window.argonStopWorkerStatusMonitor === 'function') {
        window.argonStopWorkerStatusMonitor();
    }

    var endpoint = <?php echo wp_json_encode(rest_url('argon/v1/friend-link-worker-status')); ?>;
    var restNonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
    var pollTimer = null;
    var activeController = null;
    var stopped = false;
    var inFlight = false;
    var live = document.getElementById('argon-worker-live');
    var liveText = document.getElementById('argon-worker-live-text');
    var updatedAt = document.getElementById('argon-worker-updated-at');
    var latency = document.getElementById('argon-worker-latency');
    var serverTime = document.getElementById('argon-worker-server-time');
    var summary = document.getElementById('argon-worker-summary');
    var jobsBody = document.getElementById('argon-worker-jobs');
    var details = document.getElementById('argon-worker-details');

    function first(object, keys, fallback) {
        if (!object || typeof object !== 'object') return fallback;
        for (var i = 0; i < keys.length; i++) {
            if (object[keys[i]] !== undefined && object[keys[i]] !== null && object[keys[i]] !== '') {
                return object[keys[i]];
            }
        }
        return fallback;
    }

    function formatTime(value) {
        if (!value) return '—';
        var date;
        if (typeof value === 'number' || /^\d+$/.test(String(value))) {
            var number = Number(value);
            date = new Date(number < 100000000000 ? number * 1000 : number);
        } else {
            date = new Date(value);
        }
        return isNaN(date.getTime()) ? String(value) : date.toLocaleString();
    }

    function textCell(row, value) {
        var cell = document.createElement('td');
        cell.textContent = value === undefined || value === null || value === '' ? '—' : String(value);
        row.appendChild(cell);
    }

    function setLive(state, message) {
        live.className = 'argon-special-page-stat argon-special-page-badge argon-special-page-live' + (state ? ' is-' + state : '');
        liveText.textContent = message;
    }

    function runtimeStatus(runtime, kind) {
        if (!runtime || typeof runtime !== 'object') return 'unknown';
        var state = runtime[kind];
        if (typeof state === 'string') return state || 'unknown';
        return state && typeof state === 'object'
            ? String(first(state, ['status'], 'unknown'))
            : 'unknown';
    }

    function statusSummary(worker, jobs) {
        var source = first(worker, ['aggregate', 'summary', 'counts', 'stats'], null);
        var queue = worker && worker.queue && typeof worker.queue === 'object' ? worker.queue : {};
        var runtime = worker && worker.runtime && typeof worker.runtime === 'object' ? worker.runtime : {};
        var service = worker && worker.service && typeof worker.service === 'object' ? worker.service : {};
        var values = {
            service: first(service, ['status'], first(worker, ['status'], first(worker, ['ok'], false) ? 'ok' : 'unknown')),
            'runtime.cron': runtimeStatus(runtime, 'cron'),
            'runtime.queue': runtimeStatus(runtime, 'queue'),
            'runtime.probe': runtimeStatus(runtime, 'probe')
        };
        if (source && typeof source === 'object' && !Array.isArray(source)) {
            values.total = first(source, ['total'], 0);
            values.queue_backlog = first(queue, ['backlog_count'], queue.available === false ? 'unavailable' : 0);
            values.queue_oldest = formatTime(first(queue, ['oldest_message_timestamp_ms'], 0));
            values.queued = first(source, ['queued'], 0);
            values.running = first(source, ['running'], 0);
            values.complete = first(source, ['complete'], 0);
            values.unknown = first(source, ['unknown'], 0);
            values.error = first(source, ['error'], 0);
            values.due_checks = first(source, ['due_checks'], 0);
            values.callback_pending = first(source, ['callback_pending'], 0);
            return values;
        }
        var derived = {};
        jobs.forEach(function(job) {
            var state = String(first(job, ['status', 'state'], 'unknown'));
            derived[state] = (derived[state] || 0) + 1;
        });
        Object.keys(derived).forEach(function(state) {
            values[state] = derived[state];
        });
        return values;
    }

    function jobDetails(job) {
        var values = [];
        var lastError = first(job, ['last_error', 'lastError', 'callback_last_error', 'callbackLastError'], '');
        if (lastError) values.push(String(lastError));
        var diagnostics = first(job, ['diagnostics'], []);
        if (Array.isArray(diagnostics)) {
            diagnostics.slice(-4).forEach(function(item) {
                if (!item || typeof item !== 'object') return;
                var phase = first(item, ['phase'], 'probe');
                var code = first(item, ['code'], 'unknown');
                var httpStatus = Number(first(item, ['http_status', 'httpStatus'], 0)) || 0;
                values.push(String(phase) + ':' + String(code) + (httpStatus ? '(' + httpStatus + ')' : ''));
            });
        }
        return values.length ? values.join(' · ') : '—';
    }

    function renderSummary(values) {
        summary.textContent = '';
        Object.keys(values).forEach(function(key) {
            var card = document.createElement('article');
            card.className = 'argon-special-page-metric';
            var label = document.createElement('div');
            label.className = 'argon-special-page-metric-label';
            label.textContent = key;
            var value = document.createElement('div');
            value.className = 'argon-special-page-metric-value';
            var raw = values[key];
            value.textContent = raw && typeof raw === 'object' ? JSON.stringify(raw) : String(raw === undefined ? '—' : raw);
            var normalizedState = String(raw || '').toLowerCase();
            if (/^(ok|running|deferred|degraded|error)$/.test(normalizedState)) {
                card.setAttribute('data-state', normalizedState);
            }
            card.appendChild(label);
            card.appendChild(value);
            summary.appendChild(card);
        });
    }

    function renderJobs(jobs) {
        jobsBody.textContent = '';
        if (!jobs.length) {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.colSpan = 7;
            emptyCell.className = 'argon-special-page-empty';
            emptyCell.textContent = '<?php echo esc_js(__('当前没有可显示的任务', 'argon')); ?>';
            emptyRow.appendChild(emptyCell);
            jobsBody.appendChild(emptyRow);
            return;
        }

        jobs.slice(0, 100).forEach(function(job) {
            var row = document.createElement('tr');
            textCell(row, first(job, ['id', 'job_id', 'jobId'], '—'));
            textCell(row, first(job, ['url', 'hostname', 'target'], '—'));
            textCell(row, first(job, ['status', 'state'], '—'));
            textCell(row, first(job, ['backlink_state', 'backlinkState'], '—'));
            textCell(row, first(job, ['callback_status', 'callbackStatus'], '—'));
            textCell(row, formatTime(first(job, ['updated_at', 'updatedAt', 'checked_at', 'checkedAt'], 0)));
            textCell(row, jobDetails(job));
            jobsBody.appendChild(row);
        });
    }

    function render(payload, elapsed) {
        var worker = payload && payload.worker ? payload.worker : (payload || {});
        var jobs = first(worker, ['recent_jobs', 'recentJobs', 'jobs'], []);
        if (!Array.isArray(jobs)) jobs = [];
        var service = worker && worker.service && typeof worker.service === 'object' ? worker.service : {};
        var serviceStatus = String(first(service, ['status'], first(worker, ['ok'], true) === false ? 'error' : 'ok'));
        var serviceState = serviceStatus === 'ok' ? 'online' : (serviceStatus === 'degraded' ? 'warning' : 'error');
        var serviceMessage = serviceStatus === 'ok'
            ? '<?php echo esc_js(__('实时连接正常', 'argon')); ?>'
            : (serviceStatus === 'degraded'
                ? '<?php echo esc_js(__('Worker 在线，部分状态数据不可用', 'argon')); ?>'
                : '<?php echo esc_js(__('Worker 报告异常', 'argon')); ?>');
        setLive(serviceState, serviceMessage);
        updatedAt.textContent = new Date().toLocaleString();
        latency.textContent = Math.round(elapsed) + ' ms';
        serverTime.textContent = formatTime(first(worker, ['server_time', 'serverTime', 'now', 'timestamp'], first(payload, ['fetched_at'], 0)));
        renderSummary(statusSummary(worker, jobs));
        renderJobs(jobs);
        details.textContent = JSON.stringify(worker, null, 2);
    }

    function showError(error) {
        var message = error && error.message ? error.message : String(error || '<?php echo esc_js(__('未知错误', 'argon')); ?>');
        setLive('error', '<?php echo esc_js(__('连接失败', 'argon')); ?>');
        updatedAt.textContent = new Date().toLocaleString();
        details.textContent = message;
    }

    function stop() {
        stopped = true;
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = null;
        if (activeController) activeController.abort();
        activeController = null;
        document.removeEventListener('visibilitychange', onVisibilityChange);
    }

    function poll() {
        if (stopped || inFlight || document.hidden || !page.isConnected) return;
        inFlight = true;
        activeController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var startedAt = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        fetch(endpoint, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-WP-Nonce': restNonce
            },
            signal: activeController ? activeController.signal : undefined
        }).then(function(response) {
            return response.json().catch(function() {
                throw new Error('<?php echo esc_js(__('服务器返回的状态不是有效 JSON', 'argon')); ?>');
            }).then(function(payload) {
                if (!response.ok || !payload || payload.success === false) {
                    throw new Error(payload && (payload.message || payload.error)
                        ? String(payload.message || payload.error)
                        : '<?php echo esc_js(__('Worker 状态请求失败', 'argon')); ?>');
                }
                return payload;
            });
        }).then(function(payload) {
            var finishedAt = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
            render(payload, finishedAt - startedAt);
        }).catch(function(error) {
            if (!error || error.name !== 'AbortError') showError(error);
        }).then(function() {
            inFlight = false;
            activeController = null;
            if (!stopped && !document.hidden && page.isConnected) {
                pollTimer = setTimeout(poll, 500);
            }
        });
    }

    function onVisibilityChange() {
        if (document.hidden) {
            if (pollTimer) clearTimeout(pollTimer);
            pollTimer = null;
            if (activeController) activeController.abort();
            return;
        }
        if (!inFlight) poll();
    }

    document.addEventListener('visibilitychange', onVisibilityChange);
    window.argonStopWorkerStatusMonitor = stop;
    poll();
})();
</script>

<?php
argon_special_page_surface_close();
get_footer();
?>

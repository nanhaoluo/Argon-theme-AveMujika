<?php

/**
 * 公开仓库中的友链 Worker 示例配置与部署文档契约测试。
 *
 * 运行：php tests/friend-link-worker-public-config-test.php
 */

$root = dirname(__DIR__);
$worker_dir = $root . '/workers/friend-link-checker';
$read = function($path) {
    $content = @file_get_contents($path);
    return $content === false ? '' : $content;
};

$example_path = $worker_dir . '/wrangler.example.jsonc';
$example = $read($example_path);
$readme = $read($worker_dir . '/README.md');
$gitignore = $read($worker_dir . '/.gitignore');
$package = $read($worker_dir . '/package.json');
$types = $read($worker_dir . '/worker-configuration.d.ts');
$legacy_doc = $read($root . '/docs/cloudflare-worker-friend-link-checker.md');

$failures = array();
function argon_worker_public_assert($condition, $message) {
    global $failures;
    if (!$condition) $failures[] = $message;
}

argon_worker_public_assert($example !== '', '缺少 wrangler.example.jsonc。');
argon_worker_public_assert(
    strpos($example, 'blog.example.com') !== false
    && strpos($example, 'REPLACE_WITH_D1_DATABASE_ID') !== false
    && strpos($example, '"workers_dev": true') !== false,
    'Wrangler 示例没有使用通用站点、D1 占位符和 workers.dev。'
);
argon_worker_public_assert(
    substr_count($example, 'https://blog.example.com') === 3
    && strpos($example, '"routes"') === false,
    'Wrangler 示例没有完全使用通用域名，或默认绑定了自定义域。'
);
argon_worker_public_assert(
    strpos($example, '"nodejs_compat"') !== false
    && strpos($example, '"global_fetch_strictly_public"') !== false,
    'Wrangler 示例缺少原生 DNS 和严格公网 fetch 所需的兼容标志。'
);
argon_worker_public_assert(
    preg_match('/^wrangler\.jsonc$/m', trim($gitignore)) === 1,
    '本地生产 wrangler.jsonc 没有被忽略。'
);
argon_worker_public_assert(
    strpos($readme, 'Copy-Item wrangler.example.jsonc wrangler.jsonc') !== false
    && strpos($readme, 'npx wrangler queues create') !== false
    && strpos($readme, 'npx wrangler d1 create') !== false
    && strpos($readme, 'npx wrangler secret put SUBMIT_HMAC_SECRET') !== false
    && strpos($readme, 'Argon 主题设置') !== false
    && strpos($readme, 'wp-config.php') !== false,
    'README 没有覆盖从示例配置到 WordPress 对接的完整部署路径。'
);
argon_worker_public_assert(
    strpos($package, '--config wrangler.example.jsonc') !== false,
    '公开仓库类型检查仍依赖被忽略的本地 wrangler.jsonc。'
);
argon_worker_public_assert(
    strpos($types, 'blog.example.com') !== false,
    '提交的 Worker 类型仍由个人生产配置生成。'
);
argon_worker_public_assert(
    strpos($legacy_doc, '将在下一步接入') === false
    && strpos($legacy_doc, '无需修改后台 `settings.php`') === false,
    '旧部署文档仍声称主题尚未接入或没有后台配置。'
);

if ($failures) {
    fwrite(STDERR, "友链 Worker 公开配置测试失败：\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "友链 Worker 公开配置测试通过。\n";

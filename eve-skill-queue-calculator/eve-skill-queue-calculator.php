<?php
/*
Plugin Name: EVE Skill Queue Calculator
Description: Adds a shortcode [eve_skill_queue_calculator] to display an EVE Online skill queue calculator to calculate required skillpoints, skill injectors, and optimal attributes.
Version: 1.4
Author: C4813
Text Domain: eve-sqc
Requires at least: 6.0
Requires PHP: 7.4
*/

defined('ABSPATH') || exit;

define('EVE_SQC_VER', '1.4');
define('EVE_SQC_CACHE_TTL', 6 * HOUR_IN_SECONDS);
define('EVE_SQC_CACHE_DIRNAME', 'eve-skill-queue-calculator/cache');

// Region + systems (The Forge: Jita + Perimeter)
define('EVE_SQC_REGION_ID', 10000002);
define('EVE_SQC_PRIMARY_SYSTEM_ID', 30000142);   // Jita
define('EVE_SQC_SECONDARY_SYSTEM_ID', 30000144); // Perimeter

// Type IDs
define('EVE_SQC_TYPE_LARGE', 40520); // Large Skill Injector
define('EVE_SQC_TYPE_SMALL', 45635); // Small Skill Injector

/** i18n */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('eve-sqc', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/** Load + sanitize skills_data.json with size cap */
function eve_sqc_load_skills_data(): array {
    $file = plugin_dir_path(__FILE__) . 'skills_data.json';
    if (!file_exists($file)) return [];
    $max_bytes = 524288; // 512KB
    $size = filesize($file);
    if ($size === false || $size > $max_bytes) return [];
    $json = @file_get_contents($file, false, null, 0, $max_bytes);
    if ($json === false) return [];
    $data = json_decode($json, true);
    if (!is_array($data)) return [];

    $allowed_attrs = ['Intelligence','Perception','Willpower','Memory','Charisma'];
    $clean = [];
    foreach ($data as $skill => $arr) {
        if (!is_string($skill) || !is_array($arr) || count($arr) !== 3) continue;
        [$mult, $primary, $secondary] = $arr;
        if (!is_int($mult)) continue;
        if (!in_array($primary, $allowed_attrs, true)) continue;
        if (!in_array($secondary, $allowed_attrs, true)) continue;
        $clean[sanitize_text_field($skill)] = [$mult, $primary, $secondary];
    }
    return $clean;
}

/** Uploads cache path */
function eve_sqc_cache_path(): array {
    $up = wp_upload_dir(null, false);
    if (!empty($up['error'])) {
        // Fallback to system temp if uploads is misconfigured
        $base = trailingslashit(sys_get_temp_dir()) . EVE_SQC_CACHE_DIRNAME;
    } else {
        $base = trailingslashit($up['basedir']) . EVE_SQC_CACHE_DIRNAME;
    }
    $file = trailingslashit($base) . 'prices.json';
    return [$base, $file];
}

/** Secure the cache directory (no listing, no PHP exec) */
function eve_sqc_ensure_cache_dir_secure(string $dir): void {
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    $index = trailingslashit($dir) . 'index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }
    $ht = trailingslashit($dir) . '.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|php\\d*)$\">\n  Deny from all\n</FilesMatch>\n");
    }
}

/** Atomic write for cache */
function eve_sqc_write_cache_atomic(string $file, array $data): void {
    $tmp = $file . '.' . wp_generate_password(8, false) . '.tmp';
    @file_put_contents($tmp, wp_json_encode($data), LOCK_EX);
    @rename($tmp, $file);
    @chmod($file, 0640);
}

/** Sanitize price array and keep only known IDs */
function eve_sqc_sanitize_prices($prices): array {
    $out = [];
    foreach ([EVE_SQC_TYPE_LARGE, EVE_SQC_TYPE_SMALL] as $id) {
        $row = (is_array($prices) && isset($prices[$id]) && is_array($prices[$id])) ? $prices[$id] : [];
        $out[$id] = [
            'buy'  => isset($row['buy'])  ? (int)$row['buy']  : 0,
            'sell' => isset($row['sell']) ? (int)$row['sell'] : 0,
        ];
    }
    return $out;
}

/**
 * ESI fetch for a single type ID within a region, filtered to specific system_ids.
 * Includes orders in public player-owned structures and NPC stations.
 * Uses pagination cap and backs off on 420.
 */
function eve_sqc_esi_fetch_type_prices(int $type_id, array $system_ids): ?array {
    $base = 'https://esi.evetech.net/latest/markets/' . rawurlencode((string)EVE_SQC_REGION_ID) . '/orders/';
    $args = [
        'datasource' => 'tranquility',
        'order_type' => 'all',
        'type_id'    => $type_id,
        'page'       => 1,
    ];

    $highest_buy  = null; // max
    $lowest_sell  = null; // min
    $max_pages    = 1;

    $request_page = function(int $page) use ($base, $args) {
        $args['page'] = $page;
        $url = add_query_arg($args, $base);
        return wp_safe_remote_get($url, [
            'timeout'     => 8,
            'redirection' => 2,
            'sslverify'   => true,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => 'EVE-Skill-Queue-Calculator/1.4; ' . home_url('/'),
            ],
        ]);
    };

    // First page
    $resp = $request_page(1);
    if (is_wp_error($resp)) return null;
    $code = wp_remote_retrieve_response_code($resp);
    if ($code === 420) return null; // rate limited; caller will fall back to cache
    if ($code !== 200) return null;

    $pages_header = wp_remote_retrieve_header($resp, 'x-pages');
    $max_pages = is_numeric($pages_header) ? (int)$pages_header : 1;
    $max_pages = max(1, min($max_pages, 20)); // hard cap

    $process = function($response) use (&$highest_buy, &$lowest_sell, $system_ids) {
        $body = wp_remote_retrieve_body($response);
    
        // Skip absurdly large pages (2 MB cap)
        if (strlen($body) > 2000000) {
            return;
        }
    
        $rows = json_decode($body, true);
        if (!is_array($rows)) return;
    
        foreach ($rows as $row) {
            if (!isset($row['system_id']) || !in_array((int)$row['system_id'], $system_ids, true)) {
                continue;
            }
            if (!isset($row['price'], $row['is_buy_order'])) continue;
    
            $price = (float)$row['price'];
            if (!is_finite($price)) continue;
    
            if (!empty($row['is_buy_order'])) {
                if ($highest_buy === null || $price > $highest_buy) $highest_buy = $price;
            } else {
                if ($lowest_sell === null || $price < $lowest_sell) $lowest_sell = $price;
            }
        }
    };

    // Process page 1
    $process($resp);

    // Remaining pages
    for ($p = 2; $p <= $max_pages; $p++) {
        $resp = $request_page($p);
        if (is_wp_error($resp)) break;
        $code = wp_remote_retrieve_response_code($resp);
        if ($code === 420) break; // back off
        if ($code !== 200) break;
        $process($resp);
    }

    return [
        'buy'  => (int)round(max(0, (float)($highest_buy ?? 0))),
        'sell' => (int)round(max(0, (float)($lowest_sell ?? 0))),
    ];
}

/**
 * PRICE PROVIDER (fresh fetch) using ESI only, unless overridden.
 * Order:
 *  1) Legacy function fetch_injector_prices()
 *  2) Filter 'eve_sqc_fetch_prices'
 *  3) ESI (The Forge region, Jita+Perimeter)
 */
function eve_sqc_provider_fetch_prices(): ?array {
    if (function_exists('fetch_injector_prices')) {
        $p = call_user_func('fetch_injector_prices');
        if (is_array($p)) return $p;
    }
    $p = apply_filters('eve_sqc_fetch_prices', null);
    if (is_array($p)) return $p;

    $systems = [EVE_SQC_PRIMARY_SYSTEM_ID, EVE_SQC_SECONDARY_SYSTEM_ID];
    $large = eve_sqc_esi_fetch_type_prices(EVE_SQC_TYPE_LARGE, $systems);
    $small = eve_sqc_esi_fetch_type_prices(EVE_SQC_TYPE_SMALL, $systems);

    if (!is_array($large) || !is_array($small)) return null;

    return [
        EVE_SQC_TYPE_LARGE => $large,
        EVE_SQC_TYPE_SMALL => $small,
    ];
}

/**
 * Get prices from cache or refresh via provider.
 * - If fresh cache (< 6h): return it.
 * - Else refresh; on success write cache and return.
 * - On refresh failure but cache exists: return cached (even if stale).
 * - Else return zeros.
 */
function eve_sqc_get_prices_from_cache_or_refresh(): array {
    [$dir, $file] = eve_sqc_cache_path();
    $defaults = [
        EVE_SQC_TYPE_LARGE => ['buy'=>0,'sell'=>0],
        EVE_SQC_TYPE_SMALL => ['buy'=>0,'sell'=>0],
    ];

    if (file_exists($file)) {
        $mtime = @filemtime($file);
        if ($mtime !== false && (time() - $mtime) < EVE_SQC_CACHE_TTL) {
            $body = @file_get_contents($file);
            $data = json_decode((string)$body, true);
            if (is_array($data)) return eve_sqc_sanitize_prices($data);
        }
    }

    // Try refresh
    $fresh = eve_sqc_provider_fetch_prices();
    if (is_array($fresh)) {
        $clean = eve_sqc_sanitize_prices($fresh);
        eve_sqc_ensure_cache_dir_secure($dir);
        eve_sqc_write_cache_atomic($file, $clean);
        return $clean;
    }

    // Fallback to any cache, even stale
    if (file_exists($file)) {
        $body = @file_get_contents($file);
        $data = json_decode((string)$body, true);
        if (is_array($data)) return eve_sqc_sanitize_prices($data);
    }

    return $defaults;
}

/** Simple IP-based throttle for the public REST endpoint */
function eve_sqc_rate_limited(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'eve_sqc_rl_' . md5($ip);
    $hits = (int) get_transient($key);
    if ($hits > 30) return true; // >30 calls per 5 minutes
    set_transient($key, $hits + 1, 5 * MINUTE_IN_SECONDS);
    return false;
}

/** Public REST endpoint: fetched on Calculate */
add_action('rest_api_init', function () {
    register_rest_route('eve-sqc/v1', '/prices', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            if (eve_sqc_rate_limited()) {
                return new WP_REST_Response(['error' => 'rate_limited'], 429);
            }
            return new WP_REST_Response(
                eve_sqc_get_prices_from_cache_or_refresh(),
                200,
                [ 'Cache-Control' => 'public, max-age=300' ] // 5 minutes client/proxy cache
            );
        }
    ]);
});

/** Shortcode output (enqueue assets only when used) */
function eve_skill_queue_calculator_shortcode(): string {
    // Style only when shortcode is used
    $style_path = plugin_dir_path(__FILE__) . 'style.css';
    wp_enqueue_style(
        'eve-skill-queue-style',
        plugin_dir_url(__FILE__) . 'style.css',
        [],
        file_exists($style_path) ? filemtime($style_path) : EVE_SQC_VER
    );

    // Register + localize + enqueue JS
    $script_path = plugin_dir_path(__FILE__) . 'queue-calculator.js';
    wp_register_script(
        'eve-skill-queue',
        plugin_dir_url(__FILE__) . 'queue-calculator.js',
        [],
        file_exists($script_path) ? filemtime($script_path) : EVE_SQC_VER,
        true
    );

    wp_localize_script('eve-skill-queue', 'EVE_SQC_DATA', [
        'skillsData'        => eve_sqc_load_skills_data(),
        'defaultAttributes' => [
            'Intelligence' => 17,
            'Perception'   => 17,
            'Willpower'    => 17,
            'Memory'       => 17,
            'Charisma'     => 17,
        ],
        'rest' => [
            'url'   => esc_url_raw( rest_url('eve-sqc/v1/prices') ),
            'nonce' => wp_create_nonce('wp_rest'),
        ],
    ]);

    wp_enqueue_script('eve-skill-queue');

    ob_start(); ?>
    <div id="queue-calculator">
        <h2><?php echo esc_html__('Skill Queue Calculator', 'eve-sqc'); ?></h2>

        <p>
            <?php
            echo wp_kses_post(
                sprintf(
                    /* translators: %s is an example skill format */
                    __('Paste your skills below (format: <code>%s</code>, one per line):', 'eve-sqc'),
                    esc_html__('SkillName Level', 'eve-sqc')
                )
            );
            ?>
        </p>

        <textarea id="skillsInput" rows="12" maxlength="20000"></textarea>

        <p>
            <label for="currentSPQueue"><?php echo esc_html__('Current Skill Points', 'eve-sqc'); ?></label>
            <input
                type="number"
                id="currentSPQueue"
                name="currentSPQueue"
                value="0"
                min="0"
                step="1"
                inputmode="numeric"
                pattern="\d*"
                aria-label="<?php echo esc_attr__('Current Skill Points (non-negative integer)', 'eve-sqc'); ?>"
            />
        </p>

        <button id="calcButton" type="button"><?php echo esc_html__('Calculate', 'eve-sqc'); ?></button>

        <div id="results" aria-live="polite"></div>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('eve_skill_queue_calculator', 'eve_skill_queue_calculator_shortcode');

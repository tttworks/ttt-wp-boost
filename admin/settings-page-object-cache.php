<?php
/**
 * TTT WP Boost — Object Cache Settings Page
 *
 * @package TTT_WP_Boost
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── 引入 Object Cache 引擎 ── */
require_once TTT_WP_BOOST_PATH . 'includes/class-object-cache.php';

/* ── 共享 Tab 导航 ── */
require_once TTT_WP_BOOST_PATH . 'admin/parts/tab-nav.php';

/* ── 处理表单提交 ── */
if ( isset( $_POST['ttt_wb_object_cache_save'] ) && check_admin_referer( 'ttt_wb_object_cache_save', 'nonce' ) ) {
    $settings['enable_object_cache'] = isset( $_POST['enable_object_cache'] ) ? 'yes' : 'no';
    $settings['object_cache_ttl']    = isset( $_POST['object_cache_ttl'] )
        ? absint( $_POST['object_cache_ttl'] )
        : 3600;
    $settings['cache_elementor_css'] = isset( $_POST['cache_elementor_css'] ) ? 'yes' : 'no';
    $settings['cache_wp_queries']    = isset( $_POST['cache_wp_queries'] ) ? 'yes' : 'no';
    update_option( 'ttt_wp_boost_settings', $settings );

    /* 保存后重新实例化 */
    $settings = get_option( 'ttt_wp_boost_settings', [] );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Object Cache settings saved.', 'ttt-wp-boost' ) . '</p></div>';
}

$settings = get_option( 'ttt_wp_boost_settings', [] );
$cache    = new TTT_WP_Boost_Object_Cache( $settings );
$stats    = $cache->get_stats();
$backend  = $cache->get_backend();
$backend_label = $cache->get_backend_label();
$server_info = $cache->get_server_info();
$is_enabled = $cache->is_enabled();

/* ── 刷新缓存 AJAX ── */
if ( isset( $_POST['ttt_wb_flush_object_cache'] ) && check_admin_referer( 'ttt_wb_flush_object_cache', 'nonce' ) ) {
    $cache->flush();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Object cache flushed.', 'ttt-wp-boost' ) . '</p></div>';
    $stats = $cache->get_stats();
}
?>

<!-- ═══════════════════════════════════════════════════════
     后端状态卡片
    ═══════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card">
        <h2><?php esc_html_e( '后端状态', 'ttt-wp-boost' ); ?></h2>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
            <?php
            $badge_class = 'backend-transients' === $backend ? 'background:#f0f0f1;color:#646970;border:1px solid #c3c4c7;' : 'background:#2271b1;color:#fff;border:1px solid #2271b1;';
            ?>
            <span class="button" style="<?php echo esc_attr( $badge_class ); ?>pointer-events:none;font-size:15px;font-weight:600;">
                <?php echo esc_html( $backend_label ); ?>
            </span>
            <span class="button" style="pointer-events:none;<?php echo $is_enabled ? 'background:#00a32a;color:#fff;border-color:#00a32a;' : 'background:#d63638;color:#fff;border-color:#d63638;'; ?>">
                <?php echo $is_enabled ? esc_html__( 'Enabled', 'ttt-wp-boost' ) : esc_html__( 'Disabled', 'ttt-wp-boost' ); ?>
            </span>
        </div>

        <?php if ( ! empty( $server_info ) ) : ?>
        <table class="widefat" style="margin-top:16px;max-width:500px;">
            <thead><tr><th colspan="2"><?php esc_html_e( 'Server Info', 'ttt-wp-boost' ); ?></th></tr></thead>
            <tbody>
                <?php foreach ( $server_info as $k => $v ) : ?>
                <tr>
                    <td style="width:160px;"><code><?php echo esc_html( $k ); ?></code></td>
                    <td><?php echo esc_html( $v ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ( 'transients' === $backend ) : ?>
        <div style="margin-top:16px;padding:12px 16px;background:#fff8e5;border-left:4px solid #d4a017;border-radius:3px;font-size:13px;">
            <strong><?php esc_html_e( 'Running in compatibility mode', 'ttt-wp-boost' ); ?></strong><br>
            <?php
            printf(
                /* translators: %s = backend name */
                esc_html__( 'Your server does not have Redis or Memcached installed. TTT WP Boost is using %s, which works on any WordPress host without additional server configuration.', 'ttt-wp-boost' ),
                '<code>WordPress Transients</code>'
            );
            ?>
            <br>
            <?php
            printf(
                /* translators: %s = URL to performance plugin article */
                esc_html__( 'Transients store data in the WordPress database. For better performance, consider adding %s to your hosting environment.', 'ttt-wp-boost' ),
                '<a href="https://developer.wordpress.org/reference/classes/wp_object_cache/" target="_blank" rel="noopener">Redis or Memcached</a>'
            );
            ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         缓存统计卡片
    ═══════════════════════════════════════════════════════════ -->
    <div class="ttt-boost-card">
        <h2><?php esc_html_e( '缓存统计', 'ttt-wp-boost' ); ?></h2>
        <p class="description" style="margin-top:0;">
            <?php esc_html_e( 'Statistics are reset when PHP process restarts. For persistent stats, a persistent backend (Redis/Memcached) is required.', 'ttt-wp-boost' ); ?>
        </p>
        <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:12px;">
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number"><?php echo esc_html( $stats['hits'] ); ?></div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cache Hits', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number"><?php echo esc_html( $stats['misses'] ); ?></div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cache Misses', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo (int)$stats['hit_rate'] > 0 ? '#00a32a' : '#646970'; ?>">
                    <?php echo esc_html( $stats['hit_rate'] ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'Hit Rate', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number"><?php echo esc_html( $stats['bytes'] ); ?></div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cached Data', 'ttt-wp-boost' ); ?></div>
            </div>
        </div>

        <div class="ttt-boost-actions" style="margin-top:16px;">
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'ttt_wb_flush_object_cache', 'nonce' ); ?>
                <button type="submit" name="ttt_wb_flush_object_cache" class="button ttt-boost-clear-btn"
                    onclick="return confirm('<?php esc_attr_e( 'Flush all object cache? This cannot be undone.', 'ttt-wp-boost' ); ?>')">
                    <?php esc_html_e( 'Flush Object Cache', 'ttt-wp-boost' ); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         设置表单
    ═══════════════════════════════════════════════════════════ -->
    <form method="post" action="" class="ttt-boost-card">
        <h2><?php esc_html_e( 'Settings', 'ttt-wp-boost' ); ?></h2>
        <?php wp_nonce_field( 'ttt_wb_object_cache_save', 'nonce' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e( '启用对象缓存', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-enable-object-cache" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_object_cache" value="yes"
                            <?php checked( 'yes', $settings['enable_object_cache'] ?? 'no' ); ?>
                            onchange="updateObjectCacheIndicators()">
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Cache frequently accessed data (Elementor CSS, query results) to reduce database load.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e( '缓存有效期（秒）', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-object-cache-ttl" style="float:right;"></span>
                </th>
                <td>
                    <input type="number" name="object_cache_ttl"
                        value="<?php echo esc_attr( $settings['object_cache_ttl'] ?? 3600 ); ?>"
                        min="60" max="86400" step="60" class="small-text">
                    <p class="description">
                        <?php esc_html_e( 'How long cached data remains valid. 3600 = 1 hour, 86400 = 24 hours. Shorter TTL = more accurate but more database queries.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e( '缓存 Elementor CSS', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-cache-elementor-css" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="cache_elementor_css" value="yes"
                            <?php checked( 'yes', $settings['cache_elementor_css'] ?? 'no' ); ?>
                            onchange="updateObjectCacheIndicators()">
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Elementor regenerates its dynamic CSS on every page load. Caching the compiled CSS can reduce TTFB by 50-200ms on Elementor sites.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e( '缓存 WordPress 查询', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-cache-wp-queries" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="cache_wp_queries" value="yes"
                            <?php checked( 'yes', $settings['cache_wp_queries'] ?? 'no' ); ?>
                            onchange="updateObjectCacheIndicators()">
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Cache common WordPress queries (recent posts, archives, categories). Reduces database SELECT queries on high-traffic pages.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="ttt_wb_object_cache_save" class="button button-primary">
                <?php esc_html_e( 'Save Settings', 'ttt-wp-boost' ); ?>
            </button>
        </p>
    </form>

    <!-- ═══════════════════════════════════════════════════════
         使用说明
    ═══════════════════════════════════════════════════════════ -->
    <div class="ttt-boost-card" style="border-left:4px solid #2271b1;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'How It Works', 'ttt-wp-boost' ); ?></h2>

        <h3><?php esc_html_e( 'Backend Priority', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'TTT WP Boost automatically detects the fastest available backend in this order:', 'ttt-wp-boost' ); ?></p>
        <ol style="margin-top:8px;">
            <li><strong>Redis</strong> — <?php esc_html_e( 'Recommended. Fast, persistent, network-accessible. Best for high-traffic sites and multisite.', 'ttt-wp-boost' ); ?></li>
            <li><strong>Memcached</strong> — <?php esc_html_e( 'Fast, distributed memory cache. Good for shared hosting environments.', 'ttt-wp-boost' ); ?></li>
            <li><strong>WordPress Transients</strong> — <?php esc_html_e( 'Works everywhere. Stores data in the WordPress database. No server configuration needed.', 'ttt-wp-boost' ); ?></li>
        </ol>

        <h3><?php esc_html_e( 'What Gets Cached', 'ttt-wp-boost' ); ?></h3>
        <ul style="margin-top:8px;">
            <li><strong>Elementor CSS</strong> — <?php esc_html_e( 'The dynamically generated CSS for each post/page is cached. Clears automatically when a post is updated.', 'ttt-wp-boost' ); ?></li>
            <li><strong>Query Results</strong> — <?php esc_html_e( 'Common WordPress queries (recent posts, category lists) are cached to reduce database load.', 'ttt-wp-boost' ); ?></li>
            <li><strong>Post Meta</strong> — <?php esc_html_e( 'Frequently accessed post metadata is cached to avoid repeated database lookups.', 'ttt-wp-boost' ); ?></li>
        </ul>

        <h3><?php esc_html_e( 'When Cache is Cleared', 'ttt-wp-boost' ); ?></h3>
        <ul style="margin-top:8px;">
            <li><?php esc_html_e( 'Publishing, updating, or deleting a post clears related caches.', 'ttt-wp-boost' ); ?></li>
            <li><?php esc_html_e( 'Post meta changes also trigger targeted cache invalidation.', 'ttt-wp-boost' ); ?></li>
            <li><?php esc_html_e( 'Use the "Flush Object Cache" button above for a full manual clear.', 'ttt-wp-boost' ); ?></li>
        </ul>

        <h3><?php esc_html_e( 'Redis / Memcached Setup', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'To enable Redis or Memcached, add the following to your wp-config.php:', 'ttt-wp-boost' ); ?></p>
        <pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:4px;font-size:13px;overflow-x:auto;">// Redis Configuration
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );

// OR Memcached Configuration
// define( 'MEMCACHED_HOST', '127.0.0.1' );
// define( 'MEMCACHED_PORT', 11211 );</pre>
    </div>
</div>

<script>
/* ── Debug 模式：实时状态指示器 ── */
function updateObjectCacheIndicators() {
    var enabled = document.querySelector('input[name="enable_object_cache"]').checked;
    var elemCss = document.querySelector('input[name="cache_elementor_css"]').checked;
    var wpQ    = document.querySelector('input[name="cache_wp_queries"]').checked;

    var indEnable = document.getElementById('ind-enable-object-cache');
    var indElem   = document.getElementById('ind-cache-elementor-css');
    var indQuery  = document.getElementById('ind-cache-wp-queries');

    function setIndicator(el, active) {
        if (!el) return;
        el.style.display = 'inline-block';
        el.style.padding = '2px 8px';
        el.style.borderRadius = '3px';
        el.style.fontSize = '11px';
        el.style.fontWeight = '600';
        el.style.verticalAlign = 'middle';
        el.style.marginLeft = '6px';
        el.textContent = active ? '<?php esc_attr_e( 'ACTIVE', 'ttt-wp-boost' ); ?>' : '<?php esc_attr_e( 'INACTIVE', 'ttt-wp-boost' ); ?>';
        el.style.background = active ? '#00a32a' : '#d63638';
        el.style.color = '#fff';
    }

    setIndicator(indEnable, enabled);
    setIndicator(indElem,   enabled && elemCss);
    setIndicator(indQuery,  enabled && wpQ);
}

document.addEventListener('DOMContentLoaded', updateObjectCacheIndicators);
</script>

</div>

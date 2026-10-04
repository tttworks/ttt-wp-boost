<?php
/**
 * TTT WP Boost — Performance Dashboard Report
 *
 * @package TTT_WP_Boost
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once TTT_WP_BOOST_PATH . 'includes/class-page-cache.php';
require_once TTT_WP_BOOST_PATH . 'includes/class-object-cache.php';

/* ── 数据准备 ── */
$page_cache = new TTT_WP_Boost_Page_Cache( get_option( 'ttt_wp_boost_settings', [] ) );
$object_cache = new TTT_WP_Boost_Object_Cache( get_option( 'ttt_wp_boost_settings', [] ) );
$asset_min = class_exists( 'TTT_WP_Boost_Asset_Minification' )
    ? new TTT_WP_Boost_Asset_Minification( get_option( 'ttt_wp_boost_settings', [] ) )
    : null;

$page_stats = ttt_wp_boost_get_cache_stats();
$obj_stats   = $object_cache->get_stats();
$asset_stats  = $asset_min ? $asset_min->get_optimization_status() : [];

/* ── 共享 Tab 导航 ── */
require_once TTT_WP_BOOST_PATH . 'admin/parts/tab-nav.php';
?>

<!-- ═════════════════════════════════════════════════════════
     缓存统计卡片
    ═════════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card">
    <h2><?php esc_html_e( 'Cache Performance', 'ttt-wp-boost' ); ?></h2>

    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number"><?php echo esc_html( $page_stats['file_count'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cached Pages', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number"><?php echo esc_html( $page_stats['total_size'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Total Size', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number" style="color:#2271b1;"><?php echo esc_html( $page_stats['last_updated'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Last Updated', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number" style="color:#00a32a;"><?php echo esc_html( $obj_stats['hits'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cache Hits', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number" style="color:#d4a017;"><?php echo esc_html( $obj_stats['misses'] ); ?></div>
            <div class="tt-boost-stat-label"><?php esc_html_e( 'Cache Misses', 'ttt-wp-boost' ); ?></div>
        </div>
    </div>

    <div class="ttt-boost-actions">
        <button id="ttt-boost-clear-btn" class="button ttt-boost-clear-btn">
            <?php esc_html_e( 'Clear All Cache', 'ttt-wp-boost' ); ?>
        </button>
        <span id="ttt-boost-spinner" class="spinner" style="float:none;margin-top:0;"></span>
        <div id="ttt-boost-notice" class="ttt-boost-notice"></div>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════
     后端状态卡片
    ═════════════════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card">
    <h2><?php esc_html_e( 'Backend Status', 'ttt-wp-boost' ); ?></h2>

    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
        <span class="button" style="pointer-events:none;<?php echo 'transients' === $obj_stats['backend'] ? 'background:#f0f0f1;color:#646970;border:1px solid #c3c4c7;' : 'background:#2271b1;color:#fff;border:1px solid #2271b1;'; ?>">
            <?php echo esc_html( $obj_stats['backend_label'] ); ?>
        </span>
        <span class="button" style="pointer-events:none;<?php echo $obj_stats['is_enabled'] ? 'background:#00a32a;color:#fff;border-color:#00a32a;' : 'background:#d63638;color:#fff;border-color:#d63638;'; ?>">
            <?php echo $obj_stats['is_enabled'] ? esc_html__( 'Enabled', 'ttt-wp-boost' ) : esc_html__( 'Disabled', 'ttt-wp-boost' ); ?>
        </span>
        <?php if ( ! empty( $obj_stats['bytes'] && $obj_stats['bytes'] !== '0.0 B' ) : ?>
        <span class="button" style="pointer-events:none;">
            <?php echo esc_html( $obj_stats['bytes'] ); ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if ( 'transients' === $obj_stats['backend'] ) : ?>
    <div style="margin-top:16px;padding:12px 16px;background:#fff8e5;border-left:4px solid #d4a017;border-radius:3px;font-size:13px;line-height:1.7;">
        <strong><?php esc_html_e( 'Running in compatibility mode', 'ttt-wp-boost' ); ?></strong><br>
        <?php printf(
            esc_html__( 'Your server does not have Redis or Memcached installed. TTT WP Boost is using %s, which works on any WordPress host without additional server configuration.', 'ttt-wp-boost' ),
            '<code>WordPress Transients API</code>'
        ); ?>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     资源使用卡片
    ═════════════════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card">
    <h2><?php esc_html_e( 'Resource Usage', 'ttt-wp-boost' ); ?></h2>

    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number"><?php echo esc_html( $obj_stats['bytes'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cached Data', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number"><?php echo esc_html( $obj_stats['sets'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cache Operations', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number"><?php echo esc_html( $obj_stats['deletes'] ); ?></div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Cache Deletions', 'ttt-wp-boost' ); ?></div>
        </div>
        <div class="ttt-boost-stat">
            <div class="ttt-boost-stat-number" style="color:<?php echo (int)$obj_stats['hit_rate'] > 0 ? '#00a32a' : '#646970'; ?>">
                <?php echo esc_html( $obj_stats['hit_rate'] ); ?>
            </div>
            <div class="ttt-boost-stat-label"><?php esc_html_e( 'Hit Rate', 'ttt-wp-boost' ); ?></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     使用说明
    ═════════════════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card" style="border-left:4px solid #2271b1;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'How It Works', 'ttt-wp-boost' ); ?></h2>

    <h3><?php esc_html_e( 'Page Cache', 'ttt-wp-boost' ); ?></h3>
    <p><?php esc_html_e( 'Static HTML caching for non-logged-in visitors. Caches are served instantly from the filesystem, reducing server load and improving response times. Each page is cached as a static HTML file in the uploads folder.', 'ttt-wp-boost' ); ?></p>

    <h3><?php esc_html_e( 'Object Cache', 'ttt-wp-boost' ); ?></h3>
    <p><?php esc_html_e( 'Three-tier fallback architecture (Redis → Memcached → WordPress Transients). Caches frequently accessed data (Elementor CSS, query results) to reduce database load. Compatible with any hosting environment.', 'ttt-wp-boost' ); ?></p>

    <h3><?php esc_html_e( 'Dashboard Accelerator', 'ttt-wp-boost' ); ?></h3>
    <p><?php esc_html_e( 'WordPress native optimizations for the admin area. Disables Gutenberg block editor, slows Heartbeat API, removes unused resources, and reduces database bloat (post revisions). Great for improving admin panel performance on low-end servers.', 'ttt-wp-boost' ); ?></p>

    <h3><?php esc_html_e( 'Asset Minification', 'ttt-wp-boost' ); ?></h3>
    <p><?php esc_html_e( 'CSS and JavaScript minification reduces file sizes by removing whitespace, comments, and unnecessary characters. Caches minified assets for 24 hours. Critical CSS can be inlined for faster First Contentful Paint (FCP).', 'ttt-wp-boost' ); ?></p>

    <h3><?php esc_html_e( 'Cache Compatibility', 'ttt-wp-boost' ); ?></h3>
    <ul style="margin-top:8px;">
        <li><?php esc_html_e( '<strong>Elementor</strong> — Page cache works with Elementor. Object Cache can cache Elementor dynamic CSS. Dashboard Accelerator optimizations do not affect Elementor pages.', 'ttt-wp-boost' ); ?></li>
        <li><?php esc_html_e( '<strong>WooCommerce</strong> — Page cache works. Object Cache can cache WooCommerce queries. Dashboard Accelerator optimizations do not affect WooCommerce pages.', 'ttt-wp-boost' ); ?></li>
        <li><?php esc_html_e( '<strong>Page Builders</strong> (Divi, Bricks) — Page cache works with Elementor. Asset Minification works alongside most page builders without conflicts.', 'ttt-wp-boost' ); ?></li>
    </ul>
</div>

<script>
document.getElementById('ttt-boost-clear-btn').addEventListener('click', function(){
    var btn  = this;
    var spin = document.getElementById('ttt-boost-spinner');
    var note = document.getElementById('ttt-boost-notice');
    btn.disabled = true;
    spin.classList.add('is-active');
    note.style.display = 'none';

    var data = new FormData();
    data.append('action', 'ttt_wp_boost_clear_cache');
    data.append('nonce', '<?php echo wp_create_nonce( 'ttt_wp_boost' ); ?>');

    fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', { method: 'POST', body: data })
    .then(function(r){ return r.json(); })
    .then(function(json){
        if (json.success) {
            note.textContent = json.data.message;
            note.className = 'ttt-boost-notice success';
            document.querySelectorAll('.ttt-boost-stat-number').forEach(function(el){
                if (el.id === 'ttt-stat-count') {
                    el.textContent = '0';
                } else if (el.id === 'ttt-stat-size') {
                    el.textContent = '0 KB';
                } else if (el.id === 'ttt-stat-time') {
                    el.textContent = 'Just now';
                } else if (el.textContent.includes('Cache Hits')) {
                    el.textContent = '0';
                } else if (el.textContent.includes('Cache Misses')) {
                    el.textContent = '0';
                } else if (el.textContent.includes('Hit Rate')) {
                    el.textContent = '0%';
                } else if (el.textContent.includes('Cached Data')) {
                    el.textContent = '0.0 B';
                } else if (el.textContent.includes('Cache Operations')) {
                    el.textContent = '0';
                }
            });
        } else {
            note.textContent = 'Error clearing cache.';
            note.className = 'ttt-boost-notice error';
        }
        note.style.display = 'block';
        btn.disabled = false;
        spin.classList.remove('is-active');
    })
    .catch(function(){
        note.textContent = 'Network error.';
        note.className = 'ttt-boost-notice error';
        note.style.display = 'block';
        btn.disabled = false;
        spin.classList.remove('is-active');
    });
});
</script>
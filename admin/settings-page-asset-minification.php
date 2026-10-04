<?php
/**
 * TTT WP Boost — Asset Minification Settings Page
 * CSS/JS 压缩优化（参考 Flying Press / WP Rocket）
 *
 * @package TTT_WP_Boost
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── 引入 Asset Minification 引擎 ── */
require_once TTT_WP_BOOST_PATH . 'includes/class-asset-minification.php';

/* ── 共享 Tab 导航 ── */
require_once TTT_WP_BOOST_PATH . 'admin/parts/tab-nav.php';

/* ── 处理表单提交 ── */
if ( isset( $_POST['ttt_wb_asset_save'] ) && check_admin_referer( 'ttt_wb_asset_save', 'nonce' ) ) {
    $settings['minify_css']           = isset( $_POST['minify_css'] ) ? 'yes' : 'no';
    $settings['inline_critical_css']  = isset( $_POST['inline_critical_css'] ) ? 'yes' : 'no';
    $settings['minify_js']            = isset( $_POST['minify_js'] ) ? 'yes' : 'no';
    $settings['defer_js']             = isset( $_POST['defer_js'] ) ? 'yes' : 'no';
    $settings['excluded_css_handles'] = isset( $_POST['excluded_css_handles'] ) ? array_map( 'trim', explode( "\n", sanitize_textarea_field( $_POST['excluded_css_handles'] ) ) ) : [];
    $settings['excluded_js_handles']  = isset( $_POST['excluded_js_handles'] ) ? array_map( 'trim', explode( "\n", sanitize_textarea_field( $_POST['excluded_js_handles'] ) ) ) : [];

    update_option( 'ttt_wp_boost_settings', $settings );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Asset Minification settings saved.', 'ttt-wp-boost' ) . '</p></div>';
}

if ( isset( $_POST['ttt_wb_clear_asset_cache'] ) && check_admin_referer( 'ttt_wb_clear_asset_cache', 'nonce' ) ) {
    $asset_min = new TTT_WP_Boost_Asset_Minification( get_option( 'ttt_wp_boost_settings', [] ) );
    $asset_min->clear_cache();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Asset cache cleared.', 'ttt-wp-boost' ) . '</p></div>';
}

$settings = get_option( 'ttt_wp_boost_settings', [] );
$asset_min = new TTT_WP_Boost_Asset_Minification( $settings );
$opt_status = $asset_min->get_optimization_status();

?>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo $opt_status['minify_css'] ? '#00a32a' : '#d63638'; ?>">
                    <?php echo $opt_status['minify_css'] ? esc_html__( '开启', 'ttt-wp-boost' ) : esc_html__( '关闭', 'ttt-wp-boost' ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'CSS Minify', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="tt-boost-stat">
                <div class="tt-boost-stat-number" style="color:<?php echo $opt_status['inline_critical_css'] ? '#00a32a' : '#d63638'; ?>">
                    <?php echo $opt_status['inline_critical_css'] ? esc_html__( '开启', 'ttt-wp-boost' ) : esc_html__( '关闭', 'ttt-wp-boost' ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'Critical CSS', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo $opt_status['minify_js'] ? '#00a32a' : '#d63638'; ?>">
                    <?php echo $opt_status['minify_js'] ? esc_html__( '开启', 'ttw-boost' ) : esc_html__( '关闭', 'ttt-wp-boost' ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'JS Minify', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="tt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo $opt_status['defer_js'] ? '#00a32a' : '#646970'; ?>">
                    <?php echo $opt_status['defer_js'] ? esc_html__( '开启', 'ttt-wp-boost' ) : esc_html__( '关闭', 'ttt-wp-boost' ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( 'JS Defer', 'ttt-wp-boost' ); ?></div>
            </div>
        </div>

        <div class="ttt-boost-actions">
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'ttt_wb_clear_asset_cache', 'nonce' ); ?>
                <button type="submit" name="ttt_wb_clear_asset_cache" class="button ttt-boost-clear-btn">
                    <?php esc_html_e( 'Clear Asset Cache', 'ttt-wp-boost' ); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════════
         设置表单
    ═════════════════════════════════════════════════════════ -->
    <form method="post" action="" class="ttt-boost-card">
        <h2><?php esc_html_e( 'CSS 优化', 'ttt-wp-boost' ); ?></h2>
        <?php wp_nonce_field( 'ttt_wb_asset_save', 'nonce' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e( '压缩 CSS', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-minify-css" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="minify_css" value="yes"
                            <?php checked( 'yes', $settings['minify_css'] ?? 'no' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Removes whitespace, comments, and unnecessary characters from CSS files. Each CSS file is minified and cached for 24 hours. Reduces page size and improves load times.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( '内联关键 CSS', 'ttt-wp-aboost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-inline-critical-css" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="inline_critical_css" value="yes"
                            <?php checked( 'yes', $settings['inline_critical_css'] ?? 'no' ); ?>>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Critical CSS are styles needed to render the above-the-fold content. By inlining them into the HTML head, they render faster. Automatically identifies critical CSS from loaded CSS and limits total size to 50KB. Great for improving First Contentful Paint (FCP).', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Exclude CSS Handles', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <textarea name="excluded_css_handles" rows="5" cols="50" class="large-text code"><?php
                        $excluded = isset( $settings['excluded_css_handles'] ) ? (array) $settings['excluded_css_handles'] : [];
                        echo esc_textarea( implode( "\n", $excluded ) );
                    ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'CSS handles (slug) that should not be minified. One per line. Commonly excluded: dashicons, admin-bar, wp-block-library.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2 style="margin-top:24px;"><?php esc_html_e( 'JavaScript 优化', 'ttt-wp-boost' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e( '压缩 JavaScript', 'ttt-wp-boost' ); ?>
                    <span class="ttt-boost-status-indicator" id="ind-minify-js" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="minify_js" value="yes"
                            <?php checked( 'yes', $settings['minify_js'] ?? 'no' ); ?>>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Removes whitespace, comments, and unnecessary characters from JavaScript files. Each JS file is minified and cached for 24 hours. Reduces page size and improves parse time.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( '延迟加载 JavaScript', 'ttt-wp-boost' ); ?>
                    <span class="tttboost-status-indicator" id="ind-defer-js" style="float:right;"></span>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="defer_js" value="yes"
                            <?php checked( 'yes', $settings['defer_js'] ?? 'no' ); ?>>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Non-essential JavaScript is loaded with the "defer" attribute, which prevents it from blocking page rendering. Core scripts (jQuery, WordPress core) are never deferred. Great for improving Time to Interactive (TTI).', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Exclude JS Handles', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <textarea name="excluded_js_handles" rows="5" cols="50" class="large-text code"><?php
                        $excluded = isset( $settings['excluded_js_handles'] ) ? (array) $settings['excluded_js_handles'] : [];
                        echo esc_textarea( implode( "\n", $excluded ) );
                    ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'JavaScript handles (slug) that should not be minified or deferred. One per line. Commonly excluded: jquery, jquery-core, elementor-frontend.', 'ttt-wp-boost' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="ttt_wb_asset_save" class="button button-primary">
                <?php esc_html_e( 'Save Settings', 'ttt-wp-boost' ); ?>
            </button>
        </p>
    </form>

    <!-- ═════════════════════════════════════════════════════
         使用说明
    ═══════════════════════════════════════════════════════════ -->
    <div class="ttt-boost-card" style="border-left:4px solid #2271b1;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'How It Works', 'ttt-wp-boost' ); ?></h2>

        <h3><?php esc_html_e( 'CSS Minification', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'CSS minification removes all whitespace (spaces, tabs, newlines) and comments from CSS files. The minified CSS is cached for 24 hours to avoid repeated processing. This can reduce CSS file size by 20-30% without changing functionality.', 'ttt-wp-boost' ); ?></p>
        <p><?php esc_html_e( 'You can exclude specific CSS handles from minification if minified CSS breaks your theme or plugin functionality.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'Critical CSS Inline', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'Critical CSS are the styles needed to render the visible part of the page (above the fold). This feature automatically identifies critical CSS from your loaded stylesheets and inlines them into the HTML head, ensuring they render immediately without waiting for additional requests.', 'ttt-wp-boost' ); ?></p>
        <p><?php esc_html_e( 'Total inline CSS is limited to 50KB. If your site loads more than 50KB of critical CSS, only the first 50KB is inlined. The rest loads normally via their original links.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'JavaScript Minification', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'JavaScript minification works similarly to CSS, removing whitespace, comments, and line breaks. This reduces parse time and file size. The minified JavaScript is cached for 24 hours.', 'ttt-wp-boost' ); ?></p>
        <p><?php esc_html_e( 'Excluded scripts (jQuery, core WordPress scripts) are never minified or deferred to avoid breaking functionality.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'JavaScript Defer', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'Deferring non-essential JavaScript allows the browser to focus on rendering the page first, then load JavaScript in the background. This significantly improves Time to Interactive (TTI) without breaking core functionality.', 'ttt-wp-boost' ); ?></p>
        <p><?php esc_html_e( 'Core scripts like jQuery are never deferred because other scripts may depend on them.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'Cache Duration', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'All minified assets are cached for 24 hours (1 day). This provides a balance between performance and ensuring changes to your theme or plugins are reflected.', 'ttt-wp-boost' ); ?></p>
        <p><?php esc_html_e( 'You can manually clear the cache using the "Clear Asset Cache" button above to force regeneration.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'Compatibility Notes', 'ttt-wp-boost' ); ?></h3>
        <ul style="margin-top:8px;">
            <li><?php esc_html_e( '<strong>Elementor</strong> — Asset minification works with Elementor but minified CSS may not be cached due to Elementor\'s dynamic CSS system. This is a limitation of Elementor\'s architecture.', 'ttt-wp-boost' ); ?></li>
            <li><?php esc_html_e( '<strong>Page Builders</strong> (Divi, Bricks, etc.) — Most page builders have their own asset optimization systems. TTT WP Boost\'s asset minification is a lightweight alternative that does not conflict.', 'ttt-wp-boost' ); ?></li>
            <li><?php esc_html_e( '<strong>WooCommerce</strong> — Asset minification works with WooCommerce, but minified styles/scripts may not be loaded correctly if they depend on inline styles or dynamic content.', 'ttt-wp-boost' ); ?></li>
        </ul>
    </div>
</div>

<!-- status badges rendered by PHP -->

<style>
/* Toggle 开关样式 */
.ttt-boost-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 22px;
    vertical-align: middle;
}
.ttt-boost-toggle input { opacity: 0; width: 0; height: 0; }
.ttt-boost-toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #c3c4c7;
    border-radius: 22px;
    transition: 0.2s;
}
.ttt-boost-toggle-slider::before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.2s;
}
.ttt-boost-toggle input:checked + .ttt-boost-toggle-slider { background-color: #2271b1; }
.ttt-boost-toggle input:checked + .ttt-boost-toggle-slider::before { transform: translateX(22px); }
.ttt-boost-toggle input:disabled + .ttt-boost-toggle-slider { opacity: 0.5; cursor: not-allowed; }

.ttt-boost-status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #fff;
    background: #d63638;
}
</style>

<script>
/* ── Debug 模式：实时状态指示器 ── */
function updateAssetIndicators() {
    var minCss   = document.querySelector('input[name="minify_css"]').checked;
    var critCss  = document.querySelector('input[name="inline_critical_css"]').checked;
    var minJs    = document.querySelector('input[name="minify_js"]').checked;
    var deferJs  = document.querySelector('input[name="defer_js"]').checked;

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

    setIndicator(document.getElementById('ind-minify-css'), minCss);
    setIndicator(document.getElementById('ind-inline-critical-css'), critCss);
    setIndicator(document.getElementById('ind-minify-js'), minJs);
    setIndicator(document.getElementById('ind-defer-js'), deferJs);
}

document.addEventListener('DOMContentLoaded', updateAssetIndicators);
</script>
</div>

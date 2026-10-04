<?php
/**
 * TTT WP Boost — Dashboard Accelerator Settings Page
 * 优化 WordPress 原生后台：无插件环境下 WordPress Dashboard 的访问速度
 *
 * @package TTT_WP_Boost
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── 引入 Dashboard Accelerator 引擎 ── */
require_once TTT_WP_BOOST_PATH . 'includes/class-dashboard.php';

/* ── 共享 Tab 导航 ── */
require_once TTT_WP_BOOST_PATH . 'admin/parts/tab-nav.php';

/* ── 处理表单提交 ── */
if ( isset( $_POST['ttt_wb_dashboard_save'] ) && check_admin_referer( 'ttt_wb_dashboard_save', 'nonce' ) ) {
    $settings['disable_gutenberg']           = isset( $_POST['disable_gutenberg'] ) ? 'yes' : 'no';
    $settings['disable_gutenberg_widgets']   = isset( $_POST['disable_gutenberg_widgets'] ) ? 'yes' : 'no';
    $settings['disable_block_directory']      = isset( $_POST['disable_block_directory'] ) ? 'yes' : 'no';
    $settings['disable_block_widgets']        = isset( $_POST['disable_block_widgets'] ) ? 'yes' : 'no';
    $settings['heartbeat_frequency']         = isset( $_POST['heartbeat_frequency'] ) ? absint( $_POST['heartbeat_frequency'] ) : 60;
    $settings['disable_heartbeat']            = isset( $_POST['disable_heartbeat'] ) ? 'yes' : 'no';
    $settings['disable_emoji']               = isset( $_POST['disable_emoji'] ) ? 'yes' : 'no';
    $settings['disable_admin_color_schemes'] = isset( $_POST['disable_admin_color_schemes'] ) ? 'yes' : 'no';
    $settings['disable_link_manager']        = isset( $_POST['disable_link_manager'] ) ? 'yes' : 'no';
    $settings['revisions_max']               = isset( $_POST['revisions_max'] ) ? absint( $_POST['revisions_max'] ) : 3;
    $settings['autosave_interval']           = isset( $_POST['autosave_interval'] ) ? absint( $_POST['autosave_interval'] ) : 120;
    $settings['dashboard_debug']             = isset( $_POST['dashboard_debug'] ) ? 'yes' : 'no';
    update_option( 'ttt_wp_boost_settings', $settings );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Dashboard Accelerator settings saved.', 'ttt-wp-boost' ) . '</p></div>';
}

$settings = get_option( 'ttt_wp_boost_settings', [] );

/* ── 获取 Dashboard Accelerator 引擎的运行时状态 ── */
$dash = new TTT_WP_Boost_Dashboard_Accelerator( $settings );
$opt_status = $dash->get_optimization_status();

/* ── 获取当前 Gutenberg 状态 ── */
$is_gutenberg_active = function_exists( 'gutenberg_init' ) || (
    version_compare( get_bloginfo( 'version' ), '5.0', '>=' )
    && ! has_filter( 'replace_editor', 'gutenberg_init' )
);

/* ── 获取当前 Heartbeat 状态 ── */
$heartbeat_disabled = defined( 'DISABLE_HEARTBEAT' ) && DISABLE_HEARTBEAT;

/* ── 计数活跃优化项 ── */
$active_count = count( array_filter( [
    $opt_status['disable_gutenberg'],
    $opt_status['disable_gutenberg_widgets'],
    $opt_status['disable_block_directory'],
    $opt_status['disable_block_widgets'],
    $opt_status['disable_heartbeat'],
    $opt_status['disable_emoji'],
    $opt_status['disable_admin_color_schemes'],
    $opt_status['disable_link_manager'],
] ) );

/* ── 获取当前生效的 revisions 限制 ── */
$current_revisions = defined( 'WP_POST_REVISIONS' ) ? WP_POST_REVISIONS : ( $opt_status['revisions_max'] ?? 3 );
$current_autosave  = defined( 'AUTOSAVE_INTERVAL' ) ? AUTOSAVE_INTERVAL : ( $opt_status['autosave_interval'] ?? 120 );
?>

<!-- ═══════════════════════════════════════════════════════
     状态概览卡片
    ═══════════════════════════════════════════════════════════ -->
<div class="ttt-boost-card">
        <h2><?php esc_html_e( '当前状态', 'ttt-wp-boost' ); ?></h2>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:8px;">
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo $is_gutenberg_active ? '#d63638' : '#00a32a'; ?>">
                    <?php echo $is_gutenberg_active ? esc_html__( '运行中', 'ttt-wp-boost' ) : esc_html__( '经典', 'ttt-wp-boost' ); ?>
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( '编辑器', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number" style="color:<?php echo $heartbeat_disabled ? '#00a32a' : '#646970'; ?>">
                    <?php echo $settings['heartbeat_frequency'] ?? 60; ?>s
                </div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( '心跳间隔', 'ttt-wp-boost' ); ?></div>
            </div>
            <div class="ttt-boost-stat">
                <div class="ttt-boost-stat-number"><?php echo $settings['revisions_max'] ?? 3; ?></div>
                <div class="ttt-boost-stat-label"><?php esc_html_e( '最大修订版数', 'ttt-wp-boost' ); ?></div>
            </div>
        </div>
        <?php if ( $is_gutenberg_active ) : ?>
        <div style="margin-top:14px;padding:12px 16px;background:#fff8e5;border-left:4px solid #d4a017;border-radius:3px;font-size:13px;">
            <strong><?php esc_html_e( 'Block Editor Detected', 'ttt-wp-boost' ); ?></strong><br>
            <?php esc_html_e( 'Gutenberg loads ~2.5MB of JavaScript and makes your admin panel significantly slower. The options below can dramatically improve performance on servers with limited RAM (1GB–2GB).', 'ttt-wp-boost' ); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         设置表单
    ═══════════════════════════════════════════════════════════ -->
    <form method="post" action="" class="ttt-boost-card">
        <h2><?php esc_html_e( '性能优化项', 'ttt-wp-boost' ); ?></h2>
        <?php wp_nonce_field( 'ttt_wb_dashboard_save', 'nonce' ); ?>

        <table class="form-table" style="table-layout:fixed;">
            <colgroup>
                <col style="width:280px;">
                <col style="width:180px;">
                <col>
            </colgroup>

            <!-- ═══ Gutenberg 编辑器 ═══ -->
            <tr>
                <td colspan="3" style="background:#f0f0f1;padding:8px 12px;font-weight:700;border-top:3px solid #2271b1;">
                    <?php esc_html_e( '古腾堡编辑器', 'ttt-wp-boost' ); ?>
                    <span style="float:right;font-weight:400;font-size:12px;color:#646970;">
                        <?php esc_html_e( '性能影响最大', 'ttt-wp-boost' ); ?>
                    </span>
                </td>
            </tr>

            <tr id="row-disable-gutenberg">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '切换至经典编辑器', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_gutenberg"
                            value="yes" id="chk-disable-gutenberg"
                            <?php checked( 'yes', $settings['disable_gutenberg'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php
                    $g_active = $opt_status['disable_gutenberg'];
                    $can_disable = ! $is_gutenberg_active || (
                        file_exists( WP_PLUGIN_DIR . '/classic-editor/classic-editor.php' )
                        || file_exists( WP_PLUGIN_DIR . '/classic-editor/classic-editor.php' )
                    );
                    $g_status_class = $g_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;';
                    $g_status_text  = $g_active
                        ? ( ! $is_gutenberg_active ? '✓ Active — Classic Editor running' : '⚠ Active — Gutenberg detected' )
                        : '✗ Inactive — Block editor may be running';
                    ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo esc_attr( $g_status_class ); ?>">
                        <?php echo esc_html( $g_status_text ); ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( '影响：非常高', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Completely disables the Gutenberg block editor and reverts to the Classic Editor. Classic Editor loads ~200KB of JS; Gutenberg loads ~2.5MB. On a 1GB RAM server, Gutenberg alone can cause admin pages to take 3–8 seconds to load.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Note: This requires the Classic Editor plugin to be installed and activated. WordPress 5.x–6.3 includes it by default. WordPress 6.4+ requires installing Classic Editor separately.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <tr id="row-disable-gutenberg-widgets">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '禁用古腾堡小工具', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_gutenberg_widgets"
                            value="yes" id="chk-disable-gutenberg-widgets"
                            <?php checked( 'yes', $settings['disable_gutenberg_widgets'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $g_w_active = $opt_status['disable_gutenberg_widgets']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $g_w_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $g_w_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Medium', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Removes Gutenberg\'s block-based widget screen from Appearance → Widgets. Falls back to the classic widgets panel. Only applies if Gutenberg is active.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'The block-based widgets screen loads the full block editor infrastructure (~1.2MB JS) even when you just want to drag a text widget. Disabling it frees ~30-50MB of RAM in admin.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <tr id="row-disable-block-directory">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '禁用区块目录', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_block_directory"
                            value="yes" id="chk-disable-block-directory"
                            <?php checked( 'yes', $settings['disable_block_directory'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $bd_active = $opt_status['disable_block_directory']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $bd_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $bd_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Low–Medium', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Removes the block directory panel from the editor sidebar (the panel that suggests installing new blocks from wordpress.org). It makes a live HTTP request to the WordPress.org blocks API on every keystroke in the block editor, adding 200-500ms latency to each keypress.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'If you do not install blocks from the directory, this is safe to enable. It does not affect existing blocks.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <tr id="row-disable-block-widgets">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '禁用区块小工具', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_block_widgets"
                            value="yes" id="chk-disable-block-widgets"
                            <?php checked( 'yes', $settings['disable_block_widgets'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $bw_active = $opt_status['disable_block_widgets']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $bw_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $bw_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Low', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Unregisters all block-based widgets (Calendar, Tag Cloud, RSS, etc.) from the widget system. These blocks load their assets on every admin page load even when not on the widgets screen.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Classic text widgets and any custom theme widgets remain functional. Only affects the block widget equivalents.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <!-- ═══ Heartbeat ═══ -->
            <tr>
                <td colspan="3" style="background:#f0f0f1;padding:8px 12px;font-weight:700;border-top:3px solid #2271b1;">
                    <?php esc_html_e( '心跳 API', 'ttt-wp-boost' ); ?>
                    <span style="float:right;font-weight:400;font-size:12px;color:#646970;">
                        <?php esc_html_e( '在文章编辑页高影响性能', 'ttt-wp-boost' ); ?>
                    </span>
                </td>
            </tr>

            <tr id="row-disable-heartbeat">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '完全禁用心跳', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_heartbeat"
                            value="yes" id="chk-disable-heartbeat"
                            <?php checked( 'yes', $settings['disable_heartbeat'] ?? 'no' ); ?>
                            <?php if ( $heartbeat_disabled ) { echo 'disabled'; } ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $hb_disabled = $opt_status['disable_heartbeat']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $hb_disabled ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $hb_disabled ? ✓ 已启用 — Heartbeat disabled' : ✗ 未启用 — Heartbeat running'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Very High (on post edit screens)', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Heartbeat is a JavaScript API that polls the server every 15-60 seconds to keep the connection alive, check for revisions, and detect conflicts. On low-end servers (1GB RAM, HDD), each heartbeat request can cause 1-3 seconds of server load and 100% CPU spikes.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Disabling it is safe if: you do not use the block editor\'s "someone else is editing" warning, you do not rely on revision autosave for crash recovery, and you save posts manually.', 'ttt-wp-boost' ); ?><br>
                    <?php if ( $heartbeat_disabled ) : ?>
                    <br><span style="color:#646970;font-weight:600;">ℹ <?php esc_html_e( 'Heartbeat is already disabled via DISABLE_HEARTBEAT constant in wp-config.php', 'ttt-wp-boost' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>

            <tr id="row-heartbeat-frequency">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '心跳频率', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <select name="heartbeat_frequency" id="sel-heartbeat-frequency" style="width:100px;"
                        <?php if ( $heartbeat_disabled || ( isset( $settings['disable_heartbeat'] ) && $settings['disable_heartbeat'] === 'yes' ) ) { echo 'disabled'; } ?>>
                        <option value="15"  <?php selected( $settings['heartbeat_frequency'] ?? 60, 15 ); ?>>15s</option>
                        <option value="30"  <?php selected( $settings['heartbeat_frequency'] ?? 60, 30 ); ?>>30s</option>
                        <option value="60"  <?php selected( $settings['heartbeat_frequency'] ?? 60, 60 ); ?>>60s</option>
                        <option value="120" <?php selected( $settings['heartbeat_frequency'] ?? 60, 120 ); ?>>120s</option>
                        <option value="300" <?php selected( $settings['heartbeat_frequency'] ?? 60, 300 ); ?>>300s</option>
                    </select>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php
                    $hb_freq = $opt_status['heartbeat_frequency'];
                    $hb_class = $hb_freq > 60 ? 'background:#00a32a;color:#fff;' : 'background:#646970;color:#fff;';
                    ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo esc_attr( $hb_class ); ?>">
                        Current: <?php echo esc_html( $hb_freq ); ?>s
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Medium–High', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Instead of disabling Heartbeat entirely, slow it down to reduce request frequency. Default is 15s in the post editor, 60s on the dashboard. Setting to 120s or 300s still gives you autosave/revision detection, but at a fraction of the server load.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Recommended: 120s on low-end servers, 60s on mid-range servers. If you experience "Post conflict" warnings frequently, keep it at 60s or lower.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <!-- ═══ 资源移除 ═══ -->
            <tr>
                <td colspan="3" style="background:#f0f0f1;padding:8px 12px;font-weight:700;border-top:3px solid #2271b1;">
                    <?php esc_html_e( '资源移除', 'ttt-wp-boost' ); ?>
                    <span style="float:right;font-weight:400;font-size:12px;color:#646970;">
                        <?php esc_html_e( '减少页面大小和 DOM 复杂度', 'ttt-wp-boost' ); ?>
                    </span>
                </td>
            </tr>

            <tr id="row-disable-emoji">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '移除表情符号脚本', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_emoji"
                            value="yes" id="chk-disable-emoji"
                            <?php checked( 'yes', $settings['disable_emoji'] ?? 'yes' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $em_active = $opt_status['disable_emoji']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $em_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $em_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Low', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'WordPress loads ~10KB of JavaScript (wp-emoji-release.min.js) to convert emoji characters (😀, 🎉) to Twitter Emoji sprites on older browsers. If your site uses SVG or web fonts for emoji (most modern setups), this script is completely unnecessary.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Removing it saves one HTTP request and ~10KB on every admin page. It does NOT remove emoji from your content — only the conversion script.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <tr id="row-disable-admin-color-schemes">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '移除后台配色方案', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_admin_color_schemes"
                            value="yes" id="chk-disable-admin-color-schemes"
                            <?php checked( 'yes', $settings['disable_admin_color_schemes'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $ac_active = $opt_status['disable_admin_color_schemes']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $ac_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $ac_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Low', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'WordPress ships with 8 admin color schemes (Fresh, Light, Blue, Coffee, Ectoplasm, Midnight, Ocean, Sunrise), each with its own CSS file (~4-8KB each). WordPress enqueues all color scheme CSS on every admin page to prevent flash of unstyled content.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'If you only use one color scheme, removing others saves ~30-50KB of CSS per admin page. Also removes the color scheme picker from the User Profile page.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <tr id="row-disable-link-manager">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '禁用链接管理器', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="disable_link_manager"
                            value="yes" id="chk-disable-link-manager"
                            <?php checked( 'yes', $settings['disable_link_manager'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $lm_active = $opt_status['disable_link_manager']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $lm_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        <?php echo $lm_active ? ✓ 已启用' : ✗ 未启用'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Very Low', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Link Manager was deprecated in WordPress 3.5 but the menu item, database tables, and associated scripts remain in the codebase. Most sites never use it. Removing it hides the Links menu item and prevents the links table from loading in admin.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Safe to enable unless you actively use a links/ blogroll feature on your site.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <!-- ═══ 数据库优化 ═══ -->
            <tr>
                <td colspan="3" style="background:#f0f0f1;padding:8px 12px;font-weight:700;border-top:3px solid #2271b1;">
                    <?php esc_html_e( '数据库优化', 'ttt-wp-boost' ); ?>
                    <span style="float:right;font-weight:400;font-size:12px;color:#646970;">
                        <?php esc_html_e( '随时间减少数据库膨胀', 'ttt-wp-boost' ); ?>
                    </span>
                </td>
            </tr>

            <tr id="row-revisions-max">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '限制文章修订版本', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <input type="number" name="revisions_max"
                        id="num-revisions-max"
                        value="<?php echo esc_attr( $settings['revisions_max'] ?? 3 ); ?>"
                        min="0" max="100" class="small-text">
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $rev_active = $current_revisions !== -1 && $current_revisions <= 5; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $rev_active ? 'background:#00a32a;color:#fff;' : 'background:#d63638;color:#fff;'; ?>">
                        Limit: <?php echo $current_revisions === -1 ? 'unlimited' : $current_revisions; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Long-term database size', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'WordPress saves a full copy of your post content on every autosave and manual save. With the default unlimited revisions, a heavily edited post can accumulate 20-50 revisions, each 20-100KB of content. On a site with 200 posts, this can mean 500MB+ of revision data in the database.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Recommended: 3-5 revisions. Set to 0 to disable revisions entirely (not recommended if you rely on revision history).', 'ttt-wp-boost' ); ?><br>
                    <?php
                    $rev_count = $GLOBALS['wpdb']->get_var(
                        "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type = 'revision'"
                    );
                    if ( $rev_count > 0 ) {
                        printf(
                            /* translators: %d = number of revision posts in database */
                            esc_html__( 'Current revision count in database: %d', 'ttt-wp-boost' ),
                            intval( $rev_count )
                        );
                    }
                    ?>
                </td>
            </tr>

            <tr id="row-autosave-interval">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '自动保存间隔（秒）', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <select name="autosave_interval" id="sel-autosave-interval" style="width:100px;">
                        <option value="30"  <?php selected( $settings['autosave_interval'] ?? 120, 30 ); ?>>30s</option>
                        <option value="60"  <?php selected( $settings['autosave_interval'] ?? 120, 60 ); ?>>60s</option>
                        <option value="120" <?php selected( $settings['autosave_interval'] ?? 120, 120 ); ?>>120s</option>
                        <option value="300" <?php selected( $settings['autosave_interval'] ?? 120, 300 ); ?>>300s</option>
                    </select>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $as_class = $current_autosave >= 120 ? 'background:#00a32a;color:#fff;' : 'background:#646970;color:#fff;'; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo esc_attr( $as_class ); ?>">
                        Current: <?php echo esc_html( $current_autosave ); ?>s
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: Autosave database writes', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'Controls how often WordPress automatically saves your post while you are editing. Default is 60 seconds. Reducing autosave frequency (e.g. to 120s or 300s) reduces database writes and is especially helpful on shared hosting with limited MySQL connections.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Note: This only affects the post editor. The Heartbeat frequency setting above controls the technical mechanism of autosave.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

            <!-- ═══ Debug ═══ -->
            <tr>
                <td colspan="3" style="background:#f0f0f1;padding:8px 12px;font-weight:700;border-top:3px solid #2271b1;">
                    <?php esc_html_e( '调试模式', 'ttt-wp-boost' ); ?>
                    <span style="float:right;font-weight:400;font-size:12px;color:#646970;">
                        <?php esc_html_e( '故障排除与验证', 'ttt-wp-boost' ); ?>
                    </span>
                </td>
            </tr>

            <tr id="row-dashboard-debug">
                <th scope="row" style="padding-left:12px;">
                    <?php esc_html_e( '显示状态徽章', 'ttt-wp-boost' ); ?>
                </th>
                <td>
                    <label class="ttt-boost-toggle">
                        <input type="checkbox" name="dashboard_debug"
                            value="yes" id="chk-dashboard-debug"
                            <?php checked( 'yes', $settings['dashboard_debug'] ?? 'no' ); ?>>
                        <span class="ttt-boost-toggle-slider"></span>
                    </label>
                </td>
                <td style="font-size:13px;line-height:1.6;">
                    <?php $dbg_active = $opt_status['dashboard_debug']; ?>
                    <span class="ttt-boost-status-badge" style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;<?php echo $dbg_active ? 'background:#2271b1;color:#fff;' : 'background:#646970;color:#fff;'; ?>">
                        <?php echo $dbg_active ? 'ENABLED — showing all badges' : 'DISABLED'; ?>
                    </span>
                    <br><br>
                    <strong><?php esc_html_e( 'Impact: None (UI only)', 'ttt-wp-boost' ); ?></strong><br>
                    <?php esc_html_e( 'When enabled, every option above displays a real-time status badge (✓ ACTIVE / ✗ INACTIVE) so you can instantly verify which optimizations are actually running. The status reflects your current settings and WordPress environment.', 'ttt-wp-boost' ); ?><br>
                    <br>
                    <?php esc_html_e( 'Enable this when you first configure the plugin, then disable it once you have verified everything works as expected.', 'ttt-wp-boost' ); ?>
                </td>
            </tr>

        </table>

        <p class="submit">
            <button type="submit" name="ttt_wb_dashboard_save" class="button button-primary">
                <?php esc_html_e( '保存设置', 'ttt-wp-boost' ); ?>
            </button>
        </p>
    </form>

    <!-- ═══════════════════════════════════════════════════════
         使用说明
    ═══════════════════════════════════════════════════════════ -->
    <div class="ttt-boost-card" style="border-left:4px solid #2271b1;">
        <h2 style="margin-top:0;"><?php esc_html_e( '工作原理', 'ttt-wp-boost' ); ?></h2>

        <h3><?php esc_html_e( 'Why Gutenberg is Slow', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'The Gutenberg block editor is a React-based single-page application (SPA) embedded in WordPress. Even for a simple text post, it loads:', 'ttt-wp-boost' ); ?></p>
        <ul style="margin-top:8px;">
            <li>~2.5MB of JavaScript (React, Backbone, block registry, editor components)</li>
            <li>~500KB of CSS (editor styles, theme styles, block styles)</li>
            <li>Block assets on demand (each block type has its own JS + CSS)</li>
        </ul>
        <p><?php esc_html_e( 'On a server with 1GB RAM and no opcode cache, parsing and executing 2.5MB of JS takes 2-5 seconds. Classic Editor does the same job with ~200KB.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'Heartbeat on Low-End Servers', 'ttt-wp-boost' ); ?></h3>
        <p><?php esc_html_e( 'Heartbeat runs via admin-ajax.php. On shared hosting with 1-2GB RAM, each request can trigger MySQL queries, PHP execution, and object cache lookups. With 60-second intervals and multiple open browser tabs, this can create a constant background load of 5-15 requests/minute, consuming ~30% of available server resources.', 'ttt-wp-boost' ); ?></p>

        <h3><?php esc_html_e( 'Recommended Presets', 'ttt-wp-boost' ); ?></h3>
        <p><strong>1GB RAM, shared hosting:</strong> Enable Classic Editor + Heartbeat 120s + Emoji + Admin Color Schemes + Revisions 3</p>
        <p><strong>2GB RAM, VPS:</strong> Disable Block Directory + Heartbeat 60s + Emoji + Revisions 5</p>
        <p><strong>4GB+ RAM, dedicated:</strong> No changes needed unless you specifically want to reduce resource usage</p>

        <h3><?php esc_html_e( 'Compatibility Notes', 'ttt-wp-boost' ); ?></h3>
        <ul style="margin-top:8px;">
            <li><strong>Classic Editor plugin</strong> — Required for "Switch to Classic Editor" to work. Bundled in WordPress 5.x–6.3. Install separately for 6.4+.</li>
            <li><strong>Page Builder plugins</strong> (Elementor, Divi) — These bypass Gutenberg entirely. The editor optimization options will have no effect on pages built with page builders.</li>
            <li><strong>WooCommerce</strong> — Product editor uses its own variation of Gutenberg. Disabling Gutenberg does not affect the WooCommerce product page.</li>
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

</div>

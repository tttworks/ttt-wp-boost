<?php
/**
 * Plugin Name: TTT WP Boost
 * Plugin URI:  https://tttworks.com
 * Description: Lightweight page cache and asset optimization for Elementor-powered WordPress sites.
 *              Boosts PageSpeed by 20-30 points with minimal configuration.
 * Version:     1.2.0
 * Author:      Aloysius Luo
 * Author URI:  https://tttworks.com
 * License:     Apache-2.0
 * Text Domain: ttt-wp-boost
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 *
 * @package TTT_WP_Boost
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TTT_WP_BOOST_VERSION', '1.2.0' );
define( 'TTT_WP_BOOST_PATH', plugin_dir_path( __FILE__ ) );
define( 'TTT_WP_BOOST_URL', plugin_dir_url( __FILE__ ) );
define( 'TTT_WP_BOOST_CACHE_DIR', WP_CONTENT_DIR . '/cache/ttt-wp-boost/' );

/* ═══════════════════════
 * 自动加载
 * ═══════════════════════ */
require_once TTT_WP_BOOST_PATH . 'includes/class-core.php';

function ttt_wp_boost_init() {
    TTT_WP_Boost_Core::instance();
}
add_action( 'plugins_loaded', 'ttt_wp_boost_init' );

/* ═══════════════════════
 * 激活 / 停用
 * ═══════════════════════ */
register_activation_hook( __FILE__, 'ttt_wp_boost_activate' );
function ttt_wp_boost_activate() {
    if ( ! file_exists( TTT_WP_BOOST_CACHE_DIR ) ) {
        wp_mkdir_p( TTT_WP_BOOST_CACHE_DIR );
        file_put_contents(
            TTT_WP_BOOST_CACHE_DIR . '.htaccess',
            "Order deny,allow\nDeny from all\n"
        );
        file_put_contents(
            TTT_WP_BOOST_CACHE_DIR . 'index.php',
            "<?php\n// Silence is golden — protect cache files from direct access.\nif ( ! defined( 'ABSPATH' ) ) {\n    exit;\n}\n"
        );
    }
    /* 设置默认选项 */
    if ( false === get_option( 'ttt_wp_boost_settings' ) ) {
        update_option( 'ttt_wp_boost_settings', [
            'enable_cache'           => 'yes',
            'cache_timeout'          => 86400,
            'exclude_urls'           => [],
            'exclude_roles'          => [ 'administrator', 'editor' ],
            'enable_object_cache'    => 'no',
            'object_cache_ttl'       => 3600,
            'cache_elementor_css'    => 'no',
            'cache_wp_queries'       => 'no',
            'disable_emoji'          => 'yes',
            'heartbeat_frequency'    => 60,
        ] );
    }
}

register_deactivation_hook( __FILE__, 'ttt_wp_boost_deactivate' );
function ttt_wp_boost_deactivate() {
    /* 清空缓存但保留目录和选项 */
    ttt_wp_boost_clear_all_cache();
}

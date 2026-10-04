<?php
/**
 * TTT WP Boost — Dashboard Accelerator Engine
 * WordPress 原生后台性能优化：无插件环境下的 Dashboard 加速
 *
 * @package TTT_WP_Boost
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TTT_WP_Boost_Dashboard_Accelerator {

    private $settings;
    private $debug_log = [];

    public function __construct( $settings ) {
        $this->settings = $settings;

        /* ── Gutenberg / Block Editor ── */
        if ( ! empty( $settings['disable_gutenberg'] ) && $settings['disable_gutenberg'] === 'yes' ) {
            add_filter( 'use_block_editor_for_post', [ $this, 'disable_block_editor' ], 100 );
            add_filter( 'gutenberg_can_edit_post_type', '__return_false', 100 );
            $this->log( 'disable_gutenberg', 'Block editor disabled via use_block_editor_for_post filter' );
        }

        if ( ! empty( $settings['disable_gutenberg_widgets'] ) && $settings['disable_gutenberg_widgets'] === 'yes' ) {
            add_action( 'after_setup_theme', [ $this, 'disable_gutenberg_widgets' ] );
            $this->log( 'disable_gutenberg_widgets', 'Gutenberg widget screen disabled' );
        }

        if ( ! empty( $settings['disable_block_directory'] ) && $settings['disable_block_directory'] === 'yes' ) {
            add_action( 'wp_loaded', [ $this, 'disable_block_directory' ] );
            $this->log( 'disable_block_directory', 'Block directory API requests blocked' );
        }

        if ( ! empty( $settings['disable_block_widgets'] ) && $settings['disable_block_widgets'] === 'yes' ) {
            add_action( 'widgets_init', [ $this, 'disable_block_widgets' ], 100 );
            $this->log( 'disable_block_widgets', 'Block widgets unregistered' );
        }

        /* ── Heartbeat ── */
        if ( ! empty( $settings['disable_heartbeat'] ) && $settings['disable_heartbeat'] === 'yes' ) {
            add_action( 'init', [ $this, 'disable_heartbeat' ], 5 );
            $this->log( 'disable_heartbeat', 'Heartbeat API disabled globally' );
        } elseif ( ! empty( $settings['heartbeat_frequency'] ) ) {
            $freq = intval( $settings['heartbeat_frequency'] );
            add_filter( 'heartbeat_settings', function( $settings ) use ( $freq ) {
                $settings['interval'] = $freq;
                return $settings;
            } );
            add_action( 'admin_enqueue_scripts', [ $this, 'modify_heartbeat_interval' ] );
            $this->log( 'heartbeat_frequency', "Heartbeat interval set to {$freq}s" );
        }

        /* ── Emoji ── */
        if ( ! empty( $settings['disable_emoji'] ) && $settings['disable_emoji'] === 'yes' ) {
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'admin_print_styles', 'print_emoji_styles' );
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            add_filter( 'emoji_svg_url', '__return_false' );
            add_filter( 'tinyce_css', '__return_empty_string' );
            $this->log( 'disable_emoji', 'Emoji scripts removed from admin and frontend' );
        }

        /* ── Admin Color Schemes ── */
        if ( ! empty( $settings['disable_admin_color_schemes'] ) && $settings['disable_admin_color_schemes'] === 'yes' ) {
            add_filter( 'get_user_option_admin_color', [ $this, 'force_admin_color' ], 100 );
            add_action( 'admin_head', [ $this, 'remove_color_scheme_picker' ] );
            add_action( 'admin_print_scripts-profile.php', [ $this, 'remove_color_scheme_picker' ] );
            $this->log( 'disable_admin_color_schemes', 'Non-default admin color schemes removed' );
        }

        /* ── Link Manager ── */
        if ( ! empty( $settings['disable_link_manager'] ) && $settings['disable_link_manager'] === 'yes' ) {
            add_filter( 'pre_option_link_manager_enabled', '__return_false' );
            $this->log( 'disable_link_manager', 'Link Manager hidden' );
        }

        /* ── Autosave / Revisions (通过常量) ── */
        if ( ! empty( $settings['revisions_max'] ) ) {
            $max = intval( $settings['revisions_max'] );
            if ( ! defined( 'WP_POST_REVISIONS' ) ) {
                define( 'WP_POST_REVISIONS', $max );
            }
            $this->log( 'revisions_max', "Post revisions limited to {$max}" );
        }

        if ( ! empty( $settings['autosave_interval'] ) ) {
            $interval = intval( $settings['autosave_interval'] );
            if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
                define( 'AUTOSAVE_INTERVAL', $interval );
            }
            $this->log( 'autosave_interval', "Autosave interval set to {$interval}s" );
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Gutenberg / Block Editor
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 强制所有 post type 使用经典编辑器
     */
    public function disable_block_editor( $use_block_editor ) {
        return false;
    }

    /**
     * 禁用 Gutenberg 的 block-based widgets 屏幕
     */
    public function disable_gutenberg_widgets() {
        remove_theme_support( 'widgets-block-editor' );
    }

    /**
     * 禁用 Block Directory（编辑器内搜索安装新 block 的功能）
     * 通过移除对应的 REST API 路由实现
     */
    public function disable_block_directory() {
        remove_action( 'enqueue_block_editor_assets', 'enqueue_block_directory_assets' );
        remove_filter( 'block_editor_rest_api_preload_paths', 'block_directory_preload_paths', 10 );
    }

    /**
     * 注销所有 WordPress 内置的 block widgets
     */
    public function disable_block_widgets() {
        $blocks_to_remove = [
            'core/calendar',
            'core/tag-cloud',
            'core/rss',
            'core/search',
            'core/latest-posts',
            'core/latest-comments',
            'core/archives',
            'core/categories',
            'core/html',
            'core/code',
            'core/preformatted',
            'core/verse',
            'core/quote',
            'core/pullquote',
            'core/image',
            'core/gallery',
            'core/video',
            'core/audio',
            'core/cover',
            'core/file',
            'core/media-text',
        ];
        foreach ( $blocks_to_remove as $block ) {
            unregister_block_type( $block );
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Heartbeat
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 禁用 Heartbeat
     */
    public function disable_heartbeat() {
        wp_deregister_script( 'heartbeat' );
    }

    /**
     * 修改 Heartbeat JavaScript 的发送间隔
     * @param string $hook
     */
    public function modify_heartbeat_interval( $hook ) {
        $freq = isset( $this->settings['heartbeat_frequency'] )
            ? intval( $this->settings['heartbeat_frequency'] )
            : 60;
        wp_localize_script(
            'heartbeat',
            'heartbeatSettings',
            [ 'interval' => $freq ]
        );
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Admin Color Schemes
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 强制使用默认 admin color scheme
     */
    public function force_admin_color( $color ) {
        return 'fresh';
    }

    /**
     * 从用户资料页移除 color scheme picker
     */
    public function remove_color_scheme_picker() {
        echo '<style>.color-palette { display: none !important; } </style>';
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Debug
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 记录调试日志
     */
    private function log( $key, $message ) {
        $debug = isset( $this->settings['dashboard_debug'] )
            && $this->settings['dashboard_debug'] === 'yes';

        if ( $debug ) {
            $this->debug_log[ $key ] = $message;
        }
    }

    /**
     * 获取调试日志（供设置页显示）
     */
    public function get_debug_log() {
        return $this->debug_log;
    }

    /**
     * 获取当前优化状态（供设置页右侧状态指示器使用）
     */
    public function get_optimization_status() {
        $s = $this->settings;
        return [
            'disable_gutenberg'           => ! empty( $s['disable_gutenberg'] ) && $s['disable_gutenberg'] === 'yes',
            'disable_gutenberg_widgets'   => ! empty( $s['disable_gutenberg_widgets'] ) && $s['disable_gutenberg_widgets'] === 'yes',
            'disable_block_directory'      => ! empty( $s['disable_block_directory'] ) && $s['disable_block_directory'] === 'yes',
            'disable_block_widgets'        => ! empty( $s['disable_block_widgets'] ) && $s['disable_block_widgets'] === 'yes',
            'disable_heartbeat'            => ! empty( $s['disable_heartbeat'] ) && $s['disable_heartbeat'] === 'yes',
            'heartbeat_frequency'         => isset( $s['heartbeat_frequency'] ) ? intval( $s['heartbeat_frequency'] ) : 60,
            'disable_emoji'               => ! empty( $s['disable_emoji'] ) && $s['disable_emoji'] === 'yes',
            'disable_admin_color_schemes' => ! empty( $s['disable_admin_color_schemes'] ) && $s['disable_admin_color_schemes'] === 'yes',
            'disable_link_manager'         => ! empty( $s['disable_link_manager'] ) && $s['disable_link_manager'] === 'yes',
            'revisions_max'              => isset( $s['revisions_max'] ) ? intval( $s['revisions_max'] ) : 3,
            'autosave_interval'          => isset( $s['autosave_interval'] ) ? intval( $s['autosave_interval'] ) : 120,
            'dashboard_debug'            => ! empty( $s['dashboard_debug'] ) && $s['dashboard_debug'] === 'yes',
        ];
    }
}

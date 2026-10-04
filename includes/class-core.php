<?php
/**
 * TTT WP Boost — Core
 *
 * @package TTT_WP_Boost
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TTT_WP_Boost_Core {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option( 'ttt_wp_boost_settings', [] );

        /* ── 引入引擎 ── */
        require_once TTT_WP_BOOST_PATH . 'includes/class-page-cache.php';
        require_once TTT_WP_BOOST_PATH . 'includes/class-dashboard.php';
        require_once TTT_WP_BOOST_PATH . 'includes/class-object-cache.php';
        require_once TTT_WP_BOOST_PATH . 'includes/class-asset-minification.php';

        /* ── 对象缓存 ── */
        $enable_object_cache = isset( $settings['enable_object_cache'] ) && $settings['enable_object_cache'] === 'yes';
        if ( $enable_object_cache ) {
            $obj_cache = new TTT_WP_Boost_Object_Cache( $settings );

            /* ── WordPress 查询缓存钩子 ── */
            if ( ! empty( $settings['cache_wp_queries'] ) && $settings['cache_wp_queries'] === 'yes' ) {
                add_filter( 'posts_pre_query', function( $posts, $query ) use ( $obj_cache ) {
                    $cache_key = 'query_' . md5( serialize( $query ) );
                    $cached = $obj_cache->get( $cache_key );
                    if ( $cached !== false ) {
                        return $cached;
                    }
                    return $posts;
                }, 10, 2 );

                add_filter( 'posts_results', function( $posts, $query ) use ( $obj_cache ) {
                    if ( empty( $posts ) ) {
                        return $posts;
                    }
                    $cache_key = 'query_' . md5( serialize( $query ) );
                    $obj_cache->set( $cache_key, $posts );
                    return $posts;
                }, 10, 2 );
            }
        }

        /* ── 页面缓存 ── */
        $enable_cache = isset( $settings['enable_cache'] ) && $settings['enable_cache'] === 'yes';
        $cache = new TTT_WP_Boost_Page_Cache( $settings );

        if ( $enable_cache ) {
            add_action( 'parse_request', [ $cache, 'maybe_serve_cache' ], -9999 );
            add_action( 'wp_footer', [ $cache, 'capture_and_store' ], 99999 );
            ob_start();
        }

        /* ── 自动清理 ── */
        add_action( 'transition_post_status', [ $cache, 'on_post_status_change' ], 10, 3 );
        add_action( 'deleted_post',           [ $cache, 'clear_cache_by_post' ], 10, 2 );

        /* ── Elementor CSS 缓存钩子（当 Object Cache 和 Elementor CSS 缓存选项都启用时） ── */
        if ( $enable_object_cache && ! empty( $settings['cache_elementor_css'] ) && $settings['cache_elementor_css'] === 'yes' ) {
            add_action( 'elementor/css_file/post/update', function( $css_file_path, $post_id ) use ( $obj_cache ) {
                $css_content = file_get_contents( $css_file_path );
                if ( $css_content ) {
                    $obj_cache->cache_elementor_css( $post_id, $css_content );
                }
            }, 10, 2 );

            add_action( 'wp_enqueue_scripts', function() use ( $obj_cache ) {
                if ( is_singular() && class_exists( 'Elementor\Plugin' ) && Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                    return;
                }
                $post_id = get_queried_object_id();
                if ( $post_id && $cached_css = $obj_cache->get_elementor_css( $post_id ) ) {
                    wp_add_inline_style( 'elementor-frontend-css', $cached_css );
                }
            }, 100 );
        }

        /* ── Dashboard Accelerator（后台优化，始终初始化，不依赖开关） ── */
        if ( is_admin() || is_network_admin() ) {
            new TTT_WP_Boost_Dashboard_Accelerator( $settings );
        }

        /* ── 后台管理 ── */
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 9 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_ttt_wp_boost_clear_cache', [ $this, 'ajax_clear_cache' ] );
        add_action( 'wp_ajax_ttt_wp_boost_get_stats',   [ $this, 'ajax_get_stats' ] );
    }

    /* ═══════ 后台菜单 ═══════ */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Performance', 'ttt-wp-boost' ),
            __( 'Performance', 'ttt-wp-boost' ),
            'manage_options',
            'ttt-wp-boost',
            [ $this, 'render_settings_page' ],
            'dashicons-performance',
            66
        );
    }

    public function add_admin_submenus() {
        /* 移除第一个重复的子菜单项 */
        add_submenu_page(
            'ttt-wp-boost',
            __( 'Performance', 'ttt-wp-boost' ),
            __( 'Performance', 'ttt-wp-boost' ),
            'manage_options',
            'ttt-wp-boost',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'ttt-wp-boost',
            __( 'Admin Optimizer', 'ttt-wp-boost' ),
            __( 'Admin Optimizer', 'ttt-wp-boost' ),
            'manage_options',
            'ttt-wp-boost-dashboard',
            [ $this, 'render_dashboard_page' ]
        );
        add_submenu_page(
            'ttt-wp-boost',
            __( 'Object Cache', 'ttt-wp-boost' ),
            __( 'Object Cache', 'ttt-wp-boost' ),
            'manage_options',
            'ttt-wp-boost-object-cache',
            [ $this, 'render_object_cache_page' ]
        );
        add_submenu_page(
            'ttt-wp-boost',
            __( 'Asset Minification', 'ttt-wp-boost' ),
            __( 'Asset Minification', 'ttt-wp-boost' ),
            'manage_options',
            'ttt-wp-boost-asset-minification',
            [ $this, 'render_asset_minification_page' ]
        );
    }

    public function render_settings_page() {
        require_once TTT_WP_BOOST_PATH . 'admin/settings-page.php';
    }

    public function render_dashboard_page() {
        require_once TTT_WP_BOOST_PATH . 'admin/settings-page-dashboard.php';
    }

    public function render_object_cache_page() {
        require_once TTT_WP_BOOST_PATH . 'admin/settings-page-object-cache.php';
    }

    public function render_asset_minification_page() {
        require_once TTT_WP_BOOST_PATH . 'admin/settings-page-asset-minification.php';
    }

    /* ═══════ 网络菜单 ═══════ */
    public function add_network_menu() {
        add_menu_page(
            __( 'TTT WP Boost', 'ttt-wp-boost' ),
            __( 'TTT WP Boost', 'ttt-wp-boost' ),
            'manage_network_options',
            'ttt-wp-boost-network',
            [ $this, 'render_network_settings_page' ],
            'dashicons-performance',
            66
        );
        add_submenu_page(
            'ttt-wp-boost-network',
            __( 'Network Settings', 'ttt-wp-boost' ),
            __( 'Network Settings', 'ttt-wp-boost' ),
            'manage_network_options',
            'ttt-wp-boost-network',
            [ $this, 'render_network_settings_page' ]
        );
    }

    public function render_network_settings_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'TTT WP Boost Network Settings', 'ttt-wp-boost' ) . '</h1>';
        echo '<p>' . esc_html__( 'Network-wide configuration is not implemented yet. Configure per-site instead.', 'ttt-wp-boost' ) . '</p></div>';
    }

    public function enqueue_admin_assets( $hook ) {
        $our_pages = [
            'settings_page_ttt-wp-boost',
            'ttt-wp-boost_page_ttt-wp-boost-dashboard',
            'ttt-wp-boost_page_ttt-wp-boost-object-cache',
            'ttt-wp-boost_page_ttt-wp-boost-asset-minification',
            'toplevel_page_ttt-wp-boost-network',
            'ttt-wp-boost-network_page_ttt-wp-boost-network',
        ];
        if ( ! in_array( $hook, $our_pages, true ) ) {
            return;
        }
        wp_enqueue_style(
            'ttt-wp-boost-admin',
            TTT_WP_BOOST_URL . 'admin/assets/css/admin.css',
            [],
            TTT_WP_BOOST_VERSION
        );
    }

    /* ═══════ AJAX handlers ═══════ */
    public function ajax_clear_cache() {
        check_ajax_referer( 'ttt_wp_boost', 'nonce' );
        if ( is_network_admin() ) {
            if ( ! current_user_can( 'manage_network_options' ) ) {
                wp_die( -1, 403 );
            }
        } else {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( -1, 403 );
            }
        }

        $count = ttt_wp_boost_clear_all_cache();
        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %d = number of cache files deleted */
                __( 'Cleared %d cached page(s).', 'ttt-wp-boost' ),
                $count
            ),
            'count' => $count,
        ] );
    }

    public function ajax_get_stats() {
        check_ajax_referer( 'ttt_wp_boost', 'nonce' );
        if ( is_network_admin() ) {
            if ( ! current_user_can( 'manage_network_options' ) ) {
                wp_die( -1, 403 );
            }
        } else {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( -1, 403 );
            }
        }

        $stats = ttt_wp_boost_get_cache_stats();
        wp_send_json_success( $stats );
    }
}

/* ═══════ 工具函数 ═══════ */
function ttt_wp_boost_clear_all_cache() {
    $files = glob( TTT_WP_BOOST_CACHE_DIR . '*.html' );
    $count = 0;
    if ( is_array( $files ) ) {
        foreach ( $files as $file ) {
            if ( 'index.php' === basename( $file ) ) {
                continue;
            }
            @unlink( $file );
            $count++;
        }
    }
    return $count;
}

function ttt_wp_boost_get_cache_stats() {
    $files = glob( TTT_WP_BOOST_CACHE_DIR . '*.html' );
    $count = 0;
    $total = 0;
    $newest = 0;
    if ( is_array( $files ) ) {
        foreach ( $files as $file ) {
            if ( 'index.php' === basename( $file ) ) {
                continue;
            }
            $count++;
            $total += filesize( $file );
            $mtime = filemtime( $file );
            if ( $mtime > $newest ) {
                $newest = $mtime;
            }
        }
    }
    return [
        'file_count'   => $count,
        'total_size'   => size_format( $total, 1 ),
        'last_updated' => $newest ? human_time_diff( $newest, time() ) . ' ago' : 'Never',
    ];
}

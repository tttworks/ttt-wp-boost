<?php
/**
 * TTT WP Boost — Page Cache Engine
 *
 * @package TTT_WP_Boost
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TTT_WP_Boost_Page_Cache {

    private $settings;
    private $cache_timeout;
    private $exclude_urls;
    private $exclude_roles;

    public function __construct( $settings ) {
        $this->settings      = $settings;
        $this->cache_timeout = isset( $settings['cache_timeout'] ) ? intval( $settings['cache_timeout'] ) : 86400;
        $this->exclude_urls  = isset( $settings['exclude_urls'] ) ? (array) $settings['exclude_urls'] : [];
        $this->exclude_roles = isset( $settings['exclude_roles'] ) ? (array) $settings['exclude_roles'] : [ 'administrator', 'editor' ];
    }

    /* ═══════ 缓存准入检查 ═══════ */
    public function is_cacheable() {
        /* 只在 GET 请求时缓存 */
        if ( 'GET' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
            return false;
        }

        /* 排除管理员/编辑 */
        if ( is_user_logged_in() ) {
            $user  = wp_get_current_user();
            $roles = (array) $user->roles;
            foreach ( $this->exclude_roles as $role ) {
                if ( in_array( $role, $roles, true ) ) {
                    return false;
                }
            }
        }

        /* 排除特殊页面 */
        if ( $this->is_excluded_url() ) {
            return false;
        }

        return true;
    }

    /* ═══════ 缓存检查与输出 ═══════ */
    public function maybe_serve_cache() {
        if ( ! $this->is_cacheable() ) {
            return;
        }

        $cache_file = $this->cache_file_path();
        if ( ! file_exists( $cache_file ) ) {
            return;
        }

        if ( ( time() - filemtime( $cache_file ) ) > $this->cache_timeout ) {
            @unlink( $cache_file );
            return;
        }

        /* 命中 — 嵌入 Speed Badge 后输出缓存内容 */
        $content = file_get_contents( $cache_file );
        if ( $content ) {
            $badge = $this->build_speed_badge( $cache_file );
            $content = str_replace( '</body>', $badge . "\n</body>", $content );
        }
        header( 'X-TTT-Cache: HIT' );
        header( 'Content-Type: text/html; charset=UTF-8' );
        echo $content;
        exit;
    }

    /* ═══════ 缓存生成 — 内部方法，hook 到 wp_footer ═══════ */
    public function capture_and_store() {
        if ( ! $this->is_cacheable() ) {
            return;
        }

        $cache_file = $this->cache_file_path();
        $content    = ob_get_contents();

        if ( ! empty( $content ) ) {
            /* 生成缓存元数据：时间、内存大小、缓存文件大小 */
            $cache_time = microtime( true ) - defined( 'WP_START_TIMESTAMP' ) ? WP_START_TIMESTAMP : microtime( true );
            $cache_time_sec = defined( 'WP_START_TIMESTAMP' )
                ? round( $cache_time, 3 ) . 's'
                : 'N/A';
            $cache_memory = defined( 'WP_DEBUG' ) && WP_DEBUG
                ? round( memory_get_peak_usage( true ) / 1024 / 1024, 1 ) . ' MB'
                : round( memory_get_usage( true ) / 1024 / 1024, 1 ) . ' MB';
            global $wpdb;
            $cache_queries = $wpdb->num_queries;
            $cache_file_size = round( filesize( $cache_file ) / 1024, 1 ) . ' KB';

            $cache_meta = sprintf(
                'TTT-Cache-Time:%s|TTT-Cache-Memory:%s|TTT-Cache-Queries:%d|TTT-Cache-Size:%s|TTT-Cache-Date:%s',
                $cache_time_sec,
                $cache_memory,
                $cache_queries,
                $cache_file_size,
                gmdate( 'Y-m-d H:i:s' )
            );

            $content .= "\n<!-- $cache_meta -->";
            file_put_contents( $cache_file, $content, LOCK_EX );
            chmod( $cache_file, 0644 );
        }
    }

    /* ═══════ 缓存清理 ═══════ */
    public function clear_cache_by_post( $post_id, $post = null ) {
        /* 清理首页缓存 */
        $home_key = md5( home_url( '/' ) . '||guest' );
        @unlink( TTT_WP_BOOST_CACHE_DIR . $home_key . '.html' );

        /* 清理该post的URL缓存 */
        if ( $post_id ) {
            $post_url = get_permalink( $post_id );
            if ( $post_url ) {
                $path = wp_parse_url( $post_url, PHP_URL_PATH );
                $key  = md5( $path . '||guest' );
                @unlink( TTT_WP_BOOST_CACHE_DIR . $key . '.html' );
            }
        }
    }

    public function on_post_status_change( $new_status, $old_status, $post ) {
        if ( 'publish' === $new_status || 'publish' === $old_status ) {
            $this->clear_cache_by_post( $post->ID, $post );
        }
    }

    /* ═══════ 缓存文件路径 ═══════ */
    private function cache_file_path() {
        $path = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $path = rtrim( $path, '/' );
        if ( empty( $path ) ) {
            $path = '/';
        }

        $role = 'guest';
        if ( is_user_logged_in() ) {
            $user  = wp_get_current_user();
            $roles = (array) $user->roles;
            $role  = implode( '_', $roles );
            /* 兜底：用户无角色时返回 subscriber（最低权限角色），避免空键 */
            if ( empty( $role ) ) {
                $role = 'subscriber';
            }
        }

        $key = md5( $path . '||' . $role );
        return TTT_WP_BOOST_CACHE_DIR . $key . '.html';
    }

    /* ═══════ 排除 URL 检查 ═══════ */
    private function is_excluded_url() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $uri_lower   = strtolower( $request_uri );

        /* WordPress 管理 */
        if ( strpos( $uri_lower, '/wp-admin' ) !== false || strpos( $uri_lower, '/wp-login' ) !== false ) {
            return true;
        }

        /* REST API */
        if ( strpos( $uri_lower, '/wp-json' ) !== false ) {
            return true;
        }

        /* 搜索 / 分页 / 预览 */
        $qs = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
        if ( strpos( $qs, 's=' ) !== false || strpos( $qs, 'p=' ) !== false || strpos( $qs, 'preview=' ) !== false ) {
            return true;
        }

        /* Elementor 编辑器不缓存 */
        if ( strpos( $qs, 'elementor-preview' ) !== false || strpos( $qs, 'elementor_library' ) !== false ) {
            return true;
        }

        /* 用户自定义排除 */
        foreach ( $this->exclude_urls as $ex ) {
            if ( ! empty( $ex ) && strpos( $uri_lower, strtolower( $ex ) ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /* ═══════ Speed Badge 生成（缓存命中专用） ═══════ */
    private function build_speed_badge( $cache_file ) {
        $settings = get_option( 'ttt_wp_boost_settings', [] );
        if ( empty( $settings['enable_speed_badge'] ) || $settings['enable_speed_badge'] !== 'yes' ) {
            return '';
        }

        /* 从缓存文件中读取元数据 */
        $cache_time = 'N/A';
        $cache_memory = 'N/A';
        $cache_queries = 'N/A';

        if ( file_exists( $cache_file ) ) {
            $content = file_get_contents( $cache_file );
            if ( preg_match( '/<!-- \$TTT-Cache-Time:(.*?)\|/s', $content, $matches ) ) {
                $cache_time = $matches[1];
            }
            if ( preg_match( '/\$TTT-Cache-Memory:(.*?)\|/s', $content, $matches ) ) {
                $cache_memory = $matches[1];
            }
            if ( preg_match( '/\$TTT-Cache-Queries:(\d+)\|/s', $content, $matches ) ) {
                $cache_queries = $matches[1];
            }
        }

        /* 已开启的优化项 */
        $items = [];
        if ( ! empty( $settings['enable_cache'] ) && $settings['enable_cache'] === 'yes' ) {
            $items[] = 'Page Cache';
        }
        if ( ! empty( $settings['enable_object_cache'] ) && $settings['enable_object_cache'] === 'yes' ) {
            $items[] = 'Object Cache';
        }
        if ( ! empty( $settings['disable_emoji'] ) && $settings['disable_emoji'] === 'yes' ) {
            $items[] = 'Emoji Off';
        }
        if ( ! empty( $settings['disable_gutenberg'] ) && $settings['disable_gutenberg'] === 'yes' ) {
            $items[] = 'Classic Editor';
        }

        $opt_list = ! empty( $items ) ? esc_attr( implode( ' | ', $items ) ) : 'No optimizations active';

        return '<div id="ttt-speed-badge" style="position:fixed;bottom:16px;right:16px;z-index:99999;background:rgba(0,0,0,0.82);color:#fff;border-radius:8px;padding:10px 14px;font-size:16px;line-height:1.6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;box-shadow:0 4px 16px rgba(0,0,0,0.3);min-width:200px;pointer-events:none;backdrop-filter:blur(4px);"><div style="font-weight:700;margin-bottom:4px;font-size:13px;opacity:0.8;">⚡ TTT WP Boost</div><div>⏱ ' . esc_html( $cache_time ) . '</div><div>📊 ' . esc_html( $cache_queries ) . ' queries</div><div>💾 ' . esc_html( $cache_memory ) . '</div><div style="margin-top:4px;padding-top:4px;border-top:1px solid rgba(255,255,255,0.2);font-size:13px;opacity:0.85;">' . esc_html( $opt_list ) . '</div></div>';
    }
}

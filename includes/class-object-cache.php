<?php
/**
 * TTT WP Boost — Object Cache Engine
 * 三层降级架构: Redis → Memcached → WordPress Transients
 *
 * @package TTT_WP_Boost
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TTT_WP_Boost_Object_Cache {

    /** @var string 当前使用的后端: 'redis' | 'memcached' | 'transients' */
    private $backend = 'transients';

    /** @var object|null Memcached 连接句柄 */
    private $memcached = null;

    /** @var object|null Redis 连接句柄 */
    private $redis = null;

    /** @var int 缓存默认 TTL（秒） */
    private $default_ttl = 3600;

    /** @var bool 是否启用 */
    private $enabled = false;

    /** @var string 键名前缀 */
    private $prefix = 'ttt_';

    /** @var array 全局统计 */
    private static $stats = [
        'hits'   => 0,
        'misses' => 0,
        'sets'   => 0,
        'deletes'=> 0,
        'bytes'  => 0,
    ];

    /* ═══════════════════════════════════════════════════════════════════════
     * 初始化 — 检测可用后端
     * ═══════════════════════════════════════════════════════════════════════ */
    public function __construct( $settings = [] ) {
        $this->default_ttl = isset( $settings['object_cache_ttl'] )
            ? intval( $settings['object_cache_ttl'] )
            : 3600;

        $this->enabled = isset( $settings['enable_object_cache'] )
            && $settings['enable_object_cache'] === 'yes';

        if ( ! $this->enabled ) {
            return;
        }

        $this->detect_backend();
    }

    /**
     * 检测并连接最快可用的后端
     * 优先级: Redis → Memcached → Transients
     */
    private function detect_backend() {
        /* ── 尝试 Redis ── */
        if ( class_exists( 'Redis' ) || extension_loaded( 'redis' ) ) {
            try {
                $host = defined( 'WP_REDIS_HOST' ) ? WP_REDIS_HOST : '127.0.0.1';
                $port = defined( 'WP_REDIS_PORT' ) ? WP_REDIS_PORT : 6379;
                $this->redis = new Redis();
                $this->redis->connect( $host, $port, 1.0 );
                $this->redis->ping();
                $this->backend = 'redis';
                return;
            } catch ( Exception $e ) {
                $this->redis = null;
            }
        }

        /* ── 尝试 Memcached ── */
        if ( class_exists( 'Memcached' ) || extension_loaded( 'memcached' ) ) {
            try {
                $this->memcached = new Memcached();
                $servers = defined( 'MEMCACHED_HOST' )
                    ? [ [ MEMCACHED_HOST, MEMCACHED_PORT ?: 11211, 1 ] ]
                    : [ [ '127.0.0.1', 11211, 1 ] ];
                $this->memcached->addServers( $servers );
                $this->memcached->getStats();
                if ( $this->memcached->getResultCode() === Memcached::RES_SUCCESS ) {
                    $this->backend = 'memcached';
                    return;
                }
            } catch ( Exception $e ) {
                $this->memcached = null;
            }
        }

        /* ── 降级到 WordPress Transients（全环境可用） ── */
        $this->backend = 'transients';
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * 公开 API — 模拟 wp_cache_* 函数族
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 获取缓存
     * @param string $key
     * @param mixed  $default  未命中时返回的默认值
     * @return mixed
     */
    public function get( $key, $default = false ) {
        $store_key = $this->make_key( $key );
        $value = false;

        switch ( $this->backend ) {
            case 'redis':
                $value = $this->redis->get( $store_key );
                break;
            case 'memcached':
                $value = $this->memcached->get( $store_key );
                break;
            case 'transients':
            default:
                $transient_key = $this->prefix . $key;
                $value = get_transient( $transient_key );
                break;
        }

        if ( $value !== false ) {
            self::$stats['hits']++;
            return maybe_unserialize( $value );
        }

        self::$stats['misses']++;
        return $default;
    }

    /**
     * 设置缓存
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl      过期时间（秒），默认取 $this->default_ttl
     * @return bool
     */
    public function set( $key, $value, $ttl = null ) {
        $ttl = ( $ttl !== null ) ? intval( $ttl ) : $this->default_ttl;
        $store_key = $this->make_key( $key );
        $serialized = maybe_serialize( $value );

        $ok = false;
        switch ( $this->backend ) {
            case 'redis':
                $ok = $this->redis->setEx( $store_key, $ttl, $serialized );
                break;
            case 'memcached':
                $ok = $this->memcached->set( $store_key, $serialized, $ttl );
                break;
            case 'transients':
            default:
                $transient_key = $this->prefix . $key;
                $ok = set_transient( $transient_key, $serialized, $ttl );
                break;
        }

        if ( $ok ) {
            self::$stats['sets']++;
            self::$stats['bytes'] += strlen( $serialized );
        }
        return (bool) $ok;
    }

    /**
     * 删除单个缓存
     * @param string $key
     * @return bool
     */
    public function delete( $key ) {
        $store_key = $this->make_key( $key );

        switch ( $this->backend ) {
            case 'redis':
                $ok = (bool) $this->redis->del( $store_key );
                break;
            case 'memcached':
                $ok = $this->memcached->delete( $store_key );
                break;
            case 'transients':
            default:
                $transient_key = $this->prefix . $key;
                $ok = delete_transient( $transient_key );
                break;
        }

        if ( $ok ) {
            self::$stats['deletes']++;
        }
        return $ok;
    }

    /**
     * 清空所有缓存（注意：Transients 模式只清本插件的 key）
     * @return bool
     */
    public function flush() {
        switch ( $this->backend ) {
            case 'redis':
                /* 只 flush 本插件前缀的 key，避免误删其他数据 */
                $keys = $this->redis->keys( $this->prefix . '*' );
                foreach ( $keys as $k ) {
                    $this->redis->del( $k );
                }
                return true;

            case 'memcached':
                $this->memcached->flush();
                return true;

            case 'transients':
            default:
                /* Transients 模式：遍历所有 transients，删除本插件的 */
                global $wpdb;
                $like = '%' . $wpdb->esc_like( '_transient_' . $this->prefix ) . '%';
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                        $like
                    )
                );
                foreach ( $rows as $row ) {
                    delete_option( $row->option_name );
                }
                return true;
        }
    }

    /**
     * 递增计数（仅 Redis/Memcached 支持，Transients 模拟）
     * @param string $key
     * @param int    $offset
     * @return int|false
     */
    public function incr( $key, $offset = 1 ) {
        $store_key = $this->make_key( $key );

        switch ( $this->backend ) {
            case 'redis':
                return $this->redis->incrBy( $store_key, $offset );
            case 'memcached':
                $this->memcached->increment( $store_key, $offset );
                return $this->memcached->get( $store_key );
            case 'transients':
            default:
                $val = $this->get( $key, 0 );
                return $this->set( $key, intval( $val ) + $offset ) ? intval( $val ) + $offset : false;
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * 缓存统计
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 获取当前后端类型
     * @return string
     */
    public function get_backend() {
        return $this->backend;
    }

    /**
     * 获取后端中文标签
     * @return string
     */
    public function get_backend_label() {
        $labels = [
            'redis'     => 'Redis (持久对象缓存)',
            'memcached' => 'Memcached (分布式缓存)',
            'transients'=> 'WordPress Transients (全环境兼容)',
        ];
        return $labels[ $this->backend ] ?? $this->backend;
    }

    /**
     * 获取后端图标（用于设置页显示）
     * @return string CSS class name
     */
    public function get_backend_class() {
        return 'backend-' . $this->backend;
    }

    /**
     * 是否已启用
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * 获取全局统计
     * @return array
     */
    public function get_stats() {
        $s = self::$stats;
        $total = $s['hits'] + $s['misses'];
        $hit_rate = ( $total > 0 ) ? round( ( $s['hits'] / $total ) * 100, 1 ) : 0;
        return [
            'hits'      => $s['hits'],
            'misses'    => $s['misses'],
            'sets'      => $s['sets'],
            'deletes'   => $s['deletes'],
            'hit_rate'  => $hit_rate . '%',
            'bytes'     => size_format( $s['bytes'], 1 ),
            'backend'   => $this->get_backend_label(),
        ];
    }

    /**
     * 获取 Redis/Memcached 服务器状态（仅在对应后端时有效）
     * @return array
     */
    public function get_server_info() {
        $info = [];

        if ( $this->backend === 'redis' && $this->redis ) {
            try {
                $info['redis_version']    = $this->redis->info( 'server' )['redis_version'] ?? 'N/A';
                $info['used_memory']      = $this->redis->info( 'memory' )['used_memory_human'] ?? 'N/A';
                $info['connected_clients'] = $this->redis->info( 'clients' )['connected_clients'] ?? 'N/A';
            } catch ( Exception $e ) {
                $info['error'] = '无法获取 Redis 信息';
            }
        }

        if ( $this->backend === 'memcached' && $this->memcached ) {
            try {
                $stats = $this->memcached->getStats();
                if ( $stats ) {
                    $server = key( $stats );
                    $info['version']      = $stats[ $server ]['version'] ?? 'N/A';
                    $info['bytes']        = size_format( $stats[ $server ]['bytes'] ?? 0, 1 );
                    $info['curr_items']   = $stats[ $server ]['curr_items'] ?? 0;
                }
            } catch ( Exception $e ) {
                $info['error'] = '无法获取 Memcached 信息';
            }
        }

        return $info;
    }

    /**
     * 专用缓存键生成（MD5 保证文件名安全）
     */
    private function make_key( $key ) {
        return $this->prefix . md5( $key );
    }

    /* ═══════════════════════════════════════════════════════════════════════
     * Elementor 专用缓存方法
     * ═══════════════════════════════════════════════════════════════════════ */

    /**
     * 缓存 Elementor 的 CSS 解析结果
     * @param int    $post_id
     * @param string $css_content
     * @return bool
     */
    public function cache_elementor_css( $post_id, $css_content ) {
        return $this->set( "elementor_css_{$post_id}", $css_content, $this->default_ttl );
    }

    /**
     * 获取 Elementor CSS 缓存
     * @param int $post_id
     * @return string|false
     */
    public function get_elementor_css( $post_id ) {
        return $this->get( "elementor_css_{$post_id}", false );
    }

    /**
     * 缓存 WordPress Object Query 结果
     * @param string $query_key  查询标识（如 'recent_posts_5'）
     * @param mixed  $data
     * @param int    $ttl
     * @return bool
     */
    public function cache_query( $query_key, $data, $ttl = null ) {
        return $this->set( "query_{$query_key}", $data, $ttl ?: $this->default_ttl );
    }

    /**
     * 获取查询缓存
     * @param string $query_key
     * @return mixed
     */
    public function get_query( $query_key ) {
        return $this->get( "query_{$query_key}" );
    }
}

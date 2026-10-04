<?php
/**
 * TTT WP Boost — Asset Minification Engine
 * CSS/JS 压缩优化（参考 Flying Press / WP Rocket）
 *
 * @package TTT_WP_Boost
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TTT_WP_Boost_Asset_Minification {

    private $settings;
    private $cached_css = [];
    private $cached_js = [];

    public function __construct( $settings ) {
        $this->settings = $settings;

        /* ── CSS 优化钩子 ── */
        if ( ! empty( $settings['minify_css'] ) && $settings['minify_css'] === 'yes' ) {
            add_action( 'wp_print_styles', [ $this, 'minify_styles' ], 999 );
        }

        if ( ! empty( $settings['inline_critical_css'] ) && $settings['inline_critical_css'] === 'yes' ) {
            add_action( 'wp_head', [ $this, 'inline_critical_css' ], 1 );
        }

        /* ── JS 优化钩子 ── */
        if ( ! empty( $settings['minify_js'] ) && $settings['minify_js'] === 'yes' ) {
            add_filter( 'print_scripts_array', [ $this, 'minify_scripts' ], 999 );
        }

        if ( ! empty( $settings['defer_js'] ) && $settings['defer_js'] === 'yes' ) {
            add_filter( 'script_loader_tag', [ $this, 'defer_non_essential_scripts' ], 10, 2 );
        }
    }

    /* ═══════ CSS Minification ═══════ */
    public function minify_styles() {
        global $wp_styles;

        foreach ( $wp_styles->registered as $handle => $style ) {
            if ( empty( $style->src ) || $this->is_excluded_style( $handle ) ) {
                continue;
            }

            $css_file = $style->src;
            $cache_key = 'css_' . md5( $css_file );

            /* 检查缓存 */
            $cached_css = get_transient( 'ttt_wb_' . $cache_key );
            if ( $cached_css !== false ) {
                $this->cached_css[ $handle ] = $cached_css;
                return;
            }

            /* 压缩 CSS */
            $minified = $this->minify_css_file( $css_file );
            if ( $minified ) {
                set_transient( 'ttt_wb_' . $cache_key, $minified, DAY_IN_SECONDS );
                $this->cached_css[ $handle ] = $minified;
            }
        }
    }

    private function minify_css_file( $css_file ) {
        if ( ! file_exists( $css_file ) ) {
            return false;
        }

        $css = file_get_contents( $css_file );
        if ( empty( $css ) ) {
            return false;
        }

        /* 移除注释 */
        $css = preg_replace( '!/\*[^*]*?\*/!s', '', $css );

        /* 移除空格和换行 */
        $css = preg_replace( '!\s+!s', ' ', $css );
        $css = preg_replace( '!\s*([{},:;])\s*!s', '$1', $css );
        $css = trim( $css );

        return $css;
    }

    private function is_excluded_style( $handle ) {
        $excluded = [
            'dashicons',
            'admin-bar',
            'wp-block-library',
            'elementor-icons',
        ];
        return in_array( $handle, $excluded, true );
    }

    /* ═══════ Critical CSS 内联 ═══════ */
    public function inline_critical_css() {
        global $wp_styles;

        $critical_css = [];
        $css_size = 0;

        foreach ( $wp_styles->registered as $handle => $style ) {
            if ( empty( $style->src ) || $style->deps || $this->is_excluded_style( $handle ) ) {
                continue;
            }

            $css_file = $style->src;
            if ( ! file_exists( $css_file ) ) {
                continue;
            }

            $css_content = file_get_contents( $css_file );
            $css_size = strlen( $css_content );

            /* 限制在 50KB 以内 */
            if ( $css_size + $css_size > 51200 ) {
                continue;
            }

            /* 内联 CSS */
            echo '<style id="ttt-wp-boost-css-' . esc_attr( $handle ) . '">';
            echo $css_content;
            echo '</style>';

            /* 原链接改为 preload + print */
            $style->src = '';
            $style->extra['rel'] = 'preload';
            $style->extra['as'] = 'style';
            $style->extra['onload'] = "this.onload=null;this.rel='stylesheet'";

            $css_size += strlen( $css_content );
        }
    }

    /* ═══════ JS Minification ═══════ */
    public function minify_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            if ( empty( $script->src ) || $this->is_excluded_script( $handle ) ) {
                continue;
            }

            $js_file = $script->src;
            $cache_key = 'js_' . md5( $js_file );

            /* 检查缓存 */
            $cached_js = get_transient( 'ttt_wb_' . $cache_key );
            if ( $cached_js !== false ) {
                $this->cached_js[ $handle ] = $cached_js;
                return;
            }

            /* 压缩 JS */
            $minified = $this->minify_js_file( $js_file );
            if ( $minified ) {
                set_transient( 'ttt_wb_' . $cache_key, $minified, DAY_IN_SECONDS );
                $this->cached_js[ $handle ] = $minified;
            }
        }
    }

    private function minify_js_file( $js_file ) {
        if ( ! file_exists( $js_file ) ) {
            return false;
        }

        $js = file_get_contents( $js_file );
        if ( empty( $js ) ) {
            return false;
        }

        /* 移除注释 */
        $js = preg_replace( '!/\*[^*]*?\*/!s', '', $js );
        $js = preg_replace( '!//.*$!m', '', $js );

        /* 移除空格和换行 */
        $js = preg_replace( '!\s+!s', ' ', $js );
        $js = preg_replace( '!\s*([,{}();:])\s*!s', '$1', $js );
        $js = trim( $js );

        return $js;
    }

    private function is_excluded_script( $handle ) {
        $excluded = [
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'elementor-frontend',
            'elementor-app-loader',
        ];
        return in_array( $handle, $excluded, true );
    }

    /* ═══════ JS 延迟加载 ═══════ */
    public function defer_non_essential_scripts( $tag, $handle, $src ) {
        $excluded = [
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'wp-embed',
        ];

        if ( in_array( $handle, $excluded, true ) ) {
            return $tag;
        }

        /* 修改为 defer */
        if ( strpos( $tag, '<script' ) !== false ) {
            $tag = str_replace( '<script', '<script defer', $tag );
            if ( strpos( $tag, 'src=' ) !== false ) {
                $tag = preg_replace( 'src=', 'data-src=', $tag, 1 );
            }
        }

        return $tag;
    }

    /* ═══════ 状态获取（用于设置页） ═══════ */
    public function get_optimization_status() {
        $s = $this->settings;
        return [
            'minify_css'           => ! empty( $s['minify_css'] ) && $s['minify_css'] === 'yes',
            'inline_critical_css'  => ! empty( $s['inline_critical_css'] ) && $s['inline_critical_css'] === 'yes',
            'minify_js'            => ! empty( $s['minify_js'] ) && $s['minify_js'] === 'yes',
            'defer_js'             => ! empty( $s['defer_js'] ) && $s['defer_js'] === 'yes',
        ];
    }

    /* ═══════ 缓存清理 ═══════ */
    public function clear_cache() {
        /* 清除所有 CSS 缓存 */
        foreach ( array_keys( $this->cached_css ) as $key ) {
            delete_transient( 'ttt_wb_' . $key );
        }

        /* 清除所有 JS 缓存 */
        foreach ( array_keys( $this->cached_js ) as $key ) {
            delete_transient( 'ttt_wb_wb_' . $key );
        }

        $this->cached_css = [];
        $this->cached_js = [];

        return true;
    }
}
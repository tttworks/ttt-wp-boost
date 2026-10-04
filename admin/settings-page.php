<?php
/**
 * TTT WP Boost — 设置页
 *
 * @package TTT_WP_Boost
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'ttt_wp_boost_settings', [] );

/* ── 处理表单提交 ── */
if ( isset( $_POST['ttt_wp_boost_save'] ) && check_admin_referer( 'ttt_wp_boost_save', 'ttt_wp_boost_nonce' ) ) {
    $settings['enable_cache']  = isset( $_POST['enable_cache'] ) ? 'yes' : 'no';
    $settings['cache_timeout'] = isset( $_POST['cache_timeout'] ) ? absint( $_POST['cache_timeout'] ) : 86400;

    /* 排除URLs */
    $raw_urls = isset( $_POST['exclude_urls'] ) ? sanitize_textarea_field( $_POST['exclude_urls'] ) : '';
    $settings['exclude_urls'] = array_filter( array_map( 'trim', explode( "\n", $raw_urls ) ) );

    update_option( 'ttt_wp_boost_settings', $settings );
    echo '<div class="notice notice-success is-dismissible"><p>设置已保存。</p></div>';
}

/* ── 共享 Tab 导航 ── */
require_once TTT_WP_BOOST_PATH . 'admin/parts/tab-nav.php';

?>
<!-- ═══════ 设置表单 ═══════ -->
<form method="post" action="" class="ttt-boost-card">
    <h2>页面缓存设置</h2>
    <?php wp_nonce_field( 'ttt_wp_boost_save', 'ttt_wp_boost_nonce' ); ?>

    <table class="form-table">
        <tr>
            <th scope="row">启用页面缓存</th>
            <td>
                <label>
                    <input type="checkbox" name="enable_cache" value="yes"
                        <?php checked( 'yes', $settings['enable_cache'] ?? 'yes' ); ?>>
                    为未登录访客缓存 HTML 页面
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">缓存有效期（秒）</th>
            <td>
                <input type="number" name="cache_timeout"
                    value="<?php echo esc_attr( $settings['cache_timeout'] ?? 86400 ); ?>"
                    min="3600" max="604800" step="3600" class="small-text">
                <p class="description">
                    缓存过期时间（秒）。86400 = 24 小时，3600 = 1 小时。
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">排除网址</th>
            <td>
                <textarea name="exclude_urls" rows="5" cols="50" class="large-text code"><?php
                    $urls = isset( $settings['exclude_urls'] ) ? (array) $settings['exclude_urls'] : [];
                    echo esc_textarea( implode( "\n", $urls ) );
                ?></textarea>
                <p class="description">
                    每行一个 URL。包含这些字符串的页面不会被缓存。后台、搜索和 Elementor 编辑器始终被排除。
                </p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" name="ttt_wp_boost_save" class="button button-primary">保存设置</button>
    </p>
</form>

<script>
/* ── AJAX 清理 ── */
document.querySelectorAll('#ttt-boost-clear-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var data = new FormData();
        data.append('action', 'ttt_wp_boost_clear_cache');
        data.append('nonce', '<?php echo wp_create_nonce( 'ttt_wp_boost' ); ?>');

        fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', { method: 'POST', body: data })
        .then(function(r){ return r.json(); })
        .then(function(json){
            if (json.success ) {
                document.querySelectorAll('.ttt-boost-stat-number').forEach(function(el){
                    if (el.id === 'tt-stat-count') el.textContent = '0';
                });
            }
        });
    });
});
</script>
<?php
/**
 * TTT WP Boost — 共享 Tab 导航
 * 所有设置页顶部统一显示的 Tab 导航
 *
 * @package TTT_WP_Boost
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'ttt-wp-boost';
$pages = [
    'ttt-wp-boost'                    => '性能报表',
    'ttt-wp-boost-dashboard'           => '后台加速',
    'ttt-wp-boost-object-cache'        => '对象缓存',
    'ttt-wp-boost-asset-minification'  => '资源压缩',
];
?>
<div class="wrap ttt-boost-wrap">
    <h1 style="margin-bottom:0;">
        <?php
        $current_title = $pages[ $current_page ] ?? 'TTT WP Boost';
        echo esc_html( $current_title );
        ?>
        <span style="font-size:14px;font-weight:400;color:#646970;margin-left:10px;">v<?php echo esc_html( defined( 'TTT_WP_BOOST_VERSION' ) ? TTT_WP_BOOST_VERSION : '1.2.0' ); ?></span>
    </h1>

    <h2 class="nav-tab-wrapper" style="border-bottom:1px solid #c3c4c7;padding-bottom:0;margin:16px 0 0 0;">
        <?php foreach ( $pages as $slug => $title ) : ?>
            <?php
            $is_active = ( $slug === $current_page );
            $url = 'ttt-wp-boost' === $slug
                ? admin_url( 'admin.php?page=ttt-wp-boost' )
                : admin_url( 'admin.php?page=' . $slug );
            ?>
            <a href="<?php echo esc_url( $url ); ?>"
               class="nav-tab<?php echo $is_active ? ' nav-tab-active' : ''; ?>"
               style="font-size:13px;padding:8px 16px;">
                <?php echo esc_html( $title ); ?>
            </a>
        <?php endforeach; ?>
    </h2>
</div>

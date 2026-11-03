<?php
/**
 * snowweb の初期設定。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

define( 'SNOWWEB_VERSION', '0.0.0.3' );

/**
 * テーマの基本機能を有効にする。
 */
function snowweb_setup() {
	load_theme_textdomain( 'snowweb', get_template_directory() . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'snowweb_setup' );

/**
 * 表側の CSS を読み込む。
 */
function snowweb_enqueue_assets() {
	wp_enqueue_style(
		'snowweb-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array(),
		SNOWWEB_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'snowweb_enqueue_assets' );

require_once get_template_directory() . '/inc/settings.php';
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/layout.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin.php';
}

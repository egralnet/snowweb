<?php
/**
 * 「snowweb設定 → 全体」の反映（カラム数・サイドバーの位置・デザイン）。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * カラム数とサイドバーの位置を body のクラスで伝える。
 *
 * @param string[] $classes body のクラス。
 * @return string[]
 */
function snowweb_layout_body_class( $classes ) {
	$columns   = snowweb_get_setting( 'layout_columns' );
	$classes[] = '2' === $columns ? 'snowweb-col-2' : 'snowweb-col-1';

	if ( '2' === $columns ) {
		$classes[] = 'left' === snowweb_get_setting( 'layout_sidebar' ) ? 'snowweb-sidebar-left' : 'snowweb-sidebar-right';
	}

	return $classes;
}
add_filter( 'body_class', 'snowweb_layout_body_class' );

/**
 * 1カラムのときは、サイドバーを出力しない。
 *
 * テンプレートにはサイドバーを常に入れておき、ここで出す・出さないを決めます。
 * サイトエディターでテンプレートを編集しても同じように効きます。
 *
 * @param string $content 出力される HTML。
 * @param array  $block   ブロックの情報。
 * @return string
 */
function snowweb_layout_hide_sidebar( $content, $block ) {
	if ( 'core/template-part' !== $block['blockName'] || empty( $block['attrs']['slug'] ) || 'sidebar' !== $block['attrs']['slug'] ) {
		return $content;
	}

	return '2' === snowweb_get_setting( 'layout_columns' ) ? $content : '';
}
add_filter( 'render_block', 'snowweb_layout_hide_sidebar', 10, 2 );

/**
 * デザイン（スタイルバリエーション）を切り替える。
 *
 * 「外観 → エディター → スタイル」で選んだときと同じく、サイトのスタイル設定を
 * 選んだデザインの内容で置き換えます。エディターで個別に変えた色や文字の設定は
 * 上書きされます。
 *
 * @param string $slug デザインのスラッグ（default は標準）。
 * @return bool 切り替えられたら true。
 */
function snowweb_apply_design_variation( $slug ) {
	$variations = snowweb_design_variations();
	if ( ! array_key_exists( $slug, $variations ) ) {
		return false;
	}

	$user_styles = array(
		'version'                     => WP_Theme_JSON::LATEST_SCHEMA,
		'isGlobalStylesUserThemeJSON' => true,
	);

	if ( 'default' !== $slug ) {
		$data = wp_json_file_decode( get_template_directory() . '/styles/' . $slug . '.json', array( 'associative' => true ) );
		if ( ! is_array( $data ) ) {
			return false;
		}
		foreach ( array( 'settings', 'styles' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				$user_styles[ $key ] = $data[ $key ];
			}
		}
		$user_styles['title'] = isset( $data['title'] ) ? (string) $data['title'] : $slug;
	}

	$post_id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	if ( ! $post_id ) {
		return false;
	}

	$result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => wp_json_encode( $user_styles ),
			)
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		return false;
	}

	WP_Theme_JSON_Resolver::clean_cached_data();
	if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
		wp_clean_theme_json_cache();
	}

	return true;
}

/**
 * 設定画面でデザインが変わったときだけ、切り替えを実行する。
 *
 * @param mixed $old_value 変更前の設定。
 * @param mixed $value     変更後の設定。
 */
function snowweb_layout_on_settings_update( $old_value, $value ) {
	$old = is_array( $old_value ) && isset( $old_value['design_variation'] ) ? $old_value['design_variation'] : 'default';
	$new = is_array( $value ) && isset( $value['design_variation'] ) ? $value['design_variation'] : 'default';

	if ( $old !== $new ) {
		snowweb_apply_design_variation( $new );
	}
}
add_action( 'update_option_snowweb_settings', 'snowweb_layout_on_settings_update', 10, 2 );

/**
 * 初めて保存したとき（オプションが新しく作られたとき）も切り替えを実行する。
 *
 * @param string $option オプション名。
 * @param mixed  $value  保存された値。
 */
function snowweb_layout_on_settings_add( $option, $value ) {
	if ( is_array( $value ) && isset( $value['design_variation'] ) && 'default' !== $value['design_variation'] ) {
		snowweb_apply_design_variation( $value['design_variation'] );
	}
}
add_action( 'add_option_snowweb_settings', 'snowweb_layout_on_settings_add', 10, 2 );

<?php
/**
 * 「snowweb設定 → アクセス解析」の機能。
 *
 * GA4・Search Console・Bing の確認コード、記事の閲覧数（人気記事）。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 確認コードと GA4 のコードを head に入れる。
 */
function snowweb_analytics_head() {
	$gsc = snowweb_get_setting( 'analytics_gsc_verification' );
	if ( '' !== $gsc ) {
		printf( '<meta name="google-site-verification" content="%s">' . "\n", esc_attr( $gsc ) );
	}

	$bing = snowweb_get_setting( 'analytics_bing_verification' );
	if ( '' !== $bing ) {
		printf( '<meta name="msvalidate.01" content="%s">' . "\n", esc_attr( $bing ) );
	}

	$ga4 = snowweb_get_setting( 'analytics_ga4_id' );
	if ( '' === $ga4 || is_preview() || ( snowweb_get_setting( 'analytics_exclude_logged_in' ) && is_user_logged_in() ) ) {
		return;
	}

	// 測定 ID は保存時に「G-英数字」の形だけを許しているので、そのまま書いても安全。
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Google の指定どおりの書き方が必要なため。
	printf( '<script async src="%s"></script>' . "\n", esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $ga4 ) ) );
	wp_print_inline_script_tag( 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode( $ga4 ) . ');' );
}
add_action( 'wp_head', 'snowweb_analytics_head', 2 );

/**
 * 記事の閲覧数を 1 つ増やす。
 */
function snowweb_analytics_count_view() {
	if ( ! snowweb_get_setting( 'analytics_view_counter' ) || ! is_singular( 'post' ) || is_preview() || is_user_logged_in() ) {
		return;
	}

	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( '' === $agent || preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|curl|wget|python/i', $agent ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$views   = (int) get_post_meta( $post_id, '_snowweb_views', true );
	update_post_meta( $post_id, '_snowweb_views', $views + 1 );
}
add_action( 'template_redirect', 'snowweb_analytics_count_view' );

/**
 * 人気記事（閲覧数の多い順）。
 *
 * @param int $count 件数。
 * @return WP_Post[]
 */
function snowweb_popular_posts( $count = 10 ) {
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 管理画面で 10 件だけ。
			'meta_key'            => '_snowweb_views',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	return $query->posts;
}

/**
 * 設定画面に人気記事の表を出す。
 */
function snowweb_analytics_render_popular() {
	echo '<h2>' . esc_html__( '人気記事（閲覧数の多い順）', 'snowweb' ) . '</h2>';

	if ( ! snowweb_get_setting( 'analytics_view_counter' ) ) {
		echo '<p>' . esc_html__( '「人気記事の集計」をオンにすると、ここに表示されます。', 'snowweb' ) . '</p>';
		return;
	}

	$posts = snowweb_popular_posts( 10 );
	if ( ! $posts ) {
		echo '<p>' . esc_html__( 'まだ集計がありません。', 'snowweb' ) . '</p>';
		return;
	}

	echo '<table class="widefat striped snowweb-popular"><thead><tr><th>' . esc_html__( '順位', 'snowweb' ) . '</th><th>' . esc_html__( '記事', 'snowweb' ) . '</th><th>' . esc_html__( '閲覧数', 'snowweb' ) . '</th></tr></thead><tbody>';
	foreach ( $posts as $i => $post ) {
		printf(
			'<tr><td>%1$d</td><td><a href="%2$s" target="_blank" rel="noopener">%3$s</a></td><td>%4$s</td></tr>',
			(int) $i + 1,
			esc_url( get_permalink( $post ) ),
			esc_html( get_the_title( $post ) ),
			esc_html( number_format_i18n( (int) get_post_meta( $post->ID, '_snowweb_views', true ) ) )
		);
	}
	echo '</tbody></table>';
}

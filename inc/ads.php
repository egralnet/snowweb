<?php
/**
 * 「snowweb設定 → 広告」の機能。
 *
 * AdSense の自動広告、決めた場所への広告コードの表示、ads.txt。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 今のページで広告を出すかどうか。
 *
 * @return bool
 */
function snowweb_ads_allowed() {
	if ( is_admin() || is_404() || is_preview() ) {
		return false;
	}
	if ( snowweb_get_setting( 'ads_hide_logged_in' ) && is_user_logged_in() ) {
		return false;
	}

	/**
	 * 広告を出すかどうかを変えるフィルター（特定のページで止めたいときなど）。
	 *
	 * @param bool $allowed 出すなら true。
	 */
	return (bool) apply_filters( 'snowweb_ads_allowed', true );
}

/**
 * 広告コードを、ラベル付きの枠に入れて返す。
 *
 * @param string $place 場所の名前（クラス名に使う）。
 * @return string
 */
function snowweb_ad_html( $place ) {
	$code = snowweb_get_setting( 'ads_code' );
	if ( '' === trim( $code ) || ! snowweb_ads_allowed() ) {
		return '';
	}

	$label = snowweb_get_setting( 'ads_label' );

	// 広告コードは保存時に、権限に応じて検査済み（snowweb_sanitize_code）。
	return '<div class="snowweb-ad snowweb-ad--' . esc_attr( $place ) . '">'
		. ( '' !== $label ? '<p class="snowweb-ad__label">' . esc_html( $label ) . '</p>' : '' )
		. $code
		. '</div>';
}

/**
 * AdSense の自動広告のコードを head に入れる。
 */
function snowweb_ads_head() {
	$client = snowweb_get_setting( 'ads_adsense_client' );
	if ( ! snowweb_get_setting( 'ads_auto' ) || '' === $client || ! snowweb_ads_allowed() ) {
		return;
	}
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- AdSense の指定どおりの書き方が必要なため。
	printf( '<script async src="%s" crossorigin="anonymous"></script>' . "\n", esc_url( 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( $client ) ) );
}
add_action( 'wp_head', 'snowweb_ads_head', 20 );

/**
 * 本文の最初・最初の見出し2の前・本文の最後に広告を入れる。
 *
 * @param string $content 本文の HTML。
 * @return string
 */
function snowweb_ads_in_content( $content ) {
	if ( ! function_exists( 'snowweb_is_main_entry' ) || ! snowweb_is_main_entry() || ! is_singular( 'post' ) ) {
		return $content;
	}

	if ( snowweb_get_setting( 'ads_before_first_h2' ) ) {
		$ad  = snowweb_ad_html( 'before-h2' );
		$pos = stripos( $content, '<h2' );
		// 目次があるときは、目次の前に入れる。
		$toc = stripos( $content, '<details class="snowweb-toc"' );
		if ( false !== $toc && ( false === $pos || $toc < $pos ) ) {
			$pos = $toc;
		}
		if ( '' !== $ad && false !== $pos ) {
			$content = substr( $content, 0, $pos ) . $ad . substr( $content, $pos );
		}
	}

	if ( snowweb_get_setting( 'ads_before_content' ) ) {
		$content = snowweb_ad_html( 'top' ) . $content;
	}

	if ( snowweb_get_setting( 'ads_after_content' ) ) {
		$content .= snowweb_ad_html( 'bottom' );
	}

	return $content;
}
// 目次（20）の後、シェアや関連記事（30）の前。
add_filter( 'the_content', 'snowweb_ads_in_content', 25 );

/**
 * サイドバーの最後に広告を入れる。
 *
 * @param string $content 出力される HTML。
 * @param array  $block   ブロックの情報。
 * @return string
 */
function snowweb_ads_in_sidebar( $content, $block ) {
	if ( '' === $content || 'core/template-part' !== $block['blockName'] || empty( $block['attrs']['slug'] ) || 'sidebar' !== $block['attrs']['slug'] ) {
		return $content;
	}
	if ( ! snowweb_get_setting( 'ads_sidebar' ) ) {
		return $content;
	}

	$ad = snowweb_ad_html( 'sidebar' );
	if ( '' === $ad ) {
		return $content;
	}

	$close = strrpos( $content, '</' );
	return false === $close ? $content . $ad : substr( $content, 0, $close ) . $ad . substr( $content, $close );
}
add_filter( 'render_block', 'snowweb_ads_in_sidebar', 20, 2 );

/**
 * /ads.txt を表示する。
 */
function snowweb_ads_txt() {
	$text = snowweb_get_setting( 'ads_txt' );
	if ( '' === trim( $text ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	if ( '/ads.txt' !== $path || '/' !== (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) {
		return;
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo esc_html( $text ) . "\n";
	exit;
}
add_action( 'parse_request', 'snowweb_ads_txt', 0 );

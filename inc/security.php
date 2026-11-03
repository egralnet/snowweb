<?php
/**
 * セキュリティ機能。
 *
 * 締め出し事故に備え、wp-config.php に次の 1 行を書くと全機能が止まります。
 *   define( 'SNOWWEB_SAFE_MODE', true );
 *
 * 個別に止めたいときは、子テーマなどで次のフィルターに false を返します。
 *   add_filter( 'snowweb_security_enabled', function ( $on, $feature ) { ... }, 10, 2 );
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 機能が有効かどうかを返す。
 *
 * @param string $feature 機能名。
 * @return bool
 */
function snowweb_security_enabled( $feature ) {
	if ( defined( 'SNOWWEB_SAFE_MODE' ) && SNOWWEB_SAFE_MODE ) {
		return false;
	}
	return (bool) apply_filters( 'snowweb_security_enabled', true, $feature );
}

/*
 * WordPress のバージョン番号をページに出さない。
 */
if ( snowweb_security_enabled( 'hide_version' ) ) {
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );
}

/*
 * 基本のセキュリティヘッダー（CSP は別の版で段階的に追加します）。
 */
if ( snowweb_security_enabled( 'headers' ) ) {
	add_action(
		'send_headers',
		static function () {
			if ( headers_sent() ) {
				return;
			}
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}
	);
}

/*
 * XML-RPC を止める（パスワード総当たりの入口になりやすいため）。
 */
if ( snowweb_security_enabled( 'disable_xmlrpc' ) ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter(
		'wp_headers',
		static function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}
	);
}

/*
 * ログインしていない人がユーザー名を調べられないようにする。
 */
if ( snowweb_security_enabled( 'block_user_enumeration' ) ) {
	// ?author=1 で投稿者ページへ転送される動きを止める。
	add_action(
		'template_redirect',
		static function () {
			if ( is_user_logged_in() || ! is_author() ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 読み取りのみ。
			if ( isset( $_GET['author'] ) ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
	);

	// REST API のユーザー一覧を、ログインしていない人には返さない。
	add_filter(
		'rest_endpoints',
		static function ( $endpoints ) {
			if ( is_user_logged_in() ) {
				return $endpoints;
			}
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
			return $endpoints;
		}
	);
}

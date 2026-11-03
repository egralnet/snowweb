<?php
/**
 * 「snowweb設定」の値の定義と読み書き。
 *
 * 設定はすべて 1 つのオプション（snowweb_settings）に配列で保存します。
 * テーマを切り替えても値は残るので、戻したときに設定をやり直す必要はありません。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 設定項目の一覧。
 *
 * 各項目は次の形です。
 *   tab     : 表示するタブ
 *   type    : bool（オン・オフ） / choice（選択肢から 1 つ） / text（1 行の文字）
 *             / textarea（複数行の文字） / url / number（整数） / code（広告などの HTML）
 *   default : 初期値
 *   choices : choice のときに選べる値（画面の表示名は admin.php 側で付けます）
 *   pattern : text のときに許す形（正規表現）。合わない値は保存しません
 *   min/max : number のときの範囲
 *
 * テーマの読み込み直後にも呼ばれるため、ここでは翻訳関数を使いません。
 *
 * @return array<string, array<string, mixed>>
 */
function snowweb_settings_schema() {
	$bool = static function ( $tab, $initial ) {
		return array(
			'tab'     => $tab,
			'type'    => 'bool',
			'default' => $initial,
		);
	};
	$text = static function ( $tab, $pattern = '' ) {
		return array(
			'tab'     => $tab,
			'type'    => 'text',
			'default' => '',
			'pattern' => $pattern,
		);
	};

	$schema = array(
		// 全体。
		'layout_columns'                  => array(
			'tab'     => 'general',
			'type'    => 'choice',
			'default' => '2',
			'choices' => array( '1', '2' ),
		),
		'layout_sidebar'                  => array(
			'tab'     => 'general',
			'type'    => 'choice',
			'default' => 'right',
			'choices' => array( 'right', 'left' ),
		),
		'design_variation'                => array(
			'tab'     => 'general',
			'type'    => 'choice',
			'default' => 'default',
			'choices' => array_keys( snowweb_design_variations() ),
		),

		// 記事。
		'article_breadcrumbs'             => $bool( 'article', true ),
		'article_show_modified'           => $bool( 'article', true ),
		'article_font_switch'             => $bool( 'article', true ),
		'article_toc'                     => $bool( 'article', true ),
		'article_toc_min'                 => array(
			'tab'     => 'article',
			'type'    => 'number',
			'default' => 3,
			'min'     => 1,
			'max'     => 20,
		),
		'article_toc_depth'               => array(
			'tab'     => 'article',
			'type'    => 'choice',
			'default' => '3',
			'choices' => array( '2', '3', '4' ),
		),
		'article_share'                   => $bool( 'article', true ),
		'article_author_box'              => $bool( 'article', true ),
		'article_related'                 => $bool( 'article', true ),
		'article_related_count'           => array(
			'tab'     => 'article',
			'type'    => 'number',
			'default' => 4,
			'min'     => 2,
			'max'     => 12,
		),
		'article_copy_protect'            => $bool( 'article', false ),

		// SEO。
		'seo_enabled'                     => $bool( 'seo', true ),
		'seo_title_separator'             => array(
			'tab'     => 'seo',
			'type'    => 'choice',
			'default' => '|',
			'choices' => array( '|', '-', '–', '·' ),
		),
		'seo_home_description'            => array(
			'tab'     => 'seo',
			'type'    => 'textarea',
			'default' => '',
		),
		'seo_ogp'                         => $bool( 'seo', true ),
		'seo_og_image'                    => array(
			'tab'     => 'seo',
			'type'    => 'url',
			'default' => '',
		),
		'seo_twitter_site'                => $text( 'seo', '/^@?[A-Za-z0-9_]{1,15}$/' ),
		'seo_structured_data'             => $bool( 'seo', true ),
		'seo_noindex_search'              => $bool( 'seo', true ),
		'seo_noindex_date'                => $bool( 'seo', true ),
		'seo_noindex_paged'               => $bool( 'seo', false ),

		// 広告。
		'ads_auto'                        => $bool( 'ads', false ),
		'ads_adsense_client'              => $text( 'ads', '/^ca-pub-[0-9]{10,20}$/' ),
		'ads_code'                        => array(
			'tab'     => 'ads',
			'type'    => 'code',
			'default' => '',
		),
		'ads_label'                       => array(
			'tab'     => 'ads',
			'type'    => 'text',
			'default' => 'スポンサーリンク',
			'pattern' => '',
		),
		'ads_before_content'              => $bool( 'ads', false ),
		'ads_before_first_h2'             => $bool( 'ads', true ),
		'ads_after_content'               => $bool( 'ads', true ),
		'ads_sidebar'                     => $bool( 'ads', false ),
		'ads_hide_logged_in'              => $bool( 'ads', true ),
		'ads_txt'                         => array(
			'tab'     => 'ads',
			'type'    => 'textarea',
			'default' => '',
		),

		// アクセス解析。
		'analytics_ga4_id'                => $text( 'analytics', '/^G-[A-Z0-9]{4,20}$/' ),
		'analytics_exclude_logged_in'     => $bool( 'analytics', true ),
		'analytics_gsc_verification'      => $text( 'analytics', '/^[A-Za-z0-9_\-]{10,100}$/' ),
		'analytics_bing_verification'     => $text( 'analytics', '/^[A-Fa-f0-9]{32}$/' ),
		'analytics_view_counter'          => $bool( 'analytics', false ),

		// セキュリティ。
		'security_hide_version'           => $bool( 'security', true ),
		'security_headers'                => $bool( 'security', true ),
		'security_disable_xmlrpc'         => $bool( 'security', true ),
		'security_block_user_enumeration' => $bool( 'security', true ),
	);

	/**
	 * 設定項目を追加・変更するためのフィルター。
	 *
	 * @param array $schema 設定項目の一覧。
	 */
	return (array) apply_filters( 'snowweb_settings_schema', $schema );
}

/**
 * 選べるデザインの一覧（styles/ フォルダーの JSON から作る）。
 *
 * 「default」は theme.json そのもの（標準）を表します。
 *
 * @return array<string, string> スラッグ => JSON の title（default は空）
 */
function snowweb_design_variations() {
	static $variations = null;
	if ( null !== $variations ) {
		return $variations;
	}

	$variations = array( 'default' => '' );
	$files      = glob( get_template_directory() . '/styles/*.json' );

	foreach ( (array) $files as $file ) {
		$slug = sanitize_key( basename( $file, '.json' ) );
		$data = wp_json_file_decode( $file, array( 'associative' => true ) );
		if ( '' === $slug || 'default' === $slug || ! is_array( $data ) ) {
			continue;
		}
		$variations[ $slug ] = isset( $data['title'] ) ? (string) $data['title'] : $slug;
	}

	return $variations;
}

/**
 * 保存されている設定を、初期値で補って返す。
 *
 * @return array<string, mixed>
 */
function snowweb_get_settings() {
	static $last_saved    = null;
	static $last_settings = null;

	$saved = get_option( 'snowweb_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();

	// 同じ値なら前回の結果を使う（1 ページで何度も呼ばれるため）。
	if ( null !== $last_settings && $saved === $last_saved ) {
		return $last_settings;
	}

	$settings = array();

	foreach ( snowweb_settings_schema() as $key => $field ) {
		$settings[ $key ] = array_key_exists( $key, $saved )
			? snowweb_normalize_setting( $field, $saved[ $key ] )
			: $field['default'];
	}

	$last_saved    = $saved;
	$last_settings = $settings;

	return $settings;
}

/**
 * 設定を 1 つ取り出す。
 *
 * @param string $key 設定名。
 * @return mixed 設定がなければ null。
 */
function snowweb_get_setting( $key ) {
	$settings = snowweb_get_settings();
	return array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
}

/**
 * 値を項目の型に合わせて安全な形にする。
 *
 * @param array $field 設定項目。
 * @param mixed $value 入力値。
 * @return mixed
 */
function snowweb_normalize_setting( $field, $value ) {
	if ( 'bool' === $field['type'] ) {
		return (bool) $value;
	}

	if ( 'choice' === $field['type'] ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		return in_array( $value, $field['choices'], true ) ? $value : $field['default'];
	}

	if ( 'number' === $field['type'] ) {
		$value = is_numeric( $value ) ? (int) $value : (int) $field['default'];
		return max( (int) $field['min'], min( (int) $field['max'], $value ) );
	}

	if ( ! is_scalar( $value ) ) {
		return $field['default'];
	}
	$value = (string) $value;

	if ( 'text' === $field['type'] ) {
		$value = trim( sanitize_text_field( $value ) );
		$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 200 ) : substr( $value, 0, 200 );
		if ( '' !== $value && ! empty( $field['pattern'] ) && ! preg_match( $field['pattern'], $value ) ) {
			return '';
		}
		return $value;
	}

	if ( 'textarea' === $field['type'] ) {
		return substr( sanitize_textarea_field( $value ), 0, 10000 );
	}

	if ( 'url' === $field['type'] ) {
		$value = esc_url_raw( trim( $value ), array( 'http', 'https' ) );
		return $value;
	}

	if ( 'code' === $field['type'] ) {
		// 読み出しのときは形だけ確かめる。中身の検査は保存時（snowweb_sanitize_settings）に行う。
		return substr( $value, 0, 20000 );
	}

	return $field['default'];
}

/**
 * 広告コードなどの HTML を、保存する人の権限に合わせて安全にする。
 *
 * スクリプトを含む HTML を保存できるのは、WordPress で「unfiltered_html」の
 * 権限を持つ人（通常は管理者）だけです。それ以外の人が保存した場合は、
 * 記事で使える HTML だけを残します。
 *
 * @param string $value 入力値。
 * @return string
 */
function snowweb_sanitize_code( $value ) {
	$value = trim( (string) $value );
	if ( current_user_can( 'unfiltered_html' ) ) {
		return $value;
	}
	return wp_kses_post( $value );
}

/**
 * 保存前の検証（register_setting の sanitize_callback）。
 *
 * 画面から送られたときは「_tab」が付いてくるので、そのタブの項目だけを
 * 書き換え、ほかのタブの値はそのまま残します。チェックボックスは
 * オフのとき何も送られないため、送られてこない bool 項目はオフとみなします。
 *
 * @param mixed $input 入力値。
 * @return array<string, mixed>
 */
function snowweb_sanitize_settings( $input ) {
	$input   = is_array( $input ) ? $input : array();
	$schema  = snowweb_settings_schema();
	$current = snowweb_get_settings();
	$tab     = isset( $input['_tab'] ) && is_string( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';

	$clean = $current;
	foreach ( $schema as $key => $field ) {
		// 画面以外からの保存（読み込み・2 回目の検証）では全項目、画面からはそのタブだけ。
		if ( '' !== $tab && $field['tab'] !== $tab ) {
			continue;
		}

		if ( '' !== $tab && 'bool' === $field['type'] ) {
			$clean[ $key ] = ! empty( $input[ $key ] );
			continue;
		}
		if ( ! array_key_exists( $key, $input ) ) {
			continue;
		}

		// options.php は保存前に wp_unslash 済みなので、ここではそのまま使う。
		$raw = $input[ $key ];

		if ( 'code' === $field['type'] ) {
			$value = snowweb_normalize_setting( $field, $raw );
			// 変わったときだけ検査する（変わっていなければ、以前に検査済み）。
			$clean[ $key ] = $value === $current[ $key ] ? $value : snowweb_sanitize_code( $value );
			continue;
		}

		$value = snowweb_normalize_setting( $field, $raw );
		if ( 'text' === $field['type'] && '' === $value && is_string( $raw ) && '' !== trim( $raw ) && '' !== $tab ) {
			add_settings_error( 'snowweb_settings', 'snowweb_' . $key, snowweb_invalid_value_message( $key ) );
			$value = $current[ $key ];
		}
		$clean[ $key ] = $value;
	}

	return $clean;
}

/**
 * 形が正しくない値が入力されたときの案内。
 *
 * @param string $key 設定名。
 * @return string
 */
function snowweb_invalid_value_message( $key ) {
	$name = $key;
	if ( function_exists( 'snowweb_admin_labels' ) ) {
		$labels = snowweb_admin_labels();
		$name   = isset( $labels[ $key ]['label'] ) ? $labels[ $key ]['label'] : $key;
	}
	/* translators: %s: 設定の名前 */
	return sprintf( __( '「%s」の形が正しくないため、保存しませんでした。', 'snowweb' ), $name );
}

/**
 * 設定を登録する。
 */
function snowweb_register_settings() {
	register_setting(
		'snowweb',
		'snowweb_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'snowweb_sanitize_settings',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'snowweb_register_settings' );

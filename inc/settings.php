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
 *   type    : bool（オン・オフ） / choice（選択肢から 1 つ）
 *   default : 初期値
 *   choices : choice のときに選べる値（画面の表示名は admin.php 側で付けます）
 *
 * テーマの読み込み直後にも呼ばれるため、ここでは翻訳関数を使いません。
 *
 * @return array<string, array<string, mixed>>
 */
function snowweb_settings_schema() {
	$schema = array(
		// 全体。
		'layout_columns'                  => array(
			'tab'     => 'general',
			'type'    => 'choice',
			'default' => '1',
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

		// セキュリティ。
		'security_hide_version'           => array(
			'tab'     => 'security',
			'type'    => 'bool',
			'default' => true,
		),
		'security_headers'                => array(
			'tab'     => 'security',
			'type'    => 'bool',
			'default' => true,
		),
		'security_disable_xmlrpc'         => array(
			'tab'     => 'security',
			'type'    => 'bool',
			'default' => true,
		),
		'security_block_user_enumeration' => array(
			'tab'     => 'security',
			'type'    => 'bool',
			'default' => true,
		),
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
	$saved    = get_option( 'snowweb_settings', array() );
	$saved    = is_array( $saved ) ? $saved : array();
	$settings = array();

	foreach ( snowweb_settings_schema() as $key => $field ) {
		$settings[ $key ] = array_key_exists( $key, $saved )
			? snowweb_normalize_setting( $field, $saved[ $key ] )
			: $field['default'];
	}

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

	return $field['default'];
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

	// 画面以外からの保存（または 2 回目の検証）：全項目を整えて返す。
	if ( '' === $tab ) {
		$clean = array();
		foreach ( $schema as $key => $field ) {
			$clean[ $key ] = array_key_exists( $key, $input )
				? snowweb_normalize_setting( $field, $input[ $key ] )
				: $current[ $key ];
		}
		return $clean;
	}

	$clean = $current;
	foreach ( $schema as $key => $field ) {
		if ( $field['tab'] !== $tab ) {
			continue;
		}
		if ( 'bool' === $field['type'] ) {
			$clean[ $key ] = ! empty( $input[ $key ] );
		} elseif ( array_key_exists( $key, $input ) ) {
			$clean[ $key ] = snowweb_normalize_setting( $field, $input[ $key ] );
		}
	}

	return $clean;
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

<?php
/**
 * 「snowweb設定 → 設定の保存」：設定の書き出しと読み込み。
 *
 * 引っ越しやバックアップのために、snowweb設定をファイル（JSON）に
 * 書き出したり、そのファイルから戻したりできます。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * タブの中身を表示する。
 */
function snowweb_backup_render_tab() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- 結果の表示だけ。
	$result = isset( $_GET['snowweb_import'] ) ? sanitize_key( wp_unslash( $_GET['snowweb_import'] ) ) : '';
	// phpcs:enable

	$messages = array(
		'done'    => array( 'success', __( '設定を読み込みました。', 'snowweb' ) ),
		'nofile'  => array( 'error', __( 'ファイルが選ばれていません。', 'snowweb' ) ),
		'toobig'  => array( 'error', __( 'ファイルが大きすぎます（100KB まで）。', 'snowweb' ) ),
		'invalid' => array( 'error', __( 'snowweb の設定ファイルではないため、読み込みませんでした。', 'snowweb' ) ),
	);
	if ( isset( $messages[ $result ] ) ) {
		printf( '<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr( $messages[ $result ][0] ), esc_html( $messages[ $result ][1] ) );
	}
	?>
	<h2><?php esc_html_e( '設定を書き出す', 'snowweb' ); ?></h2>
	<p><?php esc_html_e( 'snowweb設定のすべての項目を、ファイル（JSON）に保存します。広告コードなども含まれるので、ファイルの扱いに気をつけてください。', 'snowweb' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="snowweb_export">
		<?php wp_nonce_field( 'snowweb_export' ); ?>
		<?php submit_button( __( 'ファイルに書き出す', 'snowweb' ), 'secondary', 'submit', false ); ?>
	</form>

	<h2><?php esc_html_e( '設定を読み込む', 'snowweb' ); ?></h2>
	<p><?php esc_html_e( '書き出したファイルを選ぶと、今の設定を上書きします。形の正しくない値は初期値に戻します。', 'snowweb' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="snowweb_import">
		<?php wp_nonce_field( 'snowweb_import' ); ?>
		<p><input type="file" name="snowweb_file" accept=".json,application/json"></p>
		<?php submit_button( __( '読み込む', 'snowweb' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * 設定をファイルとしてダウンロードさせる。
 */
function snowweb_backup_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作をする権限がありません。', 'snowweb' ), 403 );
	}
	check_admin_referer( 'snowweb_export' );

	$data = array(
		'snowweb'  => SNOWWEB_VERSION,
		'exported' => gmdate( 'c' ),
		'settings' => snowweb_get_settings(),
	);

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="snowweb-settings-' . gmdate( 'Ymd' ) . '.json"' );
	header( 'X-Content-Type-Options: nosniff' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'admin_post_snowweb_export', 'snowweb_backup_export' );

/**
 * ファイルから設定を読み込む。
 */
function snowweb_backup_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作をする権限がありません。', 'snowweb' ), 403 );
	}
	check_admin_referer( 'snowweb_import' );

	$back = admin_url( 'admin.php?page=snowweb&tab=backup' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- アップロードの情報は下で 1 つずつ確かめる。
	$file = isset( $_FILES['snowweb_file'] ) ? $_FILES['snowweb_file'] : null;
	if ( ! $file || empty( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		wp_safe_redirect( add_query_arg( 'snowweb_import', 'nofile', $back ) );
		exit;
	}
	if ( (int) $file['size'] > 100 * KB_IN_BYTES ) {
		wp_safe_redirect( add_query_arg( 'snowweb_import', 'toobig', $back ) );
		exit;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- アップロードされた一時ファイルを読むだけ。
	$data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true );
	if ( ! is_array( $data ) || ! isset( $data['snowweb'], $data['settings'] ) || ! is_array( $data['settings'] ) ) {
		wp_safe_redirect( add_query_arg( 'snowweb_import', 'invalid', $back ) );
		exit;
	}

	// 知らない項目は捨て、残りは保存時の検査（snowweb_sanitize_settings）を通す。
	$settings = array_intersect_key( $data['settings'], snowweb_settings_schema() );
	update_option( 'snowweb_settings', snowweb_sanitize_settings( $settings ) );

	wp_safe_redirect( add_query_arg( 'snowweb_import', 'done', $back ) );
	exit;
}
add_action( 'admin_post_snowweb_import', 'snowweb_backup_import' );

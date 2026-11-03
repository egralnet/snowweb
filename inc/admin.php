<?php
/**
 * 管理画面の「snowweb設定」メニュー。
 *
 * 保存は WordPress の設定 API（options.php）を通すので、
 * 権限の確認と nonce の確認は WordPress 本体が行います。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * タブの一覧。
 *
 * @return array<string, string> タブ名 => 表示名
 */
function snowweb_admin_tabs() {
	return array(
		'general'  => __( '全体', 'snowweb' ),
		'security' => __( 'セキュリティ', 'snowweb' ),
	);
}

/**
 * 画面に出す項目名・説明・選択肢の表示名。
 *
 * @return array<string, array<string, mixed>>
 */
function snowweb_admin_labels() {
	$designs = array();
	foreach ( snowweb_design_variations() as $slug => $title ) {
		$designs[ $slug ] = 'default' === $slug ? __( '標準', 'snowweb' ) : $title;
	}

	return array(
		'layout_columns'                  => array(
			'label'   => __( 'カラム数', 'snowweb' ),
			'choices' => array(
				'1' => __( '1カラム', 'snowweb' ),
				'2' => __( '2カラム（サイドバーあり）', 'snowweb' ),
			),
			'help'    => __( 'サイドバーの中身は「外観 → エディター → パターン → テンプレートパーツ → サイドバー」で編集できます。', 'snowweb' ),
		),
		'layout_sidebar'                  => array(
			'label'   => __( 'サイドバーの位置', 'snowweb' ),
			'choices' => array(
				'right' => __( '右', 'snowweb' ),
				'left'  => __( '左', 'snowweb' ),
			),
			'help'    => __( '2カラムのときだけ使われます。スマートフォンでは記事の下に表示されます。', 'snowweb' ),
		),
		'design_variation'                => array(
			'label'   => __( 'デザイン', 'snowweb' ),
			'choices' => $designs,
			'help'    => __( '切り替えると、「外観 → エディター → スタイル」で個別に変えた色や文字の設定は上書きされます。', 'snowweb' ),
		),
		'security_hide_version'           => array(
			'label' => __( 'WordPress のバージョン番号を隠す', 'snowweb' ),
			'help'  => __( 'ページの HTML に出るバージョン番号を消し、古いバージョンを狙った攻撃の手がかりを減らします。', 'snowweb' ),
		),
		'security_headers'                => array(
			'label' => __( 'セキュリティヘッダーを送る', 'snowweb' ),
			'help'  => __( '他のサイトの枠内への埋め込み（クリックジャッキング）などを防ぐ指示をブラウザーに送ります。', 'snowweb' ),
		),
		'security_disable_xmlrpc'         => array(
			'label' => __( 'XML-RPC を止める', 'snowweb' ),
			'help'  => __( 'パスワードの総当たり攻撃に使われやすい入口を閉じます。Jetpack や一部のスマートフォンアプリを使う場合はオフにしてください。', 'snowweb' ),
		),
		'security_block_user_enumeration' => array(
			'label' => __( 'ユーザー名を調べられないようにする', 'snowweb' ),
			'help'  => __( 'ログインしていない人が「?author=1」や REST API からログイン用のユーザー名を知ることを防ぎます。', 'snowweb' ),
		),
	);
}

/**
 * メニューを追加する。
 */
function snowweb_admin_menu() {
	add_menu_page(
		__( 'snowweb設定', 'snowweb' ),
		__( 'snowweb設定', 'snowweb' ),
		'manage_options',
		'snowweb',
		'snowweb_admin_render_page',
		'dashicons-shield',
		61
	);
}
add_action( 'admin_menu', 'snowweb_admin_menu' );

/**
 * 設定画面だけで CSS を読み込む。
 *
 * @param string $hook_suffix 画面の識別子。
 */
function snowweb_admin_enqueue( $hook_suffix ) {
	if ( 'toplevel_page_snowweb' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_style( 'snowweb-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), SNOWWEB_VERSION );
}
add_action( 'admin_enqueue_scripts', 'snowweb_admin_enqueue' );

/**
 * いま開いているタブ。
 *
 * @return string
 */
function snowweb_admin_current_tab() {
	$tabs = snowweb_admin_tabs();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 表示するタブを選ぶだけで、保存はしない。
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	return array_key_exists( $tab, $tabs ) ? $tab : 'general';
}

/**
 * 設定画面を表示する。
 */
function snowweb_admin_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs     = snowweb_admin_tabs();
	$current  = snowweb_admin_current_tab();
	$schema   = snowweb_settings_schema();
	$labels   = snowweb_admin_labels();
	$settings = snowweb_get_settings();
	?>
	<div class="wrap snowweb-admin">
		<h1><?php esc_html_e( 'snowweb設定', 'snowweb' ); ?></h1>

		<?php if ( snowweb_is_safe_mode() ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'セーフモード中のため、セキュリティ機能はすべて止まっています。問題が解決したら、wp-config.php から SNOWWEB_SAFE_MODE の行を消してください。', 'snowweb' ); ?></p>
			</div>
		<?php endif; ?>

		<?php settings_errors(); ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $name ) : ?>
				<a
					href="<?php echo esc_url( add_query_arg( 'tab', $slug, menu_page_url( 'snowweb', false ) ) ); ?>"
					class="nav-tab<?php echo $slug === $current ? ' nav-tab-active' : ''; ?>"
					<?php echo $slug === $current ? 'aria-current="page"' : ''; ?>
				><?php echo esc_html( $name ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="options.php">
			<?php settings_fields( 'snowweb' ); ?>
			<input type="hidden" name="snowweb_settings[_tab]" value="<?php echo esc_attr( $current ); ?>">

			<table class="form-table" role="presentation">
				<?php
				foreach ( $schema as $key => $field ) {
					if ( $field['tab'] !== $current ) {
						continue;
					}
					$label = isset( $labels[ $key ] ) ? $labels[ $key ] : array( 'label' => $key );
					snowweb_admin_render_field( $key, $field, $label, $settings[ $key ] );
				}
				?>
			</table>

			<?php submit_button( __( '設定を保存', 'snowweb' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * 設定項目を 1 行表示する。
 *
 * @param string $key   設定名。
 * @param array  $field 設定項目。
 * @param array  $label 表示名など。
 * @param mixed  $value 今の値。
 */
function snowweb_admin_render_field( $key, $field, $label, $value ) {
	$id   = 'snowweb-' . str_replace( '_', '-', $key );
	$name = 'snowweb_settings[' . $key . ']';
	$help = isset( $label['help'] ) ? $label['help'] : '';
	?>
	<tr>
		<th scope="row">
			<?php if ( 'choice' === $field['type'] ) : ?>
				<?php echo esc_html( $label['label'] ); ?>
			<?php else : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label['label'] ); ?></label>
			<?php endif; ?>
		</th>
		<td>
			<?php if ( 'bool' === $field['type'] ) : ?>
				<label class="snowweb-switch">
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (bool) $value ); ?>>
					<?php esc_html_e( '有効にする', 'snowweb' ); ?>
				</label>
			<?php elseif ( 'choice' === $field['type'] ) : ?>
				<fieldset>
					<legend class="screen-reader-text"><?php echo esc_html( $label['label'] ); ?></legend>
					<?php foreach ( $field['choices'] as $choice ) : ?>
						<?php $text = isset( $label['choices'][ $choice ] ) ? $label['choices'][ $choice ] : $choice; ?>
						<label class="snowweb-choice">
							<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $choice ); ?>" <?php checked( (string) $value, (string) $choice ); ?>>
							<?php echo esc_html( $text ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<?php if ( '' !== $help ) : ?>
				<p class="description"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

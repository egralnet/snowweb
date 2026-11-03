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
		'general'   => __( '全体', 'snowweb' ),
		'article'   => __( '記事', 'snowweb' ),
		'seo'       => __( 'SEO', 'snowweb' ),
		'ads'       => __( '広告', 'snowweb' ),
		'analytics' => __( 'アクセス解析', 'snowweb' ),
		'security'  => __( 'セキュリティ', 'snowweb' ),
		'backup'    => __( '設定の保存', 'snowweb' ),
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
		'article_breadcrumbs'             => array(
			'label' => __( 'パンくずリスト', 'snowweb' ),
			'help'  => __( '記事タイトルの上に「ホーム > カテゴリー」を表示します。', 'snowweb' ),
		),
		'article_show_modified'           => array(
			'label' => __( '更新日を表示', 'snowweb' ),
			'help'  => __( '公開した日の翌日以降に更新した記事は、公開日の横に更新日も表示します。', 'snowweb' ),
		),
		'article_font_switch'             => array(
			'label' => __( '文字の大きさの切り替えボタン', 'snowweb' ),
			'help'  => __( '読む人が「標準・大・特大」を選べるボタンを記事の最初に表示します。選んだ大きさはその人のブラウザーに保存されます。', 'snowweb' ),
		),
		'article_toc'                     => array(
			'label' => __( '目次', 'snowweb' ),
			'help'  => __( '最初の見出し2の前に、開け閉めできる目次を自動で入れます。', 'snowweb' ),
		),
		'article_toc_min'                 => array(
			'label' => __( '目次を出す見出しの数', 'snowweb' ),
			'help'  => __( '見出しがこの数以上ある記事にだけ目次を出します。', 'snowweb' ),
		),
		'article_toc_depth'               => array(
			'label'   => __( '目次に入れる見出し', 'snowweb' ),
			'choices' => array(
				'2' => __( '見出し2まで', 'snowweb' ),
				'3' => __( '見出し3まで', 'snowweb' ),
				'4' => __( '見出し4まで', 'snowweb' ),
			),
		),
		'article_share'                   => array(
			'label' => __( 'シェアボタン', 'snowweb' ),
			'help'  => __( '記事の最後に X・Facebook・LINE・はてなブックマーク・リンクのコピーのボタンを表示します。外部のスクリプトは読み込まないので、表示は軽く、閲覧者の情報も外部に送りません。', 'snowweb' ),
		),
		'article_author_box'              => array(
			'label' => __( '「この記事を書いた人」', 'snowweb' ),
			'help'  => __( '記事の最後に投稿者のアイコンと紹介文を表示します。紹介文は「ユーザー → プロフィール → プロフィール情報」で書けます。', 'snowweb' ),
		),
		'article_related'                 => array(
			'label' => __( '関連記事', 'snowweb' ),
			'help'  => __( '同じカテゴリーの記事を、記事の最後にカードで表示します。', 'snowweb' ),
		),
		'article_related_count'           => array(
			'label' => __( '関連記事の数', 'snowweb' ),
		),
		'article_copy_protect'            => array(
			'label' => __( 'コピー対策', 'snowweb' ),
			'help'  => __( '本文の文字の選択・右クリック・印刷をしにくくします（ソースコードの枠は除く）。ログイン中は効きません。完全に防ぐものではありません。', 'snowweb' ),
		),
		'seo_enabled'                     => array(
			'label' => __( 'SEO 機能を使う', 'snowweb' ),
			'help'  => __( 'Yoast SEO・Rank Math・All in One SEO・SEO SIMPLE PACK などの SEO プラグインが有効なときは、重複を避けるため自動で止まります。', 'snowweb' ),
		),
		'seo_title_separator'             => array(
			'label'   => __( 'タイトルの区切り文字', 'snowweb' ),
			'choices' => array(
				'|' => '記事名 | サイト名',
				'-' => '記事名 - サイト名',
				'–' => '記事名 – サイト名',
				'·' => '記事名 · サイト名',
			),
		),
		'seo_home_description'            => array(
			'label' => __( 'トップページの説明文', 'snowweb' ),
			'help'  => __( '検索結果に出る説明文（メタディスクリプション）です。空のときはキャッチフレーズを使います。120文字前後がおすすめです。記事ごとの説明文は、投稿画面の下の「snowweb：SEO」で書けます。', 'snowweb' ),
		),
		'seo_ogp'                         => array(
			'label' => __( 'OGP（SNS でシェアされたときの表示）', 'snowweb' ),
			'help'  => __( 'X や Facebook などでシェアされたときに、タイトル・説明文・画像がきれいに出るようにします。', 'snowweb' ),
		),
		'seo_og_image'                    => array(
			'label' => __( 'OGP の画像（初期値）', 'snowweb' ),
			'help'  => __( 'アイキャッチ画像がない記事とトップページで使う画像の URL です。1200×630 ピクセルがおすすめです。「メディア」で画像を開き、「URL をクリップボードにコピー」で取得できます。', 'snowweb' ),
		),
		'seo_twitter_site'                => array(
			'label' => __( 'X（Twitter）のアカウント', 'snowweb' ),
			'help'  => __( '例：@snowweb（半角英数字と _ の15文字まで）', 'snowweb' ),
		),
		'seo_structured_data'             => array(
			'label' => __( '構造化データ', 'snowweb' ),
			'help'  => __( '記事・パンくずリスト・サイトの情報を、Google が読み取りやすい形（JSON-LD）でページに入れます。', 'snowweb' ),
		),
		'seo_noindex_search'              => array(
			'label' => __( '検索結果ページを検索エンジンに載せない', 'snowweb' ),
		),
		'seo_noindex_date'                => array(
			'label' => __( '日付別の記事一覧を検索エンジンに載せない', 'snowweb' ),
		),
		'seo_noindex_paged'               => array(
			'label' => __( '一覧の2ページ目以降を検索エンジンに載せない', 'snowweb' ),
		),
		'ads_auto'                        => array(
			'label' => __( 'AdSense の自動広告', 'snowweb' ),
			'help'  => __( 'オンにすると、下のサイト運営者 ID で AdSense のコードを全ページに入れます。', 'snowweb' ),
		),
		'ads_adsense_client'              => array(
			'label' => __( 'AdSense のサイト運営者 ID', 'snowweb' ),
			'help'  => __( '例：ca-pub-1234567890123456', 'snowweb' ),
		),
		'ads_code'                        => array(
			'label' => __( '広告コード', 'snowweb' ),
			'help'  => __( '広告ユニットのコードを貼り付けます。下でオンにした場所に表示します。スクリプトを含むコードは、管理者だけが保存できます。', 'snowweb' ),
		),
		'ads_label'                       => array(
			'label' => __( '広告の上に出す文字', 'snowweb' ),
			'help'  => __( '「スポンサーリンク」「広告」など。空にすると何も出しません。', 'snowweb' ),
		),
		'ads_before_content'              => array(
			'label' => __( '本文の最初に表示', 'snowweb' ),
		),
		'ads_before_first_h2'             => array(
			'label' => __( '最初の見出し2の前に表示', 'snowweb' ),
		),
		'ads_after_content'               => array(
			'label' => __( '本文の最後に表示', 'snowweb' ),
		),
		'ads_sidebar'                     => array(
			'label' => __( 'サイドバーの最後に表示', 'snowweb' ),
		),
		'ads_hide_logged_in'              => array(
			'label' => __( 'ログイン中は広告を出さない', 'snowweb' ),
			'help'  => __( '自分の広告を誤ってクリックするのを防ぎます。', 'snowweb' ),
		),
		'ads_txt'                         => array(
			'label' => __( 'ads.txt の中身', 'snowweb' ),
			'help'  => __( 'AdSense の「ads.txt」の内容を貼り付けると、/ads.txt で表示します。サイトがドメインの直下にあり、サーバーに ads.txt のファイルがない場合だけ使えます。', 'snowweb' ),
		),
		'analytics_ga4_id'                => array(
			'label' => __( 'Google アナリティクス（GA4）の測定 ID', 'snowweb' ),
			'help'  => __( '例：G-XXXXXXXXXX', 'snowweb' ),
		),
		'analytics_exclude_logged_in'     => array(
			'label' => __( 'ログイン中のアクセスは計測しない', 'snowweb' ),
		),
		'analytics_gsc_verification'      => array(
			'label' => __( 'Google Search Console の確認コード', 'snowweb' ),
			'help'  => __( '「HTML タグ」で確認するときの content="…" の中の文字だけを入れます。', 'snowweb' ),
		),
		'analytics_bing_verification'     => array(
			'label' => __( 'Bing Webmaster Tools の確認コード', 'snowweb' ),
			'help'  => __( 'msvalidate.01 の content="…" の中の文字（32文字）だけを入れます。', 'snowweb' ),
		),
		'analytics_view_counter'          => array(
			'label' => __( '人気記事の集計', 'snowweb' ),
			'help'  => __( '記事ごとの閲覧数を数え、このページの下に人気記事を表示します。ログイン中・検索エンジンのロボットは数えません。キャッシュを使うサーバーでは正しく数えられないことがあります。', 'snowweb' ),
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

		<?php
		if ( 'backup' === $current ) {
			snowweb_backup_render_tab();
			echo '</div>';
			return;
		}
		?>

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

		<?php
		if ( 'analytics' === $current ) {
			snowweb_analytics_render_popular();
		}
		?>
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
			<?php elseif ( 'textarea' === $field['type'] || 'code' === $field['type'] ) : ?>
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo 'code' === $field['type'] ? 8 : 4; ?>" class="large-text<?php echo 'code' === $field['type'] ? ' code' : ''; ?>"><?php echo esc_textarea( (string) $value ); ?></textarea>
			<?php elseif ( 'number' === $field['type'] ) : ?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" min="<?php echo esc_attr( (string) $field['min'] ); ?>" max="<?php echo esc_attr( (string) $field['max'] ); ?>" class="small-text">
			<?php else : ?>
				<input type="<?php echo 'url' === $field['type'] ? 'url' : 'text'; ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="regular-text">
			<?php endif; ?>

			<?php if ( '' !== $help ) : ?>
				<p class="description"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

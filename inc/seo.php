<?php
/**
 * 「snowweb設定 → SEO」の機能。
 *
 * タイトルの区切り、メタディスクリプション、noindex、OGP、構造化データ（JSON-LD）。
 * SEO プラグインが有効なときは、重複を避けるためすべて止めます。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 有効になっている SEO プラグインの名前（なければ空）。
 *
 * @return string
 */
function snowweb_seo_conflicting_plugin() {
	$known = array(
		'wordpress-seo/wp-seo.php'                        => 'Yoast SEO',
		'wordpress-seo-premium/wp-seo-premium.php'        => 'Yoast SEO Premium',
		'seo-by-rank-math/rank-math.php'                  => 'Rank Math',
		'all-in-one-seo-pack/all_in_one_seo_pack.php'     => 'All in One SEO',
		'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'All in One SEO Pro',
		'seo-simple-pack/seo-simple-pack.php'             => 'SEO SIMPLE PACK',
		'autodescription/autodescription.php'             => 'The SEO Framework',
		'wp-seopress/seopress.php'                        => 'SEOPress',
	);

	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	foreach ( $known as $file => $name ) {
		if ( in_array( $file, $active, true ) ) {
			return $name;
		}
	}

	/**
	 * ほかの SEO プラグインを使っているときに名前を返すフィルター。
	 *
	 * @param string $name プラグイン名（なければ空）。
	 */
	return (string) apply_filters( 'snowweb_seo_conflicting_plugin', '' );
}

/**
 * SEO 機能を使うかどうか。
 *
 * @return bool
 */
function snowweb_seo_active() {
	return snowweb_get_setting( 'seo_enabled' ) && '' === snowweb_seo_conflicting_plugin();
}

/**
 * 今のページの説明文。
 *
 * @return string
 */
function snowweb_seo_description() {
	$text = '';

	if ( is_front_page() || is_home() ) {
		$text = snowweb_get_setting( 'seo_home_description' );
		if ( '' === $text ) {
			$text = get_bloginfo( 'description' );
		}
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$text = (string) get_post_meta( $post->ID, '_snowweb_description', true );
		if ( '' === $text ) {
			$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
	}

	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( (string) $text ) ) ) ) );

	return function_exists( 'mb_strimwidth' ) ? mb_strimwidth( $text, 0, 240, '…', 'UTF-8' ) : substr( $text, 0, 160 );
}

/**
 * 今のページの画像（アイキャッチ → 設定の初期画像）。
 *
 * @return string
 */
function snowweb_seo_image() {
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'large' );
		if ( $src ) {
			return $src[0];
		}
	}
	return snowweb_get_setting( 'seo_og_image' );
}

/**
 * 今のページの正規の URL。
 *
 * @return string
 */
function snowweb_seo_url() {
	if ( is_singular() ) {
		return (string) wp_get_canonical_url();
	}
	if ( is_front_page() || is_home() ) {
		return home_url( '/' );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? '' : $link;
	}
	return '';
}

/**
 * タイトルの区切り文字。
 *
 * @param string $sep 区切り文字。
 * @return string
 */
function snowweb_seo_title_separator( $sep ) {
	return snowweb_seo_active() ? snowweb_get_setting( 'seo_title_separator' ) : $sep;
}
add_filter( 'document_title_separator', 'snowweb_seo_title_separator' );

/**
 * 検索エンジンに載せないページ（noindex）。
 *
 * @param array $robots robots の指定。
 * @return array
 */
function snowweb_seo_robots( $robots ) {
	if ( ! snowweb_seo_active() ) {
		return $robots;
	}

	$noindex = false;
	if ( is_search() && snowweb_get_setting( 'seo_noindex_search' ) ) {
		$noindex = true;
	} elseif ( is_date() && snowweb_get_setting( 'seo_noindex_date' ) ) {
		$noindex = true;
	} elseif ( is_paged() && snowweb_get_setting( 'seo_noindex_paged' ) ) {
		$noindex = true;
	} elseif ( is_singular() && get_post_meta( get_queried_object_id(), '_snowweb_noindex', true ) ) {
		$noindex = true;
	}

	if ( $noindex ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	} elseif ( ! isset( $robots['noindex'] ) ) {
		$robots['max-image-preview'] = 'large';
	}

	return $robots;
}
add_filter( 'wp_robots', 'snowweb_seo_robots' );

/**
 * head に説明文・OGP・構造化データを出す。
 */
function snowweb_seo_head() {
	if ( ! snowweb_seo_active() || is_404() ) {
		return;
	}

	$description = snowweb_seo_description();
	$url         = snowweb_seo_url();
	$image       = snowweb_seo_image();
	$title       = wp_get_document_title();

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	if ( snowweb_get_setting( 'seo_ogp' ) ) {
		$tags = array(
			'og:locale'      => get_locale(),
			'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:title'       => is_singular() ? wp_strip_all_tags( get_the_title( get_queried_object_id() ) ) : $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:image'       => $image,
		);
		foreach ( $tags as $property => $content ) {
			if ( '' !== (string) $content ) {
				printf( '<meta property="%1$s" content="%2$s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
			}
		}
		if ( is_singular( 'post' ) ) {
			printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c', get_queried_object_id() ) ) );
			printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c', get_queried_object_id() ) ) );
		}

		printf( '<meta name="twitter:card" content="%s">' . "\n", '' !== $image ? 'summary_large_image' : 'summary' );
		$site = snowweb_get_setting( 'seo_twitter_site' );
		if ( '' !== $site ) {
			printf( '<meta name="twitter:site" content="%s">' . "\n", esc_attr( '@' . ltrim( $site, '@' ) ) );
		}
	}

	if ( snowweb_get_setting( 'seo_structured_data' ) ) {
		foreach ( snowweb_seo_structured_data( $description, $image ) as $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
		}
	}
}
add_action( 'wp_head', 'snowweb_seo_head', 5 );

/**
 * 構造化データ（JSON-LD）。
 *
 * @param string $description 説明文。
 * @param string $image       画像の URL。
 * @return array[]
 */
function snowweb_seo_structured_data( $description, $image ) {
	$data = array();

	if ( is_front_page() || is_home() ) {
		$data[] = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();

		if ( is_singular( 'post' ) ) {
			$article = array(
				'@context'         => 'https://schema.org',
				'@type'            => 'BlogPosting',
				'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
				'datePublished'    => get_the_date( 'c', $post_id ),
				'dateModified'     => get_the_modified_date( 'c', $post_id ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
					'url'   => home_url( '/' ),
				),
				'publisher'        => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				),
				'mainEntityOfPage' => (string) get_permalink( $post_id ),
			);
			if ( '' !== $description ) {
				$article['description'] = $description;
			}
			if ( '' !== $image ) {
				$article['image'] = array( $image );
			}
			$data[] = $article;
		}

		$list = array();
		foreach ( snowweb_breadcrumb_items( $post_id ) as $i => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( $item['name'] ),
				'item'     => is_string( $item['url'] ) ? $item['url'] : '',
			);
		}
		$data[] = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		);
	}

	return $data;
}

/*
 * ===== 記事ごとの SEO 設定（投稿画面の下の「snowweb：SEO」） =====
 */

/**
 * 投稿・固定ページの編集画面に入力欄を付ける。
 */
function snowweb_seo_add_meta_box() {
	if ( ! snowweb_get_setting( 'seo_enabled' ) ) {
		return;
	}
	add_meta_box( 'snowweb-seo', __( 'snowweb：SEO', 'snowweb' ), 'snowweb_seo_render_meta_box', array( 'post', 'page' ), 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'snowweb_seo_add_meta_box' );

/**
 * 入力欄を表示する。
 *
 * @param WP_Post $post 記事。
 */
function snowweb_seo_render_meta_box( $post ) {
	wp_nonce_field( 'snowweb_seo_meta', 'snowweb_seo_nonce' );
	$description = (string) get_post_meta( $post->ID, '_snowweb_description', true );
	$noindex     = (bool) get_post_meta( $post->ID, '_snowweb_noindex', true );
	?>
	<p>
		<label for="snowweb-seo-description"><strong><?php esc_html_e( '説明文（メタディスクリプション）', 'snowweb' ); ?></strong></label><br>
		<textarea id="snowweb-seo-description" name="snowweb_seo_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
		<span class="description"><?php esc_html_e( '検索結果に出る説明文です。空のときは抜粋か本文の最初を使います。120文字前後がおすすめです。', 'snowweb' ); ?></span>
	</p>
	<p>
		<label><input type="checkbox" name="snowweb_seo_noindex" value="1" <?php checked( $noindex ); ?>> <?php esc_html_e( 'このページを検索エンジンに載せない（noindex）', 'snowweb' ); ?></label>
	</p>
	<?php
}

/**
 * 入力欄の内容を保存する。
 *
 * @param int $post_id 記事の ID。
 */
function snowweb_seo_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['snowweb_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snowweb_seo_nonce'] ) ), 'snowweb_seo_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$description = isset( $_POST['snowweb_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['snowweb_seo_description'] ) ) : '';
	$description = function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 300 ) : substr( $description, 0, 300 );

	if ( '' === $description ) {
		delete_post_meta( $post_id, '_snowweb_description' );
	} else {
		update_post_meta( $post_id, '_snowweb_description', $description );
	}

	if ( ! empty( $_POST['snowweb_seo_noindex'] ) ) {
		update_post_meta( $post_id, '_snowweb_noindex', 1 );
	} else {
		delete_post_meta( $post_id, '_snowweb_noindex' );
	}
}
add_action( 'save_post', 'snowweb_seo_save_meta_box' );

/**
 * SEO プラグインと重なっているとき、設定画面に知らせる。
 */
function snowweb_seo_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_snowweb' !== $screen->id || ! snowweb_get_setting( 'seo_enabled' ) ) {
		return;
	}
	$plugin = snowweb_seo_conflicting_plugin();
	if ( '' === $plugin ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		/* translators: %s: プラグイン名 */
		esc_html( sprintf( __( '%s が有効なため、snowweb の SEO 機能（説明文・OGP・構造化データ・noindex）は止めています。', 'snowweb' ), $plugin ) )
	);
}
add_action( 'admin_notices', 'snowweb_seo_admin_notice' );

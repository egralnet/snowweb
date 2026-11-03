<?php
/**
 * 「snowweb設定 → 記事」の機能。
 *
 * パンくず・更新日・文字の大きさ・目次・シェア・書いた人・関連記事・コピー対策。
 * ブロックを増やさず、記事のタイトル・日付・本文の出力に書き足す形で入れています。
 * そのため、サイトエディターでテンプレートを編集しても効きます。
 *
 * @package snowweb
 */

defined( 'ABSPATH' ) || exit;

/**
 * 今表示している記事・固定ページ本体の出力かどうか。
 *
 * 一覧や関連記事の中の記事には付け足さないようにするためのものです。
 *
 * @return bool
 */
function snowweb_is_main_entry() {
	return is_singular() && in_the_loop() && get_the_ID() === get_queried_object_id();
}

/**
 * ブロックに指定のクラスが付いているか。
 *
 * @param array  $block ブロックの情報。
 * @param string $class_name クラス名。
 * @return bool
 */
function snowweb_block_has_class( $block, $class_name ) {
	return ! empty( $block['attrs']['className'] ) && in_array( $class_name, preg_split( '/\s+/', $block['attrs']['className'] ), true );
}

/*
 * ===== パンくずリスト =====
 */

/**
 * パンくずリストの項目を作る。
 *
 * @param int $post_id 記事の ID。
 * @return array<int, array{name: string, url: string}>
 */
function snowweb_breadcrumb_items( $post_id ) {
	$items = array(
		array(
			'name' => __( 'ホーム', 'snowweb' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( 'post' === get_post_type( $post_id ) ) {
		$cats = get_the_category( $post_id );
		if ( $cats ) {
			$cat   = $cats[0];
			$chain = array_reverse( get_ancestors( $cat->term_id, 'category', 'taxonomy' ) );
			foreach ( $chain as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'category' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$items[] = array(
						'name' => $ancestor->name,
						'url'  => get_term_link( $ancestor ),
					);
				}
			}
			$items[] = array(
				'name' => $cat->name,
				'url'  => get_term_link( $cat ),
			);
		}
	} else {
		foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor_id ) {
			$items[] = array(
				'name' => get_the_title( $ancestor_id ),
				'url'  => get_permalink( $ancestor_id ),
			);
		}
	}

	$items[] = array(
		'name' => get_the_title( $post_id ),
		'url'  => get_permalink( $post_id ),
	);

	return $items;
}

/**
 * パンくずリストの HTML。
 *
 * @param int $post_id 記事の ID。
 * @return string
 */
function snowweb_breadcrumb_html( $post_id ) {
	$items = snowweb_breadcrumb_items( $post_id );
	$last  = count( $items ) - 1;
	$html  = '<nav class="snowweb-crumb" aria-label="' . esc_attr__( 'パンくずリスト', 'snowweb' ) . '"><ol>';

	foreach ( $items as $i => $item ) {
		if ( $i === $last ) {
			$html .= '<li aria-current="page">' . esc_html( $item['name'] ) . '</li>';
		} else {
			$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
		}
	}

	return $html . '</ol></nav>';
}

/**
 * 記事タイトルの上にパンくずリストを入れる。
 *
 * @param string $content 出力される HTML。
 * @param array  $block   ブロックの情報。
 * @return string
 */
function snowweb_article_before_title( $content, $block ) {
	if ( 'core/post-title' !== $block['blockName'] || ! snowweb_block_has_class( $block, 'snowweb-entry__title' ) ) {
		return $content;
	}
	if ( ! snowweb_is_main_entry() || ! snowweb_get_setting( 'article_breadcrumbs' ) ) {
		return $content;
	}
	return snowweb_breadcrumb_html( get_the_ID() ) . $content;
}
add_filter( 'render_block', 'snowweb_article_before_title', 10, 2 );

/*
 * ===== 更新日・文字の大きさ =====
 */

/**
 * 日付の横に更新日と、文字の大きさの切り替えボタンを入れる。
 *
 * @param string $content 出力される HTML。
 * @param array  $block   ブロックの情報。
 * @return string
 */
function snowweb_article_meta( $content, $block ) {
	if ( 'core/post-date' !== $block['blockName'] || ! snowweb_block_has_class( $block, 'snowweb-entry__date' ) || ! snowweb_is_main_entry() ) {
		return $content;
	}

	$extra = '';
	if ( snowweb_get_setting( 'article_show_modified' ) ) {
		$published = (int) get_post_time( 'U', true );
		$modified  = (int) get_post_modified_time( 'U', true );
		if ( $modified - $published > DAY_IN_SECONDS ) {
			$extra .= sprintf(
				'<span class="snowweb-entry__modified">%1$s <time datetime="%2$s">%3$s</time></span>',
				esc_html__( '更新', 'snowweb' ),
				esc_attr( get_the_modified_date( 'c' ) ),
				esc_html( get_the_modified_date( 'Y.m.d' ) )
			);
		}
	}

	if ( snowweb_get_setting( 'article_font_switch' ) ) {
		$extra .= '<span class="snowweb-font-switch" role="group" aria-label="' . esc_attr__( '文字の大きさ', 'snowweb' ) . '">'
			. '<span class="snowweb-font-switch__label">' . esc_html__( '文字の大きさ', 'snowweb' ) . '</span>'
			. '<button type="button" data-size="m" aria-pressed="true">' . esc_html__( '標準', 'snowweb' ) . '</button>'
			. '<button type="button" data-size="l" aria-pressed="false">' . esc_html__( '大', 'snowweb' ) . '</button>'
			. '<button type="button" data-size="xl" aria-pressed="false">' . esc_html__( '特大', 'snowweb' ) . '</button>'
			. '</span>';
	}

	if ( '' === $extra ) {
		return $content;
	}

	return '<div class="snowweb-entry__meta">' . $content . $extra . '</div>';
}
add_filter( 'render_block', 'snowweb_article_meta', 10, 2 );

/*
 * ===== 目次 =====
 */

/**
 * 本文の見出しに id を付け、目次を最初の見出し2の前に入れる。
 *
 * @param string $content 本文の HTML。
 * @return string
 */
function snowweb_article_toc( $content ) {
	if ( ! snowweb_is_main_entry() || ! snowweb_get_setting( 'article_toc' ) ) {
		return $content;
	}

	$depth = (int) snowweb_get_setting( 'article_toc_depth' );
	if ( ! preg_match_all( '/<h([2-' . $depth . '])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}
	if ( count( $matches ) < (int) snowweb_get_setting( 'article_toc_min' ) ) {
		return $content;
	}

	$items  = array();
	$offset = 0;
	$used   = array();

	foreach ( $matches as $i => $match ) {
		$level = (int) $match[1][0];
		$attrs = $match[2][0];
		$text  = trim( html_entity_decode( wp_strip_all_tags( $match[3][0] ), ENT_QUOTES, 'UTF-8' ) );
		if ( '' === $text ) {
			continue;
		}

		if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
			$id = $id_match[2];
		} else {
			$id      = 'toc-' . ( $i + 1 );
			$new     = '<h' . $level . $attrs . ' id="' . esc_attr( $id ) . '">' . $match[3][0] . '</h' . $level . '>';
			$pos     = $match[0][1] + $offset;
			$old     = $match[0][0];
			$content = substr_replace( $content, $new, $pos, strlen( $old ) );
			$offset += strlen( $new ) - strlen( $old );
		}

		if ( isset( $used[ $id ] ) ) {
			continue;
		}
		$used[ $id ] = true;
		$items[]     = array(
			'level' => $level,
			'id'    => $id,
			'text'  => $text,
		);
	}

	if ( ! $items ) {
		return $content;
	}

	$toc = '<details class="snowweb-toc"><summary class="snowweb-toc__head"><span class="snowweb-toc__title">' . esc_html__( '目次', 'snowweb' ) . '</span>'
		. '<span class="snowweb-toc__toggle" aria-hidden="true"><span class="snowweb-toc__open">' . esc_html__( 'OPEN', 'snowweb' ) . '</span><span class="snowweb-toc__close">' . esc_html__( 'CLOSE', 'snowweb' ) . '</span></span></summary>'
		. snowweb_toc_list( $items ) . '</details>';

	// 最初の見出し2の前（なければ本文の最初）に入れる。
	$first_h2 = stripos( $content, '<h2' );
	if ( false === $first_h2 ) {
		return $toc . $content;
	}
	return substr( $content, 0, $first_h2 ) . $toc . substr( $content, $first_h2 );
}
add_filter( 'the_content', 'snowweb_article_toc', 20 );

/**
 * 目次の入れ子のリストを作る。
 *
 * @param array $items 見出しの一覧（level・id・text）。
 * @return string
 */
function snowweb_toc_list( $items ) {
	$html  = '';
	$stack = array();

	foreach ( $items as $item ) {
		$level = $item['level'];

		if ( ! $stack ) {
			$html   .= '<ol>';
			$stack[] = $level;
		} elseif ( $level > end( $stack ) ) {
			$html   .= '<ol>';
			$stack[] = $level;
		} else {
			$depth = count( $stack );
			while ( $depth > 1 && $level < end( $stack ) ) {
				$html .= '</li></ol>';
				array_pop( $stack );
				--$depth;
			}
			$html .= '</li>';
		}

		$html .= '<li><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['text'] ) . '</a>';
	}

	while ( $stack ) {
		$html .= '</li></ol>';
		array_pop( $stack );
	}

	return $html;
}

/*
 * ===== シェア・書いた人・関連記事 =====
 */

/**
 * 本文の後ろに、シェア・書いた人・関連記事を足す。
 *
 * @param string $content 本文の HTML。
 * @return string
 */
function snowweb_article_after_content( $content ) {
	if ( ! snowweb_is_main_entry() ) {
		return $content;
	}

	$is_post = 'post' === get_post_type();
	$after   = '';

	if ( $is_post && snowweb_get_setting( 'article_share' ) ) {
		$after .= snowweb_share_html();
	}
	if ( $is_post && snowweb_get_setting( 'article_author_box' ) ) {
		$after .= snowweb_author_box_html();
	}
	if ( $is_post && snowweb_get_setting( 'article_related' ) ) {
		$after .= snowweb_related_html();
	}

	return $content . $after;
}
add_filter( 'the_content', 'snowweb_article_after_content', 30 );

/**
 * シェアボタン（外部のスクリプトは使わず、リンクだけ）。
 *
 * @return string
 */
function snowweb_share_html() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( wp_strip_all_tags( get_the_title() ) );

	$links = array(
		'x'        => array( 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
		'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'line'     => array( 'LINE', 'https://social-plugins.line.me/lineit/share?url=' . $url ),
		'hatena'   => array( 'はてブ', 'https://b.hatena.ne.jp/add?mode=confirm&url=' . $url . '&title=' . $title ),
	);

	$html = '<div class="snowweb-share"><p class="snowweb-share__title">' . esc_html__( 'この記事をシェアする', 'snowweb' ) . '</p><ul>';
	foreach ( $links as $slug => $link ) {
		$html .= sprintf(
			'<li><a class="snowweb-share__%1$s" href="%2$s" target="_blank" rel="noopener noreferrer nofollow">%3$s</a></li>',
			esc_attr( $slug ),
			esc_url( $link[1] ),
			esc_html( $link[0] )
		);
	}
	$html .= '<li><button type="button" class="snowweb-share__copy" data-url="' . esc_url( get_permalink() ) . '" data-done="' . esc_attr__( 'コピーしました', 'snowweb' ) . '">' . esc_html__( 'リンクをコピー', 'snowweb' ) . '</button></li>';

	return $html . '</ul></div>';
}

/**
 * 「この記事を書いた人」。
 *
 * @return string
 */
function snowweb_author_box_html() {
	$author_id = (int) get_the_author_meta( 'ID' );
	if ( ! $author_id ) {
		return '';
	}

	$bio = get_the_author_meta( 'description', $author_id );

	return '<section class="snowweb-author"><h2 class="snowweb-author__legend">' . esc_html__( 'この記事を書いた人', 'snowweb' ) . '</h2>'
		. '<div class="snowweb-author__main">'
		. '<div class="snowweb-author__person">' . get_avatar( $author_id, 96, '', '' ) . '<p class="snowweb-author__name">' . esc_html( get_the_author_meta( 'display_name', $author_id ) ) . '</p></div>'
		. ( '' !== $bio ? '<div class="snowweb-author__text">' . wpautop( esc_html( $bio ) ) . '</div>' : '' )
		. '</div></section>';
}

/**
 * 関連記事（同じカテゴリーの新しい順）。
 *
 * @return string
 */
function snowweb_related_html() {
	$post_id = get_the_ID();
	$cats    = wp_get_post_categories( $post_id );
	if ( ! $cats ) {
		return '';
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) snowweb_get_setting( 'article_related_count' ),
			'post__not_in'        => array( $post_id ),
			'category__in'        => $cats,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return '';
	}

	$html = '<section class="snowweb-related"><h2 class="snowweb-section-title wp-block-heading">' . esc_html__( 'あわせて読みたい', 'snowweb' ) . '</h2><ul class="snowweb-cards snowweb-related__list">';
	foreach ( $query->posts as $post ) {
		$thumb = get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy' ) );
		if ( '' === $thumb ) {
			$thumb = '<span class="snowweb-thumb--empty">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
		}
		$html .= '<li class="snowweb-card"><div class="snowweb-thumb">' . $thumb . '</div>'
			. '<p class="snowweb-card__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></p></li>';
	}

	return $html . '</ul></section>';
}

/*
 * ===== コピー対策・読み込むファイル =====
 */

/**
 * コピー対策がオンなら、html に印を付ける（ログイン中は付けない）。
 *
 * @param string[] $classes body のクラス。
 * @return string[]
 */
function snowweb_article_body_class( $classes ) {
	if ( snowweb_get_setting( 'article_copy_protect' ) && ! is_user_logged_in() && is_singular() ) {
		$classes[] = 'snowweb-protect';
	}
	return $classes;
}
add_filter( 'body_class', 'snowweb_article_body_class' );

/**
 * 記事ページでだけ、小さなスクリプトを読み込む。
 */
function snowweb_article_enqueue() {
	if ( ! is_singular() ) {
		return;
	}
	wp_enqueue_script( 'snowweb-article', get_template_directory_uri() . '/assets/js/article.js', array(), SNOWWEB_VERSION, array( 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'snowweb_article_enqueue' );

/**
 * 保存した文字の大きさを、表示の前に当てる（ちらつきを防ぐ）。
 */
function snowweb_article_font_size_early() {
	if ( ! is_singular() || ! snowweb_get_setting( 'article_font_switch' ) ) {
		return;
	}
	wp_print_inline_script_tag( "try{var s=localStorage.getItem('snowweb-font-size');if(s==='l'||s==='xl'){document.documentElement.setAttribute('data-font-size',s);}}catch(e){}" );
}
add_action( 'wp_head', 'snowweb_article_font_size_early', 1 );

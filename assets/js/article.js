/**
 * snowweb：記事ページの小さな動き
 * - 文字の大きさの切り替え（選んだ大きさはこのブラウザーに保存）
 * - リンクのコピー
 * - コピー対策（body に snowweb-protect が付いているときだけ）
 */
( function () {
	'use strict';

	var root = document.documentElement;

	// 文字の大きさ
	function current() {
		return root.getAttribute( 'data-font-size' ) || 'm';
	}

	function apply( size ) {
		if ( 'm' === size ) {
			root.removeAttribute( 'data-font-size' );
		} else {
			root.setAttribute( 'data-font-size', size );
		}
		document.querySelectorAll( '.snowweb-font-switch button' ).forEach( function ( btn ) {
			btn.setAttribute( 'aria-pressed', btn.dataset.size === size ? 'true' : 'false' );
		} );
	}

	apply( current() );

	document.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '.snowweb-font-switch button' );
		if ( btn ) {
			apply( btn.dataset.size );
			try {
				localStorage.setItem( 'snowweb-font-size', btn.dataset.size );
			} catch ( e ) {}
			return;
		}

		var copy = event.target.closest( '.snowweb-share__copy' );
		if ( copy && navigator.clipboard ) {
			navigator.clipboard.writeText( copy.dataset.url ).then( function () {
				var label = copy.textContent;
				copy.textContent = copy.dataset.done;
				setTimeout( function () {
					copy.textContent = label;
				}, 2000 );
			} );
		}
	} );

	// コピー対策（ソースコードの枠は除く）
	if ( document.body.classList.contains( 'snowweb-protect' ) ) {
		var allowed = function ( target ) {
			return target && target.closest && target.closest( 'pre, code, input, textarea' );
		};
		[ 'copy', 'cut', 'contextmenu', 'dragstart' ].forEach( function ( type ) {
			document.addEventListener( type, function ( event ) {
				if ( ! allowed( event.target ) ) {
					event.preventDefault();
				}
			} );
		} );
	}
} )();

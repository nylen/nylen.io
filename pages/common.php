<?php

$GLOBALS['nylen_tags'] = array();
$GLOBALS['nylen_page_css'] = '';

function nylen_add_content_tag( $tag_name, $callback ) {
	global $nylen_tags;

	$nylen_tags[ $tag_name ] = $callback;
}

function nylen_parse_content_tags( $content ) {
	global $nylen_tags;

	$content_tag_regex = '<\s*nylen:(?P<tag>[a-z-]+)\s+(?P<params>.*?)\s*/>';

	// Tags that stand on their own line are assumed to be block-level and
	// should provide their own <p> wrapper if needed.
	$content = preg_replace(
		'#^<p>(' . $content_tag_regex . ')</p>$#m',
		'$1',
		$content
	);

	// This parsing is definitely not perfect!  If ">" appears in the tag
	// parameters, it should be escaped as "&lt;".
	$content = preg_replace_callback(
		'#' . $content_tag_regex . '#',
		function( $match ) use ( $nylen_tags ) {
			$tag    = $match['tag'];
			$params = $match['params'];
			if ( isset( $nylen_tags[ $tag ] ) ) {
				if ( ! empty( $params ) ) {
					try {
						$el = (array) @new SimpleXmlElement( "<el $params />" );
						$params = $el['@attributes'] ?? array();
						// Result is not a string; fall through to 2nd return
					} catch ( ErrorException $ex ) {
						// Probably an XML parse error
						return '<!-- ' . htmlentities( $ex->getMessage() ) . ' -->';
					}
				} else {
					$params = array();
				}
				$result = call_user_func( $nylen_tags[ $tag ], $params );
				if ( is_string( $result ) ) {
					return $result;
				}
			}
			return '<!-- ' . htmlentities( $match[0] ) . ' -->';
		},
		$content
	);

	return $content;
}

function nylen_begin_add_page_css() {
	ob_start();
}

function nylen_end_add_page_css() {
	global $nylen_page_css;
	$css = trim( ob_get_clean() );
	// Allow CSS sections to be wrapped in a <style> tag
	// This makes vim syntax highlighting work as expected
	if ( substr( $css, 0, 7 ) === '<style>' ) {
		$css = substr( $css, 7 );
	}
	if ( substr( $css, -8 ) === '</style>' ) {
		$css = substr( $css, 0, -8 );
	}
	$css = trim( $css );
	$nylen_page_css = trim( "$nylen_page_css\n\n$css" );
}

// Dynamic feature: Public repo count on my GitHub account.
nylen_add_content_tag( 'repo-count', function() {
	global $page_language;
	// This file is populated by a cron job.
	$count = trim( @file_get_contents(
		dirname( __DIR__ ) . '/nylen_github_repo_count' )
	);
	if ( empty( $count ) ) {
		if ( $page_language === 'es' ) {
			return '<strong>muchos</strong> proyectos públicos';
		}
		return '<strong>a lot</strong> of public repositories';
	} else {
		if ( $page_language === 'es' ) {
			return "<strong>$count</strong> proyectos públicos (y contando)";
		}
		return "<strong>$count</strong> public repositories (and counting)";
	}
} );

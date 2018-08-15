<?php

$GLOBALS['nylen_tags'] = array();

function nylen_add_content_tag( $tag_name, $callback ) {
	global $nylen_tags;

	$nylen_tags[ $tag_name ] = $callback;
}

function nylen_parse_content_tags( $content ) {
	global $nylen_tags;

	// This parsing is definitely not perfect!  If ">" appears in the tag
	// parameters, it should be escaped as "&lt;".
	$content = preg_replace_callback(
		'#<\s*nylen:(?P<tag>[a-z-]+)\s+(?P<params>.*?)\s*/>#',
		function ( $match ) use ( $nylen_tags ) {
			$tag    = $match['tag'];
			$params = $match['params'];
			if ( isset( $nylen_tags[ $tag ] ) ) {
				try {
					$el = (array) @new SimpleXmlElement( "<el $params />" );
					$params = $el['@attributes'];
					$result = call_user_func( $nylen_tags[ $tag ], $params );
					if ( is_string( $result ) ) {
						return $result;
					}
					// Result is not a string; fall through to 2nd return
				} catch ( ErrorException $ex ) {
					// Probably an XML parse error
					return '<!-- ' . htmlentities( $ex->getMessage() ) . ' -->';
				}
			}
			return '<!-- ' . htmlentities( $match[0] ) . ' -->';
		},
		$content
	);

	return $content;
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

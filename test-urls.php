<?php

$paths = array(
	'//',
	'/contact',
	'/contact/',
	'/contact//',
	'//contact',
	'//contact/',
	'//contact//',
	'/contact?x=y',
	'/contact/?x=y',
	'/contact//?x=y',
	'/missing',
	'/missing/',
	'/missing//',
	'/blog',
	'/blog/',
	'/blog//',
);

$max_path_length = 0;
foreach ( $paths as $path ) {
	$max_path_length = max( $max_path_length, strlen( $path ) );
}

$root = rtrim( $argv[1], '/' );

foreach ( $paths as $path ) {
	$url = $root . $path;
	$pad = str_repeat( ' ', $max_path_length - strlen( $path ) );

	print $url . $pad . ' : ';

	$curl = curl_init();
	curl_setopt_array( $curl, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER         => true,
		CURLOPT_URL            => $url,
	) );
	if ( ! ( $response = curl_exec( $curl ) ) ) {
		die( 'Error ' . curl_error( $curl ) . ': ' . curl_error( $curl ) );
	}

	$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	if ( $http_code >= 300 && $http_code <= 399 ) {
		// Redirect
		$headers = substr(
			$response,
			0,
			curl_getinfo( $curl, CURLINFO_HEADER_SIZE )
		);
		if ( preg_match( '#^Location:\s+(.*)$#mi', $headers, $matches ) ) {
			$location = trim( $matches[1] );
		} else {
			$location = '<?>';
		}
		print "$http_code → $location\n";
	} else {
		// Something else
		print "$http_code\n";
	}

	curl_close( $curl );
}

<?php
require_once dirname( dirname( __DIR__ ) ) . '/common.php';

if ( ! isset( $page_language ) ) {
	$page_language = 'en';
}

$blog_path = isset( $_GET['path'] )
	? trim( $_GET['path'], '/' )
	: '';
if ( $blog_path ) {
	$blog_path = "/$blog_path/";
} else {
	$blog_path = '/';
}

if ( $blog_path === '/' ) {
	nylen_serve_blog_index(
		$blog_path
	);

} else if ( preg_match(
	'#^/([1-9]\d{3})/$#',
	$blog_path,
	$matches
) ) {
	nylen_serve_blog_index(
		$blog_path,
		(int) $matches[1] // year
	);

} else if ( preg_match(
	'#^/([1-9]\d{3})/(1[0-2]|0[1-9])/$#',
	$blog_path,
	$matches
) ) {
	nylen_serve_blog_index(
		$blog_path,
		(int) $matches[1], // year
		(int) $matches[2]  // month
	);

} else if ( preg_match(
	'#^/([1-9]\d{3})/(1[0-2]|0[1-9])/([a-z0-9-]+)/$#',
	$blog_path,
	$matches
) ) {
	nylen_serve_blog_post(
		$blog_path,
		(int) $matches[1], // year
		(int) $matches[2], // month
		$matches[3]        // post slug
	);

} else {
	nylen_serve_error( 404 );
}

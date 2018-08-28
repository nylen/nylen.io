<?php

if ( strpos( '..', $_SERVER['SCRIPT_NAME'] ) !== false ) {
	echo 'Invalid path';
	die();
}

// TODO move this url to /contact-submit.php
if ( $_SERVER['SCRIPT_NAME'] === '/site/contact-submit.php' ) {
	require __DIR__ . '/contact-submit.php';
	die();
}

$loader_filename = __DIR__ . '/loaders' . $_SERVER['SCRIPT_NAME'] . '/index.php';
if ( file_exists( $loader_filename ) ) {
	// XXX DOES NOT WORK (`/contact/` → `/contact//`):
	//$url_normalized = preg_replace(
	//	'#/*(\?|$)#',
	//	'/$1',
	//	$_SERVER['REQUEST_URI']
	//);
	$url_pieces = preg_split(
		'#(\?|$)#',
		$_SERVER['REQUEST_URI'],
		2,
		PREG_SPLIT_DELIM_CAPTURE
	);
	$url_pieces[0] = rtrim( $url_pieces[0], '/' ) . '/';
	$url_normalized = implode( '', $url_pieces );
	if ( $_SERVER['REQUEST_URI'] !== $url_normalized ) {
		/* TODO this behaves a bit differently than the live site (Apache):
		 * |----------------|---------------------|---------------------|
		 * | URL            | Live site           | router.php          |
		 * |----------------|---------------------|---------------------|
		 * | //             | 200                 | 302 → /             |
		 * | /contact       | 301 → /contact/     | 302 → /contact/     |
		 * | /contact/      | 200                 | 200                 |
		 * | /contact//     | 200                 | 302 → /contact/     |
		 * | //contact      | 301 → /contact/     | 302 → //contact/    |
		 * | //contact/     | 200                 | 200                 |
		 * | //contact//    | 200                 | 302 → //contact/    |
		 * | /contact?x=y   | 301 → /contact/?x=y | 302 → /contact/?x=y |
		 * | /contact/?x=y  | 200                 | 200                 |
		 * | /contact//?x=y | 200                 | 302 → /contact/?x=y |
		 * | /missing       | 404                 | 404                 |
		 * | /missing/      | 404                 | 404                 |
		 * | /missing//     | 404                 | 404                 |
		 * | /blog          | 301 → /blog/        | 302 → /blog/        |
		 * | /blog/         | 200                 | 200                 |
		 * | /blog//        | 301 → /blog/        | 302 → /blog/        |
		 * |----------------|---------------------|---------------------|
		 */
		header( 'HTTP/1.1 302 Found' );
		header( 'Location: ' . $url_normalized );
		die();
	}
	require $loader_filename;
	die();
}

require __DIR__ . '/loaders/404/index.php';

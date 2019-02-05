#!/usr/bin/env php
<?php

ini_set( 'error_log', '' );
ini_set( 'display_errors', '1' );
error_reporting( E_ALL );

require_once __DIR__ . '/contact-antispam.php';

if ( $argc < 4 ) {
	throw new ErrorException(
		"Usage: $argv[0] messages.js messages-good.js messages-bad.js"
	);
}

$messages_file = $argv[1];
$messages_good_file = $argv[2];
$messages_bad_file = $argv[3];

if ( ! is_file( $messages_file ) ) {
	throw new ErrorException( "Input file '$messages_file' does not exist" );
}

foreach ( [ $messages_good_file, $messages_bad_file ] as $output_file ) {
	if ( is_file( $output_file ) ) {
		throw new ErrorException( "Output file '$output_file' already exists" );
	}
}

$messages = file( $messages_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$stats = [ 'good' => 0, 'bad' => 0 ];
$messages_good = '';
$messages_bad = '';

foreach ( $messages as $message_raw ) {
	$message = json_decode( $message_raw, true );
	if ( $message === null ) {
		error_log( "Invalid message line: '$message_raw'" );
		continue;
	}
	$spam_check = contact_message_is_spam( $message );
	$message['spam_score'] = $spam_check['score'];
	$message = json_encode(
		$message,
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . "\n";
	if ( $spam_check['is_spam'] ) {
		$stats['bad']++;
		$messages_bad .= $message;
	} else {
		$stats['good']++;
		$messages_good .= $message;
	}
}

file_put_contents( $messages_good_file, $messages_good );
file_put_contents( $messages_bad_file, $messages_bad );
error_log( json_encode( $stats ) );

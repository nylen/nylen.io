<?php

require_once __DIR__ . '/contact-messages.php';

if (
	$_SERVER['REQUEST_METHOD'] !== 'POST' ||
	! isset( $_POST['language'] )
) {
	header( 'Location: /contact/' );
	die();
}

$language = $_POST['language'];
$dest = ( $language === 'es' ? '/es/contact/' : '/contact/' );

$messages = array();

if (
	! isset( $_POST['name'] ) ||
	strlen( $_POST['name'] ) < 3
) {
	$messages[] = $contact_messages['MSG_NAME_INVALID'];
}

if (
	! isset( $_POST['email'] ) ||
	strlen( $_POST['email'] ) < 3 ||
	! preg_match( '#^[a-z0-9_.+-]+@[a-z0-9.-]+\.[a-z]{2,}$#', $_POST['email'] )
) {
	$messages[] = $contact_messages['MSG_EMAIL_INVALID'];
}

if (
	! isset( $_POST['message'] ) ||
	strlen( $_POST['message'] ) < $contact_min_length
) {
	$messages[] = $contact_messages['MSG_TEXT_TOO_SHORT'];
}

if ( empty( $messages ) ) {
	$entry = array(
		'date'     => date( 'Y-m-d H:i:s P' ),
		'ip'       => $_SERVER['REMOTE_ADDR'],
		'name'     => $_POST['name'],
		'email'    => $_POST['email'],
		'message'  => $_POST['message'],
		'language' => $language,
		'ua'       => $_SERVER['HTTP_USER_AGENT'],
	);
	$written = @file_put_contents(
		__DIR__ . '/html/contact.js',
		json_encode(
			$entry,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . "\n",
		FILE_APPEND
	);
	if ( $written ) {
		$messages[] = $contact_messages['MSG_OK'];
	} else {
		$messages[] = $contact_messages['MSG_UNKNOWN_ERROR'];
	}
}

$dest .= '?msg=' . implode( ',', $messages );
$dest .= '&name=' . urlencode( $_POST['name'] );
$dest .= '&email=' . urlencode( $_POST['email'] );
$dest .= '&message=' . urlencode( $_POST['message'] );
header( 'Location: ' . $dest );

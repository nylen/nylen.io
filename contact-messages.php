<?php

$contact_min_length = 30;

$contact_messages_data = array(
	'MSG_OK' => array(
		'en' => 'Your message has been sent.  Thanks!',
		'es' => 'Tu mensaje ha sido enviado.  Gracias!',
	),
	'MSG_NAME_INVALID' => array(
		'en' => 'Please enter your name.',
		'es' => 'Por favor escribe tu nombre.',
	),
	'MSG_EMAIL_INVALID' => array(
		'en' => 'Please enter a valid email address.',
		'es' => 'Por favor escribe una dirección de correo válida.',
	),
	'MSG_TEXT_TOO_SHORT' => array(
		'en' => "Please write a message of at least $contact_min_length characters.",
		'es' => "Por favor escribe un mensaje de al menos $contact_min_length letras.",
	),
	'MSG_UNKNOWN_ERROR' => array(
		'en' => 'An error occurred.  Please try again later.',
		'es' => 'Ocurrió un error.  Por favor intenta más tarde.',
	),
);

$contact_message_ids = array_keys( $contact_messages_data );
$contact_messages = array_flip( $contact_message_ids );

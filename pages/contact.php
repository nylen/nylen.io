<?php

require dirname( __DIR__ ) . '/contact-messages.php';

nylen_begin_add_page_css();
?><style>
.messages {
	border-left: 3px solid <?php color( 'site_borders_hr' ); ?>;
	padding: 8px 8px 8px 10px;
}
.messages.success {
	border-color: #080;
	background: #cfc;
	color: #040;
}
.messages.error {
	border-left-color: #800;
	background: #fcc;
	color: #400;
}
.messages .message + .message {
	margin-top: 4px;
}

fieldset {
	border: none;
	margin: 12px 0;
}
fieldset label {
	color: <?php color( 'site_form_label_text' ); ?>;
	font-weight: bold;
	display: block;
	font-size: 18px;
}

fieldset.inline label {
	display: inline-block;
	width: 120px;
	margin-bottom: 0;
	vertical-align: middle;
}
fieldset.inline input {
	vertical-align: middle;
}

fieldset .description {
	font-size: 14px;
	color: <?php color( 'site_subtle_text' ); ?>;
	margin-top: 4px;
}

input[type="text"], textarea {
	border: 1px solid <?php color( 'site_form_borders' ); ?>;
	font-size: 16px;
	padding: 3px;
}

input[type="text"]:focus, textarea:focus {
<?php /* TODO: add a code for this color  */ ?>
	box-shadow: 0 0 5px rgba(81, 203, 238, 1);
}

fieldset textarea {
	margin-top: 4px;
	width: 100%;
	height: 200px;
	resize: vertical;
}

input[type="submit"] {
	padding: 6px;
	font-size: 18px;
}

#privacy {
	color: <?php color( 'site_subtle_text' ); ?>;
	font-size: 14px;
	font-style: italic;
}
</style>
<?php
nylen_end_add_page_css();

nylen_add_content_tag( 'form-messages', function() {
	if ( ! isset( $_GET['msg'] ) ) {
		return '';
	}

	global $page_language, $contact_message_ids, $contact_messages_data;
	global $contact_form_saved_values;

	$messages = explode( ',', $_GET['msg'], 5 );
	$messages = array_unique( array_map( 'intval', $messages ) );
	$success = ( count( $messages ) === 1 && $messages[0] === 0 );
	$lines = array();
	$lines[] = '<p><div class="messages ' . ( $success ? 'success' : 'error' ) . '">';
	foreach ( $messages as $i ) {
		$id = $contact_message_ids[ $i ];
		$lines[] = (
			'<div class="message">'
			. $contact_messages_data[ $id ][ $page_language ]
			. '</div>'
		);
	}
	$lines[] = '</div></p>';

	ob_start();
?>
<script>
	window.history && window.history.replaceState &&
	window.history.replaceState(
		null,
		null,
		document.location.href.replace( /\?.*$/, '' )
	);
</script>
<?php
	$html = implode( "\n", $lines ) . "\n" . trim( ob_get_clean() );

	$contact_form_saved_values = array();
	foreach ( array( 'name', 'email', 'message' ) as $field ) {
		if ( isset( $_GET[ $field ] ) && ! $success ) {
			$contact_form_saved_values[ $field ] = $_GET[ $field ];
		} else {
			$contact_form_saved_values[ $field ] = '';
		}
	}

	return $html;
} );

function contact_tag_form_value_impl( $params ) {
	global $contact_form_saved_values;
	if ( ! isset( $params['field'] ) ) {
		return null; // Invalid tag
	}
	if ( ! isset( $contact_form_saved_values[ $params['field'] ] ) ) {
		return ''; // Empty field
	}
	return $contact_form_saved_values[ $params['field'] ]; // Saved value
}

nylen_add_content_tag( 'form-value', function( $params ) {
	return contact_tag_form_value_impl( $params );
} );

nylen_add_content_tag( 'form-value-htmlencode', function( $params ) {
	return htmlspecialchars( contact_tag_form_value_impl( $params ), ENT_QUOTES );
} );

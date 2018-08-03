<?php

require dirname( __FILE__ ) . '/contact-messages.php';

if ( isset( $_GET['msg'] ) ) {
	global $page_language;

	$messages = explode( ',', $_GET['msg'], 5 );
	$messages = array_unique( array_map( 'intval', $messages ) );
	$success = ( count( $messages ) === 1 && $messages[0] === 0 );
	echo '<div class="paragraph messages ' . ( $success ? 'success' : 'error' ) . '">';
	foreach ( $messages as $i ) {
		$id = $contact_message_ids[ $i ];
		echo '<div class="message">';
		echo $contact_messages_data[ $id ][ $page_language ];
		echo '</div>';
	}
	echo '</div>';

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

	global $contact_form;
	$contact_form = array();
	foreach ( array( 'name', 'email', 'message' ) as $field ) {
		if ( isset( $_GET[ $field ] ) && ! $success ) {
			$contact_form[ $field ] = $_GET[ $field ];
		} else {
			$contact_form[ $field ] = '';
		}
	}
}

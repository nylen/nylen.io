<?php
require_once dirname( dirname( __DIR__ ) ) . '/common.php';

nylen_redirect_index_php();

if ( ! nylen_is_authenticated() ) {
	nylen_serve_error( 401 );
	die();
}

setcookie( 'nylen_session', time(), 0, '/' );
$_COOKIE['nylen_session'] = time();

require dirname( dirname( __DIR__ ) ) . '/pages/admin.php';
$has_translation = false;
nylen_begin_page( '/admin/' );

$fp = fopen( dirname( dirname( __DIR__ ) ) . '/html/contact.js', 'r' );
$line_num = 0;

?>
<h2>Contact form messages</h2>
<table id="contacts">
<?php
while ( ( $line = fgets( $fp ) ) !== false ) {
	$line_num++;
	$contact = array_map( 'htmlentities', json_decode( $line, true ) );
	$contact['message'] = str_replace( "\n", "<br />\n", $contact['message'] );
	echo <<<HTML
		<tr>
			<th class="date">$contact[date]</th>
			<th class="name">$contact[name]</th>
			<th class="email">$contact[email]</th>
			<th class="number">$line_num</th>
		</tr>
		<tr>
			<td class="message" colspan="3">$contact[message]</td>
		</tr>
		<tr>
			<td class="details" colspan="3">
				lang=$contact[language];
				ip=$contact[ip];
				ua=$contact[ua]
			</td>
		</tr>

HTML;
}

echo "</table>\n";

fclose( $fp );

nylen_end_page();

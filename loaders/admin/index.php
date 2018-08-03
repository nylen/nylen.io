<?php
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/common.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/config.php';

nylen_redirect_index_php();

if (
	! isset( $_SERVER['PHP_AUTH_USER'] ) ||
	$_SERVER['PHP_AUTH_USER'] !== $config['admin_user'] ||
	$_SERVER['PHP_AUTH_PW']   !== $config['admin_pass']
) {
	nylen_serve_error( 401 );
	die();
}

nylen_begin_page( '/admin/' );

$fp = fopen( dirname( dirname( dirname( __FILE__ ) ) ) . '/html/contact.js', 'r' );
$line_num = 0;

?>
<h2>Contact form messages</h2>
<table id="contacts">
<?php
while ( ( $line = fgets( $fp ) ) !== false ) {
	$contact = array_map( 'htmlentities', json_decode( $line, true ) );
	$contact['message'] = str_replace( "\n", "<br />\n", $contact['message'] );
	echo <<<HTML
		<tr>
			<th class="date">$contact[date]</th>
			<th class="name">$contact[name]</th>
			<th class="email">$contact[email]</th>
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

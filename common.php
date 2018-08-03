<?php

$nav_items = array(
	'/'         => array( 'en' => 'About'  , 'es' => 'Inicio'   ),
	'/blog/'    => array( 'en' => 'Blog'   , 'es' => 'Blog'     ),
	'/contact/' => array( 'en' => 'Contact', 'es' => 'Contacto' ),
);

if ( ! isset( $page_language ) ) {
	$page_language = null;
}
$is_error_page = false;

function nylen_redirect_index_php() {
	// Hide index.php as an implementation detail and avoid duplicated content
	// - redirect to the canonical URL without index.php.
	$request_uri = $_SERVER['REQUEST_URI'];
	$uri_without_index = preg_replace(
		'#(^|/)index\.php/*(\?|$)#',
		'$1$2',
		$request_uri
	);
	if ( $request_uri !== $uri_without_index ) {
		header( 'HTTP/1.1 301 Moved Permanently' );
		header( 'Location: ' . $uri_without_index );
		die();
	}
}

function nylen_serve_static_page( $page_path ) {
	nylen_redirect_index_php();

	// Page header.
	nylen_begin_page( $page_path );

	// Page dynamic functionality, if any.
	if ( preg_match( '#/contact/$#', $page_path ) ) {
		require dirname( __FILE__ ) . '/page-contact.php';
	}

	// Page content.
	nylen_static_page_content( $page_path );

	// Page footer.
	nylen_end_page();
}

function nylen_canonicalize_url( $page_path ) {
	if ( $_SERVER['REQUEST_URI'] !== $page_path ) {
		header( 'HTTP/1.1 301 Moved Permanently' );
		header( 'Location: ' . $page_path );
		die();
	}
}

function nylen_blog_path_to_page_path( $blog_path ) {
	global $page_language;

	if ( $page_language === 'es' ) {
		return '/es/blog' . $blog_path;
	} else {
		return '/blog' . $blog_path;
	}
}

function nylen_serve_blog_index( $blog_path, $year = null, $month = null ) {
	$page_path = nylen_blog_path_to_page_path( $blog_path );
	nylen_canonicalize_url( $page_path );

	// Page header.
	nylen_begin_page( $page_path );

	// Placeholder page content.
	global $page_language;
	nylen_static_page_content(
		$page_language === 'es' ? '/es/blog/' : '/blog/'
	);
	echo '<!-- ' . json_encode( array(
		'type'  => 'blog index',
		'lang'  => $GLOBALS['page_language'],
		'path'  => $page_path,
		'year'  => $year,
		'month' => $month,
	), JSON_UNESCAPED_SLASHES ) . ' -->';

	// Page footer.
	nylen_end_page();
}

function nylen_serve_blog_post( $blog_path, $year, $month, $slug ) {
	$page_path = nylen_blog_path_to_page_path( $blog_path );
	nylen_canonicalize_url( $page_path );

	// Page header.
	nylen_begin_page( $page_path );

	// Placeholder page content.
	print json_encode( array(
		'type'  => 'blog post',
		'lang'  => $GLOBALS['page_language'],
		'path'  => $page_path,
		'year'  => $year,
		'month' => $month,
	), JSON_UNESCAPED_SLASHES );

	// Page footer.
	nylen_end_page();
}

function nylen_serve_error( $code ) {
	global $page_language, $is_error_page;

	$is_error_page = true;

	switch ( $code ) {
		case 401:
			header( 'WWW-Authenticate: Basic realm="restricted"' );
			header( 'HTTP/1.1 401 Unauthorized' );
			break;

		case 404:
			header( 'HTTP/1.1 404 Not Found' );
			break;

		case 500:
		default:
			header( 'HTTP/1.1 500 Internal Server Error' );
			break;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], '/es/' ) === false ) {
		nylen_serve_static_page( "/$code/", true );
	} else {
		$page_language = 'es';
		nylen_serve_static_page( "/es/$code/", true );
	}
}

function nylen_static_page_content( $page_path ) {
	// The page content will come from a Markdown source file that is converted
	// to HTML "just in time" when the Markdown source is updated.

	$filename_base = str_replace( '/', '-', trim( $page_path, '/' ) );
	if ( $filename_base === '' ) {
		$filename_base = 'index';
	} else if ( $filename_base === 'es' ) {
		$filename_base = 'es-index';
	}

	$md_file   = dirname( __FILE__ ) . '/md/' . $filename_base . '.md';
	$html_file = dirname( __FILE__ ) . '/html/' . $filename_base . '.html';

	nylen_regenerate_html_if_needed(
		$md_file,
		$html_file,
		$filename_base
	);
}

function nylen_regenerate_html_if_needed(
	$md_file,
	$html_file,
	$page_for_logs,
	$echo = true
) {
	if (
		! file_exists( $html_file ) ||
		filemtime( $md_file ) > filemtime( $html_file )
	) {
		// Regenerate this HTML file from its Markdown source.
		$fp_html = fopen( $html_file, 'c' );

		if ( ! flock( $fp_html, LOCK_EX | LOCK_NB, $blocked ) ) {
			if ( $blocked ) {
				// Someone else is already generating the HTML.  Wait.
				if ( ! flock( $fp_html, LOCK_EX ) ) {
					// An error occurred.
					error_log( "generate page: $page_for_logs: waiting: flock FAILED" );
					fclose( $fp_html );
					@unlink( $html_file );
					$content = 'Failed to generate page content.';
				} else {
					// Finished - we can read the HTML content now.
					error_log( "generate page: $page_for_logs: waited" );
					flock( $fp_html, LOCK_UN );
					fclose( $fp_html );
					$content = file_get_contents( $html_file );
				}

			} else {
				// An error occurred.
				error_log( "generate page: $page_for_logs: flock FAILED" );
				fclose( $fp_html );
				@unlink( $html_file );
				$content = 'Failed to generate page content.';
			}

		} else {
			// We got the lock.  Convert Markdown to HTML and save the result.
			error_log( "generate page: $page_for_logs: start" );
			require_once dirname( __FILE__ ) . '/vendor/autoload.php';
			$html = \Michelf\MarkdownExtra::defaultTransform( file_get_contents( $md_file ) );

			// Embed known images.
			$html = preg_replace_callback(
				'#src="(/[^"]+\.(gif|jpg|png))"#',
				function( $matches ) {
					$src = dirname( dirname( __FILE__ ) ) . $matches[1];
					$data = base64_encode( file_get_contents( $src ) );
					$type = mime_content_type( $src );
					return "src=\"data:$type;base64,$data\"";
				},
				$html
			);

			ftruncate( $fp_html, 0 );
			fwrite( $fp_html, $html );
			flock( $fp_html, LOCK_UN );
			fclose( $fp_html );
			error_log( "generate page: $page_for_logs: done" );
			$content = $html;
		}

	} else {
		// The generated HTML file is up to date.
		$content = file_get_contents( $html_file );
	}

	// Dynamic feature: GitHub repo count.
	$content = str_replace(
		'<code>{repo_count}</code>',
		nylen_gh_repo_count(),
		$content
	);

	// Dynamic feature: Fill previous contact form values.
	global $contact_form;
	$content = str_replace(
		'{contact_name}',
		htmlentities( $contact_form['name'] ),
		$content
	);
	$content = str_replace(
		'{contact_email}',
		htmlentities( $contact_form['email'] ),
		$content
	);
	$content = str_replace(
		'{contact_message}',
		htmlentities( $contact_form['message'] ),
		$content
	);

	if ( $echo ) {
		print $content;
	} else {
		return $content;
	}
}

function nylen_begin_page( $page_path, $page_title = '' ) {
	global $nav_items, $page_language, $is_error_page;

	if ( ! isset( $page_language ) ) {
		if ( preg_match( '#^/es/#', $page_path ) ) {
			$page_language = 'es';
		} else {
			$page_language = 'en';
		}
	}

	if ( ! $page_title && isset( $nav_items[ $page_path ] ) ) {
		$page_title = $nav_items[ $page_path ][ $page_language ];
	}
	// TODO page title for non-root blog indices
	$page_title_full = 'James Nylen';
	if ( $page_title ) {
		$page_title_full = $page_title . ' &ndash; ' . $page_title_full;
	}
	ob_start();
?>
<!DOCTYPE html>
<html lang="<?php echo $page_language; ?>">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo $page_title_full; ?></title>
		<style>
<?php /*
COLORS

almost black: #0d161f
  NOTE: not currently used
  on white      18.23:1 (AAA)
  on light blue  8.09:1 (AAA)

dark blue: #1c2e40
  on white      13.87:1 (AAA)
  on light blue  6.15:1 (AA)

blue gray: #2d4a66
  on white       9.20:1 (AAA)
  on light blue  4.08:1 (F)

light blue gray: #4879a6
  on white       4.60:1 (AA)

light blue: #6bb2f5
  on white       2.26:1 (F)
  on dark blue   6.15:1 (AA)

link text: #2e64eb
  on white       5.07:1 (AA)
  on dark blue   2.73:1 (F)
 */
?>
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}
body {
	background: #fff;
	color: #1c2e40;
	font-family: "Open Sans", sans-serif;
	font-weight: 400;
	font-size: 16px;
	margin: 20px auto;
	padding: 0 20px;
	max-width: 660px;
}
a {
	color: #2e64eb;
	text-decoration: none;
}
a:hover {
	text-decoration: underline;
}
img {
	border: none;
}
h1, h2, h3 {
	font-weight: 600;
	margin-top: 1em;
	margin-bottom: 0.5em;
}
h1 {
	font-size: 36px;
	line-height: 36px;
	text-transform: uppercase;
}
h2 {
	font-size: 24px;
	line-height: 24px;
}
h3 {
	font-size: 18px;
	line-height: 18px;
}
p, .paragraph {
	line-height: 20px;
	margin: 18px 0;
}
ul, ol {
	margin: 18px 0;
}
ul li, ol li {
	margin: 5px 0 5px 24px;
	line-height: 18px;
}

#site-title {
	border-bottom: 2px solid #2d4a66;
	padding-bottom: 8px;
	margin: 0;
}

#nav {
	display: block;
	margin: 12px 0 18px;
}

#nav li {
	list-style: none;
	display: inline;
	padding: 0;
	margin: 0;
}

#nav li a {
	display: inline-block;
	font-weight: bold;
	padding: 8px 12px;
	color: #1c2e40;
}

#nav li a:hover {
	background: #6bb2f5;
	text-decoration: none;
}

#nav li.active a {
	color: #fff;
	background: #1c2e40;
}

#nav li.active a:hover {
	color: #6bb2f5;
}

#nav li.switch-language {
	float: right;
}
#nav li.switch-language a {
	background: #fff;
	font-weight: normal;
	font-size: 14px;
	color: #2d4a66;
}
#nav li.switch-language a:hover {
	background: #6bb2f5;
	color: #1c2e40;
}
hr {
	border-width: 0;
	border-bottom: 2px solid #2d4a66;
	padding: 0;
	margin: 24px 0;
}

/* Begin page-specific styles */
<?php if ( preg_match( '#/contact/$#', $page_path ) ) { ?>
.messages {
	border-left: 3px solid #1c2e40;
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

fieldset {
	border: none;
	margin: 12px 0;
}
fieldset label {
	font-weight: 600;
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
	color: #4879a6;
	margin-top: 4px;
}

input[type="text"], textarea {
	border: 1px solid #4879a6;
	font-size: 16px;
	padding: 3px;
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
	color: #4879a6;
	font-size: 14px;
	font-style: italic;
}
<?php } else if ( preg_match( '#/blog/#', $page_path ) ) { ?>
.under-construction {
	margin: 30px 0;
	text-align: center;
}
<?php } else if ( preg_match( '#^/admin/$#', $page_path ) ) { ?>
table#contacts th {
	text-align: left;
}
table#contacts .date {
	min-width: 215px;
}
table#contacts .number {
	min-width: 20px;
	text-align: right;
}
table#contacts .date,
table#contacts .name,
table#contacts .email,
table#contacts .number {
	padding-top: 20px;
	padding-bottom: 4px;
}
table#contacts .message {
	padding: 2px 6px;
	margin-left: 6px;
	border-left: 2px solid #2d4a66;
}
table#contacts .details {
	font-style: italic;
	color: #4879a6;
	font-size: 85%;
	padding-top: 4px;
}
<?php } ?>
/* End page-specific styles */

footer {
	margin-top: 24px;
	border-top: 2px solid #2d4a66;
	padding-top: 12px;
	font-size: 14px;
	color: #4879a6;
	text-align: center;
}

@media screen and (max-width: 660px) {
	footer .footer-3 {
		display: block;
	}
}
@media screen and (max-width: 480px) {
	footer .footer-2 {
		display: block;
	}
}
		</style>
	</head>
	<body>
		<h1 id="site-title">
			James Nylen
		</h1>
		<ul id="nav">
<?php
	foreach ( $nav_items as $nav_path => $nav_texts ) {
		if ( $page_language === 'es' ) {
			$nav_path = '/es' . $nav_path;
		}
		echo '<li' . ( $nav_path === $page_path ? ' class="active"' : '' ) . '>';
		echo '<a href="' . $nav_path . '">' . $nav_texts[ $page_language ] . '</a>';
		echo '</li>';
		echo "\n";
	}
	if ( ! $is_error_page ) {
		if ( $page_language === 'es' ) {
			$switch_language_url   = preg_replace( '#^/es/#', '/', $page_path );
			$switch_language_text  = 'In English';
			$switch_language_title = 'View this page in English';
		} else {
			$switch_language_url   = '/es' . $page_path;
			$switch_language_text  = 'En español';
			$switch_language_title = 'Ver esta página en español';
		}
		echo '<li class="switch-language">';
		echo '<a'
			. ' href="' . $switch_language_url . '"'
			. ' title="' . $switch_language_title . '"'
			. '>' . $switch_language_text . '</a>';
		echo '</li>';
		echo "\n";
	}
?>
		</ul>
		<div id="content">
<?php
}

function nylen_end_page() {
	global $page_language;
?>

		</div><!-- #content -->
		<footer>
<?php
	switch ( $page_language ) {
		case 'es':
?>
			Derechos &copy; 2018 James Nylen.
			<span class="footer-2">
				Hecho a mano con <a href="https://es.wikipedia.org/wiki/Vim">vim</a>.
			</span>
			<span class="footer-3">Tamaño de esta página: {PAGE_SIZE}.</span>
<?php
			break;

		default:
?>
			Copyright &copy; 2018 James Nylen.
			<span class="footer-2">
				Hand-coded with <a href="http://www.vim.org/">vim</a>.
			</span>
			<span class="footer-3">Page size: {PAGE_SIZE}.</span>
<?php
			break;
	}
?>
		</footer>
	</body>
</html>
<?php
	$content = ob_get_clean();
	$content = str_replace(
		'{PAGE_SIZE}',
		number_format( strlen( $content ) / 1024, 1 ) . ' KB',
		$content
	);
	echo $content;
}

function nylen_gh_repo_count() {
	global $page_language;
	// This file is populated by a cron job.
	$count = trim( @file_get_contents( __DIR__ . '/nylen_github_repo_count' ) );
	if ( empty( $count ) ) {
		if ( $page_language === 'es' ) {
			return '<strong>muchos</strong> proyectos públicos';
		}
		return '<strong>a lot</strong> of public repositories';
	} else {
		if ( $page_language === 'es' ) {
			return "<strong>$count</strong> proyectos públicos (y contando)";
		}
		return "<strong>$count</strong> public repositories (and counting)";
	}
}

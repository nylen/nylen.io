<?php

$nav_items = array(
	'/'         => array( 'en' => 'About'  , 'es' => 'Inicio'   ),
	'/blog/'    => array( 'en' => 'Blog'   , 'es' => 'Blog'     ),
	'/contact/' => array( 'en' => 'Contact', 'es' => 'Contacto' ),
);

if ( ! isset( $page_language ) ) {
	$page_language = null;
}
$has_translation = true;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/colors.php';
require_once __DIR__ . '/pages/common.php';

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

function nylen_serve_page( $page_path ) {
	nylen_redirect_index_php();

	// Load page-specific dynamic functionality, if any.
	$php_file = __DIR__ . '/pages/' . preg_replace(
		'#^es-#',
		'',
		nylen_page_filename( $page_path, 'php' )
	);

	if ( file_exists( $php_file ) ) {
		require $php_file;
	}

	// Print the page header.
	nylen_begin_page( $page_path );

	// Print the page content.
	nylen_echo_page_content( $page_path );

	// Print the page footer.
	nylen_end_page();
}

function nylen_is_authenticated() {
	global $config;
	return (
		isset( $_SERVER['PHP_AUTH_USER'] ) &&
		$_SERVER['PHP_AUTH_USER'] === $config['admin_user'] &&
		$_SERVER['PHP_AUTH_PW']   === $config['admin_pass']
	);
}

function nylen_is_session() {
	return ! empty( $_COOKIE['nylen_session'] );
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

	// Load dynamic functionality and styles common to all blog pages.
	require __DIR__ . '/pages/blog-common.php';

	// Print the page header.
	nylen_begin_page( $page_path );

	// Print some placeholder page content.
	global $page_language;
	nylen_echo_page_content(
		$page_language === 'es' ? '/es/blog/' : '/blog/'
	);
	echo '<!-- ' . json_encode( array(
		'type'  => 'blog index',
		'lang'  => $GLOBALS['page_language'],
		'path'  => $page_path,
		'year'  => $year,
		'month' => $month,
	), JSON_UNESCAPED_SLASHES ) . ' -->';

	// Print the page footer.
	nylen_end_page();
}

function nylen_serve_blog_post( $blog_path, $year, $month, $slug ) {
	$page_path = nylen_blog_path_to_page_path( $blog_path );
	nylen_canonicalize_url( $page_path );

	// Load dynamic functionality and styles common to all blog pages.
	require __DIR__ . '/pages/blog-common.php';

	// Print the page header.
	nylen_begin_page( $page_path );

	// Print some placeholder page content.
	print json_encode( array(
		'type'  => 'blog post',
		'lang'  => $GLOBALS['page_language'],
		'path'  => $page_path,
		'year'  => $year,
		'month' => $month,
	), JSON_UNESCAPED_SLASHES );

	// Print the page footer.
	nylen_end_page();
}

function nylen_serve_error( $code ) {
	global $page_language, $has_translation;

	$has_translation = false;

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
		nylen_serve_page( "/$code/", true );
	} else {
		$page_language = 'es';
		nylen_serve_page( "/es/$code/", true );
	}
}

function nylen_page_filename( $page_path, $ext = '' ) {
	// Get a filename associated with the page.  At the moment this could be
	// .md, .html, or .php, or we can return the base filename with no
	// extension.

	$filename_base = str_replace( '/', '-', trim( $page_path, '/' ) );
	if ( $filename_base === '' ) {
		$filename_base = 'index';
	} else if ( $filename_base === 'es' ) {
		$filename_base = 'es-index';
	}

	if ( ! $ext ) {
		return $filename_base;
	}
	return $filename_base . '.' . trim( $ext, '.' );
}

function nylen_echo_page_content( $page_path ) {
	// The page content will come from a Markdown source file that is converted
	// to HTML "just in time" when the Markdown source is updated.

	$filename_base = nylen_page_filename( $page_path );

	$md_file   = dirname( __FILE__ ) . '/md/' . $filename_base . '.md';
	$html_file = dirname( __FILE__ ) . '/html/' . $filename_base . '.html';

	$content = nylen_regenerate_html_if_needed(
		$md_file,
		$html_file,
		$filename_base
	);

	// Evaluate dynamic content tags.
	$content = nylen_parse_content_tags( $content );

	echo $content;
}

function nylen_regenerate_html_if_needed(
	$md_file,
	$html_file,
	$page_for_logs
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

			// Static content processing:  Embed known images.
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

	return $content;
}

function nylen_begin_page( $page_path, $page_title = '' ) {
	global $nav_items, $page_language, $has_translation, $nylen_page_css;

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
<?php

?>
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}
body {
	background: #fff;
	color: <?php color( 'site_body_text' ); ?>;
<?php /*
Sans-serif system font stack:
https://nanx.me/blog/post/the-return-of-the-native-font-stack/
*/ ?>
	font-family: -apple-system,
		BlinkMacSystemFont,
		"Segoe UI",
		Roboto,
		"Helvetica Neue",
		Arial,
		sans-serif;
	font-weight: normal;
	font-size: 16px;
	margin: 20px auto;
	padding: 0 20px;
	max-width: 660px;
}
a {
	color: <?php color( 'site_link_text' ); ?>;
	text-decoration: none;
}
a:hover {
	text-decoration: underline;
}
img {
	border: none;
}
h1, h2, h3 {
	font-weight: bold;
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
	color: <?php color( 'site_h2_text' ); ?>;
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
pre, blockquote, code, i.example {
	background: <?php color( 'site_code_bg' ); ?>;
}
pre, blockquote {
	padding: 12px;
	overflow-x: auto;
	margin: 18px 0;
}
pre {
	font-size: 14px;
	line-height: 19px;
}
code, i.example {
	border-radius: 3px;
	padding: 3px 4px 2px 4px;
}
code {
<?php /*
Monospace system font stack:
https://www.client9.com/css-system-font-stack-monospace-v2/
*/ ?>
	font-family:
		"SFMono-Regular",
		Consolas,
		"Liberation Mono",
		Menlo,
		Courier,
		monospace;
	font-size: 14px;
}
i.example {
	font-style: normal;
}
blockquote p {
	margin: 0;
}
blockquote p + p {
	margin-top: 18px;
}

#site-title {
	color: <?php color( 'site_title_text' ); ?>;
	border-bottom: 2px solid <?php color( 'site_borders_hr' ); ?>;
	padding-bottom: 8px;
	margin: 0;
	position: relative;
	padding-left: 55px;
}

#logo {
	display: block;
	position: absolute;
	top: 0;
	left: 0;
	width: 48px;
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
	color: <?php color( 'site_nav_link_text' ); ?>;
}
#nav li a:hover {
	background: <?php color( 'site_nav_hover_bg' ); ?>;
	color: <?php color( 'site_nav_hover_text' ); ?>;
	text-decoration: none;
}
#nav li.active a {
	background: <?php color( 'site_nav_active_bg' ); ?>;
	color: <?php color( 'site_nav_active_text' ); ?>;
}
#nav li.active a:hover {
	background: <?php color( 'site_nav_active_hover_bg' ); ?>;
	color: <?php color( 'site_nav_active_hover_text' ); ?>;
}
#nav li.switch-language {
	float: right;
}
#nav li.switch-language a {
	font-weight: normal;
	font-size: 14px;
	color: <?php color( 'site_nav_subtle_text' ); ?>;
}
#nav li.switch-language a:hover {
	background: <?php color( 'site_nav_subtle_hover_bg' ); ?>;
	color: <?php color( 'site_nav_subtle_hover_text' ); ?>;
}

hr {
	border-width: 0;
	border-bottom: 2px solid <?php color( 'site_borders_hr' ); ?>;
	padding: 0;
	margin: 24px 0;
}

<?php if ( ! empty( $nylen_page_css ) ) {
	echo "/* Begin page-specific styles */\n";
	echo "$nylen_page_css\n";
	echo "/* End page-specific styles */\n";
} ?>

footer {
	margin-top: 24px;
	border-top: 2px solid <?php color( 'site_borders_hr' ); ?>;
	padding-top: 12px;
	font-size: 14px;
	color: <?php color( 'site_subtle_text' ); ?>;
	text-align: center;
}

span[role=separator]::before {
	padding: 0 0.2em;
	content: '\2022';
}

@media screen and (max-width: 660px) {
	footer .sep-about-size::before {
		padding-top: 0.3em;
		content: '';
		display: block;
	}
}

@media screen and (max-width: 412px) {
	footer .sep-copyright-about::before {
		padding-top: 0.3em;
		content: '';
		display: block;
	}
}
		</style>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
		<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
		<meta name="msapplication-TileColor" content="#ffc40d">
		<meta name="theme-color" content="#ffffff">
	</head>
	<body>
		<h1 id="site-title">
			<svg id="logo" viewBox="19.8 30 104 104">
				<path id="logo-cloud" d="m112.9 71.2c-0.7 0-1.5 0.1-2.2 0.2v-0.2c0-8.3-6.8-15.1-15.1-15.1-1.5 0-2.9 0.2-4.3 0.6v-0.6c0-10.7-8.7-19.4-19.4-19.4-8.7 0-16.1 5.7-18.6 13.8-1.7-0.5-3.4-0.8-5.2-0.8-9.5 0-17.3 7.8-17.3 17.3v0.2c-6.1 1-10.8 6.3-10.8 12.8 0 7.1 5.2697 12.6 12.47 12.6 33.969-0.02724 37.825 0 80.33 0 6 0 10.8-4.7 10.8-10.7 0.1-5.8-4.8-10.7-10.7-10.7z" fill="<?php color( 'site_logo_cloud' ); ?>" />
				<path id="logo-bolt" d="m53.704 100.92 13.706-1.8593c0.23065-3e-3 0.34516-0.0344 0.22617 0.21093-11.653 22.522-8.1384 15.598-11.653 22.522-0.28496 0.49997 0.06192 0.86942 0.39429 1.1194 0.2143 0.10841 0.26307 0.1417 0.46058 0.13051l0.38992-0.0221c0.18581-0.0105 0.32248-0.10289 0.60744-0.35287l25.646-30.873c0.42744-0.62496 0-1.2499-0.7124-1.2499l-12.858 0.50003c-0.0958 0.0039-0.0666-0.07807-0.01787-0.1679l10.881-26.83c0.08724-0.49858-0.09391-0.62496-0.28496-0.87494-0.11509-0.15059-0.42744-0.24998-0.56992-0.24998-0.28496 0-0.7124 0.12499-0.99736 0.49997l-25.931 36.247c-0.42744 0.49996 0 1.2499 0.7124 1.2499z" fill="<?php color( 'site_logo_bolt' ); ?>" stroke-width="1.3345" />
			</svg>
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
	if ( $has_translation ) {
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
			Derechos &copy; 2018 James Nylen
			<span role="separator" class="sep-copyright-about"></span>
			<a href="/es/this-site/">Sobre este sitio</a>
			<span role="separator" class="sep-about-size"></span>
			Tamaño de esta página: {PAGE_SIZE}
<?php
			break;

		default:
?>
			Copyright &copy; 2018 James Nylen
			<span role="separator" class="sep-copyright-about"></span>
			<a href="/this-site/">About this site</a>
			<span role="separator" class="sep-about-size"></span>
			Page size: {PAGE_SIZE}
<?php if ( nylen_is_session() ) { ?>
			<span role="separator" class="sep-size-admin"></span>
			<a href="/admin/">It me</a>
<?php }
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

<?php

$nylen_colors = array(
	'almost_black'    => '0d161f',
	# NOTE: not currently used
	# on white        18.23:1 (AAA)
	# on light blue    8.09:1 (AAA)

	'white'           => 'ffffff',

	'dark_blue'       => '1c2e40',
	# on white        13.87:1 (AAA)
	# on light blue    6.15:1 (AA)

	'blue_gray'       => '2d4a66',
	# on white         9.20:1 (AAA)
	# on light blue    4.08:1 (F)

	'dark_red'        => '770e42',
	# on white         5.66:1 (AA)
	
	'light_blue_gray_fg' => '4879a6',
	# on white         4.60:1 (AA)

	'light_blue'      => '6bb2f5',
	# on white         2.26:1 (F)
	# on dark blue     6.15:1 (AA)

	'bright_blue'     => '2e64eb',
	# on white         5.07:1 (AA)
	# on dark blue     2.73:1 (F)

	# https://meyerweb.com/eric/tools/color-blend/#C1CDD9:FFFFFF:5:hex
	'light_blue_gray_bg1' => 'c1cdd9',
	'light_blue_gray_bg2' => 'd6dee6',
	'light_blue_gray_bg3' => 'eaeef2',

	'bright_green'    => '27e3a5',
	'dark_orange'     => 'ce481c',

	# Map site elements to color names

	'site_logo_cloud'      => 'light_blue_gray_bg1',
	'site_logo_bolt'       => 'dark_blue',

	'site_title_text'      => 'dark_blue',
	'site_body_text'       => 'dark_blue',
	'site_h2_text'         => 'dark_orange',
	'site_link_text'       => 'bright_blue',
	'site_subtle_text'     => 'light_blue_gray_fg',
	'site_code_bg'         => 'light_blue_gray_bg3',

	# nav menu: regular links
	'site_nav_link_bg'     => 'white', // not used
	'site_nav_link_text'   => 'dark_red',
	# nav menu: regular links + hover
	'site_nav_hover_bg'    => 'light_blue_gray_bg2',
	'site_nav_hover_text'  => 'dark_red',
	# nav menu: active link
	'site_nav_active_bg'   => 'dark_red',
	'site_nav_active_text' => 'white',
	# nav menu: active link + hover
	'site_nav_active_hover_bg'   => 'dark_red',
	'site_nav_active_hover_text' => 'light_blue_gray_bg1',
	# nav menu: "subtle" links (translate page link)
	'site_nav_subtle_bg'   => 'white', // not used
	'site_nav_subtle_text' => 'light_blue_gray_fg',
	# nav menu: "subtle" links + hover
	'site_nav_subtle_hover_bg'   => 'light_blue_gray_bg2',
	'site_nav_subtle_hover_text' => 'dark_red',

	'site_borders_hr'      => 'light_blue_gray_fg',

	'site_form_borders'    => 'light_blue_gray_fg',
	'site_form_label_text' => 'dark_orange',
);

function color( $name ) {
	global $nylen_colors;

	if (
		substr( $name, 0, 5 ) === 'site_' &&
		isset( $nylen_colors[ $name ] )
	) {
		// Site element ('site_element_name' => 'color_name')
		return color( $nylen_colors[ $name ] );
	}

	if ( isset( $nylen_colors[ $name ] ) ) {
		$color = $nylen_colors[ $name ];
		echo '#' . $color;
	} else {
		// Color not found - show a random color and the requested color name
		printf(
			'#%s /* %s */',
			strtoupper( substr( md5( $name ), 0, 6 ) ),
			$name
		);
	}
}

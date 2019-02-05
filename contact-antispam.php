<?php

function contact_message_is_spam( $message ) {
	$rules_file = __DIR__ . '/antispam.txt';

	if ( ! is_file( $rules_file ) ) {
		throw new ErrorException( 'Spam rules file not found' );
	}

	$rules_raw = file( $rules_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( count( $rules_raw ) < 2 ) {
		throw new ErrorException( 'Invalid rules file' );
	}

	$rules = [];
	$spam_score = intval( $rules_raw[0] );

	if ( $spam_score <= 0 ) {
		throw new ErrorException( 'Invalid spam score in rules file' );
	}

	$message_fields = [ 'ip', 'name', 'email', 'message', 'language', 'ua' ];

	for ( $i = 1; $i < count( $rules_raw ); $i++ ) {
		$rule_pieces = explode( ':', $rules_raw[ $i ], 3 );
		if (
			count( $rule_pieces ) !== 3 ||
			intval( $rule_pieces[0] ) === 0 ||
			! in_array( $rule_pieces[1], $message_fields, true ) ||
			! preg_match( '/^#.*#i?$/', $rule_pieces[2] ) ||
			// Validate regex: https://stackoverflow.com/a/12941133/106302
			! @preg_match( $rule_pieces[2], null ) === false
		) {
			$line = $i + 1;
			throw new ErrorException( "Invalid spam rule on line $line" );
		}
		$rules[] = [
			'score' => intval( $rule_pieces[0] ),
			'field' => $rule_pieces[1],
			'match' => $rule_pieces[2],
		];
	}

	foreach ( $message_fields as $field ) {
		if ( ! isset( $message[ $field ] ) ) {
			throw new ErrorException( "Missing message field: $field" );
		}
	}

	$score = 0;
	$matches = [];
	foreach ( $rules as $rule ) {
		if ( preg_match( $rule['match'], $message[ $rule['field'] ] ) ) {
			$score += $rule['score'];
			$matches[] = $rule;
		}
	}
	return [
		'is_spam' => ( $score >= $spam_score ),
		'score'   => $score,
		'matches' => $matches,
	];
}

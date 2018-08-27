<?php

nylen_begin_add_page_css();
?><style>
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
	border-left: 2px solid <?php color( 'site_borders_hr' ); ?>;
}
table#contacts .details {
	font-style: italic;
	color: <?php color( 'site_subtle_text' ); ?>;
	font-size: 85%;
	padding-top: 4px;
}
</style>
<?php
nylen_end_add_page_css();

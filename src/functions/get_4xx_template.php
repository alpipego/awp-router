<?php

namespace Alpipego\AWP\Router;

if (function_exists(__NAMESPACE__ . '\\get_405_template')) {
	return;
}

function get_4xx_template(int $code) : string
{
	if ( ! preg_match('/^4[0-9]{2}$/', $code)) {
		return get_query_template('index');
	}

	add_filter('405_template_hierarchy', function ($templates) {
		$templates[] = '4xx.php';

		return $templates;
	}, 1);

	return get_query_template((string)$code);
}

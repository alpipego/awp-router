<?php

namespace Alpipego\AWP\Router;

if (function_exists(__NAMESPACE__ . '\\get_4xx_template')) {
	return;
}

function get_4xx_template(int $code) : string
{
	if ( ! preg_match('/^4[0-9]{2}$/', $code)) {
		return get_query_template('index');
	}

	foreach (get_4xx_codes() as $statusCode) {
		add_filter($statusCode . '_template_hierarchy', function ($templates) use ($statusCode) {
			$templates[] = '4xx.php';

			return $templates;
		}, 1);
	}

	return get_query_template((string)$code);
}

function get_4xx_codes() : array
{
	$statusCodes = [];
	for ($i = 400; $i < 432; $i++) {
		if (in_array($i, [419, 420, 422, 423, 424, 427, 430])) {
			continue;
		}
		$statusCodes[] = $i;
	}
	$statusCodes[] = 451;

	return $statusCodes;
}

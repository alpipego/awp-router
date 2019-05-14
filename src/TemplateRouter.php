<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

class TemplateRouter implements TemplateRouterInterface
{
	public function get(string $route, callable $callable)
	{
		//		echo '<code><pre>';
		//		var_dump($route);
		//		echo '</pre></code>';
	}

	public function template(string $type, string $name, array $postTypes, callable $callable)
	{
		add_action('init', function () use ($type, $name, $postTypes) {
			array_walk($postTypes, function (string $postType) use ($type, $name) {
				if ( ! post_type_exists($postType)) {
					return;
				}
				add_filter("theme_{$postType}_templates", function (array $templates) use ($type, $name) {
					$templates[(string)apply_filters('awp/router/template/file', $type.'.php')] = $name;

					return $templates;
				});
			});
		});
	}
}

<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

class TemplateRouter implements TemplateRouterInterface
{
	private $conditions = [];
	private $templateRoutes = [];

	public function __construct()
	{
		add_action('parse_query', function (\WP_Query $query) {
			if ( ! $query->is_main_query() || is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX)) {
				return;
			}
			$this->resolveTemplate($query);
			$this->resolveCondition($query);
		});
	}

	public function condition(callable $condition, callable $callable)
	{
		$this->conditions[] = [
			'condition' => $condition,
			'callable'  => $callable,
		];
	}

	public function template(string $type, string $name, array $postTypes, callable $callable)
	{
		add_action('init', function () use ($type, $name, $postTypes, $callable) {
			array_walk($postTypes, function (string $postType) use (&$type, $name, $callable) {
				if ( ! post_type_exists($postType)) {
					return;
				}
				add_filter("theme_{$postType}_templates", function (array $templates) use (&$type, $name) {
					$templates[$this->parseTemplateFile($type)] = $name;

					return $templates;
				});
			});

			$this->templateRoutes[md5($type . implode('', $postTypes))] = [
				'template'  => $type,
				'callable'  => $callable,
				'postTypes' => $postTypes,
			];
		});
	}

	private function resolveTemplate(\WP_Query $query)
	{
		$templates = array_combine(array_keys($this->templateRoutes), array_column($this->templateRoutes, 'template'));
		if ( ! is_page_template($templates)) {
			return;
		}

		$routeKey = array_search(get_post_meta($query->queried_object_id, '_wp_page_template', true), $templates, true);

		if (empty($routeKey)) {
			return;
		}

		add_filter('template_include', function (string $template) use ($routeKey, $query) {
			$this->templateRoutes[$routeKey]['callable']($query);
			require_once $template;

			return false;
		});
	}

	private function parseTemplateFile(string $template) : string
	{
		if (strpos($template, '.php') !== strlen($template) - 4) {
			$template = $template . '.php';
		}

		return (string)apply_filters('awp/router/template/file', $template);
	}

	private function resolveCondition(\WP_Query $query)
	{
		foreach (array_reverse($this->conditions) as $cond) {
			if ($cond['condition']($query)) {
				add_filter('template_include', function (string $template) use ($query, $cond) {
					$cond['callable']($query);
					require_once $template;

					return false;
				});
			}

			return;
		}
	}
}

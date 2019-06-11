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
			$this->defaultController($query);
		});
	}

	public function condition(callable $condition, callable $callable)
	{
		$this->conditions[] = [
			'condition' => $condition,
			'callable'  => $callable,
		];
	}

	public function template(string $template, string $name, array $postTypes, callable $callable)
	{
		add_action('init', function () use ($template, $name, $postTypes, $callable) {
			array_walk($postTypes, function (string $postType) use (&$type, $name, $callable) {
				if ( ! post_type_exists($postType)) {
					return;
				}
				add_filter("theme_{$postType}_templates", function (array $templates) use (&$type, $name) {
					$templates[$this->parseTemplateFile($type)] = $name;

					return $templates;
				});
			});

			$this->templateRoutes[md5($template . implode('', $postTypes))] = [
				'template'  => $this->parseTemplateFile($type),
				'callable'  => $callable,
				'postTypes' => $postTypes,
			];
		});
	}

	private function resolveTemplate(\WP_Query $query)
	{
		$routeKey = array_search(
			get_post_meta($query->queried_object_id, '_wp_page_template', true),
			array_combine(array_keys($this->templateRoutes), array_column($this->templateRoutes, 'template')), true
		);

		if (empty($routeKey)) {
			return;
		}

		add_filter('template_include', function (string $template) use ($routeKey, $query) {
			if (empty($template)) {
				return false;
			}
			$newTemplate = $this->templateRoutes[$routeKey]['callable']($query);
			if (is_bool($newTemplate) && ! $newTemplate) {
				return false;
			}

			if (is_string($newTemplate)) {
				require_once $newTemplate;

				return false;
			}

			return $template;
		});
	}

	private function defaultController(\WP_Query $query)
	{
		add_filter('template_include', function (string $template) use ($query) {
			if (empty($template)) {
				return false;
			}
			$template = apply_filters('awp/router/template/resolver', $template, $query);
			if (is_bool($template) && ! $template) {
				return false;
			}

			return $template;
		}, 12);
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
					if (empty($template)) {
						return false;
					}
					$cond['callable']($query);
					require_once $template;

					return false;
				}, 11);
			}

			return;
		}
	}
}

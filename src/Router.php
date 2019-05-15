<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

class Router implements RouterInterface
{
	private $routes = [];

	public function __construct()
	{
		$this->resolveRoute();
	}

	private function addRoute(array $methods, string $route, callable $callable)
	{
		add_action('init', function () use ($methods, $route, $callable) {
			$key                = md5(implode('', $methods) . $route);
			$this->routes[$key] = (new Route($key, $methods, $route, $callable))->add();
		});
	}

	public function get(string $route, callable $callable)
	{
		$this->addRoute(['get'], $route, $callable);
	}

	public function post(string $route, callable $callable)
	{
		$this->addRoute(['post'], $route, $callable);
	}

	public function any(string $route, callable $callable)
	{
		$this->addRoute(self::METHODS, $route, $callable);
	}

	public function match(array $methods, string $route, callable $callable)
	{
		$this->addRoute(array_intersect(self::METHODS, $methods), $route, $callable);
	}

	public function redirect(string $route, string $target, array $methods = ['get'], int $status = 308)
	{
		$this->addRoute($methods, $route, function (\WP_Query $query) use ($target, $status) {
			$target = preg_replace_callback('/{(?<var>[a-zA-Z0-9_]+)}/', function (array $matches) use ($query) {
				if (array_key_exists($matches['var'], $query->query)) {
					return $query->query[$matches['var']];
				}
				return $matches[0];
			}, $target);
			wp_redirect($target, $status);
			exit();
		});
	}

	private function resolveRoute()
	{
		add_filter('parse_query', function (\WP_Query $query) {
			if (
				! $query->is_main_query()
				|| is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX)
				|| ! array_key_exists('custom_key', $query->query)
			) {
				return;
			}
			$routeKey = array_search($query->query['custom_key'], array_column($this->routes, 'key', 'key'), true);
			if (empty($routeKey)) {
				return;
			}
			add_filter('template_include', function () use ($query, $routeKey) {
				$route    = $this->routes[$routeKey];
				$template = $route['callable']($query);
				if ( ! is_null($template)) {
					require_once $template;

					return false;
				}

				if ($route['callable'] instanceof BaseController) {
					require_once $route['callable']->getTemplate();
				}

				return false;
			});
		});
	}
}

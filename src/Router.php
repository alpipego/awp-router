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
		$this->addRoute(['GET', 'HEAD'], $route, $callable);
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

	public function redirect(string $route, string $target, array $methods = ['GET', 'HEAD'], int $status = 308)
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
			$route = $this->routes[$routeKey];
			if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'HEAD' && ! in_array('HEAD', $route['methods'])) {
				status_header(405, 'Method Not Allowed');
				exit;
			}
			add_filter('template_include', function (string $template) use ($query, $route) {
				if ( ! in_array($_SERVER['REQUEST_METHOD'], $route['methods'])) {
					status_header(405, 'Method Not Allowed');
					if ($template = get_4xx_template(405)) {
						require_once $template;
					}
					exit;
				}
				$template = $route['callable']($query);
				if (is_bool($template) && ! $template) {
					return false;
				}

				if (is_string($template)) {
					require_once $template;

					return false;
				}

				return false;
			}, 9);
		});
	}
}

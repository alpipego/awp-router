<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

class Router implements RouterInterface
{
	private $methods = [];
	private $routes = [];

	public function __construct()
	{
		$this->resolveRoute();
	}

	private function addRoute(array $methods, string $route, callable $callable)
	{
		$this->routes[md5(implode('', $methods) . $route)] = (new Route($methods, $route, $callable))->add();
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

	public function redirect(string $route, string $target, int $status = 308)
	{
		$this->methods = self::METHODS;
		$route         = $this->parseRoute($route);
		// TODO finish and test redirect
	}

	private function resolveRoute()
	{
		add_filter('pre_get_posts', function (\WP_Query $query) {
			if ( ! $query->is_main_query() || is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX)) {
				return;
			}
			$routeKey = array_search(array_keys($query->query), array_combine(array_keys($this->routes), array_column($this->routes, 'vars')));
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

	public function test()
	{
		return $this->routes;
	}

	private function reset()
	{
		$this->routeVariables = [];
		$this->rewriteTags    = [];
		$this->methods        = [];
	}
}

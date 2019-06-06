<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

use Alpipego\AWP\Router\Exception\RouterException;

class AjaxRouter implements AjaxRouterInterface
{
	/**
	 * @var CustomRouterInterface
	 */
	private $router;
	private $routes = [];

	public function __construct(CustomRouterInterface $router)
	{
		add_action('awp/router/custom/pre_template', [$this, 'resolveRoute'], 10, 3);
		$this->router = $router;
	}

	public function get(string $route, callable $callable, bool $private = false)
	{
		$this->parseAjaxRequest(['GET'], $route, $callable, $private);
	}

	public function post(string $route, callable $callable, bool $private = false)
	{
		$this->parseAjaxRequest(['POST'], $route, $callable, $private);
	}

	public function any(string $route, callable $callable, bool $private = false)
	{
		$this->parseAjaxRequest(['GET', 'POST'], $route, $callable, $private);
	}

	private function parseAjaxRequest(array $methods, string $route, callable $callable, bool $private)
	{
		if (is_null($this->router)) {
			throw new RouterException(sprintf('No CustomRouter set for %s', self::class));
		}

		$key            = md5(implode('', $methods) . $route);
		$this->routes[] = $key;
		add_action('wp_ajax_' . $key, $callable);
		add_action('init', function() use ($private) {
			if ($private && !is_user_logged_in()) {
				define( 'DOING_AJAX', true );
				wp_die('Forbidden', 403);
			}
		});
		if ( ! $private) {
			add_action('wp_ajax_nopriv_' . $key, $callable);
		}

		$this->router->match($methods, $route, $callable);
	}

	public function resolveRoute(callable $callback, \WP $wp, string $routeKey)
	{
		if (in_array($routeKey, $this->routes)) {
			$_REQUEST['action'] = $routeKey;
			$_REQUEST['query']  = $wp->query_vars;
			require_once ABSPATH . 'wp-admin/admin-ajax.php';
		}
	}
}

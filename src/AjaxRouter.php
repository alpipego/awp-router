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

	public function __construct()
	{
		add_action('awp/router/custom/pre_template', [$this, 'resolveRoute'], 10, 2);
	}

	public function setCustomRouter(CustomRouterInterface $router)
	{
		$this->router = $router;
	}

	public function get(string $route, callable $callable)
	{
		$this->checkRouter();
		$this->router->match(['GET'], $route, $callable);
	}

	public function post(string $route, callable $callable)
	{
		$this->checkRouter();
		$this->router->match(['post'], $route, $callable);
	}

	public function any(string $route, callable $callable)
	{
		$this->checkRouter();
		$this->router->match(['get', 'post'], $route, $callable);
	}

	private function checkRouter()
	{
		if (is_null($this->router)) {
			throw new RouterException(sprintf('No CustomRouter set for %s', self::class));
		}
	}

	private function resolveRoute(callable $callback, \WP_Query $query)
	{

		require_once ABSPATH.'wp-admin/admin-ajax.php';
	}
}

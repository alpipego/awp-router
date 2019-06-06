<?php

namespace Alpipego\AWP\Router;

interface AjaxRouterInterface
{
	public function get(string $route, callable $callback, bool $private = false);

	public function post(string $route, callable $callback, bool $private = false);

	public function any(string $route, callable $callback, bool $private = false);
}

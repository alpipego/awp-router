<?php

namespace Alpipego\AWP\Router;

interface AjaxRouterInterface
{
	public function get(string $route, callable $callable);

	public function post(string $route, callable $callable);

	public function any(string $route, callable $callable);
}

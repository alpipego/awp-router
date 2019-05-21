<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

interface CustomRouterInterface
{
	const METHODS = ['GET', 'POST', 'HEAD'];

	public function head(string $route, callable $callable);

	public function get(string $route, callable $callable);

	public function post(string $route, callable $callable);

	public function any(string $route, callable $callable);

	public function match(array $methods, string $route, callable $callable);

	public function redirect(string $route, string $target, array $methods = ['get'], int $status = 308);
}

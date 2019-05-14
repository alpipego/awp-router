<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

interface RouterInterface
{
	const METHODS = ['get', 'post'];

	public function get(string $route, callable $callable);

	public function post(string $route, callable $callable);

	public function any(string $route, callable $callable);

	public function match(array $methods, string $route, callable $callable);

	public function redirect(string $route, string $target, int $status = 308);
}

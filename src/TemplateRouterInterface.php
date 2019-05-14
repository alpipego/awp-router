<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

interface TemplateRouterInterface
{
	public function get(string $route, callable $callable);

	public function template(string $type, string $name, array $postTypes, callable $callable);
}

<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

interface TemplateRouterInterface
{
	public function condition(callable $condition, callable $callable);

	public function template(string $template, string $name, array $postTypes, callable $callable);
}

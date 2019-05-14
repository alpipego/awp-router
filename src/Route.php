<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

use Alpipego\AWP\Router\Exception\RouterException;

class Route implements RouteInterface
{
	private $routeVariables = [];
	private $rewriteTags = [];
	private $methods;
	private $route;
	private $callable;

	public function __construct(array $methods, string $route, callable $callable)
	{
		$this->methods  = $methods;
		$this->callable = $callable;
		$this->route    = $this->parseRoute($route);
		$this->route    = sprintf('^%s/?$', trim((string)apply_filters('awp/router/route', $this->route), '/'));
		$this->route    = (string)apply_filters('awp/router/route_regex', $this->route);

		add_action('init', function () {
			$redirect = (string)apply_filters('awp/router/redirect', $this->parseRedirect());
			add_rewrite_rule($this->route, $redirect, 'top');
			$this->addQueryArgs();
			$this->addRewriteTags();
			//			$this->resolveRoute($callable);
		});
	}

	private function parseRoute(string $route) : string
	{
		$count = 1;

		return preg_replace_callback(
			'/{(?<var>[a-zA-Z0-9_%]+)?:?(?<regex>.*?}?)}/',
			function (array $matches) use (&$count) : string {
				if (empty($matches['var']) && empty($matches['regex'])) {
					return $matches[0];
				}

				$regex = sprintf('(%s)', $matches['regex']);
				if (empty($matches['regex'])) {
					$regex = '([^/]+)';
				} elseif ($matches['regex'] === '?') {
					$regex = '([^/]+)?';
				}

				if ($matches['var']) {
					if (in_array($matches['var'], self::RESERVED) || in_array('%' . $matches['var'] . '%', self::RESERVED)) {
						throw new RouterException(sprintf('Can\'t define "%s" as query var, since this is a reserved term in WordPress. See https://codex.wordpress.org/Reserved_Terms', $matches['var']));
					}

					if (strpos($matches['var'], '%') === 0 && strrpos($matches['var'], '%') === strlen($matches['var'])) {
						$this->rewriteTags[$matches['var']] = $regex;
					}

					$this->routeVariables[$count++] = trim($matches['var'], '%');
				}

				return $regex;
			},
			$route
		);
	}

	private function addQueryArgs()
	{
		add_filter('query_vars', function (array $vars) : array {
			return array_merge($vars, $this->routeVariables);
		});
	}

	private function parseRedirect()
	{
		return sprintf('index.php?%s', implode('&', array_map(function (int $key, string $value) {
			return sprintf('%s=$matches[%d]', $value, $key);
		}, array_keys($this->routeVariables), $this->routeVariables)));
	}

	private function addRewriteTags()
	{
		foreach ($this->rewriteTags as $tag => $regex) {
			add_rewrite_tag($tag, $regex, $tag . '=');
		}
	}

	public function add() : array
	{
		return [
			'route'    => $this->route,
			'vars'     => array_values($this->routeVariables),
			'callable' => $this->callable,
			'methods'  => $this->methods,
		];
	}
}

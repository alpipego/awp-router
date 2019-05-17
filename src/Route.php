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
	private $key;

	public function __construct(string $key, array $methods, string $route, callable $callable)
	{
		$this->methods          = array_map('strtoupper', $methods);
		$this->callable         = $callable;
		$this->key              = $key;
		$this->route            = $this->parseRoute((string)apply_filters('awp/router/route', $route));
		$this->route            = sprintf('^%s/?$', trim($this->route, '/'));
		$this->route            = (string)apply_filters('awp/router/route_regex', $this->route);
		$redirect               = (string)apply_filters('awp/router/redirect', $this->parseRedirect());
		$this->routeVariables[] = 'custom_key';
		add_rewrite_rule($this->route, $redirect, 'top');
		$this->addQueryVars();
		$this->addRewriteTags();
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

				if (in_array($matches['var'], self::RESERVED)) {
					throw new RouterException(sprintf('Can\'t define "%s" as query var, since this is a reserved term in WordPress. See https://codex.wordpress.org/Reserved_Terms', $matches['var']));
				}

				$regex = sprintf('(%s)', $matches['regex']);
				if (empty($matches['regex'])) {
					$regex = '([^/]+)';
					if ($this->isTag($matches['var'])) {
						/** @var \WP_Rewrite $wpRewrite */
						$wpRewrite = $GLOBALS['wp_rewrite'];
						$pos       = array_search($matches['var'], $wpRewrite->rewritecode, true);
						$regex     = $pos ? $wpRewrite->rewritereplace[$pos] : $regex;

					}
				}

				if (empty($matches['var'])) {
					return $regex;
				}

				if ($this->isTag($matches['var'])) {
					$this->rewriteTags[$matches['var']] = $regex;
				}

				$this->routeVariables[$count++] = trim($matches['var'], '%');

				return $regex;
			},
			$route
		);
	}

	private function addQueryVars()
	{
		add_filter('query_vars', function (array $vars) : array {
			return array_unique(array_merge($vars, $this->routeVariables));
		});
	}

	private function parseRedirect()
	{
		$redirect = sprintf('index.php?%s', implode('&', array_map(function (int $key, string $value) {
			return sprintf('%s=$matches[%d]', $value, $key);
		}, array_keys($this->routeVariables), $this->routeVariables)));

		return $redirect . '&custom_key=' . $this->key;
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
			'key'      => $this->key,
			'vars'     => array_values($this->routeVariables),
			'callable' => $this->callable,
			'methods'  => $this->methods,
		];
	}

	private function isTag(string $var) : bool
	{
		return strpos($var, '%') === 0 && strrpos($var, '%') === strlen($var);
	}
}

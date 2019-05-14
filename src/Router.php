<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

class Router implements RouterInterface
{
	private $routeVariables = [];
	private $rewriteTags = [];

	private function addRoute(string $route, callable $callable)
	{
		$route    = sprintf('^%s/?$', trim($this->parseRoute($route), '/'));
		$route    = (string)add_filter('awp/router/route', $route);
		$redirect = $this->parseRedirect();
		$route    = (string)add_filter('awp/router/redirect', $route);

		add_action('init', function () use ($route, $redirect) {
			add_rewrite_rule($route, $redirect, 'top');
			$this->addQueryArgs();
			$this->addRewriteTags();
			flush_rewrite_rules();
		});

		add_filter('pre_get_posts', function (\WP_Query $query) use ($callable) {
			if ( ! $query->is_main_query()) {
				return;
			}
			add_filter('template_include', function (string $template) use ($callable, $query) : string {
				echo '<code><pre>';
				var_dump($query, $template);
				echo '</pre></code>';

				return $template;
			});
		});
	}

	public function get(string $route, callable $callable)
	{
		$this->addRoute($route, $callable);
	}

	public function post(string $route, callable $callable)
	{
		// TODO: Implement post() method.
	}

	public function any(string $route, callable $callable)
	{
		// TODO: Implement any() method.
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


	public function match(array $methods, string $route, callable $callable)
	{
		// TODO: Implement match() method.
	}

	public function redirect(string $route, string $target, int $status = 308)
	{
		// TODO: Implement redirect() method.
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
}

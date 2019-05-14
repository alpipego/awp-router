<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

abstract class BaseController
{
	protected $query;

	public function __invoke(\WP_Query $query)
	{
		$this->query = $query;
	}

	abstract public function getTemplate() : string;

	protected function getTemplatePath(string $template) : string
	{
		if (is_child_theme()) {
			return locate_template([$template]);
		}

		return sprintf('%s/%s', get_template_directory(), $template);
	}

	public function __call(string $method, array $arguments)
	{
		if ($method === 'getTemplate') {
			return $this->getTemplatePath($this->getTemplate());
		}

		if (method_exists($this, $method)) {
			return call_user_func_array([$this, $method], $arguments);
		}

		trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
	}
}

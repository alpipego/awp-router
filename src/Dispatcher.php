<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router;

use Alpipego\AWP\Router\Exception\RouterException;

/**
 * Class Dispatcher
 * @package Alpipego\AWP\Router
 *
 * @property CustomRouterInterface $custom
 * @property TemplateRouterInterface $template
 * @property AjaxRouterInterface $ajax
 */
class Dispatcher
{
	private $_custom;
	private $_ajax;
	private $_template;

	public function __construct(CustomRouterInterface $custom = null, TemplateRouterInterface $template = null, AjaxRouterInterface $ajax = null)
	{
		$this->_custom   = $custom ?? new CustomRouter();
		$this->_template = $template ?? new TemplateRouter();
		$this->_ajax     = $ajax ?? new AjaxRouter();
		$this->_ajax->setCustomRouter($this->_custom);
	}

	public function setCustomRouter(CustomRouterInterface $custom)
	{
		return $this->_custom = $custom;
	}

	public function setAjaxRouter(AjaxRouterInterface $ajax)
	{
		return $this->_ajax = $ajax;
	}

	public function setTemplateRouter(TemplateRouterInterface $template)
	{
		return $this->_template = $template;
	}

	public function __get(string $var)
	{
		$var = '_' . $var;
		if ( ! isset($this->$var)) {
			throw new RouterException(sprintf('Member "%s" does not exist on %s', $var, self::class));
		}

		return $this->$var;
	}
}

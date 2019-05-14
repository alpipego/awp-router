<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Router\Exception;

class RouterException extends \Exception
{
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}

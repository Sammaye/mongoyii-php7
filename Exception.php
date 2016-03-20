<?php

namespace mongoyii;

use CException;

/**
 * EMongoException
 * The exception class that is used by this extension
 */
class Exception extends CException
{
	public $errorInfo;
	
	public function __construct($message, $code = 0, $errorInfo = null)
	{
		$this->errorInfo = $errorInfo;
		parent::__construct($message, $code);
	}
}
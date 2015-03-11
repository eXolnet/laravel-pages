<?php namespace Exolnet\Pages;

class PageValidationException extends \RuntimeException {
	/**
	 * @var array
	 */
	private $errors;

	public function __construct(array $errors)
	{
		parent::__construct();


		$this->errors = $errors;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function setErrors($errors)
	{
		$this->errors = $errors;

		return $this;
	}
}

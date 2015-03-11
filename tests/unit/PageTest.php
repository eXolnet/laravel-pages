<?php namespace Exolnet\Pages\tests;

use Exolnet\Pages\Page;
use PHPUnit_Framework_TestCase;

class PageTest extends PHPUnit_Framework_TestCase
{
	public function testPageIsInitializable()
	{
		$page = new Page();

		$this->assertInstanceOf('Exolnet\Pages\Page', $page);
	}
}

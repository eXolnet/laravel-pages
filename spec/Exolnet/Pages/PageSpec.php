<?php

namespace spec\Exolnet\Pages;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PageSpec extends ObjectBehavior
{
	public function it_is_initializable()
	{
		$this->shouldHaveType('Exolnet\Pages\Page');
	}

	public function it_implements_the_page_interface()
	{
		$this->shouldHaveType('Exolnet\Pages\PageInterface');
	}
}

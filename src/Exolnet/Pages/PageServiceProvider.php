<?php namespace Exolnet\Pages;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class PagesServiceProvider extends IlluminateServiceProvider {
	/**
	 * Register the service provider.
	 */
	public function register()
	{
		$this->registerRepository();
		$this->registerService();
	}

	/**
	 * Register the pages repository that will handle all database and filesystem
	 * operations.
	 */
	protected function registerRepository()
	{
		$this->app->bind('pages.repository', function(Application $app) {
			return new PageRepository(
				$app->make('filesystem')
			);
		});
	}

	/**
	 * Register the page service.
	 */
	protected function registerService()
	{
		$this->app->bind('pages.service', function(Application $app) {
			return new PageService(
				$app->make('pages.repository')
			);
		});
	}
}

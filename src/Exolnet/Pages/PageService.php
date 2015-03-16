<?php namespace Exolnet\Pages;

use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class PageService {
	/**
	 * @var \Exolnet\Pages\PageRepository
	 */
	protected $pageRepository;

	/**
	 * @var \Illuminate\Cache\CacheManager
	 */
	private $cacheManager;

	/**
	 * @var \Illuminate\Foundation\Application
	 */
	private $app;

	/**
	 * @var \Illuminate\Database\Eloquent\Collection
	 */
	protected $pages;

	/**
	 * Constructor.
	 *
	 * @param \Exolnet\Pages\PageRepository $pageRepository
	 * @param \Illuminate\Cache\CacheManager $cacheManager
	 * @param \Illuminate\Foundation\Application $app
	 */
	public function __construct(PageRepository $pageRepository, CacheManager $cacheManager, Application $app)
	{
		$this->pageRepository = $pageRepository;
		$this->cacheManager = $cacheManager;
		$this->app = $app;
	}

	//==========================================================================
	// Page Fetch
	//==========================================================================

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getPages()
	{
		if ( ! $this->pages) {
			$this->pages = $this->pageRepository->getPages();
		}

		return $this->pages;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getCachedPages()
	{
		return $this->cacheManager->remember('routes.pages', 5, function() {
			return $this->getPages();
		});
	}

	/**
	 * Find a page by it's primary key.
	 * @param $id
	 * @return \Exolnet\Pages\Page
	 */
	public function findById($id)
	{
		return $this->pageRepository->findById($id);
	}

	/**
	 * Find a page by it's permalink in the current application's locale.
	 *
	 * @param string $permalink
	 * @param string $locale
	 * @return \Exolnet\Pages\Page
	 */
	public function findByPermalink($permalink, $locale = null)
	{
		return $this->getCachedPages()->first(function($key, Page $page) use ($permalink, $locale) {
			return $page->getTranslation($locale)->getPermalink() === $permalink;
		});
	}

	public function loadPageContent(Page $page)
	{
		$this->pageRepository->retrievePageContent($page);

		return $this;
	}

	//==========================================================================
	// Page creation and update
	//==========================================================================

	public function getSupportedLocales()
	{
		// TODO-AD: Rendre ceci configurable <adeschambeault@exolnet.com>
		return \Config::get('pages.supported_locales');
	}

	public function rules()
	{
		$rules = [];

		foreach ($this->getSupportedLocales() as $locale) {
			$rules += [
				'translation.'. $locale .'.permalink' => 'required|max:255|unique:page_translation,permalink|regex:/^[a-z0-9-\/]+$/',
				'translation.'. $locale .'.title'     => 'required|max:255',
				'translation.'. $locale .'.locale'    => 'required',
			];
		}

		return $rules;
	}

	/**
	 * @param array $data
	 * @return Page
	 */
	public function create(array $data)
	{
		$this->validateUpdate($data);

		$page = new Page();

		$this->fillPage($page, $data);
		$this->pageRepository->storePage($page);

		$this->saveTranslations($page);

		$this->clearCache();

		return $page;
	}

	/**
	 * @param Page $page
	 * @param array $data
	 * @return $this
	 */
	public function update(Page $page, array $data)
	{
		$this->validateUpdate($data);

		$this->pageRepository->destroyPageContent($page);

		$this->fillPage($page, $data);

		$this->pageRepository
			->updatePage($page)
			->storePageContent($page);

		$this->saveTranslations($page);

		$this->clearCache();

		return $this;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @param array               $data
	 */
	public function saveTranslations(Page $page)
	{
		foreach ($this->getSupportedLocales() as $locale) {
			$page->translate($locale)->locale = $locale;
			$page->translate($locale)->page_id = $page->getId();
			$page->translate($locale)->save();
		}
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function delete(Page $page)
	{
		$this->pageRepository
			->destroyPage($page)
			->destroyPageContent($page);

		return $this;
	}

	protected function validateUpdate(array $data)
	{
		$validator = Validator::make($data, $this->rules());

		if ($validator->fails()) {
			throw new PageValidationException(
				$validator->errors()->all()
			);
		}
	}

	protected function fillPage(Page $page, array $data)
	{
		$translations = (array)Arr::get($data, 'translation');
		$translations = Arr::only($translations, $this->getSupportedLocales());

		foreach ($translations as $locale => $translation) {
			$this->fillPageTranslation($page->getTranslation($locale), $translation);
		}
	}

	protected function fillPageTranslation(PageTranslation $translation, array $data)
	{
		$translation
			->setTitle($data['title'])
			->setPermalink($data['permalink'])
			->setContent($data['content']);
	}

	//==========================================================================
	// Routes management
	//==========================================================================

	/**
	 * Register all pages to the Laravel's router
	 */
	public function registerRoutes($locale = null)
	{
		// Prevent route registration when we're in a test environment or running artisan
		if (app()->environment('test', 'testing') || app()->runningInConsole()) {
			$permalinks = [];
		} else {
			if ($locale === null) {
				$locale = Route::getLastLocale();
			}

			$pages = $this->getCachedPages();
			$permalinks = [];

			foreach ($pages as $page) {
				// We quote the permalink for the route match
				$permalinks[] = preg_quote($page->getPermalink($locale));
			}
		}

		Route::get(
			'{permalink}',
			'PageController@show'
		)->where('permalink', '(' . implode('|', $permalinks) . ')');
	}

	//==========================================================================
	// Cache Management
	//==========================================================================

	public function getCacheKey()
	{
		return 'routes.pages';
	}

	/**
	 * @return $this
	 */
	public function clearCache()
	{
		$this->cacheManager->forget($this->getCacheKey());

		return $this;
	}

	//==========================================================================
	// URL helper
	//==========================================================================

	/**
	 * @param        $permalink
	 * @param null   $locale
	 * @param string $from_locale
	 * @return null|string
	 */
	public function permalink($permalink, $locale = null, $from_locale = 'en')
	{
		$page   = $this->findByPermalink($permalink, $from_locale);
		$locale = $locale ?: $this->app->getLocale();

		return $page !== null ? $locale.'/'.$page->getTranslation($locale)->getPermalink() : null;
	}

	/**
	 * @param       $permalink
	 * @param array $parameters
	 * @param null  $secure
	 * @return string
	 */
	public function url($permalink, $parameters = [], $secure = null)
	{
		return url($this->permalink($permalink), $parameters, $secure);
	}

	/**
	 * @param       $permalink
	 * @param null  $title
	 * @param array $attributes
	 * @param null  $secure
	 * @return string
	 */
	public function link_to($permalink, $title = null, $attributes = [], $secure = null)
	{
		return link_to($this->permalink($permalink), $title, $attributes, $secure);
	}

	public function link_to_with_title($permalink, $attributes = [], $secure = null)
	{
		$page = $this->findByPermalink($permalink, 'en');
		return $page !== null ? $this->link_to($permalink, $page->getTitle(), $attributes, $secure) : null;
	}
}

<?php namespace Exolnet\Pages;

use Config;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr as Arr;
use Exolnet\Core\Arr as ArrCore;
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
	 * @param \Exolnet\Pages\Page $page
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getPagesWithoutDescendants(Page $page)
	{
		return $this->pageRepository->getPagesWithoutDescendants($page);
	}

	/**
	 * Find a page by its primary key.
	 * @param int $id
	 * @return \Exolnet\Pages\Page
	 */
	public function findById($id)
	{
		return $this->pageRepository->findById($id);
	}

	/**
	 * Find a page by its permalink in the current application locale.
	 *
	 * @param string $permalink
	 * @param string $locale
	 * @return \Exolnet\Pages\Page
	 */
	public function findByPermalink($permalink, $locale = null)
	{
		$locale = $locale ?: Config::get('laravel-pages::config.base_locale');
		return $this->getCachedPages()->first(function($key, Page $page) use ($permalink, $locale) {
			return $page->getTranslation($locale)->getPermalink() === $permalink;
		});
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function loadPageContent(Page $page)
	{
		$this->pageRepository->retrievePageContent($page);

		return $this;
	}

	//==========================================================================
	// Page creation and update
	//==========================================================================

	/**
	 * @return string
	 */
	public function getSupportedLocales()
	{
		return Config::get('laravel-pages::config.supported_locales');
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return array
	 */
	public function rules(Page $page = null)
	{
		$rules = [];

		foreach ($this->getSupportedLocales() as $locale) {
			$rules += [
				'translation.'. $locale .'.permalink' => 'required|max:255|unique:page_translation,permalink' . ($page ? ',' . $page->getId() : '') . '|regex:/^[a-z0-9-\/]+$/',
				'translation.'. $locale .'.title'     => 'required|max:255',
				'translation.'. $locale .'.locale'    => 'required',
			];
		}

		return $rules;
	}

	/**
	 * @param array $data
	 * @return \Exolnet\Pages\Page
	 */
	public function create(array $data)
	{
		$data = ArrCore::mapNullOnEmpty($data);

		$this->validateUpdate($data, $this->rules());

		$page = new Page();

		$this->fillPage($page, $data);
		$this->pageRepository->storePage($page);

		$this->saveTranslations($page);

		$parentId = array_get($data, 'parent_id', 0);
		$this->managePageClosure(
			$page,
			$parentId
		);

		$this->clearCache();

		return $page;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @param array $data
	 * @return $this
	 */
	public function update(Page $page, array $data)
	{
		$data = ArrCore::mapNullOnEmpty($data);

		$this->validateUpdate($data, $this->rules($page));

		$this->pageRepository->destroyPageContent($page);

		$this->fillPage($page, $data);

		$this->pageRepository
			->updatePage($page)
			->storePageContent($page);

		$this->saveTranslations($page);

		$parentId = array_get($data, 'parent_id', 0);

		$this->managePageClosure(
			$page,
			$parentId);

		$this->clearCache();

		return $this;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
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

	/**
	 * @param array $data
	 * @param $rules
	 */
	protected function validateUpdate(array $data, $rules)
	{
		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
			throw new PageValidationException(
				$validator->errors()->all()
			);
		}
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @param array $data
	 */
	protected function fillPage(Page $page, array $data)
	{
		$translations = (array)Arr::get($data, 'translation');
		$translations = Arr::only($translations, $this->getSupportedLocales());

		foreach ($translations as $locale => $translation) {
			$this->fillPageTranslation($page->getTranslation($locale), $translation);
		}
	}

	/**
	 * @param \Exolnet\Pages\PageTranslation $translation
	 * @param array $data
	 */
	protected function fillPageTranslation(PageTranslation $translation, array $data)
	{
		$translation
			->setTitle($data['title'])
			->setPermalink($data['permalink'])
			->setContent($data['content']);
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @param int $parentId
	 */
	protected function managePageClosure(Page $page, $parentId)
	{
		$parent = $this->pageRepository->findById($parentId);

		if($parent) {
			$page->moveAsChildOf($parent);
		} else {
			$page->makeRoot();
		}
	}

	//==========================================================================
	// Routes management
	//==========================================================================

	/**
	 * Register all pages to the Laravel's router
	 *
	 * @param string|null $locale
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

	/**
	 * @return string
	 */
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
	 * @param string   $permalink
	 * @param string|null   $locale
	 * @param string|null $from_locale
	 * @return null|string
	 */
	public function permalink($permalink, $locale = null, $from_locale = null)
	{
		$page   = $this->findByPermalink($permalink, $from_locale);
		$locale = $locale ?: $this->app->getLocale();

		return $page !== null ? $locale.'/'.$page->getTranslation($locale)->getPermalink() : null;
	}

	/**
	 * @param string $permalink
	 * @param array $parameters
	 * @param bool|null  $secure
	 * @return string
	 */
	public function url($permalink, $parameters = [], $secure = null)
	{
		return url($this->permalink($permalink), $parameters, $secure);
	}

	/**
	 * @param string $permalink
	 * @param null  $title
	 * @param array $attributes
	 * @param bool|null  $secure
	 * @return string
	 */
	public function link_to($permalink, $title = null, $attributes = [], $secure = null)
	{
		return link_to($this->permalink($permalink), $title, $attributes, $secure);
	}

	/**
	 * @param string $permalink
	 * @param array $attributes
	 * @param bool|null $secure
	 * @return null|string
	 */
	public function link_to_with_title($permalink, $attributes = [], $secure = null)
	{
		$page = $this->findByPermalink($permalink, 'en');
		return $page !== null ? $this->link_to($permalink, $page->getTitle(), $attributes, $secure) : null;
	}
}

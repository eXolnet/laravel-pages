<?php namespace Exolnet\Pages;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class PageService {
	/**
	 * @var \Exolnet\Pages\PageRepository
	 */
	protected $pageRepository;

	/**
	 * @var \Illuminate\Database\Eloquent\Collection
	 */
	protected $pages;

	/**
	 * Constructor.
	 *
	 * @param \Exolnet\Pages\PageRepository $pageRepository
	 */
	public function __construct(PageRepository $pageRepository)
	{
		$this->pageRepository = $pageRepository;
	}

	public function rules()
	{
		// TODO-TR: Validate that permalink is unique <trochette@exolnet.com>
		return rulesRequiredLanguage([
			'translation.$lang.permalink'   => 'required|max:255|regex:/^[a-z0-9-\/]+$/',
			'translation.$lang.title'       => 'required|max:255',
			'translation.$lang.locale'      => 'required',
		], ['en', 'fr']);
	}

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

	/**
	 * @return Collection
	 */
	public function getPages()
	{
		if ( ! $this->pages) {
			try {
				$this->pages = $this->pageRepository->with('translations')->get();
			} catch (QueryException $e) {
				$this->pages = new Collection;
			}
		}

		return $this->pages;
	}

	public function getCachedPages()
	{
		return Cache::remember('routes.pages', 5, function() {
			return $this->getPages();
		});
	}

	/**
	 * @param $key
	 * @return Page
	 */
	public function findByKey($key)
	{
		return $this->getCachedPages()->find($key);
	}

	/**
	 * Find a page by it's permalink in the current application's locale.
	 *
	 * @param string $permalink
	 * @param string $locale
	 * @return Page
	 */
	public function findByPermalink($permalink, $locale = null)
	{
		return $this->getCachedPages()->first(function($key, $page) use ($permalink, $locale) {
			return $page->translate($locale)->permalink === $permalink;
		});
	}

	/**
	 * @param        $permalink
	 * @param null   $locale
	 * @param string $from_locale
	 * @return null|string
	 */
	public function permalink($permalink, $locale = null, $from_locale = 'en')
	{
		$page   = $this->findByPermalink($permalink, $from_locale);
		$locale = $locale ?: App::getLocale();

		return $page !== null ? $locale.'/'.$page->translate($locale)->permalink : null;
	}

	/**
	 * @param       $permalink
	 * @param array $parameters
	 * @param null  $secure
	 * @return string
	 */
	public function url($permalink, $parameters = array(), $secure = null)
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
	public function link_to($permalink, $title = null, $attributes = array(), $secure = null)
	{
		return link_to($this->permalink($permalink), $title, $attributes, $secure);
	}

	public function link_to_with_title($permalink, $attributes = array(), $secure = null)
	{
		$page = $this->findByPermalink($permalink, 'en');
		return $page !== null ? $this->link_to($permalink, $page->getTitle(), $attributes, $secure) : null;
	}

	/**
	 * @param array $data
	 * @return Page
	 */
	public function create(array $data)
	{
		$page = new Page();

		$this->update($page, $data);

		Cache::forget('routes.pages');

		return $page;
	}

	/**
	 * @param Page  $page
	 * @param array $data
	 */
	public function update(Page $page, array $data)
	{
		$data = Arr::mapNullOnEmpty($data);

		$this->validateUpdate($data);

		$locales = Route::getSupportedLocales();
		$translations = array_get($data, 'translation', []);

		$oldFilenames = [];
		$newFilenames = [];

		DB::transaction(function() use ($page, $data, $locales, $translations, &$oldFilenames, &$newFilenames) {
			foreach ($locales as $locale) {
				$oldFilenames[] = $page->getFilename($locale);

				$page->translate($locale)->fill(array_get($translations, $locale, []));

				$newFilenames[] = $page->getFilename($locale);
			}

			$page->save();
		});

		foreach ($locales as $locale) {
			$translation = array_get($translations, $locale, []);
			$content     = array_get($translation, 'content');

			$page->setContent($content, $locale);
		}

		// Get rid of renamed files
		$removedFiles = array_diff($oldFilenames, $newFilenames);
		foreach ($removedFiles as $removedFile) {
			if ($this->filesystem->exists($removedFile)) {
				$this->filesystem->delete($removedFile);
			}
		}

		Cache::forget('routes.pages');
	}

	protected function validateUpdate(array $data)
	{
		$validator = Validator::make($data, $this->rules());

		if ($validator->fails()) {
			throw new ServiceValidationException(
				$validator->errors()->all()
			);
		}
	}
}

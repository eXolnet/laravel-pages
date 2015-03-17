<?php namespace Exolnet\Pages;

use Illuminate\Filesystem\Filesystem;

class PageRepository {

	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $filesystem;

	/**
	 * @param \Illuminate\Filesystem\Filesystem $filesystem
	 */
	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getPages()
	{
		return Page::with('translations')->get();
	}

	/**
	 * @param int $id
	 * @return \Exolnet\Pages\Page
	 */
	public function findById($id)
	{
		return Page::find($id);
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function storePage(Page $page)
	{
		$page->save();

		$locales = \Config::get('locales');
		foreach($locales as $locale) {
			$page->translate($locale)->page_id = $page->id;
			$page->translate($locale)->save();
		}

		$this->storePageContent($page);

		return $this;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function updatePage(Page $page)
	{
		// $oldFilenames = [];
		// $newFilenames = [];

		$page->save();
		// $this->storePageContent($page);

		// Get rid of renamed files
		// $removedFiles = array_diff($oldFilenames, $newFilenames);
		// foreach ($removedFiles as $removedFile) {
		// 	if ($this->filesystem->exists($removedFile)) {
		// 		$this->filesystem->delete($removedFile);
		// 	}
		// }

		return $this;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function destroyPage(Page $page)
	{
		$page->delete();
		$this->destroyPageContent($page);

		return $this;
	}

	//==========================================================================
	// Content management
	//==========================================================================

	public function retrievePageContent(Page $page)
	{
		/** @var \Exolnet\Pages\PageTranslation $translation */
		foreach ($page->getTranslations() as $translation) {
			$filename  = $translation->getFilename();

			if ($this->filesystem->exists($filename)) {
				$content = $this->filesystem->get($filename);

				$baseUrl = \URL::to('/');
				$content = str_replace('%BASE_URL%', $baseUrl, $content);

				$translation->setContent($content);
			}
		}
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function storePageContent(Page $page)
	{
		/** @var \Exolnet\Pages\PageTranslation $translation */
		foreach ($page->getTranslations() as $translation) {
			$filename  = $translation->getFilename();
			$directory = dirname($filename);
			$content   = $translation->getContent();

			if ( ! $this->filesystem->exists($directory)) {
				$this->filesystem->makeDirectory($directory, 0755, true);
			}

			$this->filesystem->put($filename, $content);
		}

		return $this;
	}

	/**
	 * @param \Exolnet\Pages\Page $page
	 * @return $this
	 */
	public function destroyPageContent(Page $page)
	{
		/** @var \Exolnet\Pages\PageTranslation $translation */
		foreach ($page->getTranslations() as $translation) {
			$filename  = $translation->getFilename();

			if ($this->filesystem->exists($filename)) {
				$this->filesystem->delete($filename);
			}
		}

		return $this;
	}
}

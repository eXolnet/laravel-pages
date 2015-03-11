<?php namespace Exolnet\Pages;

use App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Page extends Model implements PageInterface {
	use SoftDeletingTrait;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'page';

	/**
	 * Specifies which attributes should be mass-assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['permalink', 'title', 'description', 'keywords'];

	/**
	 * Validation rules for the model validation.
	 *
	 * @var array
	 */
	// protected $rules = [
	// 	'permalink'   => 'required|size:255',
	// 	'title'       => 'required|size:255',
	// 	'description' => 'size:255',
	// 	'keywords'    => 'size:255',
	// ];

	/**
	 * Specifies which attributes are translated.
	 *
	 * @var array
	 */
	public $translatedAttributes = ['permalink', 'title', 'description', 'keywords'];

	/**
	 * Get the permalink of the page.
	 *
	 * @param string $locale
	 * @return string
	 */
	public function getPermalink($locale = null)
	{
		return $this->translate($locale)->permalink;
	}

	/**
	 * Set the permalink for the page.
	 *
	 * @param string $permalink
	 * @return $this
	 */
	public function setPermalink($permalink)
	{
		$this->permalink = $permalink;

		return $this;
	}

	/**
	 * Get the title of the page.
	 *
	 * @param string|null $locale
	 * @return string
	 */
	public function getTitle($locale = null)
	{
		return $this->translate($locale)->title;
	}

	/**
	 * Set the title for the page.
	 *
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the description of the page.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set the description for the page.
	 *
	 * @param string $description
	 * @return $this
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get the keywords of the page.
	 *
	 * @return string
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * Set the keywords for the page.
	 *
	 * @param string $keywords
	 * @param string|null $locale
	 * @return $this
	 */
	public function setKeywords($keywords)
	{
		$this->keywords = $keywords;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isLocked()
	{
		return (bool)$this->is_locked;
	}

	/**
	 * @param bool $isLocked
	 * @return $this
	 */
	public function setLocked($isLocked)
	{
		$this->is_locked = (bool)$isLocked;

		return $this;
	}

	/**
	 * @param string $permalink
	 * @param string|null $locale
	 * @return bool
	 */
	public function hasPermalink($permalink, $locale = null)
	{
		$locale = $this->getLocale($locale);
		return $this->translate($locale)->permalink === $permalink;
	}

	/**
	 * @param string|null $locale
	 * @return string
	 */
	protected function getLocale($locale = null)
	{
		return $locale ?: App::getLocale();
	}

	/**
	 * Limit the query to page having a specified permalink.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @param  string $permalink
	 * @param  string $locale
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeHasPermalink($query, $permalink, $locale = null)
	{
		return $this->scopeHasTranslation($query, 'permalink', $permalink, $this->getLocale($locale));
	}
}

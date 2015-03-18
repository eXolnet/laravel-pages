<?php namespace Exolnet\Pages;

use ClosureTree\Models\NodeTrait;
use ClosureTree\Models\NodeUnorderedInterface;
use ClosureTree\Models\NodeUnorderedTrait;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Page extends Model implements PageInterface, NodeUnorderedInterface {
	use SoftDeletingTrait, Translatable, NodeTrait, NodeUnorderedTrait;

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
	 * Specifies which attributes are translated.
	 *
	 * @var array
	 */
	public $translatedAttributes = ['permalink', 'title', 'description', 'keywords'];

	/**
	 * The database table used by the NodeTrait.
	 *
	 * @var string
	 */
	protected $closure_table = 'page_closure';

	/**
	 * The ancestor column name in the closure table
	 *
	 * @var string
	 */
	protected $closure_ancestor_column = 'ancestor_id';

	/**
	 * The descendant column name in the closure table
	 *
	 * @var string
	 */
	protected $closure_descendant_column = 'descendant_id';

	/**
	 * The depth column name in the closure table
	 *
	 * @var string
	 */
	protected $closure_depth_column = 'depth';

	//==========================================================================
	// Getters & Setters
	//==========================================================================

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get the permalink of the page.
	 *
	 * @return string
	 */
	public function getPermalink()
	{
		return $this->getTranslation()->getPermalink();
	}

	/**
	 * Set the permalink for the page.
	 *
	 * @param string $permalink
	 * @return $this
	 */
	public function setPermalink($permalink)
	{
		$this->getTranslation()->setPermalink($permalink);

		return $this;
	}

	/**
	 * Get the title of the page.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getTranslation()->getTitle();
	}

	/**
	 * Set the title for the page.
	 *
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->getTranslation()->setTitle($title);

		return $this;
	}

	/**
	 * Get the content of the page.
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->getTranslation()->getContent();
	}

	/**
	 * Set the content for the page.
	 *
	 * @param string $content
	 * @return $this
	 */
	public function setContent($content)
	{
		$this->getTranslation()->setContent($content);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBasename()
	{
		return $this->getTranslation()->getBasename();
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->getTranslation()->getFilename();
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

	//==========================================================================
	// Translations
	//==========================================================================

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getTranslations()
	{
		return $this->translations;
	}

	/**
	 * @return \stdClass
	 */
	public function getTranslationsAsObject()
	{
		$translations = new \stdClass;

		foreach ($this->translations as $translation) {
			$locale = $translation->locale;

			$translations->$locale = $translation;
		}

		return $translations;
	}

	//==========================================================================
	// Scopes
	//==========================================================================

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

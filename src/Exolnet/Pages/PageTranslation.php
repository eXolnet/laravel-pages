<?php namespace Exolnet\Pages;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model {
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'page_translation';

	/**
	 * Disabling Auto Timestamps
	 *
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 * Specifies which attributes should be mass-assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['permalink', 'title'];

	/**
	 * @var string
	 */
	public $content;

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
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * @param string $locale
	 * @return $this
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPermalink()
	{
		return $this->permalink;
	}

	/**
	 * @param string $permalink
	 * @return $this
	 */
	public function setPermalink($permalink)
	{
		$this->permalink = $permalink;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBasename()
	{
		return str_replace('/', '_', $this->getPermalink());
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return app_path().'/views/pages/content/'. $this->getLocale() .'/'. $this->getBasename() .'.html';
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string $content
	 * @return $this
	 */
	public function setContent($content)
	{
		$this->content = $content;

		return $this;
	}
}

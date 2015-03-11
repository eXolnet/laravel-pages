<?php namespace Exolnet\Pages;

interface PageInterface
{
	/**
	 * Get the permalink of the page.
	 *
	 * @param string $locale
	 * @return string
	 */
	public function getPermalink();

	/**
	 * Set the permalink for the page.
	 *
	 * @param string $permalink
	 * @return $this
	 */
	public function setPermalink($permalink);

	/**
	 * Get the title of the page.
	 *
	 * @param string|null $locale
	 * @return string
	 */
	public function getTitle();

	/**
	 * Set the title for the page.
	 *
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title);

	/**
	 * Get the description of the page.
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * Set the description for the page.
	 *
	 * @param string $description
	 * @return $this
	 */
	public function setDescription($description);

	/**
	 * Get the keywords of the page.
	 *
	 * @return string
	 */
	public function getKeywords();

	/**
	 * Set the keywords for the page.
	 *
	 * @param string $keywords
	 * @param string|null $locale
	 * @return $this
	 */
	public function setKeywords($keywords);

	/**
	 * @return bool
	 */
	public function isLocked();

	/**
	 * @param bool $isLocked
	 * @return $this
	 */
	public function setLocked($isLocked);
}

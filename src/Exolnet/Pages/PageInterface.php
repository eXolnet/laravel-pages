<?php namespace Exolnet\Pages;

interface PageInterface
{
	/**
	 * @return int
	 */
	public function getId();

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
	 * @return bool
	 */
	public function isLocked();

	/**
	 * @param bool $isLocked
	 * @return $this
	 */
	public function setLocked($isLocked);
}

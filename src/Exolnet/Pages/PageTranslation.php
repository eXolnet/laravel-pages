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
	protected $fillable = ['permalink', 'title', 'description', 'keywords'];
}

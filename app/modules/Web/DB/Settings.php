<?php

namespace App\Web\DB;

/**
 * @table{"name":"web_settings"}
 */
class Settings extends \Storm\Model
{
	/**
	 * @column{"nullable":true}
	 */
	public $google_analytics_id = '';

	/**
	 * @column{"nullable":true}
	 */
	public $google_tag_manager = '';

	/**
	 * @column{"nullable":true}
	 */
	public $google_search_console = '';

	/**
	 * @column{"nullable":true}
	 */
	public $google_map_key = '';

	/**
	 * @column{"nullable":true, "locale":true}
	 */
	public $project_name = '';

	/**
	 * @column{"nullable":true, "locale":true}
	 */
	public $project_title = '';

	/**
	 * @column{"nullable":true}
	 */
	public $scripts_footer = '';

	/**
	 * @column{"nullable":true}
	 */
	public $scripts_header = '';

    /**
     * @column{"nullable":true}
     */
    public $youtube_link = '';

    /**
     * @column{"nullable":true}
     */
    public $facebook_link = '';

    /**
     * @column{"nullable":true}
     */
    public $twitter_link = '';

    /**
     * @column{"type":"longtext","locale":true,"nullable":false}
     */
    public $text404 = '';

}

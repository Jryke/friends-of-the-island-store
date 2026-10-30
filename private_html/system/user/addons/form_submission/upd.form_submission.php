<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Service\Addon\Installer;

/**
 * Form Submission Module Installer
 */
class Form_submission_upd extends Installer
{
    public $actions = [
        ['method' => 'process']
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Install the module — creates database tables
     */
    public function install()
    {
        parent::install();

        $charset = ee()->db->escape_str(ee()->db->char_set);
        $collate = ee()->db->escape_str(ee()->db->dbcollat);

        // Submissions table
        ee()->db->query("CREATE TABLE IF NOT EXISTS exp_form_submissions (
            submission_id   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_name       VARCHAR(100) NOT NULL DEFAULT '',
            submission_date INT(10) UNSIGNED NOT NULL DEFAULT '0',
            sender_ip       VARCHAR(45) NOT NULL DEFAULT '',
            fields          LONGTEXT NOT NULL,
            email_sent      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY `submission_id` (`submission_id`),
            KEY `form_name` (`form_name`),
            KEY `submission_date` (`submission_date`)
        ) DEFAULT CHARACTER SET {$charset} COLLATE {$collate}");

        // Settings table
        ee()->db->query("CREATE TABLE IF NOT EXISTS exp_form_submission_settings (
            setting_id      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id         INT(10) UNSIGNED NOT NULL DEFAULT '1',
            setting_key     VARCHAR(100) NOT NULL DEFAULT '',
            setting_value   TEXT NOT NULL,
            PRIMARY KEY `setting_id` (`setting_id`),
            UNIQUE KEY `site_setting` (`site_id`, `setting_key`)
        ) DEFAULT CHARACTER SET {$charset} COLLATE {$collate}");

        ee()->db->insert('exp_extensions', [
            'class'    => 'Form_submission_ext',
            'method'   => 'template_post_parse',
            'hook'     => 'template_post_parse',
            'settings' => '',
            'priority' => 10,
            'version'  => '1.0.0',
            'enabled'  => 'y',
        ]);

        return true;
    }

    /**
     * Uninstall the module — drops database tables
     */
    public function uninstall()
    {
        parent::uninstall();

        ee()->db->query('DROP TABLE IF EXISTS exp_form_submissions');
        ee()->db->query('DROP TABLE IF EXISTS exp_form_submission_settings');

        ee()->db->where('class', 'Form_submission_ext')->delete('exp_extensions');

        return true;
    }

    /**
     * Update handler
     */
    public function update($current = '')
    {
        return true;
    }
}
// END CLASS

// EOF

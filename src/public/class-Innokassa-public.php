<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * @link       https://digitalkassa.ru/
 * @since      1.0.0
 *
 * @package    Digitalkassa
 * @subpackage Digitalkassa/public
 */

/**
 * @package    Digitalkassa
 * @subpackage Digitalkassa/public
 * @author     Your Name <email@example.com>
 */
class DigitalkassaPublic
{
    /**
     * @since    1.0.0
     * @access   private
     * @var      string    $Digitalkassa    The ID of this plugin.
     */
    private $Digitalkassa;

    /**
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * @since    1.0.0
     * @param      string    $Digitalkassa       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($Digitalkassa, $version)
    {
        $this->Digitalkassa = $Digitalkassa;
        $this->version = $version;
    }

    /**
     * @since    1.0.0
     */
    public function enqueueStyles()
    {
        wp_enqueue_style(
            $this->Digitalkassa,
            plugin_dir_url(__FILE__) . 'css/Digitalkassa-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * @since    1.0.0
     */
    public function enqueueScripts()
    {
        wp_enqueue_script(
            $this->Digitalkassa,
            plugin_dir_url(__FILE__) . 'js/Digitalkassa-public.js',
            array('jquery'),
            $this->version,
            false
        );
    }
}

<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * @link       https://digitalkassa.ru/
 * @since      1.0.0
 *
 * @package    Digitalkassa
 * @subpackage Digitalkassa/includes
 */

/**
 * @since      1.0.0
 * @package    Digitalkassa
 * @subpackage Digitalkassa/includes
 */
class Digitalkassa
{
    /**
     * @since    1.0.0
     * @access   protected
     * @var      Digitalkassa_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * @since    1.0.0
     * @access   protected
     * @var      string    $Digitalkassa    The string used to uniquely identify this plugin.
     */
    protected $Digitalkassa;

    /**
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('INNOKASSA_VERSION')) {
            $this->version = INNOKASSA_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->Digitalkassa = 'Digitalkassa';

        $this->loadDependencies();
        $this->defineAdminHooks();
        $this->definePublicHooks();
    }

    /**
     * @since    1.0.0
     * @access   private
     */
    private function loadDependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-Digitalkassa-loader.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-Digitalkassa-admin.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-Digitalkassa-public.php';

        $this->loader = new Digitalkassa_Loader();
    }

    /**
     * @since    1.0.0
     * @access   private
     */
    private function defineAdminHooks()
    {
        $plugin_admin = new DigitalkassaAdmin($this->getDigitalkassa(), $this->getVersion());

        $this->loader->add_action('admin_menu', $plugin_admin, 'addMenu');
        $this->loader->add_action('admin_init', $plugin_admin, 'registerSettings');
        $this->loader->add_action('woocommerce_payment_complete', 'custom_process_order', 10, 1);
    }

    /**
     * @since    1.0.0
     * @access   private
     */
    private function definePublicHooks()
    {
        $plugin_public = new DigitalkassaPublic($this->getDigitalkassa(), $this->getVersion());
    }

    /**
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function getDigitalkassa()
    {
        return $this->Digitalkassa;
    }

    /**
     * @since     1.0.0
     * @return    Digitalkassa_Loader    Orchestrates the hooks of the plugin.
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function getVersion()
    {
        return $this->version;
    }
}

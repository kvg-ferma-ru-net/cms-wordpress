<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * @link       https://digitalkassa.ru/
 * @since      1.0.0
 *
 * @package    Digitalkassa
 * @subpackage Digitalkassa/admin
 */

/**
 * @package    Digitalkassa
 * @subpackage Digitalkassa/admin
 */
class DigitalkassaAdmin
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
            plugin_dir_url(__FILE__) . 'css/Digitalkassa-admin.css',
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
        wp_enqueue_script("jquery");
        wp_enqueue_script('Digitalkassa-admin', plugin_dir_url(__FILE__) . 'js/Digitalkassa-admin.js', array('jquery'));
        wp_enqueue_script('qrcode.min.js', plugin_dir_url(__FILE__) . 'js/qrcode.min.js', array('jquery'));
    }

    public function addMenu()
    {
        $hook = add_submenu_page(
            'woocommerce',
            __('Настройки Digitalkassa', 'Digitalkassa'),
            __('Настройки Digitalkassa', 'Digitalkassa'),
            'manage_options',
            'Digitalkassa_submenu',
            array($this, 'renderAdminPage')
        );
    }

    public function registerSettings()
    {
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_actor_id');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_actor_token');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_cashbox');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_scheme');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_status_first_receipt');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_status_second_receipt');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_place_of_settlement');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_taxation');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_type_of_receipt_position');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_vat');
        register_setting('Digitalkassa-option-group', 'digitalkassa_option_delivery_vat');
    }

    public function renderAdminPage()
    {
        $this->render('partials/Digitalkassa-admin-display.php', []);
    }

    private function render($viewPath, $args)
    {
        extract($args);
        include(plugin_dir_path(__FILE__) . $viewPath);
    }
}

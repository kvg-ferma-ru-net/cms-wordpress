<?php

// phpcs:disable

use Digitalkassa\MDK\Net\Transfer;
use Digitalkassa\MDK\Net\ConverterApi;
use Digitalkassa\MDK\Logger\LoggerFile;
use Digitalkassa\MDK\Net\NetClientCurl;
use Digitalkassa\MDK\Services\ConnectorBase;
use Digitalkassa\MDK\Settings\SettingsAbstract;
use Digitalkassa\MDK\Exceptions\SettingsException;
use Digitalkassa\MDK\Entities\Atoms\ReceiptSubType;
use Digitalkassa\MDK\Exceptions\Services\AutomaticException;

// автозагрузчик mdk
require_once plugin_dir_path(__FILE__) . 'include.php';

/**
 * Digitalkassa
 *
 * @link              https://digitalkassa.ru/
 * @since             1.0
 * @package           Digitalkassa
 * @copyright         @ Digitalkassa
 *
 * @wordpress-plugin
 * Plugin Name:       Digitalkassa
 * Description:       Digitalkassa - Сервис для фискализации интернет-продаж и автоматизации работы с Честным Знаком, с доступными интеграциями всех интернет площадок.
 * Version:           1.0
 * Author:            Kripak Igor @ Digitalkassa
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       Digitalkassa
 * Domain Path:       /languages
 * Requires           PHP: 5.3
 */

if (!defined('WPINC')) {
    die;
}

define('INNOKASSA_VERSION', '1.3.3');

function activate_Digitalkassa()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-Digitalkassa-activator.php';
    DigitalkassaActivator::activate();
}

function deactivate_Digitalkassa()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-Digitalkassa-deactivator.php';
    DigitalkassaDeactivator::deactivate();
    wp_clear_scheduled_hook('mc_check_hook');
}

register_activation_hook(__FILE__, 'activate_Digitalkassa');
register_deactivation_hook(__FILE__, 'deactivate_Digitalkassa');

//db
function Digitalkassa_create_database_tables()
{
    global $wpdb;

    $mc_check_table = $wpdb->prefix . "digitalkassa_receipts";
    $mc_check_log_table = $wpdb->prefix . "mr_checks_log";

    $sql = "CREATE TABLE IF NOT EXISTS $mc_check_table ( ";
    $sql .= " `id` INT NOT NULL AUTO_INCREMENT, ";
    $sql .= " `subtype` TINYINT, ";
    $sql .= " `cashbox` VARCHAR(255) NOT NULL, ";
    $sql .= " `order_id` VARCHAR(255) NOT NULL, ";
    $sql .= " `site_id` VARCHAR(255) NOT NULL, ";
    $sql .= " `receipt_id` VARCHAR(64) NOT NULL, ";
    $sql .= " `status` TINYINT NOT NULL, ";
    $sql .= " `type` TINYINT NOT NULL, ";
    $sql .= " `items` TEXT NOT NULL, ";
    $sql .= " `taxation` TINYINT NOT NULL, ";
    $sql .= " `accepted` TINYINT NOT NULL, ";
    $sql .= " `available` TINYINT NOT NULL, ";
    $sql .= " `amount` TEXT NOT NULL, ";
    $sql .= " `customer` TEXT NOT NULL, ";
    $sql .= " `notify` TEXT NOT NULL, ";
    $sql .= " `location` VARCHAR(255) NOT NULL, ";
    $sql .= " `start_time` VARCHAR(255) NOT NULL, ";
    $sql .= " PRIMARY KEY (`id`), ";
    $sql .= " INDEX filter (`order_id`, `type`, `subtype`, `status`) ";
    $sql .= " ) ENGINE = InnoDB; ";

    $sql2 = "CREATE TABLE IF NOT EXISTS $mc_check_log_table ( ";
    $sql2 .= " `id` int(16) UNSIGNED NOT NULL AUTO_INCREMENT, ";
    $sql2 .= " `check_id` int(16) NOT NULL, ";
    $sql2 .= " `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ";
    $sql2 .= " `data_in` text NOT NULL, ";
    $sql2 .= " `data_out` text NOT NULL, ";
    $sql2 .= " `response_code` int(5) NOT NULL, ";
    $sql2 .= " PRIMARY KEY (`id`), ";
    $sql2 .= " UNIQUE KEY `id` (`id`) ";
    $sql2 .= " ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1; ";

    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
}
register_activation_hook(__FILE__, 'Digitalkassa_create_database_tables');

require plugin_dir_path(__FILE__) . 'includes/class-Digitalkassa.php';

function run_Digitalkassa()
{
    $plugin = new Digitalkassa();
    $plugin->run();
}
run_Digitalkassa();

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'Digitalkassa_add_plugin_page_settings_link');
function Digitalkassa_add_plugin_page_settings_link($links)
{
    $links[] = '<a href="' . admin_url('admin.php?page=Digitalkassa_submenu') . '">' . __('Settings') . '</a>';
    return $links;
}

add_action('woocommerce_order_status_changed', 'orderStatusChanged', 10, 4);

function orderStatusChanged(int $order_id, string $old_status, string $new_status, object $order)
{
    $mdk = DigitalkassaClientFactory::build();
    $settings = $mdk->componentSettings();
    $automatic = $mdk->serviceAutomatic();

    $data = $order->get_data();

    try {
        if (
            $settings->getScheme() == SettingsAbstract::SCHEME_PRE_FULL
            && $settings->getOrderStatusReceiptPre() == "wc-" . $data['status']
        ) {
            $automatic->fiscalize($order_id, '', ReceiptSubType::PRE);
        } elseif ($settings->getOrderStatusReceiptFull() == "wc-" . $data['status']) {
            $automatic->fiscalize($order_id, '', ReceiptSubType::FULL);
        }
    } catch (AutomaticException $e) {
    } catch (\Exception $e) {
        print_r('Что то не так');
    }
    return $order_id;
}

add_action('woocommerce_admin_order_data_after_shipping_address', 'Digitalkassa_order_meta_check');

function Digitalkassa_order_meta_check($order)
{
    ?>
    <a href="https://crm.digitalkassa.ru/" target="_blank"
    style="padding: 10px 15px; background:#002365; color:#fff; text-decoration: unset;
    border-radius: 10px; width: max-content; display: block; margin: 60px 0 0 auto;">Управление чеками</a>

    <?php
}

add_action('admin_notices', 'true_custom_notice');

function true_custom_notice()
{
    if (
        isset($_GET['page'])
        && 'Digitalkassa_submenu' == $_GET['page']
        && isset($_GET['settings-updated'])
        && true == $_GET['settings-updated']
    ) {
        try {
            $transfer = new Transfer(
                new NetClientCurl(),
                new ConverterApi(),
                new LoggerFile()
            );
            $conn = new ConnectorBase($transfer);
            $conn->testSettings(new DigitalkassaSettingsConcrete(), '');
        } catch (SettingsException $e) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_attr($e->getMessage()) . '!</p></div>';
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p>Настройки сохранены!</p></div>';
    }
}

add_filter('cron_schedules', 'cron_add_ten_min');
function cron_add_ten_min($schedules)
{
    $schedules['ten_min'] = array(
        'interval' => 60 * 10,
        'display' => 'Раз в 10 минут'
    );
    return $schedules;
}

register_activation_hook(__FILE__, 'my_activation');
function my_activation()
{
    wp_clear_scheduled_hook('digitalkassa_event');
    wp_schedule_event(time(), 'ten_min', 'digitalkassa_event');
}

add_action('digitalkassa_event', 'do_this_hourly');
function do_this_hourly()
{
    if(($rootPath = realpath($_SERVER['DOCUMENT_ROOT'])) === false){
        return;
    }
    $mdk = DigitalkassaClientFactory::build();
    $pipeline = $mdk->servicePipeline();
    $pipeline->update($rootPath . '/digitalkassa.update');
    $pipeline->monitoring($rootPath . '/digitalkassa.monitoring', 'start_time');
}

register_deactivation_hook(__FILE__, 'my_deactivation');
function my_deactivation()
{
    wp_clear_scheduled_hook('digitalkassa_event');
}
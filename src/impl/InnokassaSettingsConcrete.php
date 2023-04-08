<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Digitalkassa\MDK\Settings\SettingsAbstract;

require_once plugin_dir_path(__FILE__) . '../include.php';

/**
 * Реализация настроек из массива данных
 */
class DigitalkassaSettingsConcrete extends SettingsAbstract
{
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function getActorId(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_actor_id');
    }

    /**
     * @inheritDoc
     */
    public function getActorToken(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_actor_token');
    }

    /**
     * @inheritDoc
     */
    public function getCashbox(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_cashbox');
    }

    /**
     * @inheritDoc
     */
    public function getLocation(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_place_of_settlement');
    }

    /**
     * @inheritDoc
     */
    public function getTaxation(string $siteId = ''): int
    {
        return $this->get('digitalkassa_option_taxation');
    }

    /**
     * @inheritDoc
     */
    public function getScheme(string $siteId = ''): int
    {
        return $this->get('digitalkassa_option_scheme');
    }

    /**
     * @inheritDoc
     */
    public function getVatShipping(string $siteId = ''): int
    {
        return $this->get('digitalkassa_option_delivery_vat');
    }

    /**
     * @inheritDoc
     */
    public function getVatDefaultItems(string $siteId = ''): int
    {
        return $this->get('digitalkassa_option_vat');
    }

    /**
     * @inheritDoc
     */
    public function getTypeDefaultItems(string $siteId = ''): int
    {
        return $this->get('digitalkassa_option_type_of_receipt_position');
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusReceiptPre(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_status_first_receipt');
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusReceiptFull(string $siteId = ''): string
    {
        return $this->get('digitalkassa_option_status_second_receipt');
    }

    //######################################################################

    /**
     * @inheritDoc
     */
    public function get(string $name, string $siteId = '')
    {
        return get_option($name);
    }
}

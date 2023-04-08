<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Digitalkassa\MDK\Entities\Atoms\Vat;
use Digitalkassa\MDK\Entities\Atoms\Unit;
use Digitalkassa\MDK\Entities\ReceiptItem;
use Digitalkassa\MDK\Settings\SettingsAbstract;
use Digitalkassa\MDK\Entities\Primitives\Notify;
use Digitalkassa\MDK\Entities\Atoms\PaymentMethod;
use Digitalkassa\MDK\Entities\Primitives\Customer;
use Digitalkassa\MDK\Entities\Atoms\ReceiptSubType;
use Digitalkassa\MDK\Entities\Atoms\ReceiptItemType;
use Digitalkassa\MDK\Entities\ReceiptAdapterInterface;
use Digitalkassa\MDK\Collections\ReceiptItemCollection;
use Digitalkassa\MDK\Exceptions\Base\InvalidArgumentException;

require_once plugin_dir_path(__FILE__) . '../include.php';


/**
 * Реализация адаптера чеков
 */
class DigitalkassaReceiptAdapterConcrete implements ReceiptAdapterInterface
{
    /**
     * @param SettingsAbstract $settings
     */
    public function __construct(SettingsAbstract $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function getItems(string $orderId, string $siteId, int $subType): ReceiptItemCollection
    {
        $paymentMethod = null;

        switch ($subType) {
            case ReceiptSubType::PRE:
                $paymentMethod = PaymentMethod::PREPAYMENT_FULL;
                break;
            case ReceiptSubType::FULL:
                $paymentMethod = PaymentMethod::PAYMENT_FULL;
                break;
            default:
                throw new InvalidArgumentException("invalid subType '$subType'");
        }

        $order = wc_get_order($orderId);

        $order_items = $order->get_items();

        $items = new ReceiptItemCollection();

        foreach ($order_items as $order_item) {
            $item = (new ReceiptItem())
                ->setItemId($order_item['product_id'])
                ->setName($order_item['name'])
                ->setPrice($order_item['total'] / $order_item['quantity'])
                ->setQuantity($order_item['quantity'])
                ->setPaymentMethod($paymentMethod)
                ->setType(
                    $subType == ReceiptSubType::PRE
                    ? ReceiptItemType::PAYMENT
                    : $this->settings->getTypeDefaultItems($siteId)
                )
                ->setUnit(Unit::DEFAULT)
                ->setVat(
                    $this->getVatProduct(
                        $order_item['product_id'],
                        get_option('digitalkassa_option_vat'),
                        $subType,
                        $siteId
                    )
                );

            $items[] = $item;
        }

        $data = $order->get_data();
        $deliveryPrice = $data['shipping_total'];

        if ($deliveryPrice > 0) {
            $vatShipping = $this->getVatShipping($subType, $siteId);
            $item = (new ReceiptItem())
                ->setName('Доставка')
                ->setPrice($deliveryPrice)
                ->setQuantity(1)
                ->setPaymentMethod($paymentMethod)
                ->setUnit(Unit::DEFAULT)
                ->setType(
                    $subType == ReceiptSubType::PRE
                    ? ReceiptItemType::PAYMENT
                    : ReceiptItemType::SERVICE
                )
                ->setVat($vatShipping);

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getTotal(string $orderId, string $siteId): float
    {
        $order = wc_get_order($orderId);
        $data = $order->get_data();

        // $data['total'] - в формате 250.00
        $total_price = $data['total'];
        return $total_price;
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(string $orderId, string $siteId): ?Customer
    {
        $order = wc_get_order($orderId);
        $data = $order->get_data();

        $customer = null;

        if ($data['billing']['first_name']) {
            if ($data['billing']['first_name'] && $data['billing']['last_name']) {
                $customer = new Customer();
                $customer->setName($data['billing']['first_name'] . " " . $data['billing']['last_name']);
                return $customer;
            }
            $customer = new Customer();
            $customer->setName($data['billing']['first_name']);
            return $customer;
        }

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getNotify(string $orderId, string $siteId): Notify
    {
        $order = wc_get_order($orderId);
        $data = $order->get_data();
        // $order = $this->getOrder($orderId);
        // $orderProps = $order->getPropertyCollection()->getArray();

        $notify = new Notify();

        if ($data['billing']['email']) {
            $notify->setEmail($data['billing']['email']);
        } elseif ($data['billing']['phone']) {
            $notify->setPhone($data['billing']['phone']);
        }

        return $notify;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var Order */
    private $order = null;

    //######################################################################

    /**
     * Получить НДС на продукцию
     *
     * @param integer $subType
     * @param string $siteId
     * @return Vat
     */
    private function getVatProduct(int $subType, string $siteId): Vat
    {
        $vat = (new Vat($this->settings->getVatDefaultItems($siteId)));

        if (
            !($vat->getCode() == Vat::CODE_WITHOUT || $vat->getCode() == Vat::CODE_0)
            && $subType == ReceiptSubType::PRE
        ) {
            $vatRate = $vat->getName();
            $vatRate = "$vatRate/1$vatRate";
            $vat = new Vat($vatRate);
        }

        return $vat;
    }

    /**
     * Получить НДС на доставку
     *
     * @param integer $subType
     * @param string $siteId
     * @return Vat
     */
    private function getVatShipping(int $subType, string $siteId): Vat
    {
        $vat = new Vat($this->settings->getVatShipping($siteId));

        if (($vatRate = $vat->getName()) > 0 && $subType == ReceiptSubType::PRE) {
            $vatRate = "$vatRate/1$vatRate";
            $vat = new Vat($vatRate);
        }

        return $vat;
    }
}

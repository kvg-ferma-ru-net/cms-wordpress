<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
use Digitalkassa\MDK\Client;
use Digitalkassa\MDK\Net\Transfer;
use Digitalkassa\MDK\Net\ConverterApi;
use Digitalkassa\MDK\Logger\LoggerFile;
use Digitalkassa\MDK\Net\NetClientCurl;
use Digitalkassa\MDK\Services\PipelineBase;
use Digitalkassa\MDK\Services\AutomaticBase;
use Digitalkassa\MDK\Services\ConnectorBase;
use Digitalkassa\MDK\Storage\ConverterStorage;

require_once plugin_dir_path(__FILE__) . '../include.php';

/**
 * Фабрика клиента MDK
 */
class DigitalkassaClientFactory
{
    public static function build(): Client
    {
        $receiptIdFactory = new DigitalkassaReceiptIdFactoryMetaConcrete();

        $settings = new DigitalkassaSettingsConcrete();
        $receiptStorage = new DigitalkassaReceiptStorageConcrete(
            $GLOBALS['wpdb'],
            new ConverterStorage($receiptIdFactory)
        );
        $receiptAdapter = new DigitalkassaReceiptAdapterConcrete($settings);
        $logger = new LoggerFile();
        $transfer = new Transfer(
            new NetClientCurl(),
            new ConverterApi(),
            $logger
        );

        $automatic = new AutomaticBase(
            $settings,
            $receiptStorage,
            $transfer,
            $receiptAdapter,
            $receiptIdFactory
        );
        $pipeline = new PipelineBase($settings, $receiptStorage, $transfer);
        $connector = new ConnectorBase($transfer);

        $client = new Client(
            $settings,
            $receiptStorage,
            $automatic,
            $pipeline,
            $connector,
            $logger
        );

        return $client;
    }
}

<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Digitalkassa\MDK\Entities\Receipt;
use Digitalkassa\MDK\Storage\ReceiptFilter;
use Digitalkassa\MDK\Entities\ConverterAbstract;
use Digitalkassa\MDK\Collections\ReceiptCollection;
use Digitalkassa\MDK\Storage\ReceiptStorageInterface;

require_once plugin_dir_path(__FILE__) . '../include.php';

/**
 * Реализация хранилища чеков
 */
class DigitalkassaReceiptStorageConcrete implements ReceiptStorageInterface
{
    /**
     * @param wpdb $db
     * @param ConverterAbstract $converter
     */
    public function __construct(wpdb $db, ConverterAbstract $converter)
    {
        $this->db = $db;
        $this->converter = $converter;
        $this->table = $this->db->prefix . 'digitalkassa_receipts';
    }

    /**
     * @inheritDoc
     */
    public function save(Receipt $receipt): int
    {
        $a = $this->converter->receiptToArray($receipt);
        $a = static::escapeArr($a);

        if ($receipt->getId() != 0) {
            $this->db->update($this->table, $a, array('id' => $receipt->getId()));
            return $receipt->getId();
        }

        $this->db->insert($this->table, $a, '%s');

        $id = $this->db->insert_id;
        $receipt->setId($id);

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function getOne(int $id): ?Receipt
    {
        $res = $this->db->get_results("SELECT * FROM `" . $this->table . "` WHERE `id` = " . $id, ARRAY_A);
        return $this->converter->receiptFromArray($res);
    }

    /**
     * @inheritDoc
     */
    public function getCollection(ReceiptFilter $filter, int $limit = 0): ReceiptCollection
    {
        $where = $this->where($filter);

        $res = $this->db->get_results(
            "SELECT * FROM `" . $this->table . "`
            WHERE " . $where . " ORDER BY `id` ASC " . ($limit > 0 ? "LIMIT $limit" : ''),
            ARRAY_A
        );

        $receipts = new ReceiptCollection();

        foreach ($res as $r) {
            $r['items'] = json_decode($r['items'], true);
            $r['amount'] = json_decode($r['amount'], true);
            $r['customer'] = json_decode($r['customer'], true);
            $r['notify'] = json_decode($r['notify'], true);

            $receipt = $this->converter->receiptFromArray($r);
            $receipts[] = $receipt;
        }

        return $receipts;
    }

    /**
     * @inheritDoc
     */
    public function min(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);
        $result = $this->db->get_results("SELECT MIN(`" . $column . "`) FROM `" . $this->table . "` WHERE " . $where, ARRAY_A);
        return current($result);
    }

    /**
     * @inheritDoc
     */
    public function max(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);
        $result = $this->db->get_results("SELECT MAX(`" . $column . "`) FROM `" . $this->table . "` WHERE " . $where, ARRAY_A);
        return current($result);
    }

    /**
     * @inheritDoc
     */
    public function count(ReceiptFilter $filter): int
    {
        $where = $this->where($filter);
        $result = $this->db->get_results("SELECT COUNT(*) FROM `" . $this->table . "` WHERE " . $where, ARRAY_A);
        return current($result);
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var wpdb */
    private $db = null;

    /** @var ConverterAbstract */
    private $converter = null;


    //######################################################################

    /**
     * Обработка массива данных для передачи в SQL запрос
     *
     * @param array $a
     * @return array
     */
    protected function escapeArr(array $a): array
    {
        foreach ($a as $key => $value) {
            // if (is_string($value)) {
            //     $a[$key] = sprintf("'%s'", esc_sql($value));
            // } elseif (is_array($value)) {
            //     $a[$key] = sprintf("'%s'", esc_sql(json_encode($value, JSON_UNESCAPED_UNICODE)));
            // }
            if (is_array($value)) {
                $a[$key] = sprintf("%s", json_encode($value, JSON_UNESCAPED_UNICODE));
            }
        }

        return $a;
    }

    private function where(ReceiptFilter $filter): string
    {
        $aWhere = $filter->toArray();
        $aWhere2 = [];
        foreach ($aWhere as $key => $value) {
            $val = $value['value'];
            if ($val === null) {
                $val = 'null';
            } elseif (is_array($val)) {
                $val = '(' . implode(',', $val) . ')';

                if ($value['op'] == '=') {
                    $value['op'] = ' IN ';
                } else {
                    $value['op'] = ' NOT IN ';
                }
            } else {
                $val = "'$val'";
            }
            $op = $value['op'];
            $aWhere2[] = "{$key}{$op}$val";
        }

        $where = implode(' AND ', $aWhere2);
        return $where;
    }
}

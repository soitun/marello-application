<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Strategy\MinimumQuantity\Calculator\Chain\Element\MultipleWarehouses;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Marello\Bundle\InventoryBundle\Model\OrderWarehouseResult;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use MarelloEnterprise\Bundle\InventoryBundle\Strategy\MinimumQuantity\Calculator\Chain\Element\AbstractWHCalculatorChainElement;

class MultipleWHCalculatorChainElement extends AbstractWHCalculatorChainElement
{
    /**
     * @var array
     */
    protected $results = [];

    /**
     * {@inheritdoc}
     */
    public function calculate(
        array $productsByWh,
        array $orderItemsByProducts,
        array $warehouses,
        Collection $orderItems
    ) {
        $products = array_map(
            function ($sku) {
                return strstr($sku, '_|_', true);
            },
            array_keys($orderItemsByProducts)
        );
        $this->getMultipleWarehouseResults(
            $productsByWh,
            $orderItemsByProducts,
            $warehouses,
            $products
        );

        if (count($this->results) >= 1) {
            if (count($this->results) === 1) {
                return $this->results;
            }

            usort($this->results, function ($a, $b) {
                return count($b) > count($a) ? -1 : 1;
            });

            $finalResults = array_filter($this->results, function ($result) {
                return count($result) <= count(reset($this->results));
            });
            
            return $this->usort($finalResults);
        } elseif ($this->getSuccessor()) {
            return $this->getSuccessor()->calculate($productsByWh, $orderItemsByProducts, $warehouses, $orderItems);
        }

        return [];
    }

    /**
     * @param array $productsByWh
     * @param array $orderItemsByProducts
     * @param array $warehouses array of warehouses
     * @param array $products array of products extracted from the order items
     * @param int|null $idx
     */
    protected function getMultipleWarehouseResults(
        $productsByWh,
        $orderItemsByProducts,
        $warehouses,
        $products,
        $idx = null
    ) {
        $wh = [];
        $itemsWithQuantity = [];
        foreach ($productsByWh as $id => $whProducts) {
            foreach ($whProducts as $product) {
                $totalQty = 0;
                if (isset($wh[$product['sku']])) {
                    $totalQty = $wh[$product['sku']]['totalAllocatedQty'];
                }
                $wh[$product['wh']][$product['sku']] = [
                    'totalAllocatedQty' => $totalQty + $product['qty'],
                    'totalQtyOrdered' => $product['qtyOrdered'],
                    'qtyGtq' => (bool)($totalQty + $product['qty'] >= $product['qtyOrdered'])
                ];
            }
        }

        foreach ($wh as $warehouseCode => $product) {
            $index = $idx !== null ? $idx : count($this->results);
            $matchedOrderItems = new ArrayCollection();
            foreach ($product as $productSku => $inventoryData) {
                /**
                 * @var  $combinedSku string
                 * @var  $orderItem OrderItem
                 */
                foreach ($orderItemsByProducts as $combinedSku => $orderItem) {
                    $sku = strstr($combinedSku, '_|_', true);
                    if ($productSku === $sku && !$matchedOrderItems->contains($orderItem)) {
                        $matchedOrderItems->add($orderItem);
                        $itemsWithQuantity[$productSku] = $inventoryData['totalAllocatedQty'];
                    }
                }
            }

            if (count($matchedOrderItems) > 0) {
                // add the products to the warehouse results
                $this->results[$index][implode('|', array_keys($product))] = new OrderWarehouseResult(
                    [
                        OrderWarehouseResult::WAREHOUSE_FIELD => $warehouses[$warehouseCode],
                        OrderWarehouseResult::ORDER_ITEMS_FIELD => $matchedOrderItems,
                        OrderWarehouseResult::ITEMS_WITH_QUANTITY_FIELD => $itemsWithQuantity
                    ]
                );
            }
        }
    }
}

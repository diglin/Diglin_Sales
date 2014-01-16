<?php
/**
 * Diglin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Diglin
 * @package     Diglin_Sales
 * @copyright   Copyright (c) 2011-2014 Diglin (http://www.diglin.com)
 * @author      Sylvain Rayé <firstname.lastname@diglin.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Diglin_Sales_Model_Order_Invoice_Total_Tax extends Mage_Sales_Model_Order_Invoice_Total_Tax
{
    /**
     * Collect invoice tax amount
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $totalTax       = 0;
        $baseTotalTax   = 0;
        $totalHiddenTax      = 0;
        $baseTotalHiddenTax  = 0;

        $order = $invoice->getOrder();
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $orderItemQty = $orderItem->getQtyOrdered();

            // diglin - fix missing hidden tax amount condition
            if (($orderItem->getTaxAmount() || $orderItem->getHiddenTaxAmount()) && $orderItemQty) {
                if ($item->getOrderItem()->isDummy()) {
                    continue;
                }

                /**
                 * Resolve rounding problems
                 */
                $tax            = $orderItem->getTaxAmount() - $orderItem->getTaxInvoiced();
                $baseTax        = $orderItem->getBaseTaxAmount() - $orderItem->getBaseTaxInvoiced();
                $hiddenTax      = $orderItem->getHiddenTaxAmount() - $orderItem->getHiddenTaxInvoiced();
                $baseHiddenTax  = $orderItem->getBaseHiddenTaxAmount() - $orderItem->getBaseHiddenTaxInvoiced();
                if (!$item->isLast()) {
                    $availableQty  = $orderItemQty - $orderItem->getQtyInvoiced();
                    $tax           = $invoice->roundPrice($tax / $availableQty * $item->getQty());
                    $baseTax       = $invoice->roundPrice($baseTax / $availableQty * $item->getQty(), 'base');
                    $hiddenTax     = $invoice->roundPrice($hiddenTax / $availableQty * $item->getQty());
                    $baseHiddenTax = $invoice->roundPrice($baseHiddenTax / $availableQty * $item->getQty(), 'base');
                }
                

                $item->setTaxAmount($tax);
                $item->setBaseTaxAmount($baseTax);
                $item->setHiddenTaxAmount($hiddenTax);
                $item->setBaseHiddenTaxAmount($baseHiddenTax);

                $totalTax += $tax;
                $baseTotalTax += $baseTax;
                $totalHiddenTax += $hiddenTax;
                $baseTotalHiddenTax += $baseHiddenTax;
            }
        }

        if ($this->_canIncludeShipping($invoice)) {
            $totalTax           += $order->getShippingTaxAmount();
            $baseTotalTax       += $order->getBaseShippingTaxAmount();
            $totalHiddenTax     += $order->getShippingHiddenTaxAmount();
            $baseTotalHiddenTax += $order->getBaseShippingHiddenTaxAmount();
            $invoice->setShippingTaxAmount($order->getShippingTaxAmount());
            $invoice->setBaseShippingTaxAmount($order->getBaseShippingTaxAmount());
            $invoice->setShippingHiddenTaxAmount($order->getShippingHiddenTaxAmount());
            $invoice->setBaseShippingHiddenTaxAmount($order->getBaseShippingHiddenTaxAmount());
        }
        $allowedTax     = $order->getTaxAmount() - $order->getTaxInvoiced();
        $allowedBaseTax = $order->getBaseTaxAmount() - $order->getBaseTaxInvoiced();;
        $allowedHiddenTax     = $order->getHiddenTaxAmount() + $order->getShippingHiddenTaxAmount()
            - $order->getHiddenTaxInvoiced() - $order->getShippingHiddenTaxInvoiced();
        $allowedBaseHiddenTax = $order->getBaseHiddenTaxAmount() + $order->getBaseShippingHiddenTaxAmount()
            - $order->getBaseHiddenTaxInvoiced() - $order->getBaseShippingHiddenTaxInvoiced();

        if ($invoice->isLast()) {
            $totalTax           = $allowedTax;
            $baseTotalTax       = $allowedBaseTax;
            $totalHiddenTax     = $allowedHiddenTax;
            $baseTotalHiddenTax = $allowedBaseHiddenTax;
        } else {
            $totalTax           = min($allowedTax, $totalTax);
            $baseTotalTax       = min($allowedBaseTax, $baseTotalTax);
            $totalHiddenTax     = min($allowedHiddenTax, $totalHiddenTax);
            $baseTotalHiddenTax = min($allowedBaseHiddenTax, $baseTotalHiddenTax);
        }

        $invoice->setTaxAmount($totalTax);
        $invoice->setBaseTaxAmount($baseTotalTax);
        $invoice->setHiddenTaxAmount($totalHiddenTax);
        $invoice->setBaseHiddenTaxAmount($baseTotalHiddenTax);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $totalTax + $totalHiddenTax);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseTotalTax + $baseTotalHiddenTax);

        return $this;
    }
}

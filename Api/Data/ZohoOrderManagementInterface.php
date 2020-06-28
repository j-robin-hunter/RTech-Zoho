<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoOrderManagementInterface {

  /**
  * Create Zoho estimate from a quote
  *
  * @param string $contactId
  * @param Magento\Quote\Model\Quote $quote
  * @param float $shippingAmount
  * @return array
  */
  public function quoteEstimate($contactId, $quote, $shippingAmount);

  /**
  * Create Zoho estimate from an order
  *
  * @param Magento\Sales\Model\Order $order
  * @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function orderEstimate($order);

  /**
  * Update Zoho estimate
  *
  * @param string $estimateId
  * @param string $contactId
  * @param Magento\Quote\Model\Quote|Magento\Sales\Model\Order $source
  * @param float $shippingAmount
  * @return array
  */
  public function updateEstimate($estimateId, $contactId, $source, $shippingAmount);

  /**
  * Accept Zoho estimate
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  */
  public function acceptEstimate($zohoSalesOrderManagement);

  /**
  * Create Zoho sales order
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @param Magento\Sales\Model\Order $order
  * @param string|null $ref
  * @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function createSalesOrder($zohoSalesOrderManagement, $order, $ref);

  /**
  * Mark Zoho sales order open
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  */
  public function openSalesOrder($zohoSalesOrderManagement);

  /**
  * Create Zoho invoice
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @param Magento\Sales\Model\Order $order
  * @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function createInvoice($zohoSalesOrderManagement, $order);

  /**
  * Update Magento stock from Zoho inventory stock
  *
  * @param \Magento\Sales\Model\ResourceModel\Order\Shipment $shipment
  */
  public function updateStock($shipment);
  
  /**
  * Create Zoho package and shipment
  *
  * @param \Magento\Sales\Model\ResourceModel\Order\Shipment $shipment
  */
  public function createShipment($shipment);

  /**
  * Create a Zoho credit note
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @param Magento\Sales\Model\Order\Creditmemo $creditmemo
  */
  public function createCreditNote($zohoSalesOrderManagement, $creditmemo);

  /**
  * Delete all Zoho Books sales order management documents. This will not complete
  * if an order has payed invoicves or credit notes
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  */
  public function deleteAll($zohoSalesOrderManagement);
}
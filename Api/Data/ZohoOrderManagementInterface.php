<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoOrderManagementInterface {

  /**
  * Create Zoho estimate
  *
  * @param Magento\Sales\Model\Order $order
  * @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function createEstimate($order);

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
  * @return @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function createSalesOrder($zohoSalesOrderManagement, $order);

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
  * @return @return RTech\Zoho\Data\ZohoSalesOrderManagementInterface
  */
  public function createInvoice($zohoSalesOrderManagement, $order);

  /**
  * Delete all Zoho Books sales order management documents
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  */
  public function deleteAll($zohoSalesOrderManagement);
}
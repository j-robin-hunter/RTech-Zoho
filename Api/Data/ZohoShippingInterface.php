<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoShippingInterface {

  /**
  * Create Zoho package and shipment
  *
  * @param RTech\Zoho\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @param Magento\Sales\Model\Order $order
  */
  public function createShipment($zohoSalesOrderManagement, $order);
}
<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api;

use RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface;

interface ZohoSalesOrderManagementRepositoryInterface {

  /**
  * Retrive by Magento sales order id
  *
  * @param int $productId
  * @return \RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface
  * @throws \Magento\Framework\Exception\NoSuchEntityException
  */
  public function getById($salesOrderId);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @return \RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface
  */
  public function save(ZohoSalesOrderManagementInterface $zohoSalesOrderManagement);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface $zohoSalesOrderManagement
  * @return \RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface
  */
  public function delete(ZohoSalesOrderManagementInterface $zohoSalesOrderManagement);
}
<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoSalesOrderManagementRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoSalesOrderManagementRepository implements ZohoSalesOrderManagementRepositoryInterface {

  protected $zohoSalesOrderManagementFactory;

  public function __construct(
    ZohoSalesOrderManagementFactory $zohoSalesOrderManagementFactory
  ) {
    $this->zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
  }

  /**
  * @inheritdoc
  */
  public function getById($orderId) {
    $zohoSalesOrderManagement = $this->zohoSalesOrderManagementFactory->create();
    $zohoSalesOrderManagement->getResource()->load($zohoSalesOrderManagement, $orderId);
    if (!$zohoSalesOrderManagement->getId()) {
      throw new NoSuchEntityException(__('No Zoho Sales Order Management entry for order with id "%1" exists.', $orderId));
    }
    return $zohoSalesOrderManagement;
  }

  /**
  * @inheritdoc
  */
  public function save($zohoSalesOrderManagement) {
    try {
      $zohoSalesOrderManagement->getResource()->save($zohoSalesOrderManagement);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoSalesOrderManagement;
  }

  /**
  * @inheritdoc
  */
  public function delete($zohoSalesOrderManagement) {
    try {
      $zohoSalesOrderManagement->getResource()->delete($zohoSalesOrderManagement);
    } catch (\Exception $exception) {
      throw new CouldNotDeleteException(__($exception->getMessage()));
    }
    return $zohoSalesOrderManagement;
  }
}
<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoSalesOrderManagementRepositoryInterface;
use RTech\Zoho\Model\ResourceModel\ZohoSalesOrderManagement as ZohoSalesOrderManagementResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoSalesOrderManagementRepository implements ZohoSalesOrderManagementRepositoryInterface {

  protected $zohoSalesOrderManagementResource;
  protected $zohoSalesOrderManagementFactory;

  public function __construct(
    ZohoSalesOrderManagementResource $zohoSalesOrderManagementResource,
    ZohoSalesOrderManagementFactory $zohoSalesOrderManagementFactory
  ) {
    $this->zohoSalesOrderManagementResource = $zohoSalesOrderManagementResource;
    $this->zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
  }

  /**
  * @inheritdoc
  */
  public function getId($orderId) {
    $zohoSalesOrderManagement = $this->zohoSalesOrderManagementFactory->create();
    $this->zohoSalesOrderManagementResource->load($zohoSalesOrderManagement, $orderId);
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
      $this->zohoSalesOrderManagementResource->save($zohoSalesOrderManagement);
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
      $this->zohoSalesOrderManagementResource->delete($zohoSalesOrderManagement);
    } catch (\Exception $exception) {
      throw new CouldNotDeleteException(__($exception->getMessage()));
    }
    return $zohoSalesOrderManagement;
  }
}
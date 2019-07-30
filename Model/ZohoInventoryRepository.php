<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoInventoryRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoInventoryRepository implements ZohoInventoryRepositoryInterface {

  protected $zohoInventoryFactory;

  public function __construct(
    ZohoInventoryFactory $zohoInventoryFactory
  ) {
    $this->zohoInventoryFactory = $zohoInventoryFactory;
  }

  /**
  * @inheritdoc
  */
  public function getById($productId) {
    $zohoInventory = $this->zohoInventoryFactory->create();
    $zohoInventory->getResource()->load($zohoInventory, $productId);
    if (!$zohoInventory->getId()) {
      throw new NoSuchEntityException(__('No Zoho Inventory entry for product with id "%1" exists.', $productId));
    }
    return $zohoInventory;
  }

  /**
  * @inheritdoc
  */
  public function save($zohoInventory) {
    try {
      $zohoInventory->getResource()->save($zohoInventory);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoInventory;
  }

}

<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoInventoryRepositoryInterface;
use RTech\Zoho\Model\ResourceModel\ZohoInventory as ZohoInventoryResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoInventoryRepository implements ZohoInventoryRepositoryInterface {
  protected $zohoInventoryResource;
  protected $zohoInventoryFactory;

  public function __construct(
    ZohoInventoryResource $zohoInventoryResource,
    ZohoInventoryFactory $zohoInventoryFactory
  ) {
    $this->zohoInventoryResource = $zohoInventoryResource;
    $this->zohoInventoryFactory = $zohoInventoryFactory;
  }

  /**
  * @inheritdoc
  */
  public function getId($productId) {
    $zohoInventory = $this->zohoInventoryFactory->create();
    $response = $this->zohoInventoryResource->load($zohoInventory, $productId);
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
      $this->zohoInventoryResource->save($zohoInventory);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoInventory;
  }

}

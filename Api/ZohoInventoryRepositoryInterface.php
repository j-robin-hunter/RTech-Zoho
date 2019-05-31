<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api;

use RTech\Zoho\Api\Data\ZohoInventoryInterface;

interface ZohoInventoryRepositoryInterface {

  /**
  * Retrive by Magento product id
  *
  * @param int $productId
  * @return \RTech\Zoho\Api\Data\ZohoInventoryInterface
  * @throws \Magento\Framework\Exception\NoSuchEntityException
  */
  public function getId($productId);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoInventoryInterface $zohoInventory
  * @return \RTech\Zoho\Api\Data\ZohoInventoryInterface
  */
  public function save(ZohoInventoryInterface $zohoInventory);

}
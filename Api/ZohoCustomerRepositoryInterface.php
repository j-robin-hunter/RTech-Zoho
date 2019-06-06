<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api;

use RTech\Zoho\Api\Data\ZohoCustomerInterface;

interface ZohoCustomerRepositoryInterface {

  /**
  * Retrive by Magento cusotmer id
  *
  * @param int $customerId
  * @return \RTech\Zoho\Api\Data\ZohoCustomerInterface
  * @throws \Magento\Framework\Exception\NoSuchEntityException
  */
  public function getId($customerId);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoCustomerInterface $zohoCustomer
  * @return \RTech\Zoho\Api\Data\ZohoCustomerInterface
  */
  public function save(ZohoCustomerInterface $zohoCustomer);

}
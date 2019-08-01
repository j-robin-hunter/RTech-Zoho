<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoCustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoCustomerRepository implements ZohoCustomerRepositoryInterface {

  protected $zohoCustomerFactory;

  public function __construct(
    ZohoCustomerFactory $zohoCustomerFactory
  ) {
    $this->zohoCustomerFactory = $zohoCustomerFactory;
  }

  /**
  * @inheritdoc
  */
  public function getById($customerId) {
    $zohoCustomer = $this->zohoCustomerFactory->create();
    $response = $zohoCustomer->getResource()->load($zohoCustomer, $customerId);
    if (empty($zohoCustomer->getId())) {
      throw new NoSuchEntityException(__('No Zoho Books entry for customer with id "%1" exists.', $customerId));
    }
    return $zohoCustomer;
  }

  /**
  * @inheritdoc
  */
  public function save($zohoCustomer) {
    try {
      $zohoCustomer->getResource()->save($zohoCustomer);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoCustomer;
  }

}
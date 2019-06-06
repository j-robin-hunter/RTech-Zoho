<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\ZohoCustomerRepositoryInterface;
use RTech\Zoho\Model\ResourceModel\ZohoCustomer as ZohoCustomerResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoCustomerRepository implements ZohoCustomerRepositoryInterface {
  protected $zohoCustomerResource;
  protected $zohoCustomerFactory;

  public function __construct(
    ZohoCustomerResource $zohoCustomerResource,
    ZohoCustomerFactory $zohoCustomerFactory
  ) {
    $this->zohoCustomerResource = $zohoCustomerResource;
    $this->zohoCustomerFactory = $zohoCustomerFactory;
  }

  /**
  * @inheritdoc
  */
  public function getId($customerId) {
    $zohoCustomer = $this->zohoCustomerFactory->create();
    $response = $this->zohoCustomerResource->load($zohoCustomer, $customerId);
    if (!$zohoCustomer->getId()) {
      throw new NoSuchEntityException(__('No Zoho Books entry for customer with id "%1" exists.', $customerId));
    }
    return $zohoCustomer;
  }

  /**
  * @inheritdoc
  */
  public function save($zohoCustomer) {
    try {
      $this->zohoCustomerResource->save($zohoCustomer);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoCustomer;
  }

}
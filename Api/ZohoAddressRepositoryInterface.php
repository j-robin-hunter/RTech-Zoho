<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api;

use RTech\Zoho\Api\Data\ZohoAddressInterface;

interface ZohoAddressRepositoryInterface {

  /**
  * Retrive by Magento customer address id
  *
  * @param int $customerAddressId
  * @return \RTech\Zoho\Api\Data\ZohoAddressInterface
  * @throws \Magento\Framework\Exception\NoSuchEntityException
  */
  public function getById($customerAddressId);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoAddressInterface $zohoAddress
  * @return \RTech\Zoho\Api\Data\ZohoAddressInterface
  * @throws \Magento\Framework\Exception\CouldNotSaveException
  */
  public function save(ZohoAddressInterface $zohoAddress);

  /**
  * @param \RTech\Zoho\Api\Data\ZohoAddressInterface $zohoAddress
  * @return bool true on success
  * @throws \Magento\Framework\Exception\CouldNotDeleteException
  */
  public function delete(ZohoAddressInterface $zohoAddress);

  /**
  * Retrieve customers addresses matching the specified criteria.
  *
  * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
  * @return \RTech\Zoho\Api\Data\ZohoAddressSearchResultsInterface
  * @throws \Magento\Framework\Exception\LocalizedException
  */
  public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
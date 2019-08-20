<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;


use RTech\Zoho\Api\ZohoAddressRepositoryInterface;
use RTech\Zoho\Model\ResourceModel\ZohoAddress\CollectionFactory as ZohoAddressCollectionFactory;
use RTech\Zoho\Model\ResourceModel\ZohoAddress\Collection;
use RTech\Zoho\Api\Data\ZohoAddressSearchResultsInterface;
use RTech\Zoho\Api\Data\ZohoAddressSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ZohoAddressRepository implements ZohoAddressRepositoryInterface {

  protected $zohoAddressFactory;
  protected $zohoAddressCollectionFactory;
  protected $zohoAddressSearchResultsFactory;

  public function __construct(
    ZohoAddressFactory $zohoAddressFactory,
    ZohoAddressCollectionFactory $zohoAddressCollectionFactory,
    ZohoAddressSearchResultsInterfaceFactory $zohoAddressSearchResultsFactory
  ) {
    $this->zohoAddressFactory = $zohoAddressFactory;
    $this->zohoAddressCollectionFactory = $zohoAddressCollectionFactory;
    $this->zohoAddressSearchResultsFactory = $zohoAddressSearchResultsFactory;
  }

  /**
  * @inheritdoc
  */
  public function getById($customerAddressId) {
    $zohoAddress = $this->zohoAddressFactory->create();
    $response = $zohoAddress->getResource()->load($zohoAddress, $customerAddressId);
    if (!$zohoAddress->getId()) {
      throw new NoSuchEntityException(__('No Zoho Books entry for address with id "%1" exists.', $customerAddressId));
    }
    return $zohoAddress;
  }

  /**
  * @inheritdoc
  */
  public function save($zohoAddress) {
    try {
      $zohoAddress->getResource()->save($zohoAddress);
    } catch (\Exception $exception) {
      throw new CouldNotSaveException(__($exception->getMessage()));
    }
    return $zohoAddress;
  }

  /**
  * @inheritdoc
  */
  public function delete($zohoAddress) {
    try {
      return $zohoAddress->getResource()->delete($zohoAddress);
    } catch (\Exception $exception) {
      throw new CouldNotDeleteException(__($exception->getMessage()));
    }
  }

  /**
  * @inheritdoc
  */
  public function getList(SearchCriteriaInterface $searchCriteria) {
    $collection = $this->zohoAddressCollectionFactory->create();
    $this->addFiltersToCollection($searchCriteria, $collection);
    $collection->load();
    return $this->buildSearchResult($searchCriteria, $collection);
  }

  private function addFiltersToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection) {
    foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
      $fields = $conditions = [];
      foreach ($filterGroup->getFilters() as $filter) {
        $fields[] = $filter->getField();
        $conditions[] = [$filter->getConditionType() => $filter->getValue()];
      }
      $collection->addFieldToFilter($fields, $conditions);
    }
  }

  private function buildSearchResult(SearchCriteriaInterface $searchCriteria, Collection $collection) {
    $searchResults = $this->zohoAddressSearchResultsFactory->create();
    $searchResults->setSearchCriteria($searchCriteria);
    $searchResults->setItems($collection->getItems());
    $searchResults->setTotalCount($collection->getSize());
    return $searchResults;
  }
}
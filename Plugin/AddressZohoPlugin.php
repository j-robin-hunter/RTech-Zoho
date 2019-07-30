<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoBooksClient;

class AddressZohoPlugin {

  protected $_zohoClient;
  protected $_zohoCustomerRepository;
  protected $_zohoAddressRepository;
  protected $_searchCriteriaBuilder;
  protected $_customerRepository;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoAddressRepository $zohoAddressRepository,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoAddressRepository = $zohoAddressRepository;
    $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->_customerRepository = $customerRepository;
  }

  public function beforeDelete (
    \Magento\Customer\Model\ResourceModel\Address $subject,
    \Magento\Customer\Model\Address $address
  ) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('beforeDelete');

    $address = $address->getDataModel();
    $this->_logger->info($address->getId());
    $this->_logger->info($address->getCustomerId());
    $this->_logger->info($address->isDefaultShipping());
    $this->_logger->info($address->isDefaultBilling());

    // Locate any exisitng zoho_address record for this address
    $searchCriteria = $this->_searchCriteriaBuilder->
      addFilter('customer_address_id', $address->getId(), 'eq')->
      addFilter('customer_id', $address->getCustomerId(), 'eq')->
      create();
    $zohoAddresses = $this->_zohoAddressRepository->getList($searchCriteria);
    $this->_logger->info('number of addresses: ' . count($zohoAddresses->getItems()));

    $zohoCustomerId = $this->_zohoCustomerRepository->getById($address->getCustomerId())->getZohoId();

    if ($address->isDefaultBilling() || $address->isDefaultShipping()) {
      $nullContact = array_fill_keys(array('attention', 'address', 'street2', 'city', 'state', 'zip', 'country', 'phone', 'fax'), '');
      if ($address->isDefaultBilling()) {
        // Process billing address
        $this->_logger->info('process billing');
        $customer = $this->_customerRepository->getById($address->getCustomerId());
        $zohoContact = [
          'contact_id' => $zohoCustomerId,
          'billing_address' => $nullContact
        ];
        $this->_logger->info($zohoContact);
        $zohoContact = $this->_zohoClient->updateContact($zohoContact);
      }
      if ($address->isDefaultShipping()) {
        // Process shipping address
        $this->_logger->info('process shipping');
        $zohoContact = [
          'contact_id' => $zohoCustomerId,
          'shipping_address' => $nullContact
        ];
        $this->_logger->info($zohoContact);
        $zohoContact = $this->_zohoClient->updateContact($zohoContact);
      }
    } else {
      // Process additional address
      $this->_logger->info('process additional');
      if (!empty($zohoAddresses->getItems())) {
        $item = current($zohoAddresses->getItems());
        $this->_logger->info($item->getId());
        $this->_logger->info($item->getCustomerId());
        $this->_logger->info($item->getBilling());
        $this->_logger->info($item->getShipping());
        $this->_logger->info($item->getZohoAddressId());

        $zohoAddressId = $this->_zohoClient->deleteAddress($zohoCustomerId, $item->getZohoAddressId());
      }
    }
  }
}
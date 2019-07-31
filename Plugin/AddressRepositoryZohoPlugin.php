<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;

class AddressRepositoryZohoPlugin {

  protected $_zohoClient;
  protected $_zohoCustomerRepository;
  protected $_zohoAddressRepository;
  protected $_zohoAddressFactory;
  protected $_contactHelper;
  protected $_searchCriteriaBuilder;
  protected $_customerRepository;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoAddressRepository $zohoAddressRepository,
    \RTech\Zoho\Model\ZohoAddressFactory $zohoAddressFactory,
    \RTech\Zoho\Helper\ContactHelper $contactHelper,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoAddressRepository = $zohoAddressRepository;
    $this->_zohoAddressFactory = $zohoAddressFactory;
    $this->_contactHelper = $contactHelper;
    $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->_customerRepository = $customerRepository;
    $this->_logger = $logger;
  }

  public function aroundSave (
    \Magento\Customer\Api\AddressRepositoryInterface $subject,
    callable $save,
    \Magento\Customer\Api\Data\AddressInterface $address
  ) {
    // Call save to persist address to database
    $savedAddress = $save($address);

    $addBilling = $address->isDefaultBilling();
    $removeBilling = (!$address->isDefaultBilling() && $savedAddress->isDefaultBilling());
    $addShipping = $address->isDefaultShipping();
    $removeShipping = (!$address->isDefaultShipping() && $savedAddress->isDefaultShipping());
    $this->updateZoho($address, $addBilling, $addShipping, $removeBilling, $removeShipping);

    return $savedAddress;
  }

  private function updateZoho($address, $addBilling, $addShipping, $removeBilling, $removeShipping) {

    $customerId = $address->getCustomerId();
    $customer = $this->_customerRepository->getById($customerId);
    $contactName = $this->_contactHelper->getContactName(
      $customer->getPrefix(),
      $customer->getFirstname(),
      $customer->getMiddlename(),
      $customer->getLastname(),
      $customer->getSuffix()
    );

    $zohoCustomerId = $this->_zohoCustomerRepository->getById($customerId)->getZohoId();

    $zohoAddressArray = $this->_contactHelper->getAddressArray($address);
    $nullArray = array_fill_keys(array_keys($zohoAddressArray), '');

    try {
      // Locate any exisitng zoho_address record for this address
      $searchCriteria = $this->_searchCriteriaBuilder->
        addFilter('customer_address_id', $address->getId(), 'eq')->
        addFilter('customer_id', $customerId, 'eq')->
        create();
      $zohoAddresses = $this->_zohoAddressRepository->getList($searchCriteria);
      $zohoAddress = current($zohoAddresses->getItems());

      $zohoContact = [
        'contact_id' => $zohoCustomerId,
      ];

      $zohoAddressId = '';
      if ($addBilling) {
        $vat = $this->_contactHelper->vatBillingTreatment($address, $customer->getGroupId());
        $zohoContact['contact_name'] = empty($vat['company_name']) ? $contactName : $vat['company_name'];
        $zohoContact['company_name'] = empty($vat['company_name']) ? '' : substr(trim($vat['company_name']), 0, 200);
        $zohoContact['vat_reg_no'] = $vat['vat_reg_no'];
        $zohoContact['vat_treatment'] = $vat['vat_treatment'];
        $zohoContact['country_code'] = $vat['country_code'];
        $zohoContact['billing_address'] = $zohoAddressArray;
        if ($removeShipping) {
          $zohoContact['contact_name'] = $contactName;
          $zohoContact['shipping_address'] = $nullArray;
        }
        $this->_zohoClient->updateContact($zohoContact);
      }

      if ($addShipping) {
        $zohoContact['shipping_address'] = $zohoAddressArray;
        if ($removeBilling) {
          $zohoContact['billing_address'] = $nullArray;
        }
        $zohoContact = $this->_zohoClient->updateContact($zohoContact);
      }

      if (!$addBilling && !$addShipping) {
        if (!empty($zohoAddress)) {
          if ($zohoAddress->getBilling() || $zohoAddress->getShipping()) {
            $currentZohoContact = $this->_zohoClient->getContact($zohoCustomerId);
            $zohoContact['contact_name'] = $currentZohoContact['contact_name'];
            if (isset($currentZohoContact['billing_address'])) {
              $zohoContact['billing_address'] = array_merge(array('address_id' => $currentZohoContact['billing_address']['address_id']) , $nullArray);
            }
            if (isset($currentZohoContact['shipping_address'])) {
              $zohoContact['shipping_address'] = array_merge(array('address_id'=> $currentZohoContact['shipping_address']['address_id']) , $nullArray);
            }
            $zohoContact = $this->_zohoClient->updateContact($zohoContact);
            $zohoAddressId = $this->_zohoClient->addAddress($zohoCustomerId, $zohoAddressArray)['address_id'];
          } else {
            $zohoAddressId = $this->_zohoClient->updateAddress($zohoCustomerId, $zohoAddress->getZohoAddressId(), $zohoAddressArray)['address_id'];
          }
        } else {
          $zohoAddressId = $this->_zohoClient->addAddress($zohoCustomerId, $zohoAddressArray)['address_id'];
        }
      } else {
        if (!empty($zohoAddress)) {
          if (!$zohoAddress->getBilling() && !$zohoAddress->getShipping() && $zohoAddress->getZohoAddressId()) {
            $this->_zohoClient->deleteAddress($zohoCustomerId, $zohoAddress->getZohoAddressId());
          }
        }
      }

      $this->saveZohoAddress($address->getId(), $customerId, $addBilling, $addShipping, $zohoAddressId);

    } catch (\Exception $ex) {
      $this->_logger->error(__('Error while saving address to Zoho Books: ' . $ex->getMessage()));
    }
  }

  private function saveZohoAddress($customerAddressId, $customerId, $billing, $shipping, $zohoAddressId) {
    $zohoAddress = $this->_zohoAddressFactory->create();
    $zohoAddress->setData([
      'customer_address_id' => $customerAddressId,
      'customer_id' => $customerId,
      'billing' => $billing,
      'shipping' => $shipping,
      'zoho_address_id' => $zohoAddressId
    ]);
    $this->_zohoAddressRepository->save($zohoAddress);
  }
}
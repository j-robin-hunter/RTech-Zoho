<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use RTech\Zoho\Api\Data\ZohoAddressInterface;
use RTech\Zoho\Api\Data\ZohoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AddressRepositoryZohoPlugin {
  const BILLING = 0;
  const SHIPPING = 1;

  protected $_zohoClient;
  protected $_zohoCustomerRepository;
  protected $_zohoCustomerFactory;
  protected $_zohoAddressRepository;
  protected $_zohoAddressFactory;
  protected $_contactHelper;
  protected $_searchCriteriaBuilder;
  protected $_customerRepository;
  protected $_addressRepository;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoCustomerFactory $zohoCustomerFactory,
    \RTech\Zoho\Model\ZohoAddressRepository $zohoAddressRepository,
    \RTech\Zoho\Model\ZohoAddressFactory $zohoAddressFactory,
    \RTech\Zoho\Helper\ContactHelper $contactHelper,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
    \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoAddressRepository = $zohoAddressRepository;
    $this->_zohoCustomerFactory = $zohoCustomerFactory;
    $this->_zohoAddressFactory = $zohoAddressFactory;
    $this->_contactHelper = $contactHelper;
    $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->_customerRepository = $customerRepository;
    $this->_addressRepository = $addressRepository;
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

    try {
      $zohoCustomerId = $this->_zohoCustomerRepository->getById($customerId)->getZohoId();
    } catch (NoSuchEntityException $ex) {
      // It is possible to not find an entry in the zoho_customer table when
      // a customer registers after placing an order. As such a customer must
      // be looked up and linked if one is found
      $contact = $this->_zohoClient->lookupContact(
        $this->_contactHelper->getContactName(
          $customer->getPrefix(),
          $customer->getFirstname(),
          $customer->getMiddlename(),
          $customer->getLastname(),
          $customer->getSuffix()
        ),
        $customer->getEmail());
      $zohoCustomerId = $contact['contact_id'];
      // Create entry in zoho_customer table
      $zohoCustomer = $this->_zohoCustomerFactory->create();
      $zohoCustomer->setData([
        ZohoCustomerInterface::CUSTOMER_ID => $customer->getId(),
        ZohoCustomerInterface::ZOHO_ID => $contact['contact_id']
      ]);
      $this->_zohoCustomerRepository->save($zohoCustomer);
    }

    $zohoAddressArray = $this->_contactHelper->getAddressArray($address);
    $nullAddressArray = array_fill_keys(array_keys($zohoAddressArray), '');

    try {
      $zohoAddress = null;

      // Locate any exisitng zoho_address record for this address
      // and update any exisiting addresses
      $searchCriteria = $this->_searchCriteriaBuilder->
        addFilter('customer_id', $customerId, 'eq')->
        create();
      $zohoAddresses = $this->_zohoAddressRepository->getList($searchCriteria);
      foreach ($zohoAddresses->getItems() as $item) {
        if ($item->getId() == $address->getId()) {
          $zohoAddress = $item;
        }
        if ($addBilling && $item->getBilling() && $item->getId() != $address->getId()) {
          $this->updateExisitingAddress($zohoCustomerId, $item, self::BILLING, $nullAddressArray);
        }
        if ($addShipping && $item->getShipping() && $item->getId() != $address->getId()) {
          $this->updateExisitingAddress($zohoCustomerId, $item, self::SHIPPING, $nullAddressArray);
        }
      }

      $zohoAddressId = '';
      if ($addBilling) {
        $vat = $this->_contactHelper->vatBillingTreatment($address, $customer->getGroupId());
        $zohoContact = [
          'contact_id' => $zohoCustomerId,
          'contact_name' => empty($vat['company_name']) ? $contactName : $vat['company_name'],
          'company_name' => empty($vat['company_name']) ? '' : substr(trim($vat['company_name']), 0, 200),
          'vat_reg_no' => $vat['vat_reg_no'],
          'vat_treatment' => $vat['vat_treatment'],
          'country_code' => $vat['country_code'],
          'billing_address' => $zohoAddressArray,
        ];
        if ($removeShipping) {
          $zohoContact['contact_name'] = $contactName;
          $zohoContact['shipping_address'] = $nullAddressArray;
        }
        $this->_zohoClient->updateContact($zohoContact);
      }

      if ($addShipping) {
        $zohoContact = [
          'contact_id' => $zohoCustomerId,
          'shipping_address' => $zohoAddressArray
        ];
        if ($removeBilling) {
          $zohoContact['billing_address'] = $nullAddressArray;
        }
        $zohoContact = $this->_zohoClient->updateContact($zohoContact);
      }

      if (!$addBilling && !$addShipping) {
        if (!empty($zohoAddress)) {
          if ($zohoAddress->getBilling() || $zohoAddress->getShipping()) {
            $currentZohoContact = $this->_zohoClient->getContact($zohoCustomerId);
            $zohoContact = [
              'contact_id' => $zohoCustomerId,
              'contact_name' => $currentZohoContact['contact_name']
            ];
            if (isset($currentZohoContact['billing_address'])) {
              $zohoContact['billing_address'] = array_merge(array('address_id' => $currentZohoContact['billing_address']['address_id']) , $nullAddressArray);
            }
            if (isset($currentZohoContact['shipping_address'])) {
              $zohoContact['shipping_address'] = array_merge(array('address_id'=> $currentZohoContact['shipping_address']['address_id']) , $nullAddressArray);
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
          if (!$zohoAddress->getBilling() && !$zohoAddress->getShipping() && !empty($zohoAddress->getZohoAddressId())) {
            $this->_zohoClient->deleteAddress($zohoCustomerId, $zohoAddress->getZohoAddressId());
          }
        }
      }

      $this->saveZohoAddress($address->getId(), $customerId, $addBilling, $addShipping, $zohoAddressId);

    } catch (\Exception $ex) {
      $this->_logger->error(__('Error while saving address to Zoho Books: ' . $ex->getMessage()));
    }
  }

  private function updateExisitingAddress($zohoCustomerId, $zohoAddress, $type, $nullAddressArray) {

    $zohoContact = ['contact_id' => $zohoCustomerId];
    if ($type == self::BILLING) {
      $zohoContact['billing_address'] = $nullAddressArray;
      $zohoAddress->setBilling(false);
    } else {
      $zohoContact['shipping_address'] = $nullAddressArray;
      $zohoAddress->setShipping(false);
    }
    $zohoContact = $this->_zohoClient->updateContact($zohoContact);

    if (!$zohoAddress->getBilling() && !$zohoAddress->getShipping()) {
      $address = $this->_addressRepository->getById($zohoAddress->getCustomerAddressId());
      $zohoAddressArray = $this->_contactHelper->getAddressArray($address);
      $zohoAddressId = $this->_zohoClient->addAddress($zohoCustomerId, $zohoAddressArray)['address_id'];
      $zohoAddress->setZohoAddressId($zohoAddressId);
    }
    $this->_zohoAddressRepository->save($zohoAddress);
  }

  private function saveZohoAddress($customerAddressId, $customerId, $billing, $shipping, $zohoAddressId) {
    $zohoAddress = $this->_zohoAddressFactory->create();
    $zohoAddress->setData([
      ZohoAddressInterface::CUSTOMER_ADDRESS_ID => $customerAddressId,
      ZohoAddressInterface::CUSTOMER_ID => $customerId,
      ZohoAddressInterface::BILLING => $billing,
      ZohoAddressInterface::SHIPPING => $shipping,
      ZohoAddressInterface::ZOHO_ADDRESS_ID => $zohoAddressId
    ]);
    $this->_zohoAddressRepository->save($zohoAddress);
  }
}
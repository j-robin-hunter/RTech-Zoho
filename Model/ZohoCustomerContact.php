<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoCustomerContactInterface;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use RTech\Zoho\Api\Data\ZohoCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ZohoCustomerContact implements ZohoCustomerContactInterface {

  protected $_zohoClient;
  protected $_zohoCustomerRepository;
  protected $_zohoCustomerFactory;
  protected $_messageManager;
  protected $_contactHelper;
  protected $_adminSession;
  protected $_addressRepository;
  protected $_addressDataFactory;
  protected $_country;
  protected $_region;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoCustomerFactory $zohoCustomerFactory,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    \RTech\Zoho\Helper\ContactHelper $contactHelper,
    \Magento\Backend\Model\Auth\Session $adminSession,
    \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
    \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
    \Magento\Directory\Model\Country $country,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoCustomerFactory = $zohoCustomerFactory;
    $this->_messageManager = $messageManager;
    $this->_contactHelper = $contactHelper;
    $this->_adminSession = $adminSession;
    $this->_addressRepository = $addressRepository;
    $this->_addressDataFactory = $addressDataFactory;
    $this->_country = $country;
    $this->_logger = $logger;
  }

  /**
  * @inheritdoc
  */
  public function getContact($customer) {
    $linked = false;
    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getById($customer->getId());
      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());
      $contact = $this->updateContact($contact, $customer);
    } catch (NoSuchEntityException $e) {
      // Try to lookup the contact if admin user
      $contact = null;
      if ($this->_adminSession->getUser() !== null) {
        $contact = $this->_zohoClient->lookupContact(null, $customer->getEmail());
        if ($contact) {
          $this->_messageManager->addNotice('Zoho Contact "' . $contact['contact_name']  . '" has been linked using email address ' . $customer->getEmail());
        }
      }

      if ($contact) {
        // Need to retrieve full contact record
        $contact = $this->_zohoClient->getContact($contact['contact_id']);
      } else {
        // Create a new contact
        $contact = $this->_contactHelper->getContactArray(
          $customer->getPrefix(),
          $customer->getFirstname(),
          $customer->getMiddlename(),
          $customer->getLastname(),
          $customer->getSuffix(),
          $customer->getEmail(),
          $customer->getCustomAttribute('website') ? $customer->getCustomAttribute('website')->getValue() : ''
        );
        $contact['customer_sub_type'] = 'individual';
        $contact = $this->_zohoClient->addContact($contact);
      }
      // Create entry in zoho_customer table
      $zohoCustomer = $this->_zohoCustomerFactory->create();
      $zohoCustomer->setData([
        ZohoCustomerInterface::CUSTOMER_ID => $customer->getId(),
        ZohoCustomerInterface::ZOHO_ID => $contact['contact_id']
      ]);
      try {
        $this->_zohoCustomerRepository->save($zohoCustomer);
      } catch (\Exception $e) {
        $this->_logger->error(__('Error while saving Customer Repository'), ['exception' => $e]);
        throw $e;
      }
    }
    return $contact;
  }

  /**
  * @inheritdoc
  */
  public function updateContact($contact, $customer) {
    $updatedContact['contact_id'] = $contact['contact_id'];
    if (!empty($contact['company_name'])) {
      $updatedContact['contact_name'] = $contact['company_name'];
      $updatedContact['customer_sub_type'] = 'business';
    } else {
      $updatedContact['contact_name'] = $this->_contactHelper->getContactName(
        $customer->getPrefix(),
        $customer->getFirstname(),
        $customer->getMiddlename(),
        $customer->getLastname(),
        $customer->getSuffix()
      );
      $updatedContact['customer_sub_type'] = 'individual';
    }
    $updatedContact['website'] = $customer->getCustomAttribute('website') ? $customer->getCustomAttribute('website')->getValue() : '';

    try {
      $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
      $updatedContact['contact_persons'][$primaryIndex]['contact_person_id'] = $contact['contact_persons'][$primaryIndex]['contact_person_id'];
      $updatedContact['contact_persons'][$primaryIndex]['salutation'] = $customer->getPrefix();
      $updatedContact['contact_persons'][$primaryIndex]['first_name'] = $customer->getFirstname();
      $updatedContact['contact_persons'][$primaryIndex]['last_name'] = $customer->getLastname();
      $updatedContact['contact_persons'][$primaryIndex]['email'] = $customer->getEmail();
    } catch (\Exception $ex) {
      // No person updates as no primary person
    }
    return $this->_zohoClient->updateContact($updatedContact);
  }

  /**
  * @inheritdoc
  */
  public function updateContactAddresses($contact, $billingAddress, $shippingAddress, $groupId) {
    $contact = $this->_contactHelper->updateAddresses($contact, $billingAddress, $shippingAddress, $groupId);
    return $this->_zohoClient->updateContact($contact);
  }

  /**
  * @inheritdoc
  */
  public function deleteContact($customer) {
    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getById($customer->getId());
      $this->_zohoClient->deleteContact($zohoCustomer->getZohoId());
      $this->_messageManager->addNotice('Zoho customer "' . $customer->getFirstname() . ' ' . $customer->getLastname() . '" deleted');
    } catch (ZohoOperationException $e) {
      // Customer has transations so mark as inactive
      $this->_zohoClient->contactSetInactive($zohoCustomer->getZohoId());
      $this->_messageManager->addNotice('Zoho customer "' . $customer->getFirstname() . ' ' . $customer->getLastname() . '" set to inactive');
    } catch (ZohoItemNotFoundException $e) {
      // Do Nothing
    } catch (NoSuchEntityException $e) {
      // Do nothing
    }
  }

  private function getCountryCode($countryName) {
    $countryId = '';
    $countryCollection = $this->_country->getCollection();
    foreach ($countryCollection as $country) {
       if ($country->getName() == $countryName) {
          $countryId =  $country->getCountryId();
          break;
       }
    }
    return $countryId;
  }
}
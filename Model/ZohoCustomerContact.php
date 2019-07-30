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
use Magento\Framework\Exception\NoSuchEntityException;

class ZohoCustomerContact implements ZohoCustomerContactInterface {

  protected $_zohoClient;
  protected $_zohoCustomerRepository;
  protected $_zohoCustomerFactory;
  protected $_messageManager;
  protected $_contactHelper;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoCustomerFactory $zohoCustomerFactory,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    \RTech\Zoho\Helper\ContactHelper $contactHelper,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoCustomerFactory = $zohoCustomerFactory;
    $this->_messageManager = $messageManager;
    $this->_contactHelper = $contactHelper;
    $this->_logger = $logger;
  }

  /**
  * @inheritdoc
  */
  public function getContact($customer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getContact');

    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getById($customer->getId());
      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());
      $contact = $this->updateContact($contact, $customer);
    } catch (NoSuchEntityException $e) {
      $this->_logger->info('NoSuchEntityException');
      // Try to lookup the contact
      $contact = $this->_zohoClient->lookupContact(
        $this->_contactHelper->getContactName(
            $customer->getPrefix(),
            $customer->getFirstname(),
            $customer->getMiddlename(),
            $customer->getLastname(),
            $customer->getSuffix()
          ),
        $customer->getEmail());

      if ($contact) {
        // Need to retrieve full contact record
        $this->_logger->info('existing Zoho contact');
        $this->_messageManager->addNotice('Zoho Contact "' . $contact['contact_name']  . '" has been linked');
        $contact = $this->_zohoClient->getContact($contact['contact_id']);
      } else {
        // Create a new contact
        $this->_logger->info('create client');
        $contact = $this->_zohoClient->addContact(
          $contact = $this->_contactHelper->getContactArray(
            $customer->getPrefix(),
            $customer->getFirstname(),
            $customer->getMiddlename(),
            $customer->getLastname(),
            $customer->getSuffix(),
            $customer->getEmail(),
            $customer->getCustomAttribute('website') ? $customer->getCustomAttribute('website')->getValue() : ''
          )
        );
      }
      // Create entry in zoho_customer table
      $zohoCustomer = $this->_zohoCustomerFactory->create();
      $this->_logger->info(get_class($zohoCustomer));
      $zohoCustomer->setData([
        'customer_id' => $customer->getId(),
        'zoho_id' => $contact['contact_id']
      ]);
      $this->_logger->info('set data');
      try {
        $this->_zohoCustomerRepository->save($zohoCustomer);
      } catch (\Exception $e) {
        $this->_logger->error(__('Error while saving Customer Repository: ' . $e->getMessage()));
      }
      $this->_logger->info($contact);
    }
    return $contact;
  }

  /**
  * @inheritdoc
  */
  public function updateContact($contact, $customer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('updateContact: ' . get_class($customer));

    $updatedContact['contact_id'] = $contact['contact_id'];
    if (!empty($contact['company_name'])) {
      $updatedContact['contact_name'] = $contact['company_name'];
    } else {
      $updatedContact['contact_name'] = $this->_contactHelper->getContactName(
        $customer->getPrefix(),
        $customer->getFirstname(),
        $customer->getMiddlename(),
        $customer->getLastname(),
        $customer->getSuffix()
      );
    }
    $updatedContact['website'] = $customer->getCustomAttribute('website') ? $customer->getCustomAttribute('website')->getValue() : '';

    try {
      $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
      $this->_logger->info('primary index: ' . $primaryIndex);
      $this->_logger->info($contact['contact_persons'][$primaryIndex]['contact_person_id']);
      $updatedContact['contact_persons'][$primaryIndex]['contact_person_id'] = $contact['contact_persons'][$primaryIndex]['contact_person_id'];
      $updatedContact['contact_persons'][$primaryIndex]['salutation'] = $customer->getPrefix();
      $updatedContact['contact_persons'][$primaryIndex]['first_name'] = $customer->getFirstname();
      $updatedContact['contact_persons'][$primaryIndex]['last_name'] = $customer->getLastname();
      $updatedContact['contact_persons'][$primaryIndex]['email'] = $customer->getEmail();
    } catch (\Exception $ex) {
      // No person updates as no primary person
    }
    $this->_logger->info($updatedContact);
    return $this->_zohoClient->updateContact($updatedContact);
  }

  /**
  * @inheritdoc
  */
  public function updateContactAddresses($contact, $billingAddress, $shippingAddress, $groupId) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('updateContactAddresses');
    $contact = $this->_contactHelper->updateAddresses($contact, $billingAddress, $shippingAddress, $groupId);
    return $this->_zohoClient->updateContact($contact);
  }

  /**
  * @inheritdoc
  */
  public function deleteContact($customer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('deleteContact: ' . $customer->getId());
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

  private function getBillingAddress($customer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getBillingAddress');
    if ($customer->getAddresses()) {
      foreach ($customer->getAddresses() as $address) {
        if ($address->isDefaultBilling()) {
          return $address;
        }
      }
    }
    $this->_logger->info('No billing address');
    return null;
  }

  private function getShippingAddress($customer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getShippingAddress');
    if ($customer->getAddresses()) {
      foreach ($customer->getAddresses() as $address) {
        if ($address->isDefaultShipping()) {
          return $address;
        }
      }
    }
    $this->_logger->info('No shipping address');
    return null;
  }
}
<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoOrderContactInterface;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;

class ZohoOrderContact implements ZohoOrderContactInterface {

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
  public function getContactForOrder($order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getContactForOrder');

    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getById($order->getCustomerId());
      $this->_logger->info('zohoCustomer: ' . $zohoCustomer);

      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());

    } catch (NoSuchEntityException $e) {
      $this->_logger->info('NoSuchEntityException');

      // Try to lookup the contact
      $contact = $this->_zohoClient->lookupContact(
        $this->_contactHelper->getContactName(
          $order->getCustomerFirstname(),
          $order->getCustomerLastname(),
          $order->getBillingAddress()),
        $order->getCustomerEmail());

      if ($contact) {
        // Need to retrieve full contact record
        $this->_messageManager->addNotice('Zoho Contact "' . $contact['contact_name']  . '" has been linked');
        $contact = $this->_zohoClient->getContact($contact['contact_id']);
      } else {
        // Create a new contact
        $this->_logger->info('create client');
        $contact = $this->_zohoClient->addContact(
          $contact = $this->_contactHelper->getContactArray(
            $order->getCustomerPrefix(),
            $order->getCustomerFirstname(),
            $order->getCustomerLastname(),
            $order->getCustomerEmail(),
            $order->getCustomerWebsite(),
            $order->getBillingAddress(),
            $order->getShippingAddress(),
            $order->getCustomerGroupId()
          )
        );
        $this->_logger->info($contact);
      }
      if ($order->getCustomerId()) {
        // Not a guest so create entry in zoho_customer table
        $zohoCustomer = $this->_zohoCustomerFactory->create();
        $zohoCustomer->setData([
          'customer_id' => $order->getCustomerId(),
          'zoho_id' => $contact['contact_id']
        ]);
        try {
          $this->_zohoCustomerRepository->save($zohoCustomer);
        } catch (\Exception $e) {
          $this->_logger->error(__('Error while saving Customer Repository: ' . $e->getMessage()));
        }
      }
    }
    return $contact;
  }

  /**
  * @inheritdoc
  */
  public function updateOrderContact($contact, $order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('updateOrderContact');
    $contact['contact_name'] = $this->_contactHelper->getContactName(
      $order->getCustomerFirstname(),
      $order->getCustomerLastname(),
      $order->getBillingAddress()
    );
    $contact['first_name'] = $order->getCustomerFirstname();
    $contact['last_name'] = $order->getCustomerLastname();
    $contact['email'] = $order->getCustomerEmail();

    try {
      $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
      $contact['contact_persons'][$primaryIndex]['salutation'] = $order->getCustomerPrefix();
      $contact['contact_persons'][$primaryIndex]['first_name'] = $order->getCustomerFirstname();
      $contact['contact_persons'][$primaryIndex]['last_name'] = $order->getCustomerLastname();
      $contact['contact_persons'][$primaryIndex]['email'] = $order->getCustomerEmail();
    } catch (\Exception $ex) {
      // No person updates as no primary person
    }
    $contact = $this->_contactHelper->updateAddresses(
      $contact,
      $order->getBillingAddress(),
      $order->getShippingAddress(),
      $order->getCustomerGroupId()
    );
    $this->_logger->info($contact);

    return $this->_zohoClient->updateContact($contact);
  }

}

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
use RTech\Zoho\Api\Data\ZohoCustomerInterface;
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
    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getById($order->getCustomerId());
      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());
    } catch (NoSuchEntityException $e) {

      $contact = $this->_zohoClient->lookupContact($this->getContactName($order), $order->getCustomerEmail());

      if (!$contact) {
        // Create a new contact
        $contact = $this->_zohoClient->addContact(
          $contact = $this->_contactHelper->getContactArray(
            $order->getCustomerPrefix(),
            $order->getCustomerFirstname(),
            $order->getCustomerMiddlename(),
            $order->getCustomerLastname(),
            $order->getCustomerSuffix(),
            $order->getCustomerEmail(),
            $order->getCustomerWebsite()
          )
        );
      }
      if ($order->getCustomerId()) {
        // Not a guest so create entry in zoho_customer table
        $zohoCustomer = $this->_zohoCustomerFactory->create();
        $zohoCustomer->setData([
          ZohoCustomerInterface::CUSTOMER_ID => $order->getCustomerId(),
          ZohoCustomerInterface::ZOHO_ID => $contact['contact_id']
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
    // If this is a guest order then make sure that Zoho has a full
    // billing address. The shipping address is also written to Zoho
    // although this is held within Magento as part of the order and
    // will be used to update Zoho for packing notes etc. at the time
    // the products are paid for and 'shipped' from within Magento

    if (empty($order->getCustomerId())) {
      $billingAddress = $order->getBillingAddress();
      $shippingAddress = $order->getShippingAddress();
      $vat = $this->_contactHelper->vatBillingTreatment($billingAddress, $order->getCustomerGroupId());

      $contact = [
        'contact_id' => $contact['contact_id'],
        'contact_name' => $this->getContactName($order),
        'vat_reg_no' => $vat['vat_reg_no'],
        'vat_treatment' => $vat['vat_treatment'],
        'country_code' => $vat['country_code'],
        'billing_address' => $this->_contactHelper->getAddressArray($billingAddress),
        'shipping_address' => $this->_contactHelper->getAddressArray($shippingAddress)
      ];

      try {
        $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
        $contact['contact_persons'][$primaryIndex]['salutation'] = $order->getCustomerPrefix();
        $contact['contact_persons'][$primaryIndex]['first_name'] = $order->getCustomerFirstname();
        $contact['contact_persons'][$primaryIndex]['last_name'] = $order->getCustomerLastname();
        $contact['contact_persons'][$primaryIndex]['email'] = $order->getCustomerEmail();
      } catch (\Exception $ex) {
        // No person updates as no primary person
      }
      $contact = $this->_zohoClient->updateContact($contact);
    }

    return $contact;
  }

  public function getVatTreatment($order) {
    $vat = $this->_contactHelper->vatBillingTreatment($order->getBillingAddress(), $order->getCustomerGroupId());
    return $vat['vat_treatment'];
  }

  private function getContactName($order) {
    return $order->getBillingAddress()->getCompany() ? : $this->_contactHelper->getContactName(
      $order->getCustomerPrefix(),
      $order->getCustomerFirstname(),
      $order->getCustomerMiddlename(),
      $order->getCustomerLastname(),
      $order->getCustomerSuffix()
    );
  }
}

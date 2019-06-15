<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoContactInterface;
use RTech\Zoho\Webservice\Client\ZohoBooksClient;
use RTech\Zoho\Webservice\Exception\ZohoOperationException;
use RTech\Zoho\Webservice\Exception\ZohoItemNotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;

class ZohoContact implements ZohoContactInterface {
  const EU_COUNTRIES = [
    'AT',
    'BE',
    'BG',
    'CY',
    'CZ',
    'DE',
    'DK',
    'EE',
    'ES',
    'FI',
    'FR',
    'GR',
    'GB',
    'HR',
    'HU',
    'IE',
    'IT',
    'LT',
    'LU',
    'LV',
    'MT',
    'NL',
    'PL',
    'PT',
    'RO',
    'SE',
    'SI',
    'SK'
  ];
  protected $_configData;
  protected $_storeId;
  protected $_zohoCustomerRepository;
  protected $_zohoCustomerFactory;
  protected $_countryFactory;
  protected $_regionFactory;
  protected $_messageManager;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoCustomerFactory $zohoCustomerFactory,
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Directory\Model\RegionFactory $regionFactory,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->_zohoClient = new ZohoBooksClient($configData, $zendClient, $storeManager);
    $this->_configData = $configData;
    $this->_storeId = $storeManager->getStore()->getId();
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoCustomerFactory = $zohoCustomerFactory;
    $this->_countryFactory = $countryFactory;
    $this->_regionFactory = $regionFactory;
    $this->_messageManager = $messageManager;
  }

  /**
  * @inheritdoc
  */
  public function getContactId($order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('verifyContact');
    $this->_logger->info('Customer id: ' . $order->getCustomerId());

    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getId($order->getCustomerId());
      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());

    } catch (NoSuchEntityException $e) {
      // Try to lookup the contact
      $this->_logger->info('lookup customer');
      $contact = $this->_zohoClient->lookupContact($this->getContactName($order), $order->getCustomerEmail());
      if (!$contact) {
        // Create a new contact
        $this->_logger->info('create customer');
        $contact = $this->_zohoClient->addContact($this->getContactArray($order));
      }
      if ($order->getCustomerId()) {
        // Not a guest so create entry in zoho_customer table
        $zohoCustomer = $this->_zohoCustomerFactory->create();
        $zohoCustomer->setData([
          'customer_id' => $order->getCustomerId(),
          'zoho_id' => $contact['contact_id']
        ]);
        $this->_logger->info($zohoCustomer->getData());
        try {
          $this->_zohoCustomerRepository->save($zohoCustomer);
          $this->_logger->info('saved');
        } catch (\Exception $e) {
          $this->_logger->info($e->getMessage());
        }
      }
    }
    $this->_logger->info($contact);
    return $contact;
  }

  /**
  * @inheritdoc
  */
  public function updateContact($contact, $order) {
    $contact['contact_name'] = $this->getContactName($order);
    unset($contact['tax_treatment']);
    unset($contact['contact_category']);

    try {
      $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
      $contact['contact_persons'][$primaryIndex]['salutation'] = $order->getCustomerPrefix();
      $contact['contact_persons'][$primaryIndex]['first_name'] = $order->getCustomerFirstname();
      $contact['contact_persons'][$primaryIndex]['last_name'] = $order->getCustomerLastname();
      $contact['contact_persons'][$primaryIndex]['email'] = $order->getCustomerEmail();
    } catch (\Exception $ex) {
      // No person updates as no primary person
    }

    $billingAddress = $order->getBillingAddress();
    $shippingAddress = $order->getShippingAddress();
    $contact['billing_address'] = $billingAddress ? $this->getAddressArray($billingAddress) : '';
    $contact['shipping_address'] = $shippingAddress ? $this->getAddressArray($shippingAddress) : '';

    $vat = $this->vatBillingTreatment($order);
    $contact['company_name'] = isset($vat['company_name']) ? $vat['company_name'] : '';
    $contact['vat_reg_no'] = isset($vat['vat_reg_no']) ? $vat['vat_reg_no'] : '';
    $contact['vat_treatment'] = isset($vat['vat_treatment']) ? $vat['vat_treatment'] : '';
    $contact['country_code'] = isset($vat['country_code']) ? $vat['country_code'] : '';

    return $this->_zohoClient->updateContact($contact);
  }

  /**
  * @inheritdoc
  */
  public function updateAddress($address) {
    $contact = $this->createContact($address->getCustomer());
    unset($contact['tax_treatment']);
    unset($contact['contact_category']);
    $address = $address->getDataModel();
    $contactAddress = $this->getAddressArray($address);

    if ($address->isDefaultBilling()) {
      $contact['billing_address'] = $contactAddress;
      $vat = $this->vatBillingTreatment($address);
      $contact['company_name'] = isset($vat['company_name'])?$vat['company_name']:'';
      $contact['vat_reg_no'] = isset($vat['vat_reg_no'])?$vat['vat_reg_no']:'';
      $contact['vat_treatment'] = isset($vat['vat_treatment'])?$vat['vat_treatment']:'';
      $contact['country_code'] = isset($vat['country_code'])?$vat['country_code']:'';
    }

    if ($address->isDefaultShipping()) {
      $contact['shipping_address'] = $contactAddress;
    }
    return $this->_zohoClient->updateContact($contact);
  }

  private function getContactArray($order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getContactArray');

    $billingAddress = $order->getBillingAddress();
    $shippingAddress = $order->getShippingAddress();
    $vat = $this->vatBillingTreatment($order);
    $this->_logger->info($vat);

    $firstName = $order->getCustomerFirstname();
    if (!$firstName && !$order->getCustomerLastname()) {
      $firstName = __('Guest');
    }
    return array_merge([
      'contact_name' => $this->getContactName($order),
      'contact_type' => 'customer',
      'contact_persons' => [[
        'salutation' => $order->getCustomerPrefix()?:'',
        'first_name' => $firstName,
        'last_name' => $order->getCustomerLastname()?:'',
        'email' => $order->getCustomerEmail()?:'',
        'is_primary_contact' => 'true'
      ]],
      'billing_address' => $billingAddress ? $this->getAddressArray($billingAddress) : '',
      'shipping_address' => $shippingAddress ? $this->getAddressArray($shippingAddress) : ''
    ], $vat);
  }

  private function getContactName($order) {
    if ($order->getCustomerFirstname()) {
      $customerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
    } else {
      $billingAddress = $order->getBillingAddress();
      if ($billingAddress) {
        $customerName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
      } else {
        $customerName = (string)__('Guest');
      }
    }
    return substr(trim($customerName), 0, 200);
  }

  private function getAddressArray($address) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('getAddressArray');

    $this->_logger->info($address->getName());

    if (count($address->getStreet()) >= 2) {
      $street2 = $address->getStreet()[1]?:'';
      if (count($address->getStreet()) >= 3) {
        $street2 .= strlen($street2) > 0 && strlen($address->getStreet()[2]) > 0 ? ', ' . $address->getStreet()[2] : $address->getStreet()[2];
      }
    }
    $this->_logger->info($street2);

    $country = $this->_countryFactory->create()->loadByCode($address->getCountryId());
    $this->_logger->info($country->getName());

    $region = $address->getRegion();
    if (is_object($region)) {
      $region = $region->getRegion();
    }

    if ($address->getRegionId()) {
      $region = $this->_regionFactory->create()->load($address->getRegionId())->getName();
    }
    $this->_logger->info($region);

    $contactAddress = [
      'attention' => $address->getName(),
      'address' => $address->getStreet()[0]?:'',
      'street2' => $street2,
      'city' => $address->getCity()?:'',
      'state' => $region?:'',
      'zip' => $address->getPostcode()?:'',
      'country' => $country->getName(),
      'phone' => $address->getTelephone()?:'',
      'fax' => $address->getFax()?:''
    ];
    $this->_logger->info($contactAddress);
    return $contactAddress;
  }

  private function vatBillingTreatment($order) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);

    $vat = [];
    $address = $order->getBillingAddress();

    if ($address) {
      $vat['company_name'] = $address->getCompany();
      if (in_array($address->getCountryId(), self::EU_COUNTRIES)) {
        $vat['vat_reg_no'] = preg_replace('/\s+/', '', $address->getVatId());
        if ($address->getCountryId() == 'GB') {
          $vat['vat_treatment'] = 'uk';
        } else {
          $vat['vat_treatment'] = ($order->getCustomerGroupId() == $this->_configData->getMagentoEuVatGroupId($this->_storeId)) ? 'eu_vat_registered' : 'eu_vat_not_registered';
        }
        $vat['country_code'] = $vat['vat_reg_no'] ? $address->getCountryId() : '';
      } else {
        $vat['vat_treatment'] = 'non_eu';
      }
    }
    return $vat;
  }

  /**
  * @inheritdoc
  */
  public function deleteContact($customer) {
    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getId($customer->getId());
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
}
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
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoCustomerFactory = $zohoCustomerFactory;
    $this->_countryFactory = $countryFactory;
    $this->_regionFactory = $regionFactory;
    $this->_messageManager = $messageManager;
  }

  /**
  * @inheritdoc
  */
  public function createContact($customer) {
    try {
      $zohoCustomer = $this->_zohoCustomerRepository->getId($customer->getId());
      $contact = $this->_zohoClient->getContact($zohoCustomer->getZohoId());
    } catch (NoSuchEntityException $e) {
      // Try to lookup the contact
      $contact = $this->_zohoClient->lookupContact(trim($customer->getFirstname() . ' ' . $customer->getLastname()));
      if ($contact && $contact['email'] == $customer->getEmail()) {
          $this->_messageManager->addNotice('Zoho Books contact "' . $contact['contact_name'] . '" has been linked');
      } else {
        // Create a new contact - set VAT treatment as default in 'uk'.
        // This may be changed based on address updates
        $contact = $this->_zohoClient->addContact([
          'contact_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
          'contact_type' => 'customer',
          'vat_treatment' => 'uk',
          'contact_persons' => [[
            'salutation' => $customer->getPrefix()?:'',
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'is_primary_contact' => 'true'
          ]]
        ]);
      }

      $zohoCustomer = $this->_zohoCustomerFactory->create();
      $zohoCustomer->setData([
        'customer_id' => $customer->getId(),
        'zoho_id' => $contact['contact_id']
      ]);

      $this->_zohoCustomerRepository->save($zohoCustomer);
    }
    return $contact;
  }

  /**
  * @inheritdoc
  */
  public function updateContact($customer, $contact) {
    $contact['contact_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
    unset($contact['tax_treatment']);
    unset($contact['contact_category']);
    try {
      $primaryIndex = array_search(true, array_column($contact['contact_persons'], 'is_primary_contact'));
      $contact['contact_persons'][$primaryIndex]['salutation'] = $customer->getPrefix();
      $contact['contact_persons'][$primaryIndex]['first_name'] = $customer->getFirstname();
      $contact['contact_persons'][$primaryIndex]['last_name'] = $customer->getLastname();
      $contact['contact_persons'][$primaryIndex]['email'] = $customer->getEmail();
    } catch (\Exception $ex) {
      // No person updates as no primary person
    }
    foreach ($customer->getAddresses() as $address) {
      $contactAddress = $this->address($address);
      if ($address->isDefaultBilling()) {
        $contact['billing_address'] = $contactAddress;
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
    }
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
    $contactAddress = $this->address($address);

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

  private function address($address) {
    $attention = $address->getPrefix() ? $address->getPrefix() . ' ' : '';
    $attention .= $address->getFirstname() ? $address->getFirstname() . ' ': '';
    $attention .= $address->getMiddlename() ? $address->getMiddlename() . ' ': '';
    $attention .= $address->getLastname() ? $address->getLastname() . ' ': '';
    $attention .= $address->getSuffix() ? $address->getSuffix() . ' ': '';

    if (count($address->getStreet()) >= 2) {
      $street2 = $address->getStreet()[1]?:'';
      if (count($address->getStreet()) >= 3) {
        $street2 .= strlen($street2) > 0 && strlen($address->getStreet()[2]) > 0 ? ', ' . $address->getStreet()[2] : $address->getStreet()[2];
      }
    }

    $country = $this->_countryFactory->create()->loadByCode($address->getCountryId());

    $region = $address->getRegion()->getRegion();
    if ($address->getRegionId()) {
      $region = $this->_regionFactory->create()->load($address->getRegionId())->getName();
    }

    $contactAddress = [
      'attention' => $attention,
      'address' => $address->getStreet()[0]?:'',
      'street2' => $street2,
      'city' => $address->getCity()?:'',
      'state' => $region?:'',
      'zip' => $address->getPostcode()?:'',
      'country' => $country->getName(),
      'phone' => $address->getTelephone()?:'',
      'fax' => $address->getFax()?:''
    ];
    return $contactAddress;
  }

  private function vatBillingTreatment($address) {
    $vat = ['company_name' => $address->getCompany()];

    if (in_array($address->getCountryId(), self::EU_COUNTRIES)) {
      $vat['vat_reg_no'] = $address->getVatId();
      if ($address->getCountryId() == 'GB') {
        $vat['vat_treatment'] = 'uk';
      } else {
        $vat['vat_treatment'] = $vat['vat_reg_no'] ? 'eu_vat_registered': 'eu_vat_not_registered';
      }
      $vat['country_code'] = $vat['vat_reg_no']? $address->getCountryId() : '';
    } else {
      $vat['vat_treatment'] = 'non_eu';
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
    } catch (ZohoOperationException $e) {
      // Customer has transations so mark as inactive
      $this->_zohoClient->contactSetInactive($zohoCustomer->getZohoId());
      $this->_messageManager->addNotice('Zoho customer "' . $customer->getFirstname() . ' ' . $customer->getLastname() . '" set to inactive');
    } catch (ZohoItemNotFoundException $e) {
      $this->_messageManager->addNotice('Zoho customer for "' . $customer->getFirstname() . ' ' . $customer->getLastname() . '" not found');
    } catch (NoSuchEntityException $e) {
      // Do nothing
    }
  }
}
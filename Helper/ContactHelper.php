<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Helper;

class ContactHelper {

  const ALLOWED_ZOHO_KEYS = [
    'contact_id',
    'contact_name',
    'company_name',
    'payment_terms',
    'payment_terms_label',
    'contact_type',
    'currency_id',
    'website',
    'owner_id',
    'billing_address',
    'shipping_address',
    'notes',
    'vat_reg_no',
    'country_code',
    'vat_treatment',
    'tax_authority_name'
  ];

  protected $_countryFactory;
  protected $_regionFactory;
  protected $_configData;
  protected $_storeId;
  protected $_vat;
  protected $_scopeConfig;

  public function __construct(
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Directory\Model\RegionFactory $regionFactory,
    \Rtech\Zoho\Helper\ConfigData $configData,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Customer\Model\Vat $vat,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
  ) {
    $this->_countryFactory = $countryFactory;
    $this->_regionFactory = $regionFactory;
    $this->_configData = $configData;
    $this->_storeId = $storeManager->getStore()->getId();
    $this->_vat = $vat;
    $this->_scopeConfig = $scopeConfig;
  }

  public function getContactArray($prefix, $firstName, $middleName, $lastName, $suffix, $email, $website) {
    return [
      'contact_name' => $this->getContactName($prefix, $firstName, $middleName, $lastName, $suffix),
      'contact_type' => 'customer',
      'website' => $website ? : '',
      'contact_persons' => [[
        'salutation' => $prefix ? : '',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email ? : '',
        'is_primary_contact' => 'true'
      ]]
    ];
  }

  public function getContactName($prefix, $firstName, $middleName, $lastName, $suffix) {
    $contactName = $prefix ? $prefix . ' ' : '';
    $contactName .= $firstName;
    $contactName .= $middleName ? ' ' . $middleName . ' ' : ' ';
    $contactName .= $lastName;
    $contactName .= $suffix ? ' ' . $suffix : '';
    return substr(trim($contactName), 0, 200);
  }

  public function getAddressArray($address) {
    if (count($address->getStreet()) >= 2) {
      $street2 = $address->getStreet()[1]?:'';
      if (count($address->getStreet()) >= 3) {
        $street2 .= strlen($street2) > 0 && strlen($address->getStreet()[2]) > 0 ? ', ' . $address->getStreet()[2] : $address->getStreet()[2];
      }
    } else {
      $street2 = '';
    }

    $country = $this->_countryFactory->create()->loadByCode($address->getCountryId());

    $region = $address->getRegion();
    if (is_object($region)) {
      $region = $region->getRegion();
    }

    if ($address->getRegionId()) {
      $region = $this->_regionFactory->create()->load($address->getRegionId())->getName();
    }

    $attention = $this->getContactName(
      $address->getPrefix(),
      $address->getFirstName(),
      $address->getMiddlename(),
      $address->getLastname(),
      $address->getSuffix());

    $contactAddress = [
      'attention' => substr(trim($attention), 0, 200),
      'address' => $address->getStreet()[0]?:'',
      'street2' => $street2,
      'city' => $address->getCity()?:'',
      'state' => $region?:'',
      'zip' => $address->getPostcode()?:'',
      'country' => $country->getName()?:'',
      'phone' => $address->getTelephone()?:'',
      'fax' => $address->getFax()?:''
    ];

    return $contactAddress;
  }

  public function vatBillingTreatment($address) {
    $vat = [];

    if ($address) {
      $vat['company_name'] = $address->getCompany();
      if (in_array($address->getCountryId(), explode(',', $this->_scopeConfig->getValue('general/country/eu_countries')))) {
        $vat['vat_reg_no'] = preg_replace('/\s+/', '', $address->getVatId());
        if ($address->getCountryId() == 'GB') {
          $vat['vat_treatment'] = 'uk';
        } else {
          if ($this->_vat->checkVatNumber($address->getCountryId(), $vat['vat_reg_no'])->getIsValid() === true) {
            $vat['vat_treatment'] = 'eu_vat_registered';
          } else {
            $vat['vat_treatment'] = 'eu_vat_not_registered';
            $vat['vat_reg_no'] = '';
          }
        }
        $vat['country_code'] = $vat['vat_reg_no'] ? $address->getCountryId() : '';
      } else {
        $vat['vat_treatment'] = 'non_eu';
      }
    }
    return $vat;
  }
}
<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Setup;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallData implements InstallDataInterface {

  const WEBSITE_ATTRIBUTE_CODE = 'website';

  private $eavSetup;
  private $eavConfig;

  public function __construct(
    \Magento\Eav\Setup\EavSetup $eavSetup,
    \Magento\Eav\Model\Config $eavConfig
  ) {
    $this->eavSetup = $eavSetup;
    $this->eavConfig = $eavConfig;
  }

  public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {

    $setup->startSetup();

    $this->eavSetup->addAttribute(
      CustomerMetadataInterface:: ENTITY_TYPE_CUSTOMER,
      self::WEBSITE_ATTRIBUTE_CODE,
      [
        'label' => 'Web Site',
        'input' => 'text',
        'visible' => true,
        'required' => false,
        'position' => 85,
        'sort_order' => 85,
        'system' => false
      ]
    );

    $websiteAttribute = $this->eavConfig->getAttribute(
      CustomerMetadataInterface:: ENTITY_TYPE_CUSTOMER,
      self::WEBSITE_ATTRIBUTE_CODE
    );
    $websiteAttribute->setData(
      'used_in_forms',
      ['adminhtml_customer', 'customer_account_create', 'customer_account_edit']
    );
    $websiteAttribute->save();


    $setup->endSetup();
  }
}
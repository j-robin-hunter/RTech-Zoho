<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;

class ShipmentResourceZohoPlugin {

  protected $_zohoOrderManagement;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoOrderManagement = $zohoOrderManagement;
    $this->_logger = $logger;
  }

  public function aroundSave (
    \Magento\Sales\Model\ResourceModel\Order\Shipment $subject,
    callable $save,
    \Magento\Sales\Model\Order\Shipment $shipment
  ) {
    // Update Magento stock from Zoho inventory
    $this->_zohoOrderManagement->updateStock($shipment);

    // Save Magento shipment
    $savedShipment = $save($shipment);

    // Create Zoho package and shipment documents
    $this->_zohoOrderManagement->createShipment($shipment);

    return $savedShipment;
  }
}
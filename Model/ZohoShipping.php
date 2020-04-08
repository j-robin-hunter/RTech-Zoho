<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model;

use RTech\Zoho\Api\Data\ZohoShippingInterface;
use RTech\Zoho\Webservice\Client\ZohoInventoryClient;

class ZohoShipping implements ZohoShippingInterface {

  protected $_zohoInventoryClient;
  protected $_shippingSku;

  public function __construct(
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager
  ) {
    $this->_zohoInventoryClient = new ZohoInventoryClient($configData, $zendClient, $storeManager);
    $this->_shippingSku = $configData->getZohoShippingSku($storeManager->getStore()->getId());
  }

  /**
  * @inheritdoc
  */
  public function createShipment($zohoSalesOrderManagement, $order) {
    $zohoSalesOrder = $this->_zohoInventoryClient->getSalesOrder($zohoSalesOrderManagement->getSalesOrderId());

    $lineitems = array();
    foreach($zohoSalesOrder['line_items'] as $lineitem) {
      if ($lineitem['sku'] != $this->_shippingSku) {
        $lineitems[] = [
          'so_line_item_id' => $lineitem['line_item_id'],
          'quantity' => $lineitem['quantity']
        ];
      }
    }

    $comments = '';
    foreach ($order->getShipmentsCollection() as $shipments) {
      foreach ($shipments->getComments() as $comment) {
        $comments = strlen($comments) > 0 ? '\r\n\r\n' . $comment->getComment() : $comment->getComment();
      }
    }

    $package = [
      //Having this number can create auto-increment issues in Zoho:   'package_number' => sprintf('web-%06d', $order->getIncrementId()),
      'date' => date('Y-m-d'),
      'line_items' => $lineitems,
      'notes' => $comments
    ];
    $package = $this->_zohoInventoryClient->packageAdd($zohoSalesOrderManagement->getSalesOrderId(), $package);

    $tracking = $order->getTracksCollection()->getFirstItem();
    $trackingNumber = $tracking->getNumber() ? : sprintf(__('none set'));
    $trackingTitle = $tracking->getTitle() ? : sprintf(__('none set'));
    $shipment = [
      // Having this number can create auto-increment issues in Zoho:   'shipment_number' => sprintf('web-%06d', $order->getIncrementId()),
      'date' => date('Y-m-d'),
      'tracking_number' => $trackingNumber,
      'delivery_method' => $trackingTitle
    ];
    $this->_zohoInventoryClient->shipmentAdd($package, $shipment);
  }
}

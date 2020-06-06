<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use RTech\Zoho\Webservice\Client\ZohoInventoryClient;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderRepositoryZohoPlugin {

  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoOrderManagement;

  public function __construct(
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement
  ) {
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoOrderManagement = $zohoOrderManagement;
  }

  public function beforeDelete (
    \Magento\Sales\Api\OrderRepositoryInterface $subject,
    \Magento\Sales\Api\Data\OrderInterface $order
  ) {
    //Order deleted so delete all Zoho documents and any state between Magento and Zoho
    try {
      // Get the existing state of the order between Magento and Zoho
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($order->getId());
      $this->_zohoOrderManagement->deleteAll($zohoSalesOrderManagement);
      $this->_zohoSalesOrderManagementRepository->delete($zohoSalesOrderManagement);
    } catch (NoSuchEntityException $e) {
      // No Zoho/Magento relationship exisits so ignore
    }
  }
}
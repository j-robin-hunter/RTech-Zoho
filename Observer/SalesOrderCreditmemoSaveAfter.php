<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderCreditmemoSaveAfter implements ObserverInterface {

  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoOrderManagement;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement,
    \Psr\Log\LoggerInterface $logger

  ) {
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoOrderManagement = $zohoOrderManagement;
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $creditMemo = $observer->getEvent()->getCreditmemo();

    try {
      // Get the existing state of the order between Magento and Zoho
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($creditMemo->getOrderId());
      // Order refunded so create a Zoho credit note
      $this->_zohoOrderManagement->createCreditNote($zohoSalesOrderManagement, $creditMemo);
    } catch (\Exception $e) {
      $this->_logger->error(__('Error while creating Zoho credit note'), ['exception' => $e]);
      throw $e;
    }
  }

}
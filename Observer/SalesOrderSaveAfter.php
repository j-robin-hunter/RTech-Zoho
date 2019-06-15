<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SalesOrderSaveAfter implements ObserverInterface {

  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoOrderManagement;
  protected $_zohoShipping;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement,
    \RTech\Zoho\Model\ZohoShipping $zohoShipping,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoOrderManagement = $zohoOrderManagement;
    $this->_zohoShipping = $zohoShipping;
    $this->_logger = $logger;
  }


  public function execute(\Magento\Framework\Event\Observer $observer) {
    $order = $observer->getEvent()->getOrder();

    try {
      // Get the existing state of the order between Magento and Zoho
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getId($order->getId());
    } catch (NoSuchEntityException $e) {
      // No record of the order tso start be creating a Zoho estimate
      try {
        $zohoSalesOrderManagement = $this->_zohoOrderManagement->createEstimate($order);
      } catch (\Exception $e) {
        $this->_logger->error('Error while creating Zoho estimate: '. $e->getMessage());
        throw $e;
      }
    }

    try {
      switch ($order->getState()) {
        case \Magento\Sales\Model\Order::STATE_NEW:
          if (!$zohoSalesOrderManagement->getSalesOrderId()) {
            // A new order that at this point would have had an estimate created
            $zohoSalesOrderManagement = $this->_zohoOrderManagement->createSalesOrder($zohoSalesOrderManagement, $order);
          }
          break;

        case \Magento\Sales\Model\Order::STATE_PROCESSING:
          // In the event no Zoho slaes order has been created, create one
          if (!$zohoSalesOrderManagement->getSalesOrderId()) {
            $zohoSalesOrderManagement = $this->_zohoOrderManagement->createSalesOrder($zohoSalesOrderManagement, $order);
          }
          // As this order is to be invoiced with respect to Magento, accept the Zoho estimate,
          // mark the sales order as open, which allows purchase orders, invoices and shipping to
          // occur within Zoho and then create the Zoho invoice from teh sales order
          if (!$zohoSalesOrderManagement->getInvoiceId()) {
            $this->_zohoOrderManagement->acceptEstimate($zohoSalesOrderManagement);
            $this->_zohoOrderManagement->openSalesOrder($zohoSalesOrderManagement);
            $zohoSalesOrderManagement = $this->_zohoOrderManagement->createInvoice($zohoSalesOrderManagement, $order);
          }
          break;

        case \Magento\Sales\Model\Order::STATE_COMPLETE:
          // Order complete and shipment requested. Start the shipment process in Zoho
          $this->_zohoShipping->createShipment($zohoSalesOrderManagement, $order);
          break;

        case \Magento\Sales\Model\Order::STATE_CANCELED:
          // Order cancelled so delete all Zoho documents and any state between Magento and Zoho
          $this->_zohoOrderManagement->deleteAll($zohoSalesOrderManagement);
          $this->_zohoSalesOrderManagementRepository->delete($zohoSalesOrderManagement);
          break;
      }
    } catch (\Exception $e) {
      $this->_logger->error(__('Error processing sales order in state ' . $order->getStatusLabel() . ': '. $e->getMessage()));
      throw $e;
    }
  }
}
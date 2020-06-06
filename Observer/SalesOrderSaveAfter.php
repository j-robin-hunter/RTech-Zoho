<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SalesOrderSaveAfter implements ObserverInterface {

  protected $_zohoSalesOrderManagementRepository;
  protected $_zohoOrderManagement;
  protected $_zohoShipping;
  protected $_zohoCustomerRepository;
  protected $_zohoSalesOrderManagementFactory;
  protected $_objectManager;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoSalesOrderManagementRepository $zohoSalesOrderManagementRepository,
    \RTech\Zoho\Model\ZohoOrderManagement $zohoOrderManagement,
    \RTech\Zoho\Model\ZohoShipping $zohoShipping,
    \RTech\Zoho\Model\ZohoCustomerRepository $zohoCustomerRepository,
    \RTech\Zoho\Model\ZohoSalesOrderManagementFactory $zohoSalesOrderManagementFactory,
    \Magento\Framework\ObjectManagerInterface $objectManager,
    \Magento\Quote\Model\QuoteRepository $quoteRepository,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoSalesOrderManagementRepository = $zohoSalesOrderManagementRepository;
    $this->_zohoOrderManagement = $zohoOrderManagement;
    $this->_zohoShipping = $zohoShipping;
    $this->_zohoCustomerRepository = $zohoCustomerRepository;
    $this->_zohoSalesOrderManagementFactory = $zohoSalesOrderManagementFactory;
    $this->_objectManager = $objectManager;
    $this->_logger = $logger;
  }


  public function execute(\Magento\Framework\Event\Observer $observer) {
    $order = $observer->getEvent()->getOrder();
    $soRef = null;
    try {
      // Get the existing state of the order between Magento and Zoho
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($order->getId());
    } catch (NoSuchEntityException $e) {
      // No record of the order so start by creating a Zoho estimate
      try {
        // Use object manager to see if a quote has been created.
        // Not using DI as the Quote module may not have been loaded
        $savedQuote = null;
        $savedQuoteRepository = $this->_objectManager->get('RTech\Quote\Model\QuoteRepository');
        if ($savedQuoteRepository) {
          try {
            $savedQuote = $savedQuoteRepository->getById($order->getQuoteId());
             $savedQuote->getEstimateId();
          } catch (\Exception $e) {
            // There is no saved quote
            $savedQuote = null;
          }
        }
        if ($savedQuote === null) {
          $zohoSalesOrderManagement = $this->_zohoOrderManagement->orderEstimate($order);
        } else {
          $zohoCustomer = $this->_zohoCustomerRepository->getById($savedQuote->getCustomerId());
          $estimate = $this->_zohoOrderManagement->updateEstimate(
            $savedQuote->getEstimateId(),
            $zohoCustomer->getZohoId(),
            $order,
            $order->getBaseShippingAmount());
          $soRef = $estimate['reference_number'];
          $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementFactory->create();
          $zohoSalesOrderManagement->setData([
            ZohoSalesOrderManagementInterface::ORDER_ID => $order->getId(),
            ZohoSalesOrderManagementInterface::ZOHO_ID => $zohoCustomer->getZohoId(),
            ZohoSalesOrderManagementInterface::ESTIMATE_ID => $savedQuote->getEstimateId()
          ]);
          $savedQuoteRepository->delete($savedQuote);
        }
      } catch (\Exception $e) {
        $this->_logger->error(__('Error while creating Zoho estimate'), ['exception' => $e]);
        throw $e;
      }
    }

    try {
      switch ($order->getState()) {
        case \Magento\Sales\Model\Order::STATE_NEW:
          if (!$zohoSalesOrderManagement->getSalesOrderId()) {
            // A new order that at this point would have had an estimate created
            $zohoSalesOrderManagement = $this->_zohoOrderManagement->createSalesOrder($zohoSalesOrderManagement, $order, $soRef);
          }
          break;

        case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
          // Do nothing here as this seems to only occur with on-line payments which do not impact the Zoho workflow
          break;

          case \Magento\Sales\Model\Order::STATE_PROCESSING:
          // In the event no Zoho sales order has been created, create one
          if (!$zohoSalesOrderManagement->getSalesOrderId()) {
            $zohoSalesOrderManagement = $this->_zohoOrderManagement->createSalesOrder($zohoSalesOrderManagement, $order, $soRef);
          }
          // As this order is to be invoiced with respect to Magento, accept the Zoho estimate,
          // mark the sales order as open, which allows purchase orders, invoices and shipping to
          // occur within Zoho and then create the Zoho invoice from the sales order
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

        case \Magento\Sales\Model\Order::STATE_CLOSED:
          // This is a refund. Zoho integration is captured by SalesOrderCreditCreditmemoSaveAfter
          break;

        case \Magento\Sales\Model\Order::STATE_CANCELED:
          // Order cancelled so delete all Zoho documents and any state between Magento and Zoho
          try {
            $this->_zohoOrderManagement->deleteAll($zohoSalesOrderManagement);
            $this->_zohoSalesOrderManagementRepository->delete($zohoSalesOrderManagement);
          } catch (\Exception $e) {
            $this->_logger->error(__('Unable to delete all Zoho transactions'), ['exception' => $e]);
            throw $e;
          }
          break;

        case \Magento\Sales\Model\Order::STATE_HOLDED:
          break;

        case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
          break;
        }

    } catch (\Exception $e) {
      $this->_logger->error(__('Error processing sales order in state ') . $order->getStatusLabel(), ['exception' => $e]);
      throw $e;
    }
  }
}
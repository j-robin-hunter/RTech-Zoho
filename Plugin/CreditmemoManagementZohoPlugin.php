<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

use Magento\Framework\Exception\LocalizedException;

class CreditmemoManagementZohoPlugin {

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

  public function aroundRefund (
    \Magento\Sales\Api\CreditmemoManagementInterface $subject,
    callable $refund,
    \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo,
    $offlineRequested = false
  ) {
    try {
      // Get the existing state of the order between Magento and Zoho.
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($creditmemo->getOrderId());

      // Order refunded so create a Zoho credit note
      // If this cannot be done a Localized error will be thrown which will prevent Magento
      // from creatimng the refund. This is important to prevent an online refund
      // refunding the customer to their credit/debit card potentially without having returned goods
      $this->_zohoOrderManagement->createCreditNote($zohoSalesOrderManagement, $creditmemo);

      // Call refund to execute the Magento refund
      $savedCreditmemo = $refund($creditmemo, $offlineRequested);
      
      return $savedCreditmemo;
    } catch (LocalizedException $e) {
      throw $e;
    } catch (\Exception $e) {
      $this->_logger->error(__('Error while creating Zoho credit note'), ['exception' => $e]);
      throw $e;
    }
  }
}
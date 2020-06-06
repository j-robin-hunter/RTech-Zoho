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
      // The check will throw an error if the refund is not allow otherwise it will return a Zoho order
      $zohoSalesOrderManagement = $this->_zohoSalesOrderManagementRepository->getById($creditmemo->getOrderId());
      $zohoOrder = $this->_zohoOrderManagement->isRefundAllowed($zohoSalesOrderManagement, $creditmemo);

      // Call refund to execute the Magento refund
      $savedCreditmemo = $refund($creditmemo, $offlineRequested);

      // Order refunded so create a Zoho credit note
      $this->_zohoOrderManagement->createCreditNote($zohoOrder, $creditmemo);
    } catch (LocalizedException $e) {
      throw $e;
    } catch (\Exception $e) {
      $this->_logger->error(__('Error while creating Zoho credit note'), ['exception' => $e]);
      throw $e;
    }
  }
}
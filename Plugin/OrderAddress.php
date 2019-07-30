<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Plugin;

class OrderAddress {

  public function __construct(

  ) {

  }

  public function afterSave (
    \Magento\Sales\Api\OrderAddressRepositoryInterface $subject,
    $address
  ) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('afterOrderSave');

  }
}
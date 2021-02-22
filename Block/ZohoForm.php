<?php
/**
 * Copyright © 2021 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Block;

use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use RTech\Zoho\Webservice\Client\ZohoCrmClient;

class ZohoForm extends Template implements BlockInterface {
  protected $_template = "widget/zohoform.phtml";
  protected $_zohoClient;
  protected $_module;
  protected $_crmId;

  /**
   * @param \Magento\Framework\View\Element\Template\Context $context
   */
  public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \RTech\Zoho\Helper\ConfigData $configData,
    \Zend\Http\Client $zendClient,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Framework\App\Request\Http $request,
    array $data = []
  ) {
    $this->_zohoClient = new ZohoCrmClient($configData, $zendClient, $storeManager);
    $this->_module = $request->getParam('module');
    $this->_crmId = $request->getParam('crmid');
    parent::__construct($context, $data);
  }

  public function getFormUrl() {
    $mapping = "";
    try {
      $record = $this->_zohoClient->getRecord($this->_module, $this->_crmId);
      $lines = explode(",", $this->getData('form_map'));
      foreach ($lines as $line) {
        $keys = explode(":", $line);
        if ($record[$keys[1]]) {
          $mapping .= empty($mapping) ? '?' : '&';
          $mapping .= $keys[0] . '=' . $record[$keys[1]];
        }
      }
    } catch (Exception $e) {
      // Do nothing
    }
    return $this->getData('form_url') . $mapping;
  }
}
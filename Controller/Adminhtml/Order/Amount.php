<?php

namespace Swarming\SubscribePro\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;

class Amount extends Action
{
    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quoteFactory;
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param QuoteFactory $quoteFactory
     * @param Context $context
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        QuoteFactory $quoteFactory,
        Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        try {
            $quote = $this->quoteFactory->create()->load($quoteId);
            $grandTotal = $quote->getGrandTotal();
            $result = $this->resultJsonFactory->create();
            return $result->setData(['grand_total' => $grandTotal]);
        } catch (\Exception $e) {
            $result = $this->resultJsonFactory->create();
            return $result->setData(['error' => $e->getMessage()]);
        }
    }
}

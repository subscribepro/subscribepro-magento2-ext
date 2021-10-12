<?php

namespace Swarming\SubscribePro\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

class Status extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if ($this->getRequest()->getParam('TRANSACTION_STATUS')) {
            $this->messageManager->addSuccessMessage(__('Order was placed successfully.'));
            $resultRedirect->setPath('checkout/onepage/success/');
        } else {
            $this->messageManager->addErrorMessage(__('Transaction has been declined. Please try again later.'));
            $resultRedirect->setPath('checkout/cart/index');
        }
        return $resultRedirect;
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\Request\InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}

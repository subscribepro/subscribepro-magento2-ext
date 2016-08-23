<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Swarming\SubscribePro\Model\Vault\Form
     */
    protected $vaultForm;

    /**
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->vaultForm = $paymentProfileForm;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);


        if (!$this->formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            return $resultRedirect->setPath('vault/cards/listaction');
        }

        $publicHash = $this->getRequest()->getParam(PaymentTokenInterface::PUBLIC_HASH);

        try {
            $data = (array)$this->getRequest()->getParams();
            if ($publicHash) {
                $this->vaultForm->updateProfile($publicHash, $data);
            } else {
                $this->vaultForm->createProfile($data);
            }
            $this->messageManager->addSuccessMessage(__('The credit card is saved.'));
            $resultRedirect->setPath('vault/cards/listaction');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath(
                '*/*/edit',
                ($publicHash ? ['_query' => [PaymentTokenInterface::PUBLIC_HASH => $publicHash]] : [])
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the card.'));
            $resultRedirect->setPath('vault/cards/listaction');
        }

        return $resultRedirect;
    }
}

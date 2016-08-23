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
     * @var \Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $spVaultConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm
     * @param \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm,
        \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->vaultForm = $paymentProfileForm;
        $this->spVaultConfig = $spVaultConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->formKeyValidator->validate($this->getRequest())
            || !$this->getRequest()->isPost()
            || !$this->spVaultConfig->isActive()
        ) {
            return $resultRedirect->setPath('vault/cards/listaction');
        }

        $publicHash = $this->getRequest()->getParam(PaymentTokenInterface::PUBLIC_HASH);

        try {
            $data = (array)$this->getRequest()->getParams();
            unset($data['form_key']);
            if ($publicHash) {
                unset($data[PaymentTokenInterface::PUBLIC_HASH]);
                $this->vaultForm->updateProfile($publicHash, $data, $this->customerSession->getCustomerId());
            } else {
                $this->vaultForm->createProfile($data, $this->customerSession->getCustomerId());
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

<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class Update extends \Magento\Customer\Controller\AbstractAccount
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
    protected $platformVaultConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm
     * @param \Swarming\SubscribePro\Gateway\Config\VaultConfig $platformVaultConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Swarming\SubscribePro\Model\Vault\Form $paymentProfileForm,
        \Swarming\SubscribePro\Gateway\Config\VaultConfig $platformVaultConfig
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->vaultForm = $paymentProfileForm;
        $this->platformVaultConfig = $platformVaultConfig;
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
            || !$this->getRequest()->isPost() /* @phpstan-ignore-line */
            || !$this->platformVaultConfig->isActive()
        ) {
            return $resultRedirect->setPath('vault/cards/listaction');
        }

        $publicHash = $this->getRequest()->getParam(PaymentTokenInterface::PUBLIC_HASH);
        $data = (array)$this->getRequest()->getParams();
        unset($data['form_key'], $data[PaymentTokenInterface::PUBLIC_HASH]);

        try {
            $this->vaultForm->updateProfile($publicHash, $data, $this->customerSession->getCustomerId());
            $this->messageManager->addSuccessMessage(__('The credit card is saved.'));
            $resultRedirect->setPath('vault/cards/listaction');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath(
                '*/*/edit',
                ['_query' => [PaymentTokenInterface::PUBLIC_HASH => $publicHash]]
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the card.'));
            $resultRedirect->setPath('vault/cards/listaction');
        }

        return $resultRedirect;
    }
}

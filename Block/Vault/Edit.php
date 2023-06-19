<?php

namespace Swarming\SubscribePro\Block\Vault;

use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class Edit extends \Magento\Directory\Block\Data
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $paymentTokenManagement;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformPaymentProfileService;

    /**
     * @var \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    protected $token;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    protected $profile;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        array $data = []
    ) {
        $this->session = $session;
        $this->messageManager = $messageManager;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * @inheridoc
     * @return Edit
     */
    protected function _prepareLayout()
    {
        $publicHash = $this->getRequest()->getParam(PaymentTokenInterface::PUBLIC_HASH);
        if ($publicHash) {
            $this->loadVault($publicHash);
        }

        $this->initPageTitle();

        return parent::_prepareLayout();
    }

    /**
     * @param string $publicHash
     */
    protected function loadVault($publicHash)
    {
        try {
            $this->loadToken($publicHash);
            $this->loadProfile();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @param string $publicHash
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function loadToken($publicHash)
    {
        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, $this->session->getCustomerId());
        if (!$token) {
            throw new LocalizedException(__('The saved credit is not found.'));
        }
        $this->token = $token;
    }

    /**
     * Load Subscribe Pro payment profile.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function loadProfile()
    {
        $profile = $this->platformPaymentProfileService->loadProfile($this->token->getGatewayToken());
        if (!$profile) {
            throw new LocalizedException(__('The saved credit is not found.'));
        }
        $this->profile = $profile;
    }

    /**
     * Initialize page title for this page.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function initPageTitle()
    {
        /** @var \Magento\Theme\Block\Html\Title $pageMainTitle */
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $message = $this->token ? __('Edit Card: ending %1', $this->getNumberLast4Digits()) : __('Add New Card');
            $pageMainTitle->setPageTitle($message);
        }
    }

    /**
     * @return string
     */
    protected function getNumberLast4Digits()
    {
        $tokenDetails = json_decode($this->token->getTokenDetails() ?: '{}', true);
        return $tokenDetails['maskedCC'];
    }

    /**
     * @return string
     */
    public function renderBillingAddress()
    {
        /** @var \Swarming\SubscribePro\Block\Vault\Edit\BillingAddress $billingBlock */
        $billingBlock = $this->getChildBlock('billing');
        return $billingBlock ? $billingBlock->render($this->profile) : '';
    }

    /**
     * @return string
     */
    public function renderCard()
    {
        if ($this->token) {
            /** @var \Swarming\SubscribePro\Block\Vault\Edit\CardExpiration $cardCardExpirationBlock */
            $cardCardExpirationBlock = $this->getChildBlock('card_expiration');
            $cardHtml = $cardCardExpirationBlock ? $cardCardExpirationBlock->render($this->token) : '';
        } else {
            /** @var \Swarming\SubscribePro\Block\Vault\Edit\Card $cardBlock */
            $cardBlock = $this->getChildBlock('card');
            $cardHtml = $cardBlock ? $cardBlock->toHtml() : '';
        }
        return $cardHtml;
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->token
            ? $this->buildUpdatePaymentProfileUrl()
            : $this->buildCreatePaymentProfileUrl();
    }

    /**
     * @return string
     */
    private function buildCreatePaymentProfileUrl()
    {
        return $this->getUrl(
            'swarming_subscribepro/cards/save',
            ['_secure' => true]
        );
    }

    /**
     * @return string
     */
    private function buildUpdatePaymentProfileUrl()
    {
        return $this->getUrl(
            'swarming_subscribepro/cards/update',
            [PaymentTokenInterface::PUBLIC_HASH => $this->token->getPublicHash(), '_secure' => true]
        );
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('vault/cards/listaction');
    }
}

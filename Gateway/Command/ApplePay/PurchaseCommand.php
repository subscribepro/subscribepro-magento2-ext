<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Command\ApplePay;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Command\AbstractProfileCreatorCommand;
use Swarming\SubscribePro\Gateway\Request\ApplePayPaymentDataBuilder;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class PurchaseCommand extends AbstractProfileCreatorCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \InvalidArgumentException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new \InvalidArgumentException(__('Payment token is not passed'));
        }
        $transaction = $this->platformTransactionService->createTransaction($requestData);
        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE])
            && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]
        ) {
            $this->platformTransactionService->purchaseByProfile([
                VaultDataBuilder::PAYMENT_PROFILE_ID => $requestData[ApplePayPaymentDataBuilder::PLATFORM_PROFILE_ID]
            ], $transaction);
        } else {
            $this->platformTransactionService->purchaseByToken(
                $requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN],
                $transaction
            );
        }

        return $transaction;
    }
}

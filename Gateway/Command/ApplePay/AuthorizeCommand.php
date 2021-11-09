<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Command\ApplePay;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Command\AbstractProfileCreatorCommand;
use Swarming\SubscribePro\Gateway\Request\ApplePayPaymentDataBuilder as PaymentDataBuilder;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class AuthorizeCommand extends AbstractProfileCreatorCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \InvalidArgumentException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new \InvalidArgumentException(__('ApplePay Payment token is not passed'));
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE])
            && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]
        ) {
            $profileId = $requestData[PaymentDataBuilder::PLATFORM_PROFILE_ID];
            $this->platformTransactionService->authorizeByProfile([
                VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId
            ], $transaction);
        } else {
            $this->platformTransactionService->authorizeByToken(
                $requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN],
                $transaction
            );
        }

        return $transaction;
    }
}

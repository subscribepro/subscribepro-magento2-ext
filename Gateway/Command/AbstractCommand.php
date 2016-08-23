<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandException;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileService
     */
    protected $sdkPaymentProfileService;

    /**
     * @var \SubscribePro\Service\Transaction\TransactionService
     */
    protected $sdkTransactionService;

    /**
     * @var \Magento\Payment\Gateway\Response\HandlerInterface
     */
    protected $handler;

    /**
     * @var \Magento\Payment\Gateway\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Magento\Payment\Gateway\Response\HandlerInterface $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder,
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Magento\Payment\Gateway\Response\HandlerInterface $handler,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->sdkPaymentProfileService = $platform->getSdk()->getPaymentProfileService();
        $this->sdkTransactionService = $platform->getSdk()->getTransactionService();
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        $requestData = $this->requestBuilder->build($commandSubject);

        try {
            $transaction = $this->processTransaction($requestData);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CommandException(
                __('Transaction has been declined. Please try again later.')
            );
        }

        $response = ['transaction' => $transaction];

        $result = $this->validator->validate(array_merge($commandSubject, $response));
        if (!$result->isValid()) {
            throw new CommandException(
                __('Transaction has been declined. Please try again later.')
            );
        }

        $this->handler->handle($commandSubject, $response);
    }

    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    abstract protected function processTransaction(array $requestData);
}

<?php

namespace Swarming\SubscribePro\Test\Unit\Service;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\TestFramework\Unit\Matcher\MethodInvokedAtIndex;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Swarming\SubscribePro\Service\PaymentTokenManagement;

class PaymentTokenManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Service\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenRepositoryMock;

    /**
     * @var \Magento\Framework\Api\FilterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilderMock;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactoryMock;
    
    protected function setUp()
    {
        $this->paymentTokenRepositoryMock = $this->getMockBuilder(PaymentTokenRepositoryInterface::class)->getMock();
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()->getMock();
        
        $this->paymentTokenManagement = new PaymentTokenManagement(
            $this->paymentTokenRepositoryMock,
            $this->filterBuilderMock,
            $this->searchCriteriaBuilderMock,
            $this->dateTimeFactoryMock
        );
    }

    public function testGetVisibleAvailableTokens()
    {
        $customerId = 1;

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultMock = $this->getMockBuilder(PaymentTokenSearchResultsInterface::class)->getMock();
        $tokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();

        $customerFilter = $this->createExpectedFilter(PaymentTokenInterface::CUSTOMER_ID, $customerId, 0);
        $visibilityFilter = $this->createExpectedFilter(PaymentTokenInterface::IS_VISIBLE, true, 1);
        $isActiveFilter = $this->createExpectedFilter(PaymentTokenInterface::IS_ACTIVE, true, 2);
        $expiresAtFilter = $this->createExpectedFilter(
            PaymentTokenInterface::EXPIRES_AT,
            '2018-01-01 00:00:00',
            3
        );

        $date = $this->getMockBuilder('DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $date->expects($this->once())
            ->method('format')
            ->with('Y-m-d 00:00:00')
            ->willReturn('2018-01-01 00:00:00');
        
        $this->filterBuilderMock->expects($this->once())
            ->method('setConditionType')
            ->with('gt')
            ->willReturnSelf();
        
        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->with("now", new \DateTimeZone('UTC'))
            ->willReturn($date);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->with([$customerFilter, $visibilityFilter, $isActiveFilter, $expiresAtFilter])
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->paymentTokenRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);

        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$tokenMock]);

        $this->assertEquals(
            [$tokenMock],
            $this->paymentTokenManagement->getSubscribeProTokensByCustomerId($customerId)
        );
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param int $atIndex
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createExpectedFilter($field, $value, $atIndex)
    {
        $filterObject = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilderMock->expects(new MethodInvokedAtIndex($atIndex))
            ->method('setField')
            ->with($field)
            ->willReturnSelf();
        $this->filterBuilderMock->expects(new MethodInvokedAtIndex($atIndex))
            ->method('setValue')
            ->with($value)
            ->willReturnSelf();
        $this->filterBuilderMock->expects(new MethodInvokedAtIndex($atIndex))
            ->method('create')
            ->willReturn($filterObject);

        return $filterObject;
    }
}

<?php

namespace Swarming\SubscribePro\Block\Customer;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $data = [
            'components' => [
                'subscriptions-container' => [
                    'children' => [
                        'subscriptions' => [
                            'config' => [
                                'datepickerOptions' => $this->getDatepickerOptions(),
                                'priceFormat' => $localeFormat->getPriceFormat()
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->jsLayout = array_merge_recursive($data, $this->jsLayout);
    }

    /**
     * @return array
     */
    protected function getDatepickerOptions()
    {
        return [
            'minDate' => 2,
            'showOn' => 'button',
            'buttonImage' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
            'buttonText' => __('Click to change date'),
            'buttonImageOnly' => true,
            'dateFormat' => 'yyyy-mm-dd',
        ];
    }
}

<?php

namespace Swarming\SubscribePro\Block\Customer;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    protected function _construct()
    {
        parent::_construct();

        $data = [
            'components' => [
                'subscriptions-container' => [
                    'children' => [
                        'subscriptions' => [
                            'config' => [
                                'datepickerOptions' => $this->getDatepickerOptions()
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

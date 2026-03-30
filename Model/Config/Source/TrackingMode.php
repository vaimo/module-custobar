<?php

namespace Custobar\CustoConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

class TrackingMode implements OptionSourceInterface
{
    public const MODE_NONE = 0;
    public const MODE_CUSTOM_SCRIPT = 1;
    public const MODE_GTM = 2;
    public const MODE_CUSTOM_SCRIPT_V2 = 3;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $options = $this->toArray();
        foreach ($options as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $optionArray;
    }

    /**
     * Get options as associative array
     *
     * @return Phrase[]
     */
    public function toArray()
    {
        return [
            self::MODE_NONE => __('None'),
            self::MODE_GTM => __('Google Tag Manager'),
            self::MODE_CUSTOM_SCRIPT_V2 => __('Custom Script v1'),
            self::MODE_CUSTOM_SCRIPT => __('Custom Script v2)'),
        ];
    }
}

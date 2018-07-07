<?php
namespace ide\l10n;

/**
 * Class LozalizedString
 * @package ide\l10n
 */
class LocalizedString
{
    /**
     * @var string
     */
    private $string;

    /**
     * LozalizedString constructor.
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
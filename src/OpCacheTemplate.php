<?php
/**
 * OpCacheTemplate.php Description
 * @author fyrye <fyrye@torntech.com>
 * @version 2.2.3
 */
namespace fyrye\OpCacheGui;

/**
 * Class OpCacheTemplate
 * @package fyrye\OpCacheGui
 */
class OpCacheTemplate
{
    /**
     * OpCacheTemplate constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $opcache = OpCacheService::init($options);
        require __DIR__ . '/view.php';
    }

    /**
     * @param array $options
     * @return string
     */
    public static function render(array $options = [])
    {
        ob_start();
        new self($options);

        return ob_get_clean();
    }
}

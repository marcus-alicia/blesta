<?php

use Minphp\Bridge\Initializer;
use Minphp\Language\Language as MinphpLanguage;

/**
 * Language Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Language\Language instead.
 */
class Language extends MinphpLanguage
{
    /**
     * Ensure default language, default director, and allow pass through
     * settings are set
     */
    private static function ensureSettings()
    {
        $config = Initializer::get()->getContainer()
            ->get('minphp.language');

        if (array_key_exists('default', $config)) {
            self::setDefaultLanguage($config['default']);
        }

        if (array_key_exists('dir', $config)) {
            self::setDefaultDir($config['dir']);
        }

        if (array_key_exists('pass_through', $config)) {
            self::allowPassthrough($config['pass_through']);
        }
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    public static function _($key, $return = false)
    {
        // @codingStandardsIgnoreEnd
        self::ensureSettings();
        return call_user_func_array('parent::getText', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public static function getText($key, $return = false)
    {
        self::ensureSettings();
        return call_user_func_array('parent::getText', func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public static function loadLang($file, $language = null, $dir = null)
    {
        self::ensureSettings();
        call_user_func_array('parent::loadLang', func_get_args());
    }
}

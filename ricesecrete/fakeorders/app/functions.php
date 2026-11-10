<?php
/**
 * Here is your custom functions.
 */

if (!function_exists('lang')) {
    /**
     * Translation
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    function lang(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $res = \support\Translation::trans($id, $parameters, $domain, $locale);
        $auto_lang = config('app.auto_lang');
        if ($auto_lang && $res === $id) {
            $locale = locale();
            $locale = strtolower($locale);
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $id) && ('zh-cn' != $locale && 'zh-tw' != $locale)) {
                \plugin\admin\app\services\LangServices::addItem($id, 0);
            }
        }
        return $res === '' ? $id : $res;
    }

    /**
     * Translation
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    function admin_lang(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $res = \support\Translation::trans($id, $parameters, $domain, $locale);
        $auto_lang = config('app.auto_lang');
        if ($auto_lang && $res === '') {
            $locale = locale();
            $locale = strtolower($locale);
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $id) && ('zh-cn' != $locale && 'zh-tw' != $locale)) {
                \plugin\admin\app\services\LangServices::addItem($id, 1);
            }
        }
        return $res === '' ? $id : $res;
    }
}
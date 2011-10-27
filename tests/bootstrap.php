<?php

date_default_timezone_set('UTC');

/**
 * Set include path considering that camelSpider is contained in vendors directory
 *
 */
set_include_path(
    '../src'
    . PATH_SEPARATOR . '../../Respect/Validation/library/' 
    . PATH_SEPARATOR . '../../Zend/library/' . PATH_SEPARATOR  . get_include_path());

/**
 * Autoloader that implements the PSR-0 spec for interoperability between
 * PHP software.
 */
spl_autoload_register(
    function($className) {
        $fileParts = explode('\\', ltrim($className, '\\'));

        if (false !== strpos(end($fileParts), '_'))
            array_splice($fileParts, -1, 1, explode('_', current($fileParts)));

        $file = implode(DIRECTORY_SEPARATOR, $fileParts) . '.php';

        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            if (file_exists($path = $path . DIRECTORY_SEPARATOR . $file))
                return require $path;
        }
    }
);

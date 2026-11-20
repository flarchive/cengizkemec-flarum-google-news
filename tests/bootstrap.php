<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

if (! getenv('FLARUM_TEST_TMP_DIR_LOCAL')) {
    $tmp = $root.'/tests/tmp';

    if (! is_dir($tmp)) {
        mkdir($tmp, 0777, true);
    }

    putenv('FLARUM_TEST_TMP_DIR_LOCAL='.$tmp);
}

if (! getenv('FLARUM_TEST_VENDOR_PATH')) {
    putenv('FLARUM_TEST_VENDOR_PATH='.$root.'/vendor');
}

<?php

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);


define('BASE_URL', '/crypt-of-secrets/');


define('AVATAR_UPLOAD_DIR', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'avatars');
define('POST_UPLOAD_DIR',   ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'posts');



define('TRUST_THRESHOLD', 50); 
define('CUSTOM_AVATAR_TRUST', 200);


define('DEV_MODE', true);

if (DEV_MODE) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
?>
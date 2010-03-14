<?php

define('DIF_VIRTUAL_WEB_ROOT', "/htdocs/");
define('DIF_INDEX_ROOT', dirname(__FILE__)."/");
define('DIF_WEB_ROOT', realpath(DIF_INDEX_ROOT).DIF_VIRTUAL_WEB_ROOT);
define('DIF_ROOT', realpath(DIF_WEB_ROOT."/../dif")."/");
define('DIF_SYSTEM_ROOT', realpath(DIF_WEB_ROOT."../data")."/");

require_once(DIF_ROOT.'utils/Utils.php');
require_once(DIF_ROOT.'utils/Timer.php');

// include external tools
Utils::setIncludePath();

$timer = Timer::getInstance();

require_once(DIF_ROOT."/core/Director.php");

$director = Director::getInstance();
$director->main();

//echo ' Executed in '. $timer->getTime().' seconds';
//echo '<!-- Executed in '. $timer->getTime().' seconds -->';
/*
$cache = Cache::getInstance();
$request = Request::getInstance();
Utils::debug(sprintf("%s : Executed in %s seconds (%s)", $request->getPath(), $timer->getTime(), $cache->isCached() ? 'CACHED' : 'GENERATED'), 'timer.log');
*/

?>

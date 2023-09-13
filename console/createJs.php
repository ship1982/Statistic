<?php

$script = '<script type="text/javascript">(function(b,c,d,z){c[z]=c[z] || function(){(c[z].q=c[z].q || []).push(arguments)},c.mgtsstat || (a=b.createElement("script"),a.src=d,a.async=1,m=b.getElementsByTagName(\'script\')[0],m.parentNode.insertBefore(a,m),c.mgtsstat={pin:' . mt_rand(1000,9999) . time() .'})})(document,window,"//count.mgts.ru/s.js",\'mstat\');mstat(\'require\',\'advert\');</script>';
echo "\n\nКод для вставки на сайт:\n\n" . $script . "\n\n";
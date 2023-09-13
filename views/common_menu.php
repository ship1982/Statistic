<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 25.08.16
 * Time: 0:31
 */

?>

<hr>
<ul class="nav nav-sidebar">
    <li class="<?php echo ((strpos($_SERVER['REQUEST_URI'], '/groupfilter') !== false) ? 'active' : '') ?>">
        <a href="/groupfilter">Настройки групп для фильтрации</span></a>
    </li>
    <li class="<?php echo ((strpos($_SERVER['REQUEST_URI'], '/top') !== false) ? 'active' : '') ?>">
        <a href="/top">Статистика top100</span></a>
    </li>
</ul>

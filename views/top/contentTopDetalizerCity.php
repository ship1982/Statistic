<?php

arsort($params['result']['count']);
$keys = array_keys($params['result']['count']);

foreach ($params['result']['count'] as $city => $list)
{
	echo '<tr>';
        echo '<td>' . $city . '</td>';
        echo '<td>' . $list['c'] . '</td>';
        echo '<td>';
        $itemArray = [];
        foreach ($params['result']['list'][$city] as $arItem)
        {
            if(empty($itemArray[$arItem['domain']]))
                $itemArray[$arItem['domain']] = 0;
            $itemArray[$arItem['domain']] += $arItem['c'];
        }

        foreach ($itemArray as $domain => $count)
            echo '<div>' . common_setValue($GLOBALS['app']['allDomain'], $domain) . ' - ' . $count . '</div>';


        echo '</td>';

        echo '<td>' . (int)$list['c_bots'] . '</td>';
        echo '<td>' . common_percent_from_number($list['c'], (int)$list['c_bots']) . '</td>';
        echo '<td>' . (int)$list['c_ads'] . '</td>';
        echo '<td>' . common_percent_from_number($list['c'], (int)$list['c_ads']) . '</td>';
	echo '</tr>';
}
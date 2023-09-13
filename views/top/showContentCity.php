<?php

$arLinksId = [];
$arData = [];
$countByCondition = [];
while($arResult = mysqli_fetch_assoc($data))
{
    /*
	if(empty($countByCondition[$arResult['city']]))
		$countByCondition[$arResult['city']] = 0;
	$countByCondition[$arResult['city']] += $arResult['c'];
	$arData[$arResult['city']][] = $arResult;
	*/

    if(empty($countByCondition[$arResult['city']])){
        //$countByCondition[$arResult['city']] = 0;
        $countByCondition[$arResult['city']]['c'] = 0;
        $countByCondition[$arResult['city']]['c_bots'] = 0;
        $countByCondition[$arResult['city']]['c_ads'] = 0;
    }
    if(empty($countByCondition[$arResult['city']]['c'])){
        $countByCondition[$arResult['city']]['c'] = 0;
    }
    if(empty($countByCondition[$arResult['city']]['c_bots'])){
        $countByCondition[$arResult['city']]['c_bots'] = 0;
    }
    if(empty($countByCondition[$arResult['city']]['c_ads'])){
        $countByCondition[$arResult['city']]['c_ads'] = 0;
    }
    $countByCondition[$arResult['city']]['c'] += $arResult['c'];
    $countByCondition[$arResult['city']]['c_bots'] += $arResult['c_bots'];
    $countByCondition[$arResult['city']]['c_ads'] += $arResult['c_ads'];
    $arData[$arResult['city']][] = $arResult;
}

foreach ($arData as $city => $list)
{
	echo '<tr>';
        echo '<td>' . $city . '</td>';
        echo '<td>' . $countByCondition[$city]['c'] . '</td>';
        echo '<td>';
        for ($i=0; $i < $ic = count($list); $i++)
        {
            echo '<div>' . common_setValue($GLOBALS['app']['allDomain'], $list[$i]['domain']) . ' - ' . $list[$i]['c'] . '</div>';
        }
        echo '</td>';
        echo '<td>' . $countByCondition[$city]['c_bots'] .'</td>';
        echo '<td>' . common_percent_from_number($countByCondition[$city]['c'], $countByCondition[$city]['c_bots']) . '</td>';
        echo '<td>' . $countByCondition[$city]['c_ads'] . '</td>';
        echo '<td>' . common_percent_from_number($countByCondition[$city]['c'], $countByCondition[$city]['c_ads']) . '</td>';
	echo '</tr>';
}
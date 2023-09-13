<?php

$arLinksId = [];
$arData = [];
$countByCondition = [];
while($arResult = mysqli_fetch_assoc($data))
{
	if(empty($countByCondition[$arResult['provider']])){
		//$countByCondition[$arResult['provider']] = 0;
        $countByCondition[$arResult['provider']]['c'] = 0;
        $countByCondition[$arResult['provider']]['c_bots'] = 0;
        $countByCondition[$arResult['provider']]['c_ads'] = 0;
    }
	if(empty($countByCondition[$arResult['provider']]['c'])){
		$countByCondition[$arResult['provider']]['c'] = 0;
    }
	if(empty($countByCondition[$arResult['provider']]['c_bots'])){
		$countByCondition[$arResult['provider']]['c_bots'] = 0;
    }
	if(empty($countByCondition[$arResult['provider']]['c_ads'])){
		$countByCondition[$arResult['provider']]['c_ads'] = 0;
    }
	$countByCondition[$arResult['provider']]['c'] += $arResult['c'];
	$countByCondition[$arResult['provider']]['c_bots'] += $arResult['c_bots'];
	$countByCondition[$arResult['provider']]['c_ads'] += $arResult['c_ads'];
	$arData[$arResult['provider']][] = $arResult;
}

foreach ($arData as $provider => $list)
{
	echo '<tr>';
        echo '<td>' . $provider . '</td>';
        echo '<td>' . $countByCondition[$provider]['c'] . '</td>';
        echo '<td>';
        for ($i=0; $i < $ic = count($list); $i++)
        {
            echo '<div>' . common_setValue($GLOBALS['app']['allDomain'], $list[$i]['domain']) . ' - ' . $list[$i]['c'] . '</div>';
        }
        echo '</td>';
        echo '<td>' . $countByCondition[$provider]['c_bots'] .'</td>';
        echo '<td>' . common_percent_from_number($countByCondition[$provider]['c'], $countByCondition[$provider]['c_bots']) . '</td>';
        echo '<td>' . $countByCondition[$provider]['c_ads'] . '</td>';
        echo '<td>' . common_percent_from_number($countByCondition[$provider]['c'], $countByCondition[$provider]['c_ads']) . '</td>';
	echo '</tr>';
}
<?php

$arLinksId = [];
$arData = [];
while($arResult = mysqli_fetch_assoc($data))
{
	$arLinksId[] = $arResult['link'];
	$arData[] = $arResult;
}


if(!empty($arLinksId))
{
	common_inc('_fetcher');
	$rsLinkName = fetcher_getLink([
		'IN' => [
			'id' => $arLinksId
		]
	]);
}

$arLinkName = [];
if(!empty($rsLinkName))
{
    while($arLink = mysqli_fetch_assoc($rsLinkName))
        $arLinkName[$arLink['id']] = $arLink['domain_link'];
}

for ($i=0; $i < $ic = count($arData); $i++)
{
    echo '<tr>';
        echo '<td>' . common_setValue($arLinkName, $arData[$i]['link']) . '</td>';
        echo '<td>' . $arData[$i]['c'] . '</td>
        <td>' . common_setValue($GLOBALS['app']['allDomain'], $arData[$i]['domain']) . '</td>';
        echo '<td>' . (int)$arData[$i]['c_bots'] . '</td>';
        echo '<td>' . common_percent_from_number($arData[$i]['c'],(int)$arData[$i]['c_bots']) . '</td>';
        echo '<td>' . (int)$arData[$i]['c_ads'] . '</td>';
        echo '<td>' . common_percent_from_number($arData[$i]['c'],(int)$arData[$i]['c_ads']) . '</td>';
    echo '</tr>';
}
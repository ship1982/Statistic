<?php
$data = (!empty($params['result']) ? $params['result'] : []);

if (!empty($data))
{
  $reportType = $params['filter']['report_type'];
  switch ($reportType)
  {
    case 2:
    case 4:
      $isReportByDomains = false;
      break;

    case 1:
    case 3:
    default:
      $isReportByDomains = true;
      break;
  }

  $h3 = 'Отчет &laquo;' . $params['reportTypes'][$reportType] . '&raquo; ';

  if (!empty($params['filter']['is_cross']))
    $h3 .= '<u>с пересечениями</u> ';
  else
    $h3 .= '<u>без пересечений</u> ';

  $h3 .= 'для партнеров: <i>'.implode(", ", $params['filter']['partner_domains']).'</i>';

  ?>
  <h3 class="sub-header"><?php echo($h3) ?></h3>

  <table class="table table-striped table2excel">
    <thead>
      <tr>
        <th>Реферер</th>
        <th>Рейтинг</th>
      </tr>
    </thead>
    <tbody>
    <?php
      foreach ($data as $key=>$value)
      {
        if ($isReportByDomains)
          $referer = $value['referer_domain'];
        else
          $referer = $value['referer_domain'] . $value['referer_link'];
        ?>
        <tr>
          <td><?php echo($referer)?></td>
          <td><?php echo($value['rating'])?></td>
        </tr>
        <?php
      }
    ?>
    </tbody>
  </table>
  <?php
}
elseif (!empty($params['filter']['run']))
{
?>
    <h3><i>Ничего не найдено</i></h3>
<?php
}
?>


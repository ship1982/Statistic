<?php
use model\Model;

class ParserSetup
{
  private $tblTemp;

  private $endLabel = 0;

  private $shardKeys = [];

  private $shardName = 'l_sequence_4_user_';

  private $error = '';

  function __construct()
  {
    $this->tblTemp = new Model([], 'topReferers_parserTmp', '');

    if (file_exists( __DIR__ . '/../cron/begin.txt'))
    {
      $this->endLabel = trim(file_get_contents(__DIR__ . '/../cron/begin.txt'));

      self::setShardKeys();
      self::tblTempCreateRows();
      self::tblMainCreate();
      self::tblCountCreate();
    }
    else
      $this->error = 'Требуется метка для окончания работы';
  }

  private function setShardKeys()
  {
    $this->tblTemp->query("
      SHOW TABLES like '{$this->shardName}1%';
    ");

    $data = $this->tblTemp->fetch();

    for ($i = 0; $i < count($data); $i++)
    {
      $tableName = current($data[$i]);
      $shardKey = (int) str_replace($this->shardName, '', $tableName);

      if ($this->endLabel >= $shardKey)
        $this->shardKeys[] = $shardKey;
    }

    sort($this->shardKeys);
  }

  private function tblTempCreateRows()
  {
    $sql = '';
    for ($i = 0; $i < count($this->shardKeys); $i++)
    {
      $sql .= "({$this->shardKeys[$i]}, 0, '0'),";
    }
    $sql = self::cropSql($sql);

    if (!empty($sql))
    {
      $this->tblTemp->query("
        INSERT IGNORE INTO topReferers_parserTmp VALUES {$sql}
      ");
    }
  }

  private function tblMainCreate()
  {
    for ($i = 0; $i < count($this->shardKeys); $i++)
    {
      $this->tblTemp->query("
        CREATE TABLE IF NOT EXISTS `topReferers_links_{$this->shardKeys[$i]}` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `uuid` varchar(32) NOT NULL COMMENT 'uid пользователя',
          `internal_way` text NOT NULL COMMENT 'партнерские домены посещенные пользователем',
          `internal_links` mediumtext NOT NULL COMMENT 'переходы между партнерскими доменами',
          `external_links` mediumtext NOT NULL COMMENT 'переходы с внешних доменов на партнерские',
          `time_start` int(10) unsigned NOT NULL COMMENT 'временная метка первого перехода',
          `time_end` int(10) unsigned NOT NULL COMMENT 'временная метка последнего перехода',
          PRIMARY KEY (`id`),
          UNIQUE KEY `uuid` (`uuid`),
          KEY `time_start` (`time_start`,`time_end`),
          KEY `external_links` (`external_links`(5)),
          KEY `internal_links` (`internal_links`(5)),
          FULLTEXT KEY `internal_way` (`internal_way`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Переходы пользователей между доменами';
      ");
    }
  }

  private function tblCountCreate()
  {
    for ($i = 0; $i < count($this->shardKeys); $i++)
    {
      $this->tblTemp->query("
        CREATE TABLE IF NOT EXISTS `topReferers_linksCount_{$this->shardKeys[$i]}` (
          `id` char(32) CHARACTER SET latin1 NOT NULL COMMENT 'md5(datehour,partner_domain,referer_domain,referer_link)',
          `datehour` int(8) unsigned NOT NULL COMMENT 'дата в формате YYYYMMDD',
          `partner_domain` varchar(150) NOT NULL COMMENT 'домен в строковом представлении без протокола и без www',
          `referer_link` varchar(255) NOT NULL COMMENT 'реф. ссылка без протокола и www',
          `referer_domain` varchar(150) NOT NULL COMMENT 'реф. домен без протокола и www',
          `frequency` int(10) unsigned NOT NULL COMMENT 'количество заходов по реф. ссылке за день',
          `time_label` int(10) unsigned NOT NULL COMMENT 'timestamp из параметров очереди. Устанавливается один раз при INSERT. Служебное поле для dirty парсера',
          PRIMARY KEY (`id`),
          KEY `datehour_partner_domain` (`datehour`,`partner_domain`),
          KEY `referer_domain` (`referer_domain`),
          KEY `referer_link` (`referer_link`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Агрегирующая таблица с реф. ссылками по суткам';
      ");
    }
  }

  public function getConfig()
  {
    if (!empty($this->error))
      return [];

    $data = $this->tblTemp->_list(
        ['shard_key', 'last_id'],
        ['state' => '0'],
        ['shard_key' => 'ASC'],
        '0,1'
    );

    if (!empty($data))
    {
      $data[0]['end_label'] = $this->endLabel;
      return $data[0];
    }
    else
      return [];
  }

  public function saveStep($shardKey = 0, $lastId = 0, $state = '')
  {
    if (!empty($this->error))
      return;

    if (empty($state))
    {
      $this->tblTemp->edit(
          ['last_id' => $lastId],
          [
              'shard_key' => $shardKey
          ],
          ''
      );
    }
    else
    {
      $this->tblTemp->edit(
          ['state' => $state],
          [
              'shard_key' => $shardKey
          ],
          ''
      );
    }
  }

  public static function cropSql($sql='', $length=1)
  {
    return substr($sql, 0, -$length);
  }
}
?>
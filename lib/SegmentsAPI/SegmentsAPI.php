<?php

namespace SegmentsAPI;

use model\Model;

class SegmentsAPI
{

    const SEGMENT_VIEW_PREFIX = 'view_segment_';

    /**
     * @var Model
     */
    public $model;

    /**
     * EventsAPI constructor.
     */
    function __construct()
    {
        $this->model = new Model([1], 'segments', false);
    }

    /**
     * Создание сегмента
     *
     * @param string $tableName Имя таблицы
     * @param array $conditions Условия сегмента
     * @param string $script Хранимый скрипт
     * @return bool
     */
    public function createSegment(string $tableName, array $conditions, string $script): bool
    {
        return $this->saveSegment($tableName, $conditions, $script) && $this->createSegmentView($tableName, $conditions);
    }

    /**
     * Проверка существования сегмента
     *
     * @param string $tableName Имя таблицы
     * @param array $conditions Условия сегмента
     * @return bool
     */
    public function segmentExists(string $tableName, array $conditions)
    {
        $conditionHash = md5(json_encode($conditions));
        $sql = "SELECT count(*) as cnt FROM `{$this->model->getTable()}` WHERE `table_name`='{$tableName}' AND `condition_hash`='{$conditionHash}'";
        if (!$this->model->query($sql)) {
            return false;
        }

        $result = $this->model->fetch();

        return isset($result[0]) ? ($result[0]['cnt'] > 0) : false;
    }

    public function getError()
    {
        return $this->model->error;
    }

    /**
     * Найти уникальный идентификатор пользователя по сеансу
     *
     * @param string $tableName Имя таблицы
     * @param string $seance Сеанс
     * @return string|null
     */
    public function findUserBySeance(string $tableName, string $seance)
    {
        $sql = "SELECT `uuid` FROM `{$tableName}` WHERE `seance`='{$seance}' LIMIT 1";
        if (!$this->model->query($sql)) {
            return null;
        }
        $result = $this->model->fetch();

        return isset($result[0]) ? $result[0]['uuid'] : null;
    }

    /**
     * Найти сегмент по уникальному идентификатору пользователя
     *
     * @param string $tableName Имя таблицы
     * @param string $uuid Уникальный идентификатор пользователя
     * @return array|null
     */
    public function findSegmentByUser(string $tableName, string $uuid)
    {
        $this->model->select();
        $this->model->from();
        $this->model->where('table_name', '=', $tableName);
        if (!$this->model->execute()) {
            return null;
        }

        $foundedSegment = null;
        $segments = $this->model->fetch();
        foreach ($segments as $segment) {
            $segmentViewName = self::SEGMENT_VIEW_PREFIX . $segment['condition_hash'];
            $sql = "SELECT count(*) cnt FROM `{$segmentViewName}` WHERE `uuid`='{$uuid}'";
            if (!$this->model->query($sql)) {
                continue;
            }

            $result = $this->model->fetch();
            if (isset($result[0]) && $result[0]['cnt'] > 0) {
                $foundedSegment = $segment;
                break;
            }
        }

        return $foundedSegment;
    }

    /**
     * Сохранение сегмента в БД
     *
     * @param string $tableName Имя таблицы
     * @param array $conditions Условия сегмента
     * @param string $script Хранимый скрипт
     * @return bool
     */
    protected function saveSegment(string $tableName, array $conditions, string $script): bool
    {
        $this->model->insert(['table_name' => $tableName, 'condition_hash' => md5(json_encode($conditions)), 'script' => $script]);
        return $this->model->execute();
    }

    /**
     * Создание представления сегмента в БД
     *
     * @param string $tableName Имя таблицы
     * @param array $conditions Условия сегмента
     * @return bool
     */
    protected function createSegmentView(string $tableName, array $conditions): bool
    {
        $md5 = md5(json_encode($conditions));
        $where = [];
        foreach ($conditions as $condition) {
            if (!isset($condition['type'])) {
                continue;
            }

            switch ($condition['type']) {
                case 'condition':
                    $where[] = "`{$condition['field']}`{$condition['operator']}'{$condition['value']}'";
                    break;
                case 'and':
                    $where[] = 'and';
                    break;
                case 'or':
                    $where[] = 'or';
                    break;
                case 'open_bracket':
                    $where[] = '(';
                    break;
                case 'close_bracket':
                    $where[] = ')';
                    break;
            }
        }

        $segmentViewName = self::SEGMENT_VIEW_PREFIX . $md5;
        $sql = "CREATE OR REPLACE VIEW {$segmentViewName} AS SELECT * FROM `{$tableName}` WHERE " . implode(' ', $where);

        return $this->model->query($sql);
    }

    public function removeSegment(string $tableName, string $hash)
    {
        $sql = "DELETE FROM `segments` WHERE `table_name`='{$tableName}' AND `condition_hash`='{$hash}' LIMIT 1";
        $this->model->query($sql);

        $segmentViewName = self::SEGMENT_VIEW_PREFIX . $hash;
        $sql = "DROP VIEW IF EXISTS {$segmentViewName}";
        $this->model->query($sql);
    }
}
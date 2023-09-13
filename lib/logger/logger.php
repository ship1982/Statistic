<?php

class logger {

	private $string_log = '';
	private $start_time = 0;
	private $arr_events = [];

	/**
	 * Метод выполняет инициализацию логгера,
	 * и сразу добавляет новое событие all_operation.
	 */
	public function start() {
		$this->start_time = time();
		$this->string_log = "start:" . $this->start_time . ";";
		$this->arr_events[0]['start_time'] = $this->microtime_float();
		$this->arr_events[0]['name'] = 'all_operation';
	}

	/**
	 * Метод добавляет новое событие, для которого следует начать контроль времени.
	 * @param string $event_name - название события
	 */
	public function add_event(string $event_name = '') {
		/**
		 * Если это уже не первое событие, то укажем ему время остановки.
		 */
		$count = count($this->arr_events);

		if ($count > 1) {
			$this->arr_events[$count - 1]['stop_time'] = $this->microtime_float();
		}

		//Добавим новое событие
		$this->arr_events[] = [
			'name' => $event_name,
			'start_time' => $this->microtime_float()
		];
	}

	/**
	 * Метод возвращает отформатированную строку для логов.
	 * @param string $memory
	 * @return string
	 */
	public function stop($memory = 0) {
		
		//Для первого события укажем время остановки, т.к. это общее время.
		$this->arr_events[0]['stop_time'] = $this->microtime_float();
		$this->arr_events[count($this->arr_events) - 1]['stop_time'] = $this->microtime_float();

		if(count($this->arr_events >1)){
			for ($i = 1; $i < count($this->arr_events); $i++) {
				$this->string_log .= $this->arr_events[$i]['name'] . ':' . (round($this->arr_events[$i]['stop_time'] - $this->arr_events[$i]['start_time'], 4)) . 'ms;';
			}
		}
		$this->string_log .= $this->arr_events[0]['name'] . ':' . (round($this->arr_events[0]['stop_time'] - $this->arr_events[0]['start_time'], 4)) . 'ms;';
		$this->string_log .= 'memory:'.$memory;
		return $this->string_log;
	}

	/**
	 * Возвращает текущее время в формате timestamp
	 * @return float
	 */
	private function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

}

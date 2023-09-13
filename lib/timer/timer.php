<?php

class timerPrint {

	private $arr_timer = [];

	/**
	 * Метод выполняет начало отсчёта времени,
	 * и если заполнена переменная $print_string,
	 * то её содержимое будет выведено на экран.
	 * 
	 * @param type $timer_name - название таймера,
	 * которое будет использовано для хранения метки времени.
	 * Если такое имя уже есть, то время таймера будет сброшено.
	 * Если имя таймера не указано, то вернёт false.
	 * @param type $print_string - строка, которую можно вывести
	 * перед началом отсчёта времени.
	 * @return boolean
	 */
	public function start($timer_name = '', $print_string = '') {
		//Если такой таймер уже создан
		if (empty($timer_name) /* || array_key_exists($timer_name, $this->arr_timer) */) {
			return false;
		}
		if (!empty($print_string)) {
			print($print_string);
		}
		$this->arr_timer[$timer_name]['start'] = $this->microtime_float();
		return true;
	}

	/**
	 * Метод выполняет завершение отсчёта времени и возврат итогового времени,
	 * а также, если заполнена переменная $print_string,
	 * то её содержимое будет выведено на экран.
	 * 
	 * @param type $timer_name - название таймера, которое применяется
	 * для хранения метки времени, если указано несуществующее, или пустое,
	 * то вернёт false.
	 * @param type $print_string - строка, которую можно вывести
	 * после завершения отсчёта времени.
	 * Если в тексте написать %time%, то вместо этой маски будет выведено значние времени.
	 * return boolean[false]|float
	 */
	public function stop($timer_name = '', $print_string = '') {
		//Если такой таймер ещё не создан
		if (empty($timer_name) || !array_key_exists($timer_name, $this->arr_timer)) {
			return false;
		}

		$this->arr_timer[$timer_name]['end'] = $this->microtime_float();
		$this->arr_timer[$timer_name]['time'] = round($this->arr_timer[$timer_name]['end'] - $this->arr_timer[$timer_name]['start'], 4);


		if (!empty($print_string)) {
			print(str_replace('%time%', $this->arr_timer[$timer_name]['time'], $print_string));
		}

		return $this->arr_timer[$timer_name]['time'];
	}

	/**
	 * Возвращает текущее время в формате timestamp
	 * @return float
	 */
	public function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

}
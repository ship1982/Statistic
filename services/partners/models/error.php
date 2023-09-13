<?php

/**
 * Class for manage errors.
 */
class ErrorHandler
{
	/**
	 * @var array $codes - contain codes of error
	 */
	public $codes = [];

	/**
	 * Include lang file for error.
	 *
	 * @return array
	 */
	function getLang()
	{
		if(file_exists(__DIR__ . '/../lang/ru.php'))
		{
			$lang = require(__DIR__ . '/../lang/ru.php');
			return $lang;
		}
	}

	/**
	 * Set code to @see $this->$codes
	 * 
	 * @param int $code - code of error.
	 * @param void
	 */
	function setCode($code = 0)
	{
		if(!empty($code))
			$this->codes[$code] = $code;
	}

	/**
	 * Return array with code message.
	 *
	 * @return array
	 */
	function show()
	{
		$result = [];
		if(!empty($this->codes))
		{
			$lang = $this->getLang();
			if(!empty($lang))
			{
				foreach ($this->codes as $code => $value)
				{
					if(!empty($lang[$code]))
						$result[$code] = $lang[$code];
				}
			}
		}

		return $result;
	}
}
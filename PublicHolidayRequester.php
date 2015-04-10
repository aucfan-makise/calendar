<?php
// 	http://calendar-service.net/cal?start_year=2010&start_mon=1&end_year=&end_mon=&year_style=normal&month_style=numeric&wday_style=none&format=xml&holiday_only=1
class PublicHolidayRequester{
	const SERVER = "http://calendar-service.net/cal";
	const FIXED_OPTION = "year_style=normal&month_style=numeric&wday_style=none&format=xml&holiday_only=1";
	const YEAR_OPTION_NAME = "start_year";
	const MONTH_OPTION_NAME = "start_mon";
	protected $year;
	protected $month;
	protected $holidayData;
	
	public function __construct($year, $month){
		$this->year = $year;
		$this->month = $month;
		$yearOption = self::YEAR_OPTION_NAME.'='.$year.'';
		$monthOption = self::MONTH_OPTION_NAME.'='.$month.'';
		$url = self::SERVER."?&".$yearOption."&".$monthOption."&".self::FIXED_OPTION;
		try {
			$res = simplexml_load_file($url);

			foreach ($res->response->month->mday as $array)
				$this->holidayData[(string) $array->attributes()->mday] = (string) $array->attributes()->holiday_name;
		} catch (Exception $e){
			$holidayData = array();
			throw new RequesterException();
		}
	}
	
	public function isHoliday($day){
		if($this->holidayData == null) return false;
		
		array_key_exists($day, $this->holidayData);
		return ($day > 0 && array_key_exists($day, $this->holidayData)) ? true : false;
	}
	
	public function getHolidayName($day){
		return $this->holidayData[$day];
	}
}
?>
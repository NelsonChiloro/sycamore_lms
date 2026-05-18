<?php

function checkWeekendOrHoliday($dt){

	$dt1 = strtotime($dt);
	$dt2 = date("l", $dt1);
	$dt3 = strtolower($dt2);
	if(($dt3 == "saturday" ))
	{
		return 'saturday';
	}elseif (($dt3 == "sunday")){
		return 'sunday';
	}
	else
	{
		return  'not weekend';
	}
}



?>

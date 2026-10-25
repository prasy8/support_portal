<?php

//======== Class to convert date time =========//
class siteDateTime
{
	function convertDateTime($date_time,$withTime)
	{
		$date_time_p = $date_time;
		$date_time = $date_time."00000000000000000";
		$year = substr($date_time,0,4);
		$month = substr($date_time,5,2);
		$date  = substr($date_time,8,2);
		$hour = substr($date_time,11,2);
		$minute = substr($date_time,14,2);
		$second = substr($date_time,17,2);
		
		if(!empty($date_time_p))
		{
			if($withTime == "yes")
			{
				//return $month."/".$date."/".$year;
				return date("d M, Y h:i A",mktime($hour,$minute,$second,$month,$date,$year));
			}
			else
			{
				//return $month."/".$date."/".$year;
				return date("d M, Y",mktime($hour,$minute,$second,$month,$date,$year));
			}
		}
		else
		{
			return 'N/A';
		}	
	}


	function formatDate($date_time,$withTime)
	{
		if(!empty($date_time))
		{
			if($withTime=="yes")
			{
				$date = date("M d, Y H:i:s", strtotime($date_time));
				return $date;
			}
			else
			{
				$date = date("M d, Y", strtotime($date_time));
				return $date;
			}
		}
		else
		{
			return "N/A";
		}
	}

	function formatDateMonth($date_time,$withTime)
	{
		if(!empty($date_time))
		{
			if($withTime=="yes")
			{
				$date = date("M, Y H:i:s", strtotime($date_time));
				return $date;
			}
			else
			{
				$date = date("M, Y", strtotime($date_time));
				return $date;
			}
		}
		else
		{
			return "N/A";
		}
	}

}

?>

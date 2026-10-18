<?php
$filename=""; 


if(!empty($_REQUEST['p']))
{
    if(file_exists("includes/files/".$_REQUEST['p'].".php"))
	{
		$filename="includes/files/".$_REQUEST['p'].".php";

		
	}
}

if(!empty($filename))
{
	include($filename);
}
else
{

    header("location: index".$_SESSION[SITE]['EXT']."?p=dashboard");
}

?>
<?php 
include("includes/config/config.php"); 

if(!empty($_REQUEST['logout']) && $_REQUEST['logout']=='true')
{
	$_SESSION[SITE]['user_id']		= '';
	$_SESSION[SITE]['tenant_id'] 	= '';
	$_SESSION[SITE]['user_name'] 	= '';
	$_SESSION[SITE]['user_role'] 	= '';
}

if(empty($_SESSION[SITE]['user_id']))
{
	session_destroy();
	header("location: login".$_SESSION[SITE]['EXT']);
	exit;
}
  //$_SESSION['user_id'],
//    $_SESSION['tenant_id']
//print_r($_SESSION);
?>
<?php

$qry = "SELECT DATABASE() AS database_name";
$result = mysqli_query($conn,$qry);
$row = mysqli_fetch_assoc($result);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?php echo $_SESSION[SITE]['SITE_NAME'];?></title>
	<?php include("includes/meta/meta-head.php"); ?>
</head>
<body class="bg-light">
	<?php include("includes/files/default.php"); ?>

</body>
</html>

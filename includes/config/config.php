<?php
ob_start();
/*if(empty($_SESSION['PHPSESSID']))
{
	session_set_cookie_params([
	  'lifetime' => '1200',
	  'path' => '/',
	  'domain' => ".".$_SERVER['HTTP_HOST']."",
	  'secure' => 'true',
	  
	  'samesite' => 'strict',	
	  'httponly' => 'true'
	]);
	
}*/

session_start();

set_time_limit(600);
ini_set('max_execution_time', '600');
ini_set('display_errors', 1);
error_reporting(E_ALL);


header('Access-Control-Allow-Origin: https://'.$_SERVER['HTTP_HOST']);
header('Access-Control-Allow-Credentials:true');


// Common Variables
$FOLDER_NAME = "support_portal/";

define("SITE","ADMIN",false);
$_SESSION[SITE]['EXT'] = ".php";
$_SESSION[SITE]['DBPF'] = "sp_"; 
$_SESSION[SITE]['LIST_LIMIT'] = 10; 
$_SESSION[SITE]['SUPPORT_EMAIL'] = "prashant@prashantech.com"; 
$_SESSION[SITE]['ADMIN_EMAIL'] = "prashant@prashantech.com"; 

$_SESSION[SITE]['SITE_NAME'] = "Support Portal 1.0";
$protocol = 'http';
$_SESSION[SITE]['HOST'] = $protocol."://".$_SERVER['HTTP_HOST']."/".$FOLDER_NAME;
$_SESSION[SITE]['SRC']=$_SESSION[SITE]['HOST'];
$_SESSION[SITE]['ROOT'] = rtrim($_SERVER['DOCUMENT_ROOT'],'/')."/".rtrim($FOLDER_NAME,'/')."/";

$_SESSION[SITE]['INCLUDE']=$_SESSION[SITE]['ROOT'];

$_SESSION[SITE]['MAILER'] = "";

//====LOGO
$logo = '<img src="assets/img/logo.png?v=2.0" alt="logo" class="max-h-100px"><p>'.$_SESSION[SITE]['SITE_NAME'].'</p>';
//==== Database Variables
$DATABASE_HOST_NAME = "localhost"; 
$DATABASE_USER_NAME = "root"; 
$DATABASE_PASSWORD = "";
$DATABASE_NAME = "db_support_portal";


//==== Connecting with Databse Server
$conn = mysqli_connect($DATABASE_HOST_NAME, $DATABASE_USER_NAME, $DATABASE_PASSWORD, $DATABASE_NAME);
//if($conn) { echo "success"; } else { echo mysqli_connect_error(); }
mysqli_set_charset($conn,"utf8mb4");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Classes
/*include($_SESSION[SITE]['ROOT']."includes/classes/class-date-time.php");
include($_SESSION[SITE]['ROOT']."includes/classes/class-string.php");

include($_SESSION[SITE]['ROOT']."includes/files/functions.php");*/

ini_set( 'date.timezone', 'Asia/Kolkata');
date_default_timezone_set('Asia/Kolkata');

?>
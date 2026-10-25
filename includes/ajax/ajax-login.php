<?php
include('../config/config.php');
header('Content-Type: application/json; charset=UTF-8');
$success = false;
$message='Something went wrong. Please try later.';
$error_count=0;

//$method = $_SERVER["REQUEST_METHOD"];
$action = $_REQUEST['action'];
// ========================================
// Read JSON request
// ========================================

$input = json_decode(file_get_contents("php://input"),true);

/*echo json_encode([
            "success" => false,
            "message" => $message
        ]);*/

// ========================================
// ACTION - register
// ========================================
if ($action === 'register') {
	
    $csrf_token	= isset($input["csrf_token"]) ? trim((string)$input["csrf_token"]) : "";
    $business_name	= isset($input["business_name"]) ? trim((string)$input["business_name"]) : "";
    $name 		= isset($input["name"]) ? trim((string)$input["name"]) : "";
    $email 		= isset($input["email"]) ? strtolower(trim((string)$input["email"])) : "";
    $password 	= isset($input["password"]) ? (string)$input["password"] : "";

     //validation 

	if(!hash_equals($_SESSION['csrf_token'], $csrf_token) && $error_count==0)
	{
		$message='Invalid form request. Please refresh and try again.';
		$error_count++;
	}
	
	if($business_name === '' && $error_count==0)
	{
		$message='Please enter your business name.';
		$error_count++;
	}
	
	if($name === '' && $error_count==0)
	{
		$message='Please enter your name.';
		$error_count++;
	}
   

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $error_count==0) {
		$message='Please enter a valid email address.';
		$error_count++;
       
    }
	
/*	if(!empty($email) && $error_count==0){
		$query="select * from ".$_SESSION[SITE]['DBPF']."users where email='".$email."' LIMIT 1";
		$result=mysqli_query($conn,$query);
		if(mysqli_num_rows($result)>0)
		{
			$message='Email is already registered.';
			$error_count++;

		}
	}*/
	
    if (strlen($password) < 8 && $error_count==0) {
		$message='Password must contain at least 8 characters.';
		$error_count++;
        
    }
	
	
	if($error_count==0)
	{
		$slug = strtolower(trim($business_name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
        	$slug = 'business';
        }
        $slug .= '-' . bin2hex(random_bytes(4));
        
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);

		try {

			//Start a database transaction
			mysqli_begin_transaction($conn);

			//Insert the business into tenants table
			$sql = "INSERT INTO ".$_SESSION[SITE]['DBPF']."tenants (business_name, slug, status)
					VALUES (?, ?, 'active')";
			$stmt = mysqli_prepare($conn, $sql);
			mysqli_stmt_bind_param($stmt, 'ss', $business_name, $slug);
			mysqli_stmt_execute($stmt);

			//Get the newly created tenant ID
			$tenantId = mysqli_insert_id($conn);
			mysqli_stmt_close($stmt);

			//Insert the business owner into users table
			$sql = "INSERT INTO ".$_SESSION[SITE]['DBPF']."users (tenant_id, name, email, password_hash, role, status)
					VALUES (?, ?, ?, ?, 'owner', 'active')";
			$stmt = mysqli_prepare($conn, $sql);
			mysqli_stmt_bind_param($stmt, 'isss', $tenantId, $name, $email, $passwordHash);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);

			//Save both database operations
			mysqli_commit($conn);

			//Redirect to login page
			$success = true;
			$message = "Registration successful. You can now log in.";
		} 
		catch (mysqli_sql_exception $e) {
			$error_count++;
			//Undo database changes if an error occurs
			mysqli_rollback($conn);

			// Log the actual error for the developer
			error_log('Registration error: ' . $e->getMessage());
			$data = $e->getMessage();
			// Display a safe message to the user
			
			$success = false;
			$message = "Registration failed. Check whether this email is already registered for this business, then try again.";	
		}
        
	}
    
	echo json_encode([
				"success" => $success,
				"message" => $message
			]);	
    
 
}

// ========================================
// ACTION - login
// ========================================
if ($action === 'login') {
	
    $csrf_token	= isset($input["csrf_token"]) ? trim((string)$input["csrf_token"]) : "";
    $slug 		= isset($input["business_slug"]) ? strtolower(trim((string)$input["business_slug"])) : "";
    $email 		= isset($input["email"]) ? strtolower(trim((string)$input["email"])) : "";
    $password 	= isset($input["password"]) ? (string)$input["password"] : "";

     //validation 

	if(!hash_equals($_SESSION['csrf_token'], $csrf_token) && $error_count==0)
	{
		$message='Invalid form request. Please refresh and try again.';
		$error_count++;
	}
	if ($slug == '' && $error_count==0 ) {
        $message='Please enter business slug.';
		$error_count++;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $error_count==0) {
		$message='Please enter a valid email address.';
		$error_count++;
       
    }
	
/*	if(!empty($email) && $error_count==0){
		$query="select * from ".$_SESSION[SITE]['DBPF']."users where email='".$email."' LIMIT 1";
		$result=mysqli_query($conn,$query);
		if(mysqli_num_rows($result)==0)
		{
			$message='Email is not registered.';
			$error_count++;

		}
	}*/
	
    if (strlen($password) < 8 && $error_count==0) {
		$message='Password must contain at least 8 characters.';
		$error_count++;
        
    }
	
	
	if($error_count==0)
	{
		$query="SELECT u.user_id, u.tenant_id, u.name, u.email,
						u.password_hash, u.role
				 FROM ".$_SESSION[SITE]['DBPF']."users u
				 INNER JOIN ".$_SESSION[SITE]['DBPF']."tenants t ON t.tenant_id = u.tenant_id
				 WHERE t.slug = '".$slug."' AND u.email = '".$email."' 
				   AND u.status = 'active'
				   AND t.status = 'active'
				 LIMIT 2";
		$result=mysqli_query($conn,$query);
		// Email addresses may exist in different tenants.
		// Do not guess which account the user intended.
		if(mysqli_num_rows($result)==1)
		{
			
			$user = mysqli_fetch_assoc($result);
			if(password_verify($password, $user['password_hash'])) 
			{
				// replaces the current session ID with a new random one while preserving all existing session data, 
				// and immediately deletes the old session file/data from the server.	
				session_regenerate_id(true);

				$_SESSION[SITE]['user_id'] 	= (int) $user['user_id'];
				$_SESSION[SITE]['tenant_id']= (int) $user['tenant_id'];
				$_SESSION[SITE]['user_name']= $user['name'];
				$_SESSION[SITE]['user_role']= $user['role'];

				$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

				
				$success = true;
				$message = "Login successful.";
			}
		}
		else{
			$success = false;
			$message='Invalid Email or Password';	
			$error_count++;
		}

        
	}
    
	echo json_encode([
				"success" => $success,
				"message" => $message,
				"data" => json_encode($query)
			]);	
    
 
}

?>

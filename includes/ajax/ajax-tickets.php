<?php
include('../config/config.php');
header('Content-Type: application/json; charset=UTF-8');
$success = false;
$message = 'Unable to create ticket. Please try again.';
$data = [];
$error_count=0;



// ========================================
// Read JSON request
// ========================================

$input = json_decode(file_get_contents("php://input"),true);
if (!is_array($input)) {
    $input = [];
}

$action = '';
if(isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
} 
elseif(isset($input['action'])) {
    $action = $input['action'];
} 

$csrf_token	= isset($input["csrf_token"]) ? trim((string)$input["csrf_token"]) : "";
$tenantId 	= (int)$_SESSION[SITE]['tenant_id'];
$userId 	= (int)$_SESSION[SITE]['user_id'];

$ticketTable  = $_SESSION[SITE]['DBPF'] . 'tickets';
$messageTable = $_SESSION[SITE]['DBPF'] . 'ticket_messages';
$eventTable   = $_SESSION[SITE]['DBPF'] . 'ticket_events';
// ========================================
// ACTION - create
// ========================================
if ($action === 'create') {
	
    $department_id	= isset($input["department_id"]) ? (int)$input["department_id"] : null;
    $category_id	= isset($input["category_id"]) ? (int)$input["category_id"] : null;
    $customer_name	= isset($input["customer_name"]) ? trim((string)$input["customer_name"]) : "";
    $description 	= isset($input["description"]) ? trim((string)$input["description"]) : "";
    $email 			= isset($input["customer_email"]) ? strtolower(trim((string)$input["customer_email"])) : "";
    $priority 		= isset($input["priority"]) ? (string)$input["priority"] : "";
    $subject 		= isset($input["subject"]) ? (string)$input["subject"] : "";

	
	
	$allowedPriorities = [
		'low',
		'normal',
		'high',
		'urgent'
	];
     //validation 

	if(!hash_equals($_SESSION['csrf_token'], $csrf_token) && $error_count==0)
	{
		$message='Invalid form request. Please refresh and try again.';
		$error_count++;
	}
	
	if($customer_name === '' && $error_count==0)
	{
		$message='Please enter your name.';
		$error_count++;
	}
   

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $error_count==0) {
		$message='Please enter a valid email address.';
		$error_count++;
       
    }
	if($subject === '' && $error_count==0)
	{
		$message='Enter a valid ticket subject.';
		$error_count++;
	}
	if($description === '' && $error_count==0)
	{
		$message='Enter the ticket description.';
		$error_count++;
	}

	if (!in_array($priority, $allowedPriorities, true)) {
		$message='Select a valid priority.';
		$error_count++;
		
	}
	
	//$department_id $category_id
	/* Table names from server-side configuration */
	$customerTable = $_SESSION[SITE]['DBPF'] . 'customers';
	$departmentTable = $_SESSION[SITE]['DBPF'] . 'departments';
	$categoryTable = $_SESSION[SITE]['DBPF'] . 'ticket_categories';
	
	if($error_count==0)
	{
		//$success = true;
		
		try {

			/* Verify the selected department belongs to this tenant */
			if ($department_id !== null) {

				$query = "SELECT dept_id FROM $departmentTable WHERE dept_id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1";
				$stmt = mysqli_prepare($conn, $query);
				mysqli_stmt_bind_param($stmt,'ii',$department_id,$tenantId);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$department = mysqli_fetch_assoc($result);
				mysqli_stmt_close($stmt);

				if (!$department) {
					$success = false;
					$message='The selected department is not available.';
					echo jsonResponse($success, $message);
					
				}
			}

			/* Verify the selected category belongs to this tenant */
			if ($category_id !== null) {

				$query = "SELECT id FROM $categoryTable WHERE id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1";
				$stmt = mysqli_prepare($conn, $query);
				mysqli_stmt_bind_param($stmt,'ii',$category_id,$tenantId);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$category = mysqli_fetch_assoc($result);
				mysqli_stmt_close($stmt);

				if (!$category) {
					$success = false;
					$message='The selected category is not available.';
					echo jsonResponse($success, $message);
					
				}
			}
			
			mysqli_begin_transaction($conn);

			/*
			 * Find the customer within this tenant.
			 * The same email may exist in another tenant.
			 */
			$query = "SELECT customer_id FROM $customerTable WHERE tenant_id = ? AND email = ? LIMIT 1";
			$stmt = mysqli_prepare($conn, $query);
			mysqli_stmt_bind_param($stmt,'is',$tenantId,$email);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);
			$customer = mysqli_fetch_assoc($result);
			mysqli_stmt_close($stmt);

			if ($customer) {
				$customerId = (int)$customer['customer_id'];
				/* Refresh the customer's name */
				$query = "UPDATE $customerTable SET name = ? WHERE customer_id = ? AND tenant_id = ?";
				$stmt = mysqli_prepare($conn, $query);
				mysqli_stmt_bind_param($stmt,'sii',$customer_name,$customerId,$tenantId);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);

			} 
			else {

				$query = "INSERT INTO $customerTable (tenant_id, name, email) VALUES (?, ?, ?)";
				$stmt = mysqli_prepare($conn, $query);
				mysqli_stmt_bind_param($stmt,'iss',$tenantId,$customer_name,$email);
				mysqli_stmt_execute($stmt);
				$customerId = (int)mysqli_insert_id($conn);
				mysqli_stmt_close($stmt);
			}
			//echo jsonResponse(true, 'testing message3',['custID'=>$customerId]);
			/*
			 * Create ticket.
			 * ticket_id is the ticket reference for now.
			 */
			$query1 = "INSERT INTO $ticketTable (tenant_id, customer_id, department_id, category_id, assigned_user_id, subject, description, priority, preferred_language, input_mode, status)
					  VALUES (".$tenantId.", ".$customerId.", ".$department_id.", ".$category_id.", NULL, '".$subject."', '".$description."', '".$priority."', 'en', 'text', 'open')";
			$query = "INSERT INTO $ticketTable (tenant_id, customer_id, department_id, category_id, assigned_user_id, subject, description, priority, preferred_language, input_mode, status)
					  VALUES (?, ?, ?, ?, NULL, ?, ?, ?, 'en', 'text', 'open')";
			//echo jsonResponse(true, 'testing message3',[$query1]);
			$stmt = mysqli_prepare($conn, $query);
			mysqli_stmt_bind_param($stmt,'iiiisss',$tenantId,$customerId,$department_id,$category_id,$subject,$description,$priority);
			mysqli_stmt_execute($stmt);
			$ticketId = (int)mysqli_insert_id($conn);
			mysqli_stmt_close($stmt);

			/*
			 * Add the initial message.
			 * The ticket is created on behalf of the customer,
			 * so this first message is recorded as a customer message.
			 */
			$senderType = 'customer';
			$isInternal = 0;

			$query2 = "INSERT INTO $messageTable (tenant_id, ticket_id, sender_type, sender_id, message, is_internal)
					  VALUES (".$tenantId.", ".$ticketId.", '".$senderType."', ".$customerId.", '".$description."', ".$isInternal.")";
			$query = "INSERT INTO $messageTable (tenant_id, ticket_id, sender_type, sender_id, message, is_internal)
					  VALUES (?, ?, ?, ?, ?, ?)";
			//echo jsonResponse(true, 'testing message3',[$query2]);
			$stmt = mysqli_prepare($conn, $query);
			mysqli_stmt_bind_param($stmt,'iisisi',$tenantId,$ticketId,$senderType,$customerId,$description,$isInternal);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			//echo jsonResponse(true, 'testing message3',[$query2]);	
			/* Record the creation event */
			$eventType = 'ticket_created';
			//$eventDetails = 'eventDetails';
			$eventDetails = json_encode(['subject' => $subject,	'priority' => $priority, 'created_by' => $userId]);

			$query3 = "INSERT INTO $eventTable (tenant_id, ticket_id, actor_user_id, event_type, event_details)
					  VALUES (".$tenantId.", ".$ticketId.", ".$userId.", '".$eventType."', '".$eventDetails."')";
			//echo jsonResponse(true, 'testing message',[$query3]);
			$query = "INSERT INTO $eventTable (tenant_id, ticket_id, actor_user_id, event_type, event_details)
					  VALUES (?, ?, ?, ?, ?)";
			
			
			$stmt = mysqli_prepare($conn, $query);
			mysqli_stmt_bind_param($stmt,'iiiss',$tenantId,$ticketId,$userId,$eventType,$eventDetails);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);

			mysqli_commit($conn);

			echo jsonResponse(true, 'Ticket created successfully.', ['ticket_id' => $ticketId]);

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
			$message = "Unable to create ticket. Please try again.";	
			echo jsonResponse($success, $message,[$data]);
		}
        
	}
    
		
    //echo jsonResponse($success, $message, $input);
 
}
elseif($action === 'reply') {
	//replyMessage: 'gfhfgh', csrf_token: 'e672490e6b3a7569e0899fafc20808590c16d5ec2844e2e48ceab41b1b845ae6', ticket_id: '37', action: 'reply', is_internal: 0
	$replyMessage 	= isset($input['replyMessage']) ? trim((string)$input['replyMessage']) : '';
	$ticket_id		= isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;
	$isInternal		= isset($input['is_internal']) && (string)$input['is_internal']=='1' ? 1 : 0;
	
	/* Only authenticated staff may reply or add notes */
    $role = '';
	if(!empty($_SESSION[SITE]['user_role'])){
		$role = $_SESSION[SITE]['user_role'];	
	}
	//validations
	if(!in_array($role,['owner','admin','agent'],true)){
		echo jsonResponse(false, 'You are not authorized to reply.');
	}
	
	if(!hash_equals($_SESSION['csrf_token'], $csrf_token) && $error_count==0)
	{
		echo jsonResponse(false, 'Invalid form request. Please refresh and try again.');
	}
	if($ticket_id<=0){
		echo jsonResponse(false, 'Invalid ticket.');
	}
	if($replyMessage==''){
		echo jsonResponse(false, 'Enter a message.');
	}
	
	/* Verify ticket belongs to this tenant */
	$query = "select * from $ticketTable where ticket_id=".$ticket_id." and tenant_id=".$tenantId." limit 1";
	$result = mysqli_query($conn,$query);
	$ticket = mysqli_fetch_assoc($result);
	if(!$ticket){
		echo jsonResponse(false, 'Ticket not found.');
	}
	if($ticket['status']=='closed'){
		echo jsonResponse(false, 'This ticket is closed. Reopen before replying.');
	}
	
	try{
		mysqli_begin_transaction($conn);
		//insert into ticket_messages
		$senderType = 'agent';
		$insertQry 	= "INSERT INTO $messageTable (`tenant_id`, `ticket_id`, `sender_type`, `sender_id`, `message`, `is_internal`) 
						VALUES (".$tenantId.",".$ticket_id.",'".$senderType."',".$userId.",'".$replyMessage."',".$isInternal.")";
		$result 	= mysqli_query($conn,$insertQry);
		$messageId 	= (int)mysqli_insert_id($conn);
		
		//get msgID and insert into ticket_events (ticket history)
		$event_type	= $isInternal ? 'internal_note_added' : 'agent_reply_added';
		$event_details = json_encode(['msg_id' => $messageId, 'created_by' => $userId]);
		$eventQry 	= "INSERT INTO $eventTable (`tenant_id`, `ticket_id`, `actor_user_id`, `event_type`, `event_details`) 
						VALUES (".$tenantId.",".$ticket_id.",".$userId.",'".$event_type."','".$event_details."')";
		$result 	= mysqli_query($conn,$eventQry);
		
		mysqli_commit($conn);
		
		echo jsonResponse(
						true,
						$isInternal ? 'Internal note added successfully.' : 'Reply added successfully.',
						[
							'message' => [
								'msg_id' => $messageId,
								'sender_type' => $senderType,
								'sender_id' => $userId,
								'message' => $replyMessage,
								'is_internal' => $isInternal,
								'created_at' => date('Y-m-d H:i:s')
							]
						]
					);
	}
	catch (mysqli_sql_exception $e) {
		//Undo database changes if an error occurs
		mysqli_rollback($conn);

		// Log the actual error for the developer
		error_log('Ticket reply error: ' . $e->getMessage());
		$data = $e->getMessage();
		// Display a safe message to the user
		echo jsonResponse(false, 'Unable to save your replied-message',[$data]);
	}
	
	
}
elseif($action === 'status'){
	$ticket_id	= isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;
	$new_status	= isset($input['status']) ? trim((string)$input['status']) : '';
	
	$role = '';
	if(!empty($_SESSION[SITE]['user_role'])){
		$role = $_SESSION[SITE]['user_role'];	
	}
	//validations
	if(!in_array($role,['owner','admin','agent'],true)){
		echo jsonResponse(false, 'You are not authorized to reply.');
	}
	
	if(!hash_equals($_SESSION['csrf_token'], $csrf_token) && $error_count==0)
	{
		echo jsonResponse(false, 'Invalid form request. Please refresh and try again.');
	}
	if($ticket_id<=0){
		echo jsonResponse(false, 'Invalid ticket.');
	}
	
	try{
		mysqli_begin_transaction($conn);
		
		/* Lock and verify the tenant's ticket */
        $query = "SELECT ticket_id, status FROM $ticketTable WHERE ticket_id = ".$ticket_id." AND tenant_id = ".$tenantId." LIMIT 1 FOR UPDATE";
		$result= mysqli_query($conn,$query);
		$ticket= mysqli_fetch_assoc($result);
		if(!$ticket){
			mysqli_rollback($conn);
			echo jsonResponse(false, 'Ticket not found.');
		}
		//echo jsonResponse(false, 'Ticket found.',[$ticket]);
		$old_status = $ticket['status'];
		if($old_status == $new_status){
			echo jsonResponse(true, 'Ticket status is already '.$new_status.'.',['status'=>$new_status]);
		}
		
		$updateQry = "UPDATE $ticketTable SET status = '".$new_status."' WHERE ticket_id = ".$ticket_id." AND tenant_id = ".$tenantId."";
		mysqli_query($conn,$updateQry);
		
		/* Add an audit event (log) */
        $eventType = 'status_changed';
        $eventDetails = json_encode(['old_status' => $old_status,'new_status' => $new_status,'changed_by' => $userId]);
		$logQry = "INSERT INTO $eventTable (tenant_id, ticket_id, actor_user_id, event_type, event_details)
					  VALUES (".$tenantId.", ".$ticket_id.", ".$userId.", '".$eventType."', '".$eventDetails."')";
		mysqli_query($conn,$logQry);
		
		mysqli_commit($conn);
		
		echo jsonResponse(
						true,
						'Ticket status updated successfully.',
						[
							'old_status' => $old_status,
							'new_status' => $new_status
							
						]
					);
	}
	catch (mysqli_sql_exception $e) {
		//Undo database changes if an error occurs
		mysqli_rollback($conn);

		// Log the actual error for the developer
		error_log('Ticket status update error: ' . $e->getMessage());
		$data = $e->getMessage();
		// Display a safe message to the user
		echo jsonResponse(false, 'Unable to update ticket status',[$data]);
	}
	
}
else{
	echo jsonResponse(false, 'Invalid action.');
}

?>
<?php
include("includes/config/config.php"); 

if(!empty($_SESSION[SITE]['user_id']))
{
	header("location: index".$_SESSION[SITE]['EXT']);
	exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Customer Support Portal</title>
	<?php include("includes/meta/meta-head.php"); ?>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:450px">
        <div class="card-body p-4">
            <h1 class="h3 mb-4">Business login</h1>
        
			<div id="message"></div>
            <form id="loginForm" name="loginForm" method="post">
				<div class="mb-3">
					<label for="business_slug" class="form-label">Business ID / Slug</label>
					<input type="text" id="business_slug" name="business_slug" class="form-control" placeholder="e.g. business-a" required>
				</div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
				<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');?>">
                <button class="btn btn-primary w-100" type="submit">Log in</button>
            </form>

            <p class="mt-3 mb-0">
                New business?
                <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
<script>
const AUTH_API_URL = "includes/ajax/ajax-login.php";
	
document.getElementById('loginForm').addEventListener('submit', async function (event) {

    event.preventDefault();
	
    const password = document.getElementById('password').value;
    //const confirmPassword = document.getElementById('confirmPassword').value;

    /*if (password !== confirmPassword) {
        showMessage('Passwords do not match.', 'danger');
        return;
    }
*/
	const response = await fetch(AUTH_API_URL+ "?action=login", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
					csrf_token: 	document.getElementById('csrf_token').value,
					business_slug: 	document.getElementById('business_slug').value,
					email: 			document.getElementById('email').value,
                    password: 		document.getElementById('password').value

                })

            });


    const result = await response.json();
  
	//alert(result.data);
	//alert(result.message);
    showMessage(result.message, result.success ? 'success' : 'danger');
	
    if(result.success) {
        document.getElementById('loginForm').reset();
        setTimeout(() => window.location.href = 'index.php', 1200);
    }
	
});
</script>
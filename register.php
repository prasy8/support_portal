<?php
include("includes/config/config.php"); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Business</title>
	<?php include("includes/meta/meta-head.php"); ?>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:520px">
        <div class="card-body p-4">
            <h1 class="h3 mb-4">Create your business account</h1>
			<div id="message"></div>
            <form id="registerForm" name="registerForm" method="post">
                
                <div class="mb-3">
                    <label class="form-label">Business name</label>
                    <input class="form-control" name="business_name" id="business_name" maxlength="150" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Your name</label>
                    <input class="form-control" name="name" id="name" maxlength="100" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" maxlength="190" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" minlength="8" required>
                    <div class="form-text">Use at least 8 characters.</div>
                </div>
				<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');?>">
                <button class="btn btn-primary w-100" type="submit"> Register business </button>
            </form>

            <p class="mt-3 mb-0">
                Already registered?
                <a href="login.php">Log in</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
<script>
const AUTH_API_URL = "includes/ajax/ajax-login.php";
	
document.getElementById('registerForm').addEventListener('submit', async function (event) {
	//alert(document.getElementById('name').value);
    event.preventDefault();
	
    const password = document.getElementById('password').value;

	const response = await fetch(AUTH_API_URL+ "?action=register", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
					csrf_token: 	document.getElementById('csrf_token').value,
					business_name: 	document.getElementById('business_name').value,
					name: 			document.getElementById('name').value,
                    email: 			document.getElementById('email').value,
                    password: 		document.getElementById('password').value

                })

            });


    const result = await response.json();
  
	//alert(result.data);
    showMessage(result.message, result.success ? 'success' : 'danger');
	
    if(result.success) {
        document.getElementById('registerForm').reset();
        setTimeout(() => window.location.href = 'login.php', 1200);
    }
	
});
</script>
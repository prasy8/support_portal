<?php
$tenantId = (int)$_SESSION[SITE]['tenant_id'];

$departmentTable = $_SESSION[SITE]['DBPF'] . "departments";
$categoryTable = $_SESSION[SITE]['DBPF'] . "ticket_categories";

/* Fetch active departments */
$departments = [];
$query = "SELECT dept_id, name FROM $departmentTable WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $tenantId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}
mysqli_stmt_close($stmt);

/* Fetch active categories */
$categories = [];
$query = "SELECT id, category_name FROM $categoryTable WHERE tenant_id = ? AND is_active = 1 ORDER BY category_name ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $tenantId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
mysqli_stmt_close($stmt);

$obj = new singleQuoteRemove();



?>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Ticket</h1>
            <p class="text-muted mb-0">
                Create a support ticket on behalf of a customer.
            </p>
        </div>

        <a href="index.php?p=tickets" class="btn btn-outline-secondary">Back to Tickets</a>
    </div>

    <div id="message"></div>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <form id="ticketForm">
               
                <h2 class="h5 mb-3">Customer Details</h2>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" maxlength="150" required>
                    </div>

                    <div class="col-md-6">
                        <label for="customer_email" class="form-label">Customer Email</label>
                        <input type="email" class="form-control" id="customer_email" name="customer_email" maxlength="190" required>
                    </div>

                </div>

                <hr class="my-4">

                <h2 class="h5 mb-3">Ticket Details</h2>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                </div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority"required>
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">No department</option>
                            <?php foreach ($departments as $department) { ?>
                                <option value="<?php echo $department['dept_id']; ?>"><?php echo $department['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">No category</option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                </div>
				<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $obj->escapeSecurityHelper($_SESSION['csrf_token']);?>">
                <div class="mt-4">
                    <button type="submit" id="submitTicket" class="btn btn-primary">Create Ticket</button>
                    <a href="tickets.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
const TICKET_API_URL = "includes/ajax/ajax-tickets.php?action=create";

document.getElementById("ticketForm").addEventListener("submit", async function (event) {
        event.preventDefault();

        const form = this;
        const submitButton = document.getElementById("submitTicket");

        submitButton.disabled = true;
        submitButton.textContent = "Creating...";

        const formData = new FormData(form);

        const payload = Object.fromEntries(formData.entries());
		console.log(payload);
        try {
            const response = await fetch(TICKET_API_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            });
			
			const result = await response.json();
			console.log(result);
            if (!response.ok || !result.success) {
                showMessage(result.message || "Unable to create ticket.", "danger");
                return;
            }

            showMessage(result.message, "success");

            window.location.href = "index.php?p=tickets";

        } 
		catch (error) {
            showMessage("A network or server error occurred. Please try again.", "danger");
        } 
		finally {
            submitButton.disabled = false;
            submitButton.textContent = "Create Ticket";
        }
    });
</script>
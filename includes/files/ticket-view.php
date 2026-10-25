<?php
/*
 * ticket-view.php
 * Assumes index.php loads config and authentication.
 */

$tenantId = (int) $_SESSION[SITE]['tenant_id'];
$userId   = (int) $_SESSION[SITE]['user_id'];

$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$ticketTable   = $_SESSION[SITE]['DBPF'] . 'tickets';
$customerTable = $_SESSION[SITE]['DBPF'] . 'customers';
$messageTable  = $_SESSION[SITE]['DBPF'] . 'ticket_messages';
$departmentTable = $_SESSION[SITE]['DBPF'] . 'departments';
$categoryTable = $_SESSION[SITE]['DBPF'] . 'ticket_categories';
$eventTable    = $_SESSION[SITE]['DBPF'] . 'ticket_events';

$ticket = null;
$messages = [];

/* Fetch ticket, scoped to the logged-in tenant */
$query = "SELECT t.*, c.name AS customer_name, c.email AS customer_email
          FROM $ticketTable AS t
          INNER JOIN $customerTable AS c
            ON c.customer_id = t.customer_id AND c.tenant_id = t.tenant_id
          WHERE t.ticket_id = ? AND t.tenant_id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $ticketId, $tenantId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($result);
//print_r($ticket);
mysqli_stmt_close($stmt);

if (!$ticket) {
    echo '<div class="alert alert-danger">Ticket not found or you do not have access.</div>';
    return;
}

/* Fetch conversation messages */
$query = "SELECT msg_id, sender_type, sender_id, message, is_internal, created_at
          FROM $messageTable
          WHERE ticket_id = ? AND tenant_id = ? ORDER BY msg_id ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $ticketId, $tenantId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}
mysqli_stmt_close($stmt);


$obj = new singleQuoteRemove();

$allowedStatuses = [
    'open',
    'in_progress',
    'waiting_customer',
    'resolved',
    'closed'
];

$statusLabels = [
    'open'             => 'Open',
    'in_progress'      => 'In Progress',
    'waiting_customer' => 'Waiting for Customer',
    'resolved'         => 'Resolved',
    'closed'           => 'Closed'
];

$currentStatus = $ticket['status'];
?>

<div class="container-fluid py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <a href="index.php?p=tickets"
               class="text-decoration-none">
                &larr; Back to Tickets
            </a>
            <h3 class="mt-2 mb-1">
                Ticket #<?php echo $ticket['ticket_id']; ?>
            </h3>

            <div class="text-muted">
                <?php echo $ticket['subject']; ?>
            </div>
        </div>

        <span class="badge bg-primary fs-6" id="ticketStatusBadge"><?php echo $currentStatus;?></span>

    </div>

    <div id="ticketMessageBox" aria-live="polite"></div>

    <div class="row g-4">

        <!-- Ticket information -->
        <div class="col-lg-4">

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    Ticket Information
                </div>

                <div class="card-body">

                    <p>
                        <strong>Customer:</strong><br>
                        <?php echo $obj->escapeSecurityHelper($ticket['customer_name']);?>
                    </p>

                    <p>
                        <strong>Email:</strong><br>
                        <?php echo $obj->escapeSecurityHelper($ticket['customer_email']);?>
                    </p>

                    <p>
                        <strong>Priority:</strong><br>
                        <?php echo $obj->escapeSecurityHelper(ucfirst($ticket['priority']));?>
                    </p>

                    <p>
                        <strong>Department ID:</strong><br>
                        <?php echo $ticket['department_id']; ?>
                    </p>

                    <p>
                        <strong>Category ID:</strong><br>
                        <?php echo $ticket['category_id']; ?>
                    </p>

                    <p>
                        <strong>Assigned Agent ID:</strong><br>
                        <?php echo $ticket['assigned_user_id'] !== null ? (int) $ticket['assigned_user_id'] : 'Unassigned';?>
                    </p>

                    <hr>

                    <label for="ticketStatus" class="form-label fw-semibold">Update Status</label>

                    <select class="form-select mb-3" id="ticketStatus">

                        <?php foreach ($statusLabels as $value => $label) { ?>
                            <option value="<?php echo $value; ?>" <?php echo $currentStatus === $value ? 'selected' : '';?>><?php echo $label; ?></option>
                        <?php } ?>

                    </select>

                    <button type="button" class="btn btn-primary w-100" id="updateTicketStatus">Update Status</button>

                </div>
            </div>

        </div>

        <!-- Conversation -->
        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header fw-semibold">
                    Ticket Conversation
                </div>

                <div class="card-body">

                    <div id="ticketConversation">

                        <?php if (empty($messages)) { ?>

                            <div class="text-muted text-center py-4">
                                No conversation messages found.
                            </div>

                        <?php } else { ?>

                            <?php foreach ($messages as $message) {

                                $isInternal = (int) $message['is_internal'] === 1;
                                $senderType = $message['sender_type'];

                                if ($isInternal) {
                                    $heading = 'Internal Note';
                                } 
								elseif ($senderType === 'customer') {
                                    $heading = 'Customer';
                                } 
								elseif ($senderType === 'agent') {
                                    $heading = 'Agent #' . (int) $message['sender_id'];
                                } 
								else {
                                    $heading = ucfirst($senderType);
                                }

                                $messageClass = $isInternal ? 'border-warning bg-warning-subtle' : 'border-light bg-light';
                            ?>

                                <div class="card mb-3
                                    <?php echo $messageClass; ?>">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                            <strong><?php echo $obj->escapeSecurityHelper($heading);?></strong>
                                            <small class="text-muted"><?php echo $obj->escapeSecurityHelper($message['created_at']);?></small>
                                        </div>

                                        <?php if ($isInternal) { ?>
                                            <span class="badge bg-warning text-dark mb-2">
                                                Internal — staff only
                                            </span>
                                        <?php } ?>

                                        <div style="white-space: pre-wrap; overflow-wrap: anywhere;">
											<?php echo $obj->escapeSecurityHelper($message['message']);?>
										</div>

                                    </div>
                                </div>

                            <?php } ?>

                        <?php } ?>

                    </div>

                    <hr class="my-4">

                    <!-- Reply / internal note form -->
                    <form id="ticketReplyForm">

                        <label for="replyMessage" class="form-label fw-semibold">Write a Reply or Internal Note</label>

                        <textarea class="form-control mb-3" id="replyMessage" name="replyMessage" rows="5" maxlength="10000" placeholder="Type your message here..." required></textarea>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isInternal" name="is_internal" value="1">
                            <label class="form-check-label" for="isInternal">
                                Add as internal note (not visible to customer)
                            </label>
                        </div>
						<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $obj->escapeSecurityHelper($_SESSION['csrf_token']);?>">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">

                        <button type="submit" class="btn btn-success" id="sendTicketReply">Send Reply</button>

                    </form>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
const TICKET_VIEW_API = "includes/ajax/ajax-tickets.php";

const ticketReplyForm = document.getElementById("ticketReplyForm");
const replyMessage = document.getElementById("replyMessage");
const internalCheckbox = document.getElementById("isInternal");
const sendReplyButton = document.getElementById("sendTicketReply");
const ticketMessageBox = document.getElementById("ticketMessageBox");

function showTicketMessage(message, type) {
    ticketMessageBox.innerHTML = "";

    const alert = document.createElement("div");
    alert.className = "alert alert-" + type;
    alert.textContent = message;

    ticketMessageBox.appendChild(alert);
}

function escapeTicketHtml(value) {
    const element = document.createElement("span");
    element.textContent = String(value ?? "");
    return element.innerHTML;
}

function renderTicketMessage(message) {
    const internal = Number(message.is_internal) === 1;
    const senderType = message.sender_type;

    let heading = "System";

    if (internal) {
        heading = "Internal Note";
    } 
	else if (senderType === "customer") {
        heading = "Customer";
    } 
	else if (senderType === "agent") {
        heading = "Agent #" + message.sender_id;
    }

    const cardClass = internal ? "border-warning bg-warning-subtle" : "border-light bg-light";

    return `<div class="card mb-3 ${cardClass}">
			<div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <strong>${escapeTicketHtml(heading)}</strong>
                    <small class="text-muted">${escapeTicketHtml(message.created_at)}</small>
                </div>
                ${internal ? '<span class="badge bg-warning text-dark mb-2">Internal — staff only</span>' : ''}
                <div style="white-space:pre-wrap;overflow-wrap:anywhere">${escapeTicketHtml(message.message)}</div>
            </div>
        </div>`;
}

ticketReplyForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const message = replyMessage.value.trim();

    if (!message) {
        showTicketMessage("Please enter a message.", "warning");
        return;
    }

    sendReplyButton.disabled = true;
    sendReplyButton.textContent = "Sending...";

    const payload = Object.fromEntries(new FormData(ticketReplyForm).entries());

    payload.action = "reply";
    payload.is_internal = internalCheckbox.checked ? 1 : 0;

    try {
        const response = await fetch(TICKET_VIEW_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
		console.log(result);
        if (!response.ok || !result.success) {
            showTicketMessage(result.message || "Unable to send message.","danger");
            return;
        }

        const conversation = document.getElementById("ticketConversation");

        // Remove the initial empty-state message, if present.
        if (conversation.querySelector(".text-center")) {
            conversation.innerHTML = "";
        }

        conversation.insertAdjacentHTML("beforeend",renderTicketMessage(result.data.message));

        replyMessage.value = "";
        internalCheckbox.checked = false;

        showTicketMessage(result.message, "success");

    } 
	catch (error) {
        console.error(error);
        showTicketMessage("A network or server error occurred.", "danger");
    } 
	finally {
        sendReplyButton.disabled = false;
        sendReplyButton.textContent = "Send Reply";
    }
});

internalCheckbox.addEventListener("change", function () {
    sendReplyButton.textContent = this.checked ? "Add Internal Note" : "Send Reply";
});

document.getElementById("updateTicketStatus").addEventListener("click", async function () {
		alert('updateTicketStatus');
        const button = this;
        const statusSelect = document.getElementById("ticketStatus");
        const originalStatus = statusSelect.dataset.current || statusSelect.value;

        button.disabled = true;
        button.textContent = "Updating...";

        const csrfToken = ticketReplyForm.querySelector('[name="csrf_token"]').value;

        try {
            const response = await fetch(TICKET_VIEW_API, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "status",
                    ticket_id: <?php echo (int) $ticketId; ?>,
                    status: statusSelect.value,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();
			console.log(result);
            if (!response.ok || !result.success) {
                showTicketMessage(result.message || "Unable to update status.","danger");
                statusSelect.value = originalStatus;
                return;
            }

            statusSelect.dataset.current = statusSelect.value;

            const statusLabels = {
                open: "Open",
                in_progress: "In Progress",
                waiting_customer: "Waiting for Customer",
                resolved: "Resolved",
                closed: "Closed"
            };

            document.getElementById("ticketStatusBadge").textContent =  statusLabels[statusSelect.value] || statusSelect.value;

            showTicketMessage(result.message, "success");

        } 
		catch (error) {
            console.error(error);
            showTicketMessage("A network or server error occurred.", "danger");
            statusSelect.value = originalStatus;

        } 
		finally {
            button.disabled = false;
            button.textContent = "Update Status";
        }
    });

document.getElementById("ticketStatus").dataset.current = document.getElementById("ticketStatus").value;
</script>
<?php
$tenantId = (int)$_SESSION[SITE]['tenant_id'];
/*if($_POST){
	print_r($_POST);
}*/
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowedStatus = [
    'open',
    'in_progress',
    'waiting_customer',
    'resolved',
    'closed'
];

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$limit = (int)$_SESSION[SITE]['LIST_LIMIT'];
$offset = ($page - 1) * $limit;

$ticketTable = $_SESSION[SITE]['DBPF'] . "tickets";
$customerTable = $_SESSION[SITE]['DBPF'] . "customers";
$departmentTable = $_SESSION[SITE]['DBPF'] . "departments";
$categoryTable = $_SESSION[SITE]['DBPF'] . "ticket_categories";
$userTable = $_SESSION[SITE]['DBPF'] . "users";

// ========================================
// Build WHERE conditions
// ========================================
$where = [];
// Only logged-in business/tenant's tickets
$where[] = "t.tenant_id = ".$tenantId;

// ========================================
// Search
// ========================================

if($search !== "") {
	$where[] = "(CAST(t.ticket_id AS CHAR) LIKE '%".$search."%' OR c.name LIKE '%".$search."%' OR c.email LIKE '%".$search."%' OR t.subject LIKE '%".$search."%')";
}

if($status !== '') {
	$where[] = "t.status ='".$status."'";
}

// ========================================
// Build WHERE SQL
// ========================================

$whereSQL = "";
if (count($where) > 0) {
	$whereSQL = " WHERE " .implode(" AND ",$where);
}
/* Count tickets */
$countQuery = "
    SELECT COUNT(*) AS total
    FROM $ticketTable AS t
    INNER JOIN $customerTable AS c
        ON c.tenant_id = t.tenant_id
        AND c.customer_id = t.customer_id
    $whereSQL
";
echo $countQuery;
$countResult = mysqli_query($conn, $countQuery);
if ($countResult === false) {
	throw new Exception(mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$totalRecords = (int)$countRow["total"];

$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 0;
// ========================================
// If requested page is greater than total pages, reset to last page
// ========================================

if ($totalPages > 0 && $page > $totalPages) {
	$page   = $totalPages;
	$offset = ($page - 1) * $limit;
}


/* Fetch tickets */
$query = "
    SELECT
        t.ticket_id,
        t.subject,
        t.priority,
        t.status,
        t.preferred_language,
        t.input_mode,
        t.created_at,
        t.updated_at,
        c.name AS customer_name,
        c.email AS customer_email,
        d.name AS department_name,
        cat.category_name AS category_name,
        u.name AS assigned_user_name
    FROM $ticketTable AS t

    INNER JOIN $customerTable AS c
        ON c.tenant_id = t.tenant_id
        AND c.customer_id = t.customer_id

    LEFT JOIN $departmentTable AS d
        ON d.tenant_id = t.tenant_id
        AND d.dept_id = t.department_id

    LEFT JOIN $categoryTable AS cat
        ON cat.tenant_id = t.tenant_id
        AND cat.id = t.category_id

    LEFT JOIN $userTable AS u
        ON u.tenant_id = t.tenant_id
        AND u.user_id = t.assigned_user_id

    $whereSQL
    ORDER BY t.ticket_id DESC
    LIMIT $offset,$limit";
$result=mysqli_query($conn,$query);
$tickets = [];
if(mysqli_num_rows($result)>0)
{
	while($rs=mysqli_fetch_assoc($result))
	{
		$tickets[] = $rs;
	}
}

function ticketEscape($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Support Tickets</h1>
            <p class="text-muted mb-0">
                Manage customer support requests.
            </p>
        </div>

        <a href="index.php?p=ticket-add" class="btn btn-primary">Create Ticket</a>
    </div>

    <form action="index.php?p=tickets" method="post" class="row g-2 mb-4">

        <div class="col-md-6">
            <input type="search" name="search" class="form-control" placeholder="Search ticket, customer or subject" value="<?php echo ticketEscape($search); ?>">
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach ($allowedStatus as $item) { ?>
                    <option value="<?php echo ticketEscape($item); ?>"
                        <?php echo $status === $item ? 'selected' : ''; ?>>
                        <?php echo ticketEscape(ucwords(str_replace('_', ' ', $item))); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary">Search</button>
            <a href="index.php?p=tickets" class="btn btn-outline-secondary">Reset</a>
        </div>

    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Ticket ID</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($tickets) > 0) { ?>

                    <?php foreach ($tickets as $ticket) { ?>
                        <tr>
                            <td>
                                #<?php echo (int)$ticket['ticket_id']; ?>
                            </td>

                            <td>
                                <?php echo ticketEscape($ticket['customer_name']); ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo ticketEscape($ticket['customer_email']); ?>
                                </small>
                            </td>

                            <td>
                                <?php echo ticketEscape($ticket['subject']); ?>
                            </td>

                            <td>
                                <?php echo ticketEscape($ticket['department_name'] ?? '—'); ?>
                            </td>

                            <td>
                                <?php echo ticketEscape(ucfirst($ticket['priority'])); ?>
                            </td>

                            <td>
                                <?php echo ticketEscape(ucwords(str_replace('_',' ',$ticket['status']))); ?>
                            </td>

                            <td>
                                <?php echo ticketEscape($ticket['assigned_user_name'] ?? 'Unassigned'); ?>
                            </td>

                            <td>
                                <?php echo ticketEscape($ticket['created_at'] ?? ''); ?>
                            </td>

                            <td>
                                <a href="index.php?p=ticket-view&id=<?php echo (int)$ticket['ticket_id'];?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="9" class="text-center py-4">No tickets found.</td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>

        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">

        <span class="text-muted">
            Total tickets: <?php echo $totalRecords; ?>
        </span>

        <div>
            <?php if ($page > 1) { ?>
                <a class="btn btn-sm btn-outline-secondary"
                   href="&<?php echo http_build_query([
                       'search' => $search,
                       'status' => $status,
                       'page' => $page - 1
                   ]); ?>">
                    Previous
                </a>
            <?php } ?>

            <span class="mx-2">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            </span>

            <?php if ($page < $totalPages) { ?>
                <a class="btn btn-sm btn-outline-secondary" href="&<?php echo http_build_query([
                       'search' => $search,
                       'status' => $status,
                       'page' => $page + 1
                   ]); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</div>
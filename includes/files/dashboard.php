<nav class="navbar bg-white border-bottom">
    <div class="container">
        <span class="navbar-brand">Customer Support Portal</span>
		<a href="index.php?logout=true" class="btn btn-outline-danger btn-sm">
			Logout
		</a>

    </div>
</nav>

<main class="container py-5">
    <h1 class="h3">
        Welcome,
        <?= htmlspecialchars($_SESSION[SITE]['user_name'], ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <p class="text-secondary">
        Business ID: <?= (int) $_SESSION[SITE]['tenant_id'] ?>
    </p>

    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Your support workspace</h2>
            <p class="mb-0">
                Login is working. Next we will build the customer
                and ticket management modules.
            </p>
        </div>
    </div>
</main>
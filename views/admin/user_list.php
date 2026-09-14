<?php
/**
 * View: Admin - User Accounts List
 * Part A: Nripendra Sutradhar Pranto
 * Screen: Table of every account, with filters by role and status.
 */

$pageTitle = "Manage Accounts - Admin Portal";
$hasLayout = false; // use this page's own styled header, not layouts/header.php
if ($hasLayout) {
    include __DIR__ . '/../layouts/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #f4f6f9; color: #2d3748; line-height: 1.6; }
        .navbar { background: #1a202c; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 1rem; font-weight: 500; font-size: 0.95rem; }
        .navbar a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.8rem; color: #1a202c; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 1.5rem; margin-bottom: 1.5rem; }
        
        /* Filter Form */
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 1rem; align-items: flex-end; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; color: #4a5568; }
        .form-control { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 0.95rem; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #edf2f7; color: #4a5568; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.85rem 1rem; border-bottom: 2px solid #cbd5e0; }
        td { padding: 0.9rem 1rem; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f7fafc; }
        
        .badge { display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .badge-admin { background: #fed7d7; color: #9b2c2c; }
        .badge-passenger { background: #e2e8f0; color: #4a5568; }
        .badge-bookingmgr { background: #feebc8; color: #7b341e; }
        .badge-busroute { background: #e9d8fd; color: #553c9a; }
        .badge-active { background: #c6f6d5; color: #22543d; }
        .badge-inactive { background: #cbd5e0; color: #718096; }
        
        .btn { display: inline-block; padding: 0.45rem 0.85rem; border-radius: 5px; font-size: 0.88rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: 0.2s; }
        .btn-sm { padding: 0.35rem 0.65rem; font-size: 0.82rem; }
        .btn-primary { background: #3182ce; color: white; }
        .btn-primary:hover { background: #2b6cb0; }
        .btn-success { background: #38a169; color: white; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-warning { background: #d69e2e; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .actions { display: flex; gap: 0.4rem; align-items: center; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
    </style>
</head>
<body>
<nav class="navbar">
    <div style="font-size: 1.3rem; font-weight: bold;">🛡️ Admin Portal - InterCity MS</div>
    <div>
        <a href="../controllers/UserController.php?action=index" style="text-decoration: underline;">Manage Users</a>
        <a href="../controllers/AnnouncementControllerT.php?action=index">Announcements</a>
        <a href="../controllers/HomeController.php" target="_blank">View Website</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>User Accounts & Staff Management</h1>
            <p style="color: #718096; font-size: 0.95rem;">Create staff accounts, change roles, and deactivate or remove accounts</p>
        </div>
        <div>
            <a href="../controllers/UserController.php?action=create" class="btn btn-primary">+ Create New Staff / Account</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card" style="padding: 1.25rem;">
        <form action="../controllers/UserController.php" method="GET" class="filter-form">
            <input type="hidden" name="action" value="index">
            
            <div class="form-group">
                <label for="role">Filter by Role</label>
                <select name="role" id="role" class="form-control">
                    <option value="">-- All Roles --</option>
                    <option value="admin" <?= (isset($roleFilter) && $roleFilter === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="passenger" <?= (isset($roleFilter) && $roleFilter === 'passenger') ? 'selected' : '' ?>>Passenger</option>
                    <option value="bookingmgr" <?= (isset($roleFilter) && $roleFilter === 'bookingmgr') ? 'selected' : '' ?>>Booking Manager (Part C)</option>
                    <option value="busroute" <?= (isset($roleFilter) && $roleFilter === 'busroute') ? 'selected' : '' ?>>Bus Route Operator (Part B)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Filter by Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">-- All Statuses --</option>
                    <option value="active" <?= (isset($statusFilter) && $statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (isset($statusFilter) && $statusFilter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label for="search">Search Name / Email / Phone</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Search keyword..." value="<?= htmlspecialchars(isset($search) ? $search : '') ?>">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
                <a href="../controllers/UserController.php?action=index" class="btn btn-secondary" style="height: 38px; line-height: 24px;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Accounts Table Card -->
    <div class="card" style="overflow-x: auto;">
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email & Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong>#<?= (int)$u['id'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                    <span style="font-size: 0.75rem; color: #3182ce; font-weight: bold;">(You)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($u['email']) ?></div>
                                <div style="font-size: 0.85rem; color: #718096;"><?= htmlspecialchars($u['phone']) ?></div>
                            </td>
                            <td>
                                <?php
                                $roleBadge = 'badge-passenger';
                                if ($u['role'] === 'admin') $roleBadge = 'badge-admin';
                                elseif ($u['role'] === 'bookingmgr') $roleBadge = 'badge-bookingmgr';
                                elseif ($u['role'] === 'busroute') $roleBadge = 'badge-busroute';
                                ?>
                                <span class="badge <?= $roleBadge ?>"><?= htmlspecialchars($u['role']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= htmlspecialchars($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.88rem; color: #718096;">
                                    <?= !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '—' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="../controllers/UserController.php?action=edit&id=<?= (int)$u['id'] ?>" class="btn btn-secondary btn-sm" title="Edit account and role">
                                        ✏️ Edit
                                    </a>

                                    <!-- Deactivate / Activate Button -->
                                    <?php if ($u['status'] === 'active'): ?>
                                        <a href="../controllers/UserController.php?action=toggleStatus&id=<?= (int)$u['id'] ?>&status=inactive" class="btn btn-warning btn-sm" onclick="return confirm('Deactivate this user account?');">
                                            Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="../controllers/UserController.php?action=toggleStatus&id=<?= (int)$u['id'] ?>&status=active" class="btn btn-success btn-sm">
                                            Activate
                                        </a>
                                    <?php endif; ?>

                                    <!-- Delete Button (cannot delete self) -->
                                    <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="../controllers/UserController.php?action=delete&id=<?= (int)$u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Permanently delete this user account?');">
                                            Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <p style="font-size: 1.15rem; color: #4a5568;">No user accounts matched the filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
<footer style="background: #1a202c; color: #cbd5e0; text-align: center; padding: 1.5rem; margin-top: 3rem; font-size: 0.9rem;">
    <p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System. Part A by Nripendra Sutradhar Pranto (23-51909-2).</p>
</footer>
</body>
</html>
<?php } ?>

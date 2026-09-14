<?php
/**
 * View: Cancellation & Refund Management
 * Part C: Booking Manager (counter staff)
 * Screen: the refund queue. Approve with a deduction or reject.
 * Opened through ManagerControllerT.php?action=refunds.
 */
 
// Fallback safety checks for dynamic variables
$dbConnected = $dbConnected ?? true;
$error = $error ?? '';
$refundMethods = $refundMethods ?? ['bKash', 'Nagad', 'Bank Transfer', 'Cash'];
 
// Redirect guard
if (!isset($refunds)) {
    // Simulated fallback data for direct local preview/testing
    $refunds = [
        [
            'id' => 1,
            'booking_ref' => 'BK-8842',
            'customer' => 'Farhana Akter',
            'origin' => 'Dhaka',
            'destination' => 'Khulna',
            'ticket_amount' => 1100.00,
            'deduction' => 110.00,
            'refund_amount' => 990.00,
            'cancel_reason' => 'Emergency travel plan change',
            'method' => 'bKash',
            'status' => 'Pending',
            'requested_at' => '2026-09-12 10:30:00',
            'processed_at' => null
        ],
        [
            'id' => 2,
            'booking_ref' => 'BK-5521',
            'customer' => 'Kamal Uddin',
            'origin' => 'Dhaka',
            'destination' => 'Chittagong',
            'ticket_amount' => 850.00,
            'deduction' => 85.00,
            'refund_amount' => 765.00,
            'cancel_reason' => 'Bus schedule delayed',
            'method' => 'Bank Transfer',
            'status' => 'Pending',
            'requested_at' => '2026-09-11 14:15:00',
            'processed_at' => null
        ]
    ];
}
 
// Fallback helper function for badge CSS classes
if (!function_exists('managerBadge')) {
    function managerBadge($status) {
        switch (strtolower($status)) {
            case 'approved':
            case 'settled':
            case 'refunded':
                return 'badge-green';
            case 'pending':
                return 'badge-yellow';
            case 'rejected':
                return 'badge-red';
            default:
                return 'badge-grey';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation & Refund Management - RoadLine</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f8; color: #333; display: flex; min-height: 100vh; }
 
        .sidebar { width: 240px; flex-shrink: 0; background: #fff; border-right: 1px solid #e2e8f0; padding: 24px 16px; display: flex; flex-direction: column; gap: 20px; }
        .brand { font-size: 20px; font-weight: bold; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item a { text-decoration: none; color: #64748b; padding: 10px 14px; display: block; border-radius: 8px; font-weight: 500; }
        .nav-item.active a, .nav-item a:hover { background-color: #fff1ec; color: #ff5722; font-weight: bold; }
 
        .main-content { flex: 1; min-width: 0; padding: 32px; overflow-y: auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
 
        /* switch between the manager and customer tables */
        .view-toggle { display: flex; gap: 8px; background: #e2e8f0; padding: 4px; border-radius: 8px; width: fit-content; margin-bottom: 24px; }
        .toggle-btn { padding: 8px 16px; border: none; background: transparent; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; }
        .toggle-btn.active { background: white; color: #ff5722; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
 
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; margin-top: 12px; }
        th, td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        th { color: #64748b; font-weight: 600; background: #f8fafc; }
        .muted { color: #94a3b8; font-size: 12px; }
        .empty { color: #64748b; padding: 12px 0; }
 
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-yellow { background: #fef9c3; color: #a16207; }
        .badge-blue   { background: #e0e7ff; color: #3730a3; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-grey   { background: #f1f5f9; color: #475569; }
 
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-orange { background: #ff5722; color: white; }
        .btn-orange:hover { background: #e64a19; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #334155; }
        .btn-outline:hover { background: #f8fafc; }
 
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-danger { background: #fee2e2; color: #b91c1c; }
 
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; padding: 16px; z-index: 1000; }
        .modal-content { background: white; border-radius: 12px; width: 450px; max-width: 100%; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .field input, .field select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: white; }
        .field input[readonly] { background: #f8fafc; }
    </style>
</head>
<body>
 
    <div class="sidebar">
        <div class="brand">🚌 RoadLine</div>
        <ul class="nav-list">
            <li class="nav-item"><a href="../controllers/ManagerControllerT.php?action=dashboard">Dashboard</a></li>
            <li class="nav-item"><a href="../controllers/ManagerControllerT.php?action=dashboard#reservations">Reservations</a></li>
            <li class="nav-item"><a href="../controllers/ManagerControllerT.php?action=dashboard#seatmap-section">Seat Allocation</a></li>
            <li class="nav-item active"><a href="../controllers/ManagerControllerT.php?action=refunds">Cancellations & Refunds</a></li>
            <li class="nav-item"><a href="../controllers/AnnouncementControllerT.php?action=index">Announcements</a></li>
            <li class="nav-item"><a href="../controllers/AuthController.php?action=logout">Log out</a></li>
        </ul>
    </div>
 
    <div class="main-content">
        <div class="header-bar">
            <div>
                <h2>Cancellation & Refund Management</h2>
                <p style="color: #64748b; font-size: 14px;">Review refund requests, apply deductions, and track status</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="goBack('../controllers/ManagerControllerT.php?action=dashboard')">← Back</button>
                <a href="../controllers/ManagerControllerT.php?action=dashboard" class="btn btn-outline">Dashboard</a>
            </div>
        </div>
 
        <?php if (!$dbConnected): ?>
            <div class="alert alert-danger">The database is not connected. Start MySQL in XAMPP and import <strong>busdbT.sql</strong>.</div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
 
        <div class="view-toggle">
            <button type="button" class="toggle-btn active" id="admin-view-btn" onclick="switchView('admin')">Admin & Manager View</button>
            <button type="button" class="toggle-btn" id="customer-view-btn" onclick="switchView('customer')">Customer View</button>
        </div>
 
        <!-- Manager View Section -->
        <div id="admin-section" class="card">
            <h3>All Refund Requests</h3>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">Approve with a cancellation deduction, or reject the request.</p>
 
            <?php if (!empty($refunds)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Customer</th>
                            <th>Trip</th>
                            <th>Ticket Price</th>
                            <th>Reason</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refunds as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['booking_ref']) ?></strong></td>
                            <td><?= htmlspecialchars($item['customer'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(($item['origin'] ?? '-') . ' → ' . ($item['destination'] ?? '-')) ?></td>
                            <td>৳<?= number_format((float) $item['ticket_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($item['cancel_reason']) ?></td>
                            <td><?= date('d M Y', strtotime($item['requested_at'])) ?></td>
                            <td><span class="badge <?= managerBadge($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                            <td>
                                <?php if (in_array($item['status'], ['Pending', 'Approved'], true)): ?>
                                    <button type="button" class="btn btn-orange"
                                        data-id="<?= (int) $item['id'] ?>"
                                        data-ref="<?= htmlspecialchars($item['booking_ref']) ?>"
                                        data-customer="<?= htmlspecialchars($item['customer'] ?? '-') ?>"
                                        data-amount="<?= htmlspecialchars(number_format((float) $item['ticket_amount'], 2, '.', '')) ?>"
                                        data-percent="<?= (float) $item['ticket_amount'] > 0 ? round(($item['deduction'] ?? 0) / $item['ticket_amount'] * 100, 2) : 0 ?>"
                                        data-method="<?= htmlspecialchars($item['method'] ?? 'bKash') ?>"
                                        onclick="openProcessModal(this)">Process Refund</button>
                                <?php else: ?>
                                    <span class="muted"><?= !empty($item['processed_at']) ? 'Done ' . date('d M', strtotime($item['processed_at'])) : 'Done' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="empty">No refund requests yet.</p>
            <?php endif; ?>
        </div>
 
        <!-- Customer View Section -->
        <div id="customer-section" class="card" style="display: none;">
            <h3>Refund Status (as the customer sees it)</h3>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">Deduction and the amount paid back for each request.</p>
 
            <?php if (!empty($refunds)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>Trip Details</th>
                            <th>Original Fare</th>
                            <th>Deduction</th>
                            <th>Net Refund Amount</th>
                            <th>Payout Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refunds as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['booking_ref']) ?></strong></td>
                            <td><?= htmlspecialchars(($item['origin'] ?? '-') . ' → ' . ($item['destination'] ?? '-')) ?></td>
                            <td>৳<?= number_format((float) $item['ticket_amount'], 2) ?></td>
                            <td>৳<?= number_format((float) ($item['deduction'] ?? 0), 2) ?></td>
                            <td><strong style="color: #15803d;">৳<?= number_format((float) ($item['refund_amount'] ?? 0), 2) ?></strong></td>
                            <td><?= htmlspecialchars($item['method'] ?? 'N/A') ?></td>
                            <td><span class="badge <?= managerBadge($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="empty">No refund requests yet.</p>
            <?php endif; ?>
        </div>
    </div>
 
    <!-- Modal Form -->
    <div class="modal" id="processModal">
        <div class="modal-content">
            <h3 style="margin-bottom: 12px;">Process Refund Claim</h3>
            <form method="POST" action="../controllers/ManagerControllerT.php?action=refunds">
                <input type="hidden" name="refund_id" id="modal_refund_id">
 
                <div class="field">
                    <label for="modal_customer">Customer</label>
                    <input type="text" id="modal_customer" readonly>
                </div>
 
                <div class="field">
                    <label for="modal_deduction">Cancellation Penalty Deduction (%)</label>
                    <input type="number" name="deduction" id="modal_deduction" value="10" min="0" max="100" step="0.01" required oninput="updateNet()">
                    <span class="muted" id="modal_net"></span>
                </div>
 
                <div class="field">
                    <label for="modal_method">Payout Method</label>
                    <select name="method" id="modal_method">
                        <?php foreach ($refundMethods as $method): ?>
                            <option value="<?= htmlspecialchars($method) ?>"><?= htmlspecialchars($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="field" style="margin-bottom: 20px;">
                    <label for="modal_status">Decision</label>
                    <select name="status" id="modal_status" onchange="updateNet()">
                        <option value="Settled">Approve & Settle Refund</option>
                        <option value="Rejected">Reject Refund Request</option>
                    </select>
                </div>
 
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeProcessModal()">Cancel</button>
                    <button type="submit" class="btn btn-orange">Submit Decision</button>
                </div>
            </form>
        </div>
    </div>
 
    <!-- Corrected JavaScript logic -->
    <script>
        var currentFare = 0;
 
        function goBack(fallback) {
            var previous = document.referrer.split('#')[0];
            if (previous !== '' && previous !== location.href.split('#')[0] && history.length > 1) {
                history.back();
            } else {
                location.href = fallback;
            }
        }
 
        function switchView(view) {
            var isAdmin = view === 'admin';
            document.getElementById('admin-section').style.display = isAdmin ? 'block' : 'none';
            document.getElementById('customer-section').style.display = isAdmin ? 'none' : 'block';
            document.getElementById('admin-view-btn').classList.toggle('active', isAdmin);
            document.getElementById('customer-view-btn').classList.toggle('active', !isAdmin);
        }
 
        function openProcessModal(button) {
            currentFare = parseFloat(button.dataset.amount) || 0;
            document.getElementById('modal_refund_id').value = button.dataset.id;
            document.getElementById('modal_customer').value = button.dataset.customer + ' · ' + button.dataset.ref + ' (Fare: ৳' + button.dataset.amount + ')';
            document.getElementById('modal_deduction').value = button.dataset.percent;
            document.getElementById('modal_method').value = button.dataset.method;
            document.getElementById('modal_status').value = 'Settled';
            updateNet();
            document.getElementById('processModal').style.display = 'flex';
        }
 
        function closeProcessModal() {
            document.getElementById('processModal').style.display = 'none';
        }
 
        function updateNet() {
            var net = document.getElementById('modal_net');
            if (document.getElementById('modal_status').value === 'Rejected') {
                net.textContent = 'Rejected: nothing is paid back.';
                return;
            }
            var percent = Math.min(100, Math.max(0, parseFloat(document.getElementById('modal_deduction').value) || 0));
            var netAmount = (currentFare - (currentFare * percent / 100)).toFixed(2);
            net.textContent = 'Customer gets ৳' + netAmount;
        }
 
        document.getElementById('processModal').addEventListener('click', function (e) {
            if (e.target === this) closeProcessModal();
        });
    </script>
</body>
</html>
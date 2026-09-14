<?php
// Announcements list. Shown by controllers/AnnouncementControllerT.php, which
// provides $announcements, $audiences and $dbConnected.
if (!isset($announcements)) {
	header("Location: ../../controllers/AnnouncementControllerT.php?action=index");
	exit;
}

$today = date('Y-m-d');

// Live, Scheduled, Expired or Off, from the switch and the dates.
function announcementState($a, $today) {
	if (!$a['is_active']) {
		return 'Off';
	}
	if ($today < $a['publish_from']) {
		return 'Scheduled';
	}
	if ($today > $a['publish_to']) {
		return 'Expired';
	}
	return 'Live';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Announcements - Bus Ticket MS</title>
	<link rel="stylesheet" href="../public/css/announcementT.css">
</head>
<body>
<?php include __DIR__ . '/navT.php'; ?>

<div class="container">
	<div class="page-header">
		<div>
			<h1>Announcements</h1>
			<p class="subtitle">Post notices for passengers and staff. Passengers see "Everyone" and "Passengers" notices on their dashboard, booking managers see "Everyone" and "Booking managers" notices.</p>
		</div>
		<a href="../controllers/AnnouncementControllerT.php?action=create" class="btn btn-primary">+ New announcement</a>
	</div>

	<?php if (!$dbConnected): ?>
		<div class="alert alert-danger">The database is not connected. Start MySQL in XAMPP and import <strong>busdbT.sql</strong>.</div>
	<?php endif; ?>
	<?php if (!empty($_SESSION['flash_success'])): ?>
		<div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
	<?php endif; ?>
	<?php if (!empty($_SESSION['flash_error'])): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
	<?php endif; ?>

	<div class="card">
		<?php if ($announcements): ?>
			<div class="table-wrap">
				<table>
					<thead>
						<tr>
							<th>Announcement</th>
							<th>Shown to</th>
							<th>Dates</th>
							<th>Status</th>
							<th>Posted by</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($announcements as $a): ?>
							<?php $state = announcementState($a, $today); ?>
							<tr>
								<td>
									<strong><?= htmlspecialchars($a['title']) ?></strong>
									<div class="body-preview"><?= htmlspecialchars(mb_strimwidth($a['body'], 0, 160, '…')) ?></div>
								</td>
								<td><span class="badge badge-audience"><?= htmlspecialchars($audiences[$a['audience']] ?? $a['audience']) ?></span></td>
								<td class="muted" style="white-space: nowrap;">
									<?= date('d M Y', strtotime($a['publish_from'])) ?><br>to <?= date('d M Y', strtotime($a['publish_to'])) ?>
								</td>
								<td><span class="badge badge-<?= strtolower($state) ?>"><?= $state ?></span></td>
								<td class="muted">
									<?= htmlspecialchars($a['author'] ?? '-') ?><br><?= date('d M Y', strtotime($a['created_at'])) ?>
								</td>
								<td>
									<div class="actions">
										<a href="../controllers/AnnouncementControllerT.php?action=edit&amp;id=<?= (int) $a['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>

										<form method="POST" action="../controllers/AnnouncementControllerT.php?action=toggle">
											<input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
											<?php if ($a['is_active']): ?>
												<input type="hidden" name="active" value="0">
												<button type="submit" class="btn btn-warning btn-sm">Switch off</button>
											<?php else: ?>
												<input type="hidden" name="active" value="1">
												<button type="submit" class="btn btn-success btn-sm">Switch on</button>
											<?php endif; ?>
										</form>

										<form method="POST" action="../controllers/AnnouncementControllerT.php?action=delete" onsubmit="return confirm('Delete this announcement for good?');">
											<input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
											<button type="submit" class="btn btn-danger btn-sm">Delete</button>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else: ?>
			<p class="empty">No announcements yet. Click <strong>+ New announcement</strong> to post one.</p>
		<?php endif; ?>
	</div>
</div>

<footer>
	<p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System.</p>
</footer>
</body>
</html>

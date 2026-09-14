<?php
// Post or edit an announcement. Shown by controllers/AnnouncementControllerT.php,
// which provides $data, $errors, $isEdit and $audiences.
if (!isset($data)) {
	header("Location: ../../controllers/AnnouncementControllerT.php?action=create");
	exit;
}

// the "is-invalid" class and the first error message for a field
function fieldError($errors, $field) {
	return empty($errors[$field]) ? '' : '<div class="field-error">' . htmlspecialchars($errors[$field][0]) . '</div>';
}
function invalid($errors, $field) {
	return empty($errors[$field]) ? '' : 'is-invalid';
}

$action = $isEdit
	? '../controllers/AnnouncementControllerT.php?action=edit&id=' . (int) $data['id']
	: '../controllers/AnnouncementControllerT.php?action=create';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $isEdit ? 'Edit' : 'New' ?> Announcement - Bus Ticket MS</title>
	<link rel="stylesheet" href="../public/css/announcementT.css">
</head>
<body>
<?php include __DIR__ . '/navT.php'; ?>

<div class="container" style="max-width: 760px;">
	<div class="page-header">
		<div>
			<h1><?= $isEdit ? 'Edit announcement' : 'New announcement' ?></h1>
			<p class="subtitle">It shows up for the chosen people between the two dates while it is switched on.</p>
		</div>
		<a href="../controllers/AnnouncementControllerT.php?action=index" class="btn btn-secondary">← Back to announcements</a>
	</div>

	<?php if (!empty($errors['general'])): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
	<?php endif; ?>

	<div class="card">
		<form method="POST" action="<?= htmlspecialchars($action) ?>" novalidate>
			<div class="form-group">
				<label for="title">Title *</label>
				<input type="text" id="title" name="title" maxlength="150" class="form-control <?= invalid($errors, 'title') ?>" value="<?= htmlspecialchars($data['title']) ?>" placeholder="e.g. Eid holiday schedule">
				<?= fieldError($errors, 'title') ?>
			</div>

			<div class="form-group">
				<label for="body">Message *</label>
				<textarea id="body" name="body" class="form-control <?= invalid($errors, 'body') ?>" placeholder="What should people know?"><?= htmlspecialchars($data['body']) ?></textarea>
				<?= fieldError($errors, 'body') ?>
			</div>

			<div class="form-group">
				<label for="audience">Shown to *</label>
				<select id="audience" name="audience" class="form-control <?= invalid($errors, 'audience') ?>">
					<?php foreach ($audiences as $value => $label): ?>
						<option value="<?= htmlspecialchars($value) ?>" <?= $data['audience'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
					<?php endforeach; ?>
				</select>
				<?= fieldError($errors, 'audience') ?>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="publish_from">Show from *</label>
					<input type="date" id="publish_from" name="publish_from" class="form-control <?= invalid($errors, 'publish_from') ?>" value="<?= htmlspecialchars($data['publish_from']) ?>">
					<?= fieldError($errors, 'publish_from') ?>
				</div>
				<div class="form-group">
					<label for="publish_to">Show until *</label>
					<input type="date" id="publish_to" name="publish_to" class="form-control <?= invalid($errors, 'publish_to') ?>" value="<?= htmlspecialchars($data['publish_to']) ?>">
					<?= fieldError($errors, 'publish_to') ?>
				</div>
			</div>

			<div class="form-group">
				<label class="check">
					<input type="checkbox" name="is_active" value="1" <?= !empty($data['is_active']) ? 'checked' : '' ?>>
					Switched on (uncheck to save it without showing it)
				</label>
			</div>

			<button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Post announcement' ?></button>
		</form>
	</div>
</div>

<footer>
	<p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System.</p>
</footer>
</body>
</html>

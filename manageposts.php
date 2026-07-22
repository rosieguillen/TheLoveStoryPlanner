<?php

session_start();

require_once __DIR__ . '/connect.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: authenticate.php');
    exit;
}

$sortOptions = [
    'title' => [
        'column' => 'title',
        'label' => 'Title'
    ],
    'created' => [
        'column' => '`timestamp`',
        'label' => 'Created date'
    ],
    'updated' => [
        'column' => 'updated_at',
        'label' => 'Updated date'
    ]
];

$directionOptions = [
    'asc' => 'ASC',
    'desc' => 'DESC'
];

$sort = $_GET['sort'] ?? 'created';
$direction = $_GET['direction'] ?? 'desc';

if (!isset($sortOptions[$sort])) {
    $sort = 'created';
}

if (!isset($directionOptions[$direction])) {
    $direction = 'desc';
}

$orderColumn = $sortOptions[$sort]['column'];
$orderDirection = $directionOptions[$direction];

$query = "
    SELECT
        blogID,
        title,
        `timestamp`,
        updated_at
    FROM blogspots
    ORDER BY {$orderColumn} {$orderDirection}, blogID DESC
";

$posts = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

function escapeManage(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function manageDate(string $value): string
{
    $time = strtotime($value);
    return $time ? date('M j, Y \a\t g:i a', $time) : '';
}

function sortUrl(string $column, string $currentSort, string $currentDirection): string
{
    $nextDirection = $currentSort === $column && $currentDirection === 'asc'
        ? 'desc'
        : 'asc';

    return '?sort=' . urlencode($column) . '&direction=' . $nextDirection;
}

function sortIndicator(string $column, string $currentSort, string $currentDirection): string
{
    if ($column !== $currentSort) {
        return '';
    }

    return $currentDirection === 'asc' ? ' ▲' : ' ▼';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="post.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="post-page manage-page">
        <section class="post-form-section">
            <div class="form-introduction">
                <p class="page-label">Admin Area</p>
                <h1>Manage Blog Posts</h1>
                <p>Review, sort and edit all existing journal posts.</p>
            </div>

            <div class="manage-content">
                <div class="manage-toolbar">
                    <p class="sort-status">
                        Sorted by <strong><?= escapeManage($sortOptions[$sort]['label']) ?></strong>
                        in <strong><?= $direction === 'asc' ? 'ascending' : 'descending' ?></strong> order.
                    </p>
                    <a class="btn-submit" href="post.php">Create New Post</a>
                </div>

                <div class="manage-table-wrapper">
                    <table class="manage-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= escapeManage(sortUrl('title', $sort, $direction)) ?>" <?= $sort === 'title' ? 'aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                                        Title<?= sortIndicator('title', $sort, $direction) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= escapeManage(sortUrl('created', $sort, $direction)) ?>" <?= $sort === 'created' ? 'aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                                        Created<?= sortIndicator('created', $sort, $direction) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= escapeManage(sortUrl('updated', $sort, $direction)) ?>" <?= $sort === 'updated' ? 'aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                                        Updated<?= sortIndicator('updated', $sort, $direction) ?>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?= escapeManage($post['title']) ?></td>
                                    <td><?= escapeManage(manageDate($post['timestamp'])) ?></td>
                                    <td><?= escapeManage(manageDate($post['updated_at'])) ?></td>
                                    <td class="manage-actions">
                                        <a href="post.php?id=<?= (int) $post['blogID'] ?>">View</a>
                                        <a href="edit.php?id=<?= (int) $post['blogID'] ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$posts): ?>
                    <p class="empty-manage-list">No blog posts have been created yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

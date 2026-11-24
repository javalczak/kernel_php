<?php
declare(strict_types=1);
/** @var \App\Entity\Example $item */
/** @var \Engine\Translation\Translator $translator */
$pageTitle = $translator->trans('example.show.title', ['title' => $item->title]);
require BASE_PATH . '/templates/_partials/_header.php';
?>

<p class="back"><a href="/items" class="btn btn-secondary">&larr; <?= htmlspecialchars($translator->trans('example.show.back')) ?></a></p>

<h1><?= htmlspecialchars($item->title) ?></h1>

<p class="meta">
    <?= htmlspecialchars($translator->trans('example.show.created')) ?>: <?= htmlspecialchars((string) $item->createdAt) ?>
    <?php if (!$item->isActive): ?>
        &middot; <span class="danger"><?= htmlspecialchars($translator->trans('example.inactive')) ?></span>
    <?php endif; ?>
</p>

<?php require BASE_PATH . '/templates/_partials/_footer.php'; ?>

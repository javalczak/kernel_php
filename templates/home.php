<?php
declare(strict_types=1);
/** @var \Engine\Translation\Translator $translator */
$pageTitle = 'Pure Kernel';
require BASE_PATH . '/templates/_partials/_header.php';
?>

<div class="card">
    <div class="badge">Pure Kernel</div>
    <h1>It works.</h1>
    <p>PHP 8.3 micro-framework for FTP-only shared hosting. No Composer on the server, no SSH, no Docker.</p>
    <p>Front controller and MVC, entities instead of arrays, zero dependencies.</p>
    <p><a href="/items" class="btn">See the example CRUD &rarr;</a></p>
</div>

<?php require BASE_PATH . '/templates/_partials/_footer.php'; ?>

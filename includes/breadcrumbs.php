<?php
/**
 * breadcrumbs.php — Breadcrumb visible accesible
 * Requiere: $page['breadcrumbs'] definido.
 */
if (empty($page['breadcrumbs'])) return;
?>
<nav class="breadcrumbs" aria-label="Migas de pan">
    <ol class="breadcrumbs-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="/" itemprop="item"><span itemprop="name">Inicio</span></a>
            <meta itemprop="position" content="1">
        </li>
        <?php
        $pos = 2;
        $total = count($page['breadcrumbs']);
        foreach ($page['breadcrumbs'] as $i => $crumb):
            $is_last = ($i === $total - 1);
        ?>
        <li class="breadcrumbs-item<?= $is_last ? ' breadcrumbs-item--current' : '' ?>"
            itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php if (!$is_last && !empty($crumb['url'])): ?>
                <a href="<?= h($crumb['url']) ?>" itemprop="item"><span itemprop="name"><?= h($crumb['label']) ?></span></a>
            <?php else: ?>
                <span itemprop="name" aria-current="page"><?= h($crumb['label']) ?></span>
            <?php endif; ?>
            <meta itemprop="position" content="<?= $pos ?>">
        </li>
        <?php $pos++; endforeach; ?>
    </ol>
</nav>

<?php
/**
 * Public blog results column: active-filter chips, article cards (or the
 * empty state) and pagination.
 *
 * Rendered standalone as HTML for the GET blog/results JSON endpoint and
 * embedded inside #blog-results-region on the full /blog page, so both
 * paths render IDENTICAL markup from the same data.
 *
 * Every link is built by the single canonical $blogUrl builder and carries
 * data-blog-link so the page JS can intercept it for async updates (plain
 * navigation still works without JS).
 *
 * Expected data: $articles, $total, $page, $totalPages, $q, $hasFilters,
 * $selectedCategories, $selectedTags, $catNames, $articleCategoryIds,
 * $cardLayout, $blogUrl (closure: (catSlugs, tagSlugs, q, ?page) => url).
 */
?>
<?php if ($hasFilters): ?>
<div class="blog-active-filters">
    <span class="af-label">Filter aktif:</span>
    <?php if ($q !== ''): ?>
        <span class="af-chip">Pencarian: &ldquo;<?= esc($q) ?>&rdquo; <a data-blog-link href="<?= $blogUrl($categorySlugs, $tagSlugs, '', null) ?>" aria-label="Hapus pencarian">&times;</a></span>
    <?php endif; ?>
    <?php foreach ($selectedCategories as $selCat): ?>
        <span class="af-chip">Kategori: <?= esc($selCat['name']) ?> <a data-blog-link href="<?= $blogUrl(array_values(array_diff($categorySlugs, [$selCat['slug']])), $tagSlugs, $q, null) ?>" aria-label="Hapus kategori <?= esc($selCat['name']) ?>">&times;</a></span>
    <?php endforeach; ?>
    <?php foreach ($selectedTags as $selTag): ?>
        <span class="af-chip">Tag: <?= esc($selTag['name']) ?> <a data-blog-link href="<?= $blogUrl($categorySlugs, array_values(array_diff($tagSlugs, [$selTag['slug']])), $q, null) ?>" aria-label="Hapus tag <?= esc($selTag['name']) ?>">&times;</a></span>
    <?php endforeach; ?>
    <a class="af-reset" data-blog-link href="<?= base_url('blog') ?>">Reset filter</a>
</div>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <div class="blog-empty">
        <p><?= $hasFilters ? 'Tidak ada artikel yang sesuai.' : 'Belum ada artikel yang diterbitkan.' ?></p>
        <?php if ($hasFilters): ?>
            <a class="af-reset" data-blog-link href="<?= base_url('blog') ?>">Reset filter</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php // Desktop column count from the "Tata Letak Kartu" setting; 1 column on small screens. ?>
    <?php $cardColClass = ($cardLayout ?? 'grid_3_columns') === 'grid_2_columns' ? 'col-12 col-md-6' : 'col-12 col-md-4'; ?>
    <div class="row">
        <?php foreach ($articles as $a): ?>
        <div class="<?= $cardColClass ?>">
            <div class="post-box portal-blog-card <?= empty($a['cover_image']) ? 'no-cover' : '' ?>">
                <?php if (! empty($a['cover_image'])): ?>
                <div class="post-thumb">
                    <a href="<?= base_url('blog/' . $a['slug']) ?>">
                        <img src="<?= base_url($a['cover_image']) ?>" class="img-fluid" alt="<?= esc($a['cover_alt'] ?? $a['title']) ?>" loading="lazy" />
                    </a>
                </div>
                <?php endif; ?>
                <div class="post-info">
                    <h4><a href="<?= base_url('blog/' . $a['slug']) ?>"><?= esc($a['title']) ?></a></h4>
                    <ul>
                        <?php foreach ($articleCategoryIds[(int) $a['id']] ?? [] as $postCatId): ?>
                            <?php if (isset($catNames[$postCatId])): ?>
                        <li><?= esc($catNames[$postCatId]) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (! empty($a['published_at'])): ?>
                        <li><?= esc(date('d M Y', strtotime($a['published_at']))) ?></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (! empty($a['excerpt'])): ?>
                    <p><?= esc($a['excerpt']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
    <?php
    // Previous, first page, ellipsis when needed, nearby page numbers,
    // last page, Next (Portal theme colors). Links keep every filter.
    $pagesList = range(max(1, $page - 2), min($totalPages, $page + 2));
    if (! in_array(1, $pagesList, true)) {
        array_unshift($pagesList, 1);
    }
    if (! in_array($totalPages, $pagesList, true)) {
        $pagesList[] = $totalPages;
    }
    $pagesList = array_values(array_unique($pagesList));
    sort($pagesList);
    $prev = ($page > 1) ? $blogUrl($categorySlugs, $tagSlugs, $q, $page - 1) : null;
    $next = ($page < $totalPages) ? $blogUrl($categorySlugs, $tagSlugs, $q, $page + 1) : null;
    ?>
    <nav aria-label="Navigasi halaman artikel" class="blog-pagination">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $prev === null ? 'disabled' : '' ?>">
                <?php if ($prev !== null): ?>
                    <a class="page-link" data-blog-link href="<?= $prev ?>" aria-label="Previous">Sebelumnya</a>
                <?php else: ?>
                    <span class="page-link" aria-label="Previous">Sebelumnya</span>
                <?php endif; ?>
            </li>
            <?php foreach ($pagesList as $i => $p): ?>
                <?php if ($i > 0 && $p - $pagesList[$i - 1] > 1): ?>
                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>>
                    <a class="page-link" data-blog-link href="<?= $blogUrl($categorySlugs, $tagSlugs, $q, $p) ?>"><?= $p ?></a>
                </li>
            <?php endforeach; ?>
            <li class="page-item <?= $next === null ? 'disabled' : '' ?>">
                <?php if ($next !== null): ?>
                    <a class="page-link" data-blog-link href="<?= $next ?>" aria-label="Next">Berikutnya</a>
                <?php else: ?>
                    <span class="page-link" aria-label="Next">Berikutnya</span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

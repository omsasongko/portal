<?php

namespace App\Commands;

use App\Models\CategoryModel;
use App\Models\TagModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Temporary seeding helper (2026-11-07): creates the four Indonesian sample
 * categories and the twelve tags requested for testing. Uses the exact same
 * persistence path and validation rules as the admin workflows
 * (Admin\Categories::save / Admin\Tags::save): slug generated with
 * mb_url_title() when blank, same slug pattern/length checks, slug unique per
 * table. Idempotent: a record whose slug OR name already exists is REUSED,
 * not duplicated, so re-running only fills in the missing ones. Tags live in
 * the single global `tags` table (this CMS has no per-category tag scoping),
 * so they are created as global tags; existing articles/categories/tags and
 * article-tag assignments are never modified.
 */
class SeedSampleCategoriesTags extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'taxonomy:seed-samples';
    protected $description = 'Creates 4 sample categories + 12 global tags; reuses records whose name or slug already exists.';

    /**
     * Grouping is for readability only — the CMS stores tags globally and the
     * article editor lists every tag regardless of category.
     */
    private const SAMPLES = [
        'Kebugaran' => ['Latihan Kekuatan', 'Kardio', 'Peregangan'],
        'Kesehatan' => ['Gizi Seimbang', 'Tidur Berkualitas', 'Kesehatan Mental'],
        'Edukasi'   => ['Tips Belajar', 'Literasi Digital', 'Belajar Mandiri'],
        'Teknologi' => ['Kecerdasan Buatan', 'Keamanan Digital', 'Perangkat Pintar'],
    ];

    public function run(array $params)
    {
        $this->seedTable(new CategoryModel(), 'Kategori', array_keys(self::SAMPLES));
        $this->seedTable(new TagModel(), 'Tag', array_merge(...array_values(self::SAMPLES)));

        return EXIT_SUCCESS;
    }

    /**
     * Mirrors the admin save() flow: blank slug => mb_url_title(name), same
     * pattern/length validation, then reuse-by-slug-or-name before inserting.
     */
    private function seedTable($model, string $label, array $names): void
    {
        $table = $model->getTable();

        $created = [];
        $reused  = [];

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '' || mb_strlen($name) > 100) {
                CLI::write("{$label} '{$name}' tidak valid (nama kosong / > 100 karakter) — dilewati.", 'red');
                continue;
            }

            $slug = mb_url_title($name, '-', true);
            if ($slug === '' || mb_strlen($slug) > 150 || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                CLI::write("Slug '{$slug}' tidak valid untuk {$label} '{$name}' — dilewati.", 'red');
                continue;
            }

            // Reuse pass 1: exact slug match (the uniqueness key the admin
            // workflow enforces).
            $existing = $model->where('slug', $slug)->first();

            // Reuse pass 2: same name (case-insensitive), e.g. someone stored
            // a different slug spelling for the same name earlier.
            if (! $existing) {
                foreach ($model->findAll() as $row) {
                    if (mb_strtolower(trim($row['name'])) === mb_strtolower($name)) {
                        $existing = $row;
                        break;
                    }
                }
            }

            if ($existing) {
                $reused[] = ['id' => (int) $existing['id'], 'name' => $existing['name'], 'slug' => $existing['slug']];
                continue;
            }

            $id = $model->insert(['name' => $name, 'slug' => $slug, 'description' => null], true);
            $created[] = ['id' => (int) $id, 'name' => $name, 'slug' => $slug];
        }

        foreach ($created as $c) {
            CLI::write("DIBUAT  {$label} id={$c['id']}  {$c['name']}  ({$c['slug']})", 'green');
        }
        foreach ($reused as $r) {
            CLI::write("PAKAI   {$label} id={$r['id']}  {$r['name']}  ({$r['slug']}) — sudah ada", 'yellow');
        }

        CLI::write("{$label}: " . count($created) . ' dibuat, ' . count($reused) . ' sudah ada. Tabel ' . $table . ' kini berisi ' . count($model->findAll()) . ' baris.', 'white');
    }
}

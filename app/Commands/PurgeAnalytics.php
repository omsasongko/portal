<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PageVisitModel;

/**
 * Delete old analytics records from the page_visits table.
 *
 * Usage:
 *   php spark analytics:purge              # deletes records older than 90 days (default)
 *   php spark analytics:purge --days=180   # deletes records older than 180 days
 *
 * Run this manually or schedule it via cron:
 *   0 3 * * 0  cd /path/to/portal && php spark analytics:purge --days=90
 *
 * Records are permanently deleted. This command does NOT run automatically
 * unless explicitly configured via cron or a task scheduler.
 */
class PurgeAnalytics extends BaseCommand
{
    protected $group       = 'Analytics';
    protected $name        = 'analytics:purge';
    protected $description = 'Delete page_visits records older than a configurable number of days.';
    protected $usage       = 'analytics:purge [--days=90]';
    protected $arguments   = [];
    protected $options     = [
        '--days' => 'Number of days to keep (default: 90). Records older than this are deleted.',
    ];

    public function run(array $params)
    {
        $days = (int) ($params['days'] ?? 90);
        if ($days < 1) {
            CLI::error('--days must be at least 1.');
            return;
        }

        $model   = new PageVisitModel();
        $deleted = $model->purgeOlderThan($days);

        CLI::write("Purged {$deleted} analytics record(s) older than {$days} days.", 'green');
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageVisitModel;
use App\Models\SettingModel;

class Analytics extends BaseController
{
    /**
     * Analytics display timezone.  Dates entered by the admin and all
     * timestamps shown on the dashboard use Asia/Jakarta (WIB, UTC+7).
     * Stored UTC values are never modified.
     */
    private const DISPLAY_TZ = 'Asia/Jakarta';

    public function index()
    {
        $settingModel = new SettingModel();
        $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
        $visitModel   = new PageVisitModel();

        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');

        // Default dates use the WIB calendar day, not UTC.
        $wibNow = new \DateTime('now', new \DateTimeZone(self::DISPLAY_TZ));

        if (! $from || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = (clone $wibNow)->modify('-30 days')->format('Y-m-d');
        }
        if (! $to || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $wibNow->format('Y-m-d');
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $totalVisits       = $visitModel->totalVisits($from, $to);
        $topPages          = $visitModel->topPages($from, $to, 15);
        $browserBreakdown  = $visitModel->browserBreakdown($from, $to);
        $deviceBreakdown   = $visitModel->deviceBreakdown($from, $to);
        $recentVisits      = $visitModel->recentVisits($from, $to, 50);
        $frequentIps       = $visitModel->frequentIps($from, $to, 20, 20);
        $dailyCounts       = $visitModel->dailyCounts($from, $to);

        return view('admin/analytics', [
            'title'            => 'Analitik',
            'identity'         => $identity,
            'from'             => $from,
            'to'               => $to,
            'totalVisits'      => $totalVisits,
            'topPages'         => $topPages,
            'browserBreakdown' => $browserBreakdown,
            'deviceBreakdown'  => $deviceBreakdown,
            'recentVisits'     => $recentVisits,
            'frequentIps'      => $frequentIps,
            'dailyCounts'      => $dailyCounts,
        ]);
    }
}

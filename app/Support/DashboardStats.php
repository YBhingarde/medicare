<?php
namespace App\Support;

class DashboardStats
{
    public const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /** Appointments per month for a year, split by status group. */
    public static function monthly($appts, int $year): array
    {
        $done = $open = $cancel = array_fill(0, 12, 0);
        foreach ($appts as $a) {
            if ($a->date->year !== $year) continue;
            $m = $a->date->month - 1;
            match ($a->status) {
                'completed' => $done[$m]++,
                'cancelled', 'expired' => $cancel[$m]++,
                default => $open[$m]++,
            };
        }
        return ['labels' => self::MONTHS, 'completed' => $done, 'open' => $open, 'cancelled' => $cancel];
    }

    public static function statusCounts($appts): array
    {
        $c = ['pending' => 0, 'confirmed' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'expired' => 0];
        foreach ($appts as $a) if (isset($c[$a->status])) $c[$a->status]++;
        return $c;
    }

    /** % change vs the previous period. */
    public static function pct(int $cur, int $prev): int
    {
        if ($prev === 0) return $cur > 0 ? 100 : 0;
        return (int) round(($cur - $prev) / $prev * 100);
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;

class BillingCalendar
{
    /**
     * أقرب تاريخ فوترة (يوم ثابت من الشهر الحالي أو التالي).
     */
    public static function nextBillingDate(Carbon $today, int $billingDay): Carbon
    {
        $billingDay = max(1, min(28, $billingDay));
        $candidate = $today->copy()->startOfMonth()->addDays($billingDay - 1);

        if ($today->lt($candidate)) {
            return $candidate;
        }

        if ($today->isSameDay($candidate)) {
            return $candidate;
        }

        return $today->copy()->addMonth()->startOfMonth()->addDays($billingDay - 1);
    }

    /**
     * عدد الأيام حتى يوم الفوترة القادم (0 = اليوم هو يوم الفوترة).
     */
    public static function daysUntilNextBilling(Carbon $today, int $billingDay): int
    {
        $next = self::nextBillingDate($today->copy(), $billingDay);

        if ($today->isSameDay($next)) {
            return 0;
        }

        return (int) $today->copy()->startOfDay()->diffInDays($next->copy()->startOfDay());
    }

    /**
     * كل تواريخ الفوترة من بداية الاشتراك حتى تاريخ محدّد (شامل).
     *
     * @return list<Carbon>
     */
    public static function billingDatesThrough(Carbon $from, Carbon $through, int $billingDay): array
    {
        $billingDay = max(1, min(28, $billingDay));
        $through = $through->copy()->startOfDay();
        $dates = [];

        $cursor = self::nextBillingDate($from->copy()->startOfDay()->subDay(), $billingDay);

        while ($cursor->lte($through)) {
            $dates[] = $cursor->copy();
            $cursor = self::nextBillingDate($cursor->copy()->addDay(), $billingDay);
        }

        return $dates;
    }

    /**
     * تاريخ الفوترة في شهر التاريخ المرجعي (مثلاً 5/8 عند billingDay=5).
     */
    public static function billingDateForMonth(Carbon $reference, int $billingDay): Carbon
    {
        $billingDay = max(1, min(28, $billingDay));

        return $reference->copy()->startOfMonth()->addDays($billingDay - 1);
    }
}

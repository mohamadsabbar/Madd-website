<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="olivia-stat-card olivia-stat-card--indigo">
            <div class="label">المشتركين</div>
            <div class="value">{{ $subscribersCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="olivia-stat-card olivia-stat-card--emerald">
            <div class="label">المتصلين الآن</div>
            <div class="value">{{ $onlineSubscribers }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="olivia-stat-card olivia-stat-card--amber">
            <div class="label">منتهي الاشتراك</div>
            <div class="value">{{ $expiredSubscribers }}</div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="olivia-stat-card olivia-stat-card--cyan">
            <div class="label">دخل اليوم</div>
            <div class="value">{{ number_format($todayIncome, 2) }} <small class="fs-6" style="-webkit-text-fill-color:#64748b;background:none;color:#64748b;">شيكل</small></div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="olivia-stat-card olivia-stat-card--violet">
            <div class="label">دخل الشهر</div>
            <div class="value">{{ number_format($monthIncome, 2) }} <small class="fs-6" style="-webkit-text-fill-color:#64748b;background:none;color:#64748b;">شيكل</small></div>
        </div>
    </div>
</div>

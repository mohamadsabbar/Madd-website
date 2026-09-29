<x-layouts.glass-app :title="'الراوترات (MikroTik)'" :autoRefreshSeconds="(int) config('mikrotik.admin_ui_refresh_seconds', 0)">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:14px;">{{ session('error') }}</div>
    @endif

    <div class="dg-greet mb-4">
        <h1>راوترات MikroTik</h1>
        <p>أضف بيانات الـ API (المنفذ 8728 عادةً)، ثم اختبر الاتصال. مع تفعيل الجدولة على الخادم (<code>schedule:run</code> كل دقيقة) تُحدَّث الجلسات و«متصل» تلقائياً دون الضغط على «مزامنة». زر المزامنة يبقى لتحديث فوري يدوي.</p>
    </div>

    <div class="dg-card mb-4">
        <strong class="d-block mb-3" style="font-size:0.95rem;">إضافة راوتر</strong>
        <form method="post" action="{{ route('web.admin.routers.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label small text-muted">الاسم</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">IP / Host</label>
                <input type="text" name="host" class="form-control" required value="{{ old('host') }}" placeholder="192.168.88.1">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">منفذ API</label>
                <input type="number" name="api_port" class="form-control" value="{{ old('api_port', 8728) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">نشط</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="r_active">
                    <label class="form-check-label" for="r_active">نعم</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">مستخدم API</label>
                <input type="text" name="username" class="form-control" required value="{{ old('username') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">الموقع (اختياري)</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-dark w-100" style="border-radius:12px;">حفظ</button>
            </div>
        </form>
    </div>

    <div class="dg-table-wrap">
        <div class="table-responsive">
            <table class="dg-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>Host</th>
                        <th>المنفذ</th>
                        <th>المستخدم</th>
                        <th>الموقع</th>
                        <th>نشط</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $r)
                        <tr>
                            <td class="fw-bold">{{ $r->name }}</td>
                            <td><code>{{ $r->host }}</code></td>
                            <td>{{ $r->api_port }}</td>
                            <td>{{ $r->username }}</td>
                            <td>{{ $r->location ?? '—' }}</td>
                            <td>
                                @if($r->is_active)
                                    <span class="dg-badge dg-badge--done">نعم</span>
                                @else
                                    <span class="dg-badge dg-badge--wait">لا</span>
                                @endif
                            </td>
                            <td style="min-width: 280px;">
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <form method="post" action="{{ route('web.admin.routers.test', $r) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:10px;">اختبار</button>
                                    </form>
                                    <form method="post" action="{{ route('web.admin.routers.sync', $r) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">مزامنة</button>
                                    </form>
                                    <form method="post" action="{{ route('web.admin.routers.sync-subscriber-plans', $r) }}" class="d-inline" title="تحديث باقة كل مشترك من عمود profile على المايكروتيك">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" style="border-radius:10px;">مزامنة باقات المشتركين</button>
                                    </form>
                                    <a href="{{ route('web.admin.routers.ppp', $r) }}" class="btn btn-sm btn-outline-dark" style="border-radius:10px;">تحليل PPP</a>
                                    <form method="post" action="{{ route('web.admin.routers.destroy', $r) }}" class="d-inline" onsubmit="return confirm('حذف هذا الراوتر؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:10px;">حذف</button>
                                    </form>
                                </div>
                                <details>
                                    <summary class="btn btn-sm btn-outline-dark" style="border-radius:10px; list-style:none; cursor:pointer; width:auto;">تعديل</summary>
                                    <div class="mt-2 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                        <form method="post" action="{{ route('web.admin.routers.update', $r) }}" class="row g-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-3">
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $r->name }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="host" class="form-control form-control-sm" value="{{ $r->host }}" required>
                                            </div>
                                            <div class="col-md-1">
                                                <input type="number" name="api_port" class="form-control form-control-sm" value="{{ $r->api_port }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="username" class="form-control form-control-sm" value="{{ $r->username }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="password" name="password" class="form-control form-control-sm" placeholder="كلمة مرور جديدة (اختياري)">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="location" class="form-control form-control-sm" value="{{ $r->location }}" placeholder="الموقع">
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act{{ $r->id }}" {{ $r->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="act{{ $r->id }}">نشط</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-sm btn-primary w-100">حفظ</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">لا يوجد راوترات بعد — أضف واحداً أعلاه.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted mt-3 mb-0">
        يتطلب PHP تفعيل <code>extension=sockets</code> لاتصال RouterOS API. عرّف في كل باقة حقل <code>mikrotik_profile</code> ليطابق اسم ملف تعريف PPPoE في الراوتر (أو استخدم الافتراضي من <code>MIKROTIK_DEFAULT_PPP_PROFILE</code> في <code>.env</code>).
    </p>
</x-layouts.glass-app>

@extends('layouts.app')

@section('content')
<div class="page-frame mx-auto py-3">

    {{-- Header / actions --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
        <h2 class="h5 fw-bold mb-0 text-center text-md-start"><i class="bi bi-people"></i> المديونين (لي عندهم فلوس)</h2>

        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end align-items-center">
            <button type="button" id="toggleSearchBtn" class="btn-action outline-info btn-compact" title="عرض/إخفاء البحث">
                <i class="bi bi-search"></i> بحث
            </button>

            <button type="button" class="btn-action primary" id="openAddDebtorBtn" data-bs-toggle="modal" data-bs-target="#debtorModal">
                <i class="bi bi-plus-circle"></i> إضافة مديون
            </button>

            <a href="{{ route('my_debtors.index') }}" class="btn-action outline-info">
                <i class="bi bi-arrow-clockwise"></i> إعادة تحميل
            </a>

            <div class="d-flex align-items-center gap-2">
                <label class="form-check form-check-inline mb-0 align-items-center">
                    <input class="form-check-input" type="checkbox" id="exportPerPayment" aria-label="Each payment as row">
                    <span class="form-check-label small ms-1">كل دفعة صف</span>
                </label>

                @php
                    $csvUrl = Route::has('my_debtors.export') ? route('my_debtors.export', request()->all()) : '#';
                    $pdfUrl = '#';
                    try {
                        if(Route::has('my_debtors.exportPdfAll')) $pdfUrl = route('my_debtors.exportPdfAll', request()->all());
                        elseif(Route::has('my_debtors.exportPdf')) $pdfUrl = route('my_debtors.exportPdf', request()->all());
                    } catch (\Throwable $e) { $pdfUrl = '#'; }
                @endphp

                <a id="exportCsvBtn" data-base="{{ $csvUrl }}" href="{{ $csvUrl }}" class="btn-action outline-info {{ $csvUrl === '#' ? 'disabled' : '' }}">
                    <i class="bi bi-file-earmark-excel"></i> CSV
                </a>

                <a id="exportPdfBtn" data-base="{{ $pdfUrl }}" href="{{ $pdfUrl }}" class="btn-action outline-danger {{ $pdfUrl === '#' ? 'disabled' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>

                @if(Route::has('my_debtors.bulkDestroy'))
                    <form id="bulkDeleteForm" action="{{ route('my_debtors.bulkDestroy') }}" method="POST" class="d-inline ms-2" onsubmit="return confirmBulkDelete(event)">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="ids[]" id="bulk_ids_holder" value="">
                        <button type="submit" id="bulkDeleteBtn" class="btn-action outline-danger" disabled>
                            <i class="bi bi-trash"></i> حذف المحدد
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @php $total = $debtors->sum('amount'); @endphp
    <div class="mb-3 small text-muted">إجمالي الديون ليك: <span class="fw-bold">{{ number_format($total, 2) }} جنيه</span></div>

    @if(session('success'))
        <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif

    {{-- Search area (hidden by default) --}}
    <div id="searchArea" class="search-area card card-body mb-4" aria-hidden="true" style="display:none;">
        <form method="GET" action="{{ route('my_debtors.index') }}" class="row g-2">
            <div class="col-12 col-sm-6 col-md-3">
                <input type="text" name="debtor_name" class="form-control form-control-sm" placeholder="بحث باسم المديون..." value="{{ request('debtor_name') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="المبلغ" value="{{ request('amount') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">كل الحالات</option>
                    <option value="pending" @if(request('status')=='pending') selected @endif>قيد الانتظار</option>
                    <option value="paid" @if(request('status')=='paid') selected @endif>مدفوع</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ request('due_date') }}">
            </div>
            <div class="col-6 col-sm-2 col-md-3 d-grid">
                <button type="submit" class="btn-action primary w-100"><i class="bi bi-search"></i> بحث</button>
            </div>
        </form>
    </div>

    <!-- Modals and tables (unchanged structure) -->
    {{-- debtorModal, showDebtorModal, table etc. --}} 

    {{-- Modal add/edit/pay debtor --}}
    <div class="modal fade" id="debtorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> إدارة المديون</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>

                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="debtorTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="add-debtor-tab" data-bs-toggle="tab" data-bs-target="#add-debtor" type="button">إضافة</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="edit-debtor-tab" data-bs-toggle="tab" data-bs-target="#edit-debtor" type="button">تعديل</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="pay-debtor-tab" data-bs-toggle="tab" data-bs-target="#pay-debtor" type="button">دفع</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Add --}}
                        <div class="tab-pane fade show active" id="add-debtor">
                            <form method="POST" action="{{ route('my_debtors.store') }}" id="addDebtorForm" autocomplete="off" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-bold">اسم المديون</label>
                                    <input type="text" name="debtor_name" class="form-control" required value="{{ old('debtor_name') }}">
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">المبلغ</label>
                                        <input type="number" step="0.01" min="0" name="amount" class="form-control" required value="{{ old('amount') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', \Carbon\Carbon::now()->toDateString()) }}">
                                    </div>
                                </div>

                                <div class="mb-2 mt-2">
                                    <label class="form-label fw-bold">الحالة</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                        <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>مدفوع</option>
                                    </select>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">العنوان</label>
                                        <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">رقم الهاتف</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                                    </div>
                                </div>

                                <div class="mb-2 mt-2">
                                    <label class="form-label fw-bold">مرفق (اختياري)</label>
                                    <input type="file" name="attachment" id="add_attachment_input" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="form-control form-control-sm">
                                    <div id="add_attachment_preview" class="mt-2 small text-muted"></div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold">ملاحظات</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                </div>
                            </form>
                        </div>

                        {{-- Edit --}}
                        <div class="tab-pane fade" id="edit-debtor">
                            <div class="alert alert-info" id="editDebtorInfo">اضغط "تعديل" على السطر لملء النموذج هنا.</div>
                            <form method="POST" id="editDebtorForm" style="display:none;" autocomplete="off" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <input type="hidden" name="id" id="edit_debtor_id">

                                <div class="mb-2">
                                    <label class="form-label fw-bold">اسم المديون</label>
                                    <input type="text" name="debtor_name" id="edit_debtor_name" class="form-control" required>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">المبلغ</label>
                                        <input type="number" step="0.01" min="0" name="amount" id="edit_amount" class="form-control" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                                        <input type="date" name="due_date" id="edit_due_date" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-2 mt-2">
                                    <label class="form-label fw-bold">الحالة</label>
                                    <select name="status" id="edit_status" class="form-select">
                                        <option value="pending">قيد الانتظار</option>
                                        <option value="paid">مدفوع</option>
                                    </select>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">العنوان</label>
                                        <input type="text" name="address" id="edit_address" class="form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">رقم الهاتف</label>
                                        <input type="text" name="phone" id="edit_phone" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-2 mt-2">
                                    <label class="form-label fw-bold d-block">المرفق الحالي</label>
                                    <div id="edit_current_attachment" class="mb-2"><span class="text-muted small">لا يوجد مرفق</span></div>

                                    <div class="form-check mb-2" id="edit_remove_attachment_wrap" style="display:none;">
                                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="edit_remove_attachment">
                                        <label class="form-check-label small" for="edit_remove_attachment">حذف المرفق الحالي</label>
                                    </div>

                                    <label class="form-label fw-bold">مرفق (استبدال)</label>
                                    <input type="file" name="attachment" id="edit_attachment_input" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="form-control form-control-sm">
                                    <div id="edit_attachment_preview" class="mt-2 small text-muted"></div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold">ملاحظات</label>
                                    <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                                </div>
                            </form>
                        </div>

                        {{-- Pay --}}
                        <div class="tab-pane fade" id="pay-debtor">
                            <div class="alert alert-info" id="payDebtorInfo">اضغط "دفع" على السطر لفتح نموذج الدفع هنا.</div>
                            <form method="POST" id="payDebtorForm" style="display:none;" autocomplete="off">
                                @csrf
                                <input type="hidden" name="debtor_id" id="pay_debtor_id">
                                <div class="mb-2">
                                    <label class="form-label fw-bold">اسم الدافع</label>
                                    <input type="text" name="payer_name" id="pay_payer_name" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">المبلغ المتبقي</label>
                                    <input type="text" id="pay_remaining_display" class="form-control text-success fw-bold" disabled>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">قيمة الدفع</label>
                                    <input type="number" name="amount" id="pay_amount" class="form-control" step="0.01" min="0.01" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">تاريخ الدفع</label>
                                    <input type="date" name="payment_date" id="pay_payment_date" class="form-control" value="{{ \Carbon\Carbon::now()->toDateString() }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">ملاحظات</label>
                                    <textarea name="notes" id="pay_notes" class="form-control" rows="2"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-success" form="addDebtorForm" id="addDebtorSubmitBtn"><i class="bi bi-plus-circle"></i> إضافة</button>
                    <button type="submit" class="btn btn-warning d-none" form="editDebtorForm" id="editDebtorSubmitBtn"><i class="bi bi-save"></i> حفظ</button>
                    <button type="button" class="btn btn-success d-none" id="payDebtorSubmitBtn"><i class="bi bi-cash"></i> دفع</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Show Debtor Modal --}}
    <div class="modal fade" id="showDebtorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-person-lines-fill"></i> تفاصيل المديون</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>

                <div class="modal-body" id="showDebtorBody">
                    <div class="p-3 border rounded-3">
                        <h6 class="mb-3 fw-bold">تفاصيل كاملة</h6>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2">
                                <div class="p-2 border-bottom"><strong>الاسم:</strong> <span id="show_debtor_name"></span></div>
                                <div class="p-2 border-bottom"><strong>العنوان:</strong> <span id="show_debtor_address"></span></div>
                                <div class="p-2 border-bottom"><strong>الهاتف:</strong> <span id="show_debtor_phone"></span></div>
                                <div class="p-2 border-bottom"><strong>المبلغ:</strong> <span id="show_debtor_amount"></span></div>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <div class="p-2 border-bottom"><strong>المدفوع:</strong> <span id="show_debtor_paid"></span></div>
                                <div class="p-2 border-bottom"><strong>المتبقي:</strong> <span id='show_debtor_remaining'></span></div>
                                <div class="p-2 border-bottom"><strong>الاستحقاق:</strong> <span id="show_debtor_due_date"></span></div>
                                <div class="p-2 border-bottom"><strong>الحالة:</strong> <span id="show_debtor_status"></span></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6 class="fw-bold">ملاحظات</h6>
                            <div id="show_debtor_notes" class="text-muted p-2 border rounded"></div>
                        </div>

                        <div class="mt-3">
                            <h6 class="fw-bold d-flex justify-content-between align-items-center">
                                <span>المرفق</span>
                                <div>
                                    
                                    <a id="show_debtor_download" class="btn btn-sm btn-outline-success" target="_blank" style="display:none;"><i class="bi bi-download"></i> تحميل</a>
                                </div>
                            </h6>
                            <div id="show_debtor_attachment_area" class="p-2"></div>
                        </div>

                        <div class="mt-3">
                            <h6 class="fw-bold">سجل الدفعات</h6>
                            <div class="table-responsive" style="max-height: 50vh; overflow:auto;">
                                <table class="table table-striped table-sm table-bordered mb-0" id="show_payments_table">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>#</th>
                                            <th>الدافع</th>
                                            <th>المبلغ</th>
                                            <th>تاريخ الدفع</th>
                                            <th>ملاحظات</th>
                                        </tr>
                                    </thead>
                                    <tbody id="show_debtor_payments_body">
                                        {{-- filled by JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" id="show_debtor_export" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> تصدير PDF</a>

                    <form id="show_delete_attachment_form" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                        
                    </form>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

     {{-- Desktop table (show on large screens only) --}}
    <div class="card shadow-sm d-none d-lg-block mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle text-center mb-0 table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAllRows" aria-label="تحديد الكل"></th>
                            <th style="width:50px">#</th>
                            <th class="text-start">اسم المديون</th>
                            <th>العنوان</th>
                            <th>الهاتف</th>
                            <th>مرفق</th>
                            <th>المبلغ</th>
                            <th>تم دفع</th>
                            <th>المتبقي</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th class="text-start">ملاحظات</th>
                            <th>عرض</th>
                            <th>PDF فردي</th>
                            <th>التقويم</th>
                            <th>أوامر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debtors as $i => $debtor)
                        <tr>
                            <td class="checkbox-cell"><input type="checkbox" class="row-checkbox" value="{{ $debtor->id }}"></td>
                            <td data-label="#" class="mono">{{ ($debtors->firstItem() ?? 0) + $i }}</td>
                            <td data-label="اسم المديون" class="text-start text-truncate-custom" style="max-width:150px;">{{ $debtor->debtor_name }}</td>
                            <td data-label="العنوان" class="text-start text-truncate-custom" style="max-width:150px;">{{ $debtor->address ?? '-' }}</td>
                            <td data-label="الهاتف" class="text-center text-truncate-custom" style="max-width:120px;">{{ $debtor->phone ?? '-' }}</td>

                            <td data-label="مرفق" class="text-center">
                                @if(!empty($debtor->attachment))
                                    @php $ext = strtolower(pathinfo($debtor->attachment, PATHINFO_EXTENSION) ?? ''); $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']); @endphp
                                    @if($isImage)
                                        <a href="{{ asset('/storage_link/'.$debtor->attachment) }}" target="_blank"><img src="{{ asset('/storage_link/'.$debtor->attachment) }}" alt="att" style="height:40px;max-width:90px;object-fit:cover;border-radius:4px;"></a>
                                    @else
                                        <a href="{{ asset('/storage_link/'.$debtor->attachment) }}" target="_blank" class="btn-action outline-info btn-compact"><i class="bi bi-paperclip"></i> مرفق</a>
                                    @endif
                                @else
                                    <span class="text-muted small">لا يوجد</span>
                                @endif
                            </td>

                            <td data-label="المبلغ" class="text-nowrap text-success mono">{{ number_format($debtor->amount, 2) }}</td>
                            <td data-label="تم دفع" class="text-nowrap text-success mono">{{ number_format(method_exists($debtor,'paidAmount') ? $debtor->paidAmount() : ($debtor->paid_amount ?? 0), 2) }}</td>
                            <td data-label="المتبقي" class="text-nowrap text-danger mono">{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0, ($debtor->amount - ($debtor->paid_amount ?? 0))), 2) }}</td>
                            <td data-label="تاريخ الاستحقاق" class="text-nowrap">{{ $debtor->due_date }}</td>
                            <td data-label="الحالة">
                                @if($debtor->status == 'paid')
                                    <span class="badge bg-success">مدفوع</span>
                                @else
                                    <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                @endif
                            </td>
                            <td data-label="ملاحظات" class="text-start text-truncate-custom" style="max-width:200px;">{{ Str::limit($debtor->notes, 100) }}</td>

                            <td data-label="عرض" class="actions-cell">
                                <button type="button" class="btn-action outline-info btn-compact btn-show-debtor"
                                    title="عرض"
                                    data-id="{{ $debtor->id }}"
                                    data-debtor_name="{{ $debtor->debtor_name }}"
                                    data-address="{{ $debtor->address }}"
                                    data-phone="{{ $debtor->phone }}"
                                    data-amount="{{ number_format($debtor->amount, 2, '.', '') }}"
                                    data-paid="{{ number_format(method_exists($debtor,'paidAmount') ? $debtor->paidAmount() : ($debtor->paid_amount ?? 0), 2, '.', '') }}"
                                    data-remaining="{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0, ($debtor->amount - ($debtor->paid_amount ?? 0))), 2, '.', '') }}"
                                    data-due_date="{{ $debtor->due_date }}"
                                    data-status="{{ $debtor->status }}"
                                    data-notes="{{ $debtor->notes }}"
                                    data-attachment="{{ $debtor->attachment }}"
                                    data-export-url="{{ route('my_debtors.exportPdf', ['id' => $debtor->id]) }}">
                                    <i class="bi bi-eye"></i> عرض
                                </button>
                            </td>

                            <td data-label="PDF" class="actions-cell">
                                <a href="{{ route('my_debtors.exportPdf', ['id' => $debtor->id]) }}" target="_blank" class="btn-action outline-danger btn-compact"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                            </td>

                            <td data-label="التقويم" class="actions-cell">
                                @if(auth()->user()->google_token ?? false)
                                    <form action="{{ route('debtors.addToGoogle', $debtor->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn-action outline-success btn-compact"><i class="bi bi-calendar-plus"></i> تقويم</button>
                                    </form>
                                @else
                                    <a href="{{ route('google.redirect') }}" class="btn-action outline-info btn-compact"><i class="bi bi-google"></i> جوجل</a>
                                @endif
                            </td>

                            <td data-label="أوامر" class="actions-cell">
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    @if($debtor->status != 'paid')
                                        <button type="button" class="btn-action primary btn-compact btn-pay-debtor" data-id="{{ $debtor->id }}" data-update-url="{{ Route::has('my_debtors.pay.submit') ? route('my_debtors.pay.submit', $debtor->id) : '#' }}" data-amount="{{ number_format($debtor->amount, 2, '.', '') }}" data-remaining="{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0,$debtor->amount - ($debtor->paid_amount ?? 0)), 2) }}"><i class="bi bi-cash"></i> دفع</button>
                                    @endif

                                    <button type="button" class="btn-action primary btn-compact btn-edit-debtor" title="تعديل" data-id="{{ $debtor->id }}" data-debtor_name="{{ $debtor->debtor_name }}" data-address="{{ $debtor->address }}" data-phone="{{ $debtor->phone }}" data-amount="{{ $debtor->amount }}" data-due_date="{{ $debtor->due_date ? \Carbon\Carbon::parse($debtor->due_date)->toDateString() : '' }}" data-status="{{ $debtor->status }}" data-notes="{{ $debtor->notes }}" data-attachment="{{ $debtor->attachment }}" data-update-url="{{ route('my_debtors.update', $debtor->id) }}"><i class="bi bi-pencil-square"></i> تعديل</button>

                                    <form action="{{ route('my_debtors.destroy', $debtor->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟')" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn-action outline-danger btn-compact"><i class="bi bi-trash"></i> حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- hidden JSON row for JS to read details if needed --}}
                        <tr id="details-{{ $debtor->id }}" class="d-none">
                            <td colspan="16">
                                <div class="details-json" data-payments='@json($debtor->payments ?? [])' data-attachment='{{ $debtor->attachment }}'></div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="16" class="text-center">لا يوجد مديونين</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div>{{ $debtors->links() }}</div>
                <div class="text-muted small">إجمالي الصفحة: {{ number_format($debtors->sum('amount') ?? 0, 2) }} ج.م</div>
            </div>
        </div>
    </div>

    {{-- Mobile/cards view (show on lg and below) --}}
    <div class="d-block d-lg-none mt-3 p-3 app-mobile-view">
        <div class="row g-3">
            @forelse($debtors as $debtor)
            <div class="col-12">
                <div class="card mobile-debtor-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 text-truncate-custom">{{ $debtor->debtor_name }}</h5>
                                <div class="small muted">ملاحظات: {{ Str::limit($debtor->notes, 60) }}</div>
                            </div>
                            <div class="text-end">
                                <div class="h6 mb-1 text-success mono">{{ number_format($debtor->amount, 2) }} ج.م</div>
                                <div>
                                    <span class="badge {{ $debtor->status == 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $debtor->status == 'paid' ? 'مدفوع' : 'قيد الانتظار' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="row g-1">
                                <div class="col-12 p-2 border rounded mb-1">
                                    <strong class="d-block text-muted small">الهاتف</strong>
                                    <div class="text-dark">{{ $debtor->phone ?? '-' }}</div>
                                </div>

                                <div class="col-12 p-2 border rounded mb-1">
                                    <strong class="d-block text-muted small">العنوان</strong>
                                    <div class="text-dark">{{ $debtor->address ?? '-' }}</div>
                                </div>

                                <div class="col-12 p-2 border rounded mb-1">
                                    <strong class="d-block text-muted small">تاريخ الاستحقاق</strong>
                                    <div class="text-dark">{{ $debtor->due_date ?? '-' }}</div>
                                </div>

                                <div class="col-12 p-2 border rounded mb-1">
                                    <strong class="d-block text-muted small">المدفوع</strong>
                                    <div class="text-dark text-success">{{ number_format(method_exists($debtor,'paidAmount') ? $debtor->paidAmount() : ($debtor->paid_amount ?? 0), 2) }}</div>
                                </div>

                                <div class="col-12 p-2 border rounded mb-1">
                                    <strong class="d-block text-muted small">المتبقي</strong>
                                    <div class="text-dark text-danger">{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0, ($debtor->amount - ($debtor->paid_amount ?? 0))), 2) }}</div>
                                </div>
                            </div>
                        </div>

                        @if(!empty($debtor->attachment))
                        @php $ext = strtolower(pathinfo($debtor->attachment, PATHINFO_EXTENSION) ?? ''); $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']); @endphp
                        <div class="mb-3">
                            <strong class="d-block text-muted small mb-2">المرفق</strong>
                            <div class="text-center">
                                @if($isImage)
                                    <a href="{{ asset('/storage_link/'.$debtor->attachment) }}" target="_blank">
                                        <img src="{{ asset('/storage_link/'.$debtor->attachment) }}" alt="مرفق" class="img-fluid rounded shadow-sm" style="max-height:180px; object-fit:cover;">
                                    </a>
                                @else
                                    <a href="{{ asset('/storage_link/'.$debtor->attachment) }}" target="_blank" class="btn-action outline-info w-100"><i class="bi bi-paperclip"></i> عرض المرفق</a>
                                @endif
                            </div>
                        </div>
                        @endif

                        <div class="d-grid gap-2 mobile-action-buttons">
                            @if($debtor->status != 'paid')
                                <button class="btn-action primary w-100 btn-pay-debtor" data-id="{{ $debtor->id }}" data-update-url="{{ Route::has('my_debtors.pay.submit') ? route('my_debtors.pay.submit', $debtor->id) : '#' }}" data-amount="{{ number_format($debtor->amount, 2, '.', '') }}" data-remaining="{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0,$debtor->amount - ($debtor->paid_amount ?? 0)), 2) }}">
                                    <i class="bi bi-cash"></i> دفع
                                </button>
                            @endif

                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn-action outline-info w-100 btn-show-debtor"
                                        data-id="{{ $debtor->id }}"
                                        data-debtor_name="{{ $debtor->debtor_name }}"
                                        data-address="{{ $debtor->address }}"
                                        data-phone="{{ $debtor->phone }}"
                                        data-amount="{{ number_format($debtor->amount, 2, '.', '') }}"
                                        data-paid="{{ number_format(method_exists($debtor,'paidAmount') ? $debtor->paidAmount() : ($debtor->paid_amount ?? 0), 2, '.', '') }}"
                                        data-remaining="{{ number_format(method_exists($debtor,'remainingAmount') ? $debtor->remainingAmount() : max(0, ($debtor->amount - ($debtor->paid_amount ?? 0))), 2, '.', '') }}"
                                        data-due_date="{{ $debtor->due_date }}"
                                        data-status="{{ $debtor->status }}"
                                        data-notes="{{ $debtor->notes }}"
                                        data-attachment="{{ $debtor->attachment }}"
                                        data-export-url="{{ route('my_debtors.exportPdf', ['id' => $debtor->id]) }}"
                                    ><i class="bi bi-eye"></i> عرض</button>
                                </div>

                                <div class="col-6">
                                    <a href="{{ route('my_debtors.exportPdf', ['id' => $debtor->id]) }}" class="btn-action outline-danger w-100" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                </div>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <button class="btn-action primary w-100 btn-edit-debtor" data-id="{{ $debtor->id }}" data-debtor_name="{{ $debtor->debtor_name }}" data-address="{{ $debtor->address }}" data-phone="{{ $debtor->phone }}" data-amount="{{ $debtor->amount }}" data-due_date="{{ $debtor->due_date ? \Carbon\Carbon::parse($debtor->due_date)->toDateString() : '' }}" data-status="{{ $debtor->status }}" data-notes="{{ $debtor->notes }}" data-attachment="{{ $debtor->attachment }}" data-update-url="{{ route('my_debtors.update', $debtor->id) }}"><i class="bi bi-pencil-square"></i> تعديل</button>
                                </div>

                                <div class="col-6">
                                    <form action="{{ route('my_debtors.destroy', $debtor->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟')" class="w-100">
                                        @csrf @method('DELETE')
                                        <button class="btn-action outline-danger w-100"><i class="bi bi-trash"></i> حذف</button>
                                    </form>
                                </div>
                            </div>

                            @if(auth()->user()->google_token ?? false)
                                <form action="{{ route('debtors.addToGoogle', $debtor->id) }}" method="POST" class="d-grid mt-2">
                                    @csrf
                                    <button class="btn-action outline-success w-100"><i class="bi bi-calendar-plus"></i> إضافة لتقويم جوجل</button>
                                </form>
                            @else
                                <a href="{{ route('google.redirect') }}" class="btn-action outline-info w-100 mt-2"><i class="bi bi-google"></i> ربط مع جوجل</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted p-4">لا يوجد مديونون لعرضهم.</div>
            @endforelse
        </div>

        @if ($debtors->hasPages() || $debtors->isNotEmpty())
        <div class="d-flex justify-content-center mt-3">
            {{ $debtors->links() }}
        </div>
        <div class="text-center text-muted small mt-2">إجمالي الصفحة: {{ number_format($debtors->sum('amount') ?? 0, 2) }} ج.م</div>
        @endif
    </div>

</div>
{{-- Styles (exact selectors you provided with CSS variable to control page width) --}}
<style>
:root{
  --primary:#0d6efd; --primary-dark:#0b5ed7; --muted:#6c757d; --card-bg:#ffffff;
  --shadow:0 6px 18px rgba(15,23,42,0.06); --btn-h:44px; --page-frame-width:1800px;
  --light-bg:#f8fafc; --light-border:#eef2f7;
}
.page-frame { max-width: var(--page-frame-width); width:100%; margin:0 auto; box-sizing:border-box; padding:0 12px; }

/* Unified action buttons with clear outline variants */
.btn-action{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.45rem 1rem; min-height:var(--btn-h); height:var(--btn-h); font-weight:600; border-radius:.5rem; font-size:.95rem; line-height:1; box-sizing:border-box; }
.btn-action i{ font-size:1.15rem; }
.btn-action.primary{ background:var(--primary); color:#fff; border:1px solid var(--primary); }
.btn-action.primary:hover{ background:var(--primary-dark); border-color:var(--primary-dark); color:#fff; }
.btn-action.outline-info{ color:var(--primary); border:1px solid var(--primary); background:transparent; }
.btn-action.outline-info:hover{ background: rgba(13,110,253,0.06); }
.btn-action.outline-danger{ color:#dc3545; border:1px solid #dc3545; background:transparent; }
.btn-action.outline-danger:hover{ background: rgba(220,53,69,0.06); }
.btn-action.outline-success{ color:#28a745; border:1px solid #28a745; background:transparent; }
.btn-compact{ padding:.32rem .6rem; font-size:.92rem; min-height:38px; height:auto; }

/* table responsiveness and truncation */
.table-responsive{ -webkit-overflow-scrolling: touch; overflow-x:auto; }
.table .text-truncate-custom{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* stacked rows for screens below lg (tablet & mobile & ipad) */
@media (max-width: 991.98px){
  .table-responsive table thead{ display:none; }
  .table-responsive table tbody, .table-responsive table tr{ display:block; width:100%; }
  .table-responsive table tbody tr{ background: var(--card-bg); margin-bottom:12px; border-radius:10px; box-shadow:var(--shadow); padding:12px; border:1px solid rgba(0,0,0,0.03); }
  .table-responsive table tbody tr td{ display:flex; justify-content:space-between; align-items:center; padding:8px 10px; border:none; border-bottom:1px dashed rgba(0,0,0,0.04); width:100%; }
  .table-responsive table tbody tr td:last-child{ border-bottom:none; }
  .table-responsive table tbody tr td[data-label]::before{ content: attr(data-label); font-weight:700; color:var(--muted); display:block; margin-bottom:6px; }
  .table-responsive table tbody tr td.checkbox-cell{ width:48px; display:flex; justify-content:flex-start; align-items:center; padding-left:0; }
  .table-responsive table tbody tr td.actions-cell{ display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
}

/* iPhone specific small tweaks */
@media only screen and (max-width:420px){
  :root{ --btn-h:48px; }
  .mobile-debtor-card .card-title{ font-size:1rem; }
  .mobile-action-buttons .btn-action{ font-size:1rem; padding:.6rem .8rem; height:48px; }
}

/* iPad / tablets adjustments (768 - 1024) */
@media only screen and (min-width:768px) and (max-width:1024px){
  :root{ --btn-h:46px; }
  .page-frame{ padding:0 8px; }
  /* Slightly larger card shadows and spacing for iPad */
  .mobile-debtor-card{ box-shadow:0 6px 18px rgba(0,0,0,0.06); padding:14px; }
  .table-responsive table tbody tr{ padding:14px; }
  .btn-action{ min-height:46px; }
}

/* mobile card visuals */
.app-mobile-view{ background-color:var(--light-bg); padding-top:1rem; padding-bottom:1rem; }
.mobile-debtor-card{ border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.06); background:var(--card-bg); border:none; }
.mobile-debtor-card .card-body{ padding:15px; }
.mobile-debtor-card .card-title{ font-size:1.05rem; font-weight:700; color:var(--primary); line-height:1.25; }
.mobile-debtor-card .small{ color:var(--muted); }

.search-area[aria-hidden="true"]{ display:none; }
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Helpers ---
    const $id = id => document.getElementById(id) || null;
    const qsa = sel => Array.from(document.querySelectorAll(sel));
    const csrfToken = () => {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    };
    const safeDataset = el => (el && el.dataset) ? el.dataset : {};

    // --- Central modal (processing / success / error) ---
    function showCenterMessage(message, { type = 'info', autoClose = 2500 } = {}) {
        if (!message) return;
        const id = 'centerMessageModal';
        const existing = document.getElementById(id);
        if (existing) existing.remove();

        const iconClass = type === 'success' ? 'bi-check-circle-fill text-success'
            : type === 'danger' ? 'bi-x-circle-fill text-danger'
            : type === 'warning' ? 'bi-exclamation-triangle-fill text-warning'
            : 'bi-info-circle-fill text-primary';

        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-body py-4">
                        <div class="mb-2"><i class="bi ${iconClass}" style="font-size:2rem;"></i></div>
                        <div class="fs-6">${String(message)}</div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bs = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, keyboard: true });
        bs.show();
        if (autoClose && autoClose > 0) {
            setTimeout(() => { try { bs.hide(); } catch (e) {} }, autoClose);
        }
        modal.addEventListener('hidden.bs.modal', () => { try { modal.remove(); } catch (e) {} });
    }

    // --- Centered Confirm modal (returns Promise) ---
    function showConfirmModal(title, message) {
        return new Promise(resolve => {
            const id = 'centerConfirmModal';
            const old = document.getElementById(id);
            if (old) old.remove();

            const modal = document.createElement('div');
            modal.id = id;
            modal.className = 'modal fade';
            modal.tabIndex = -1;
            modal.setAttribute('aria-hidden', 'true');
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer justify-content-center border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-danger" id="${id}-confirm-btn">تأكيد</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const bs = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: true });
            bs.show();

            const confirmBtn = document.getElementById(`${id}-confirm-btn`);
            const cleanup = (v) => { try { bs.hide(); } catch (e) {} ; resolve(v); };

            confirmBtn.addEventListener('click', () => cleanup(true), { once: true });
            modal.querySelector('[data-bs-dismiss="modal"]')?.addEventListener('click', () => cleanup(false), { once: true });
            modal.addEventListener('hidden.bs.modal', () => { try { modal.remove(); } catch(e) {} });
        });
    }

    // --- Elements (define early and reuse) ---
    const addForm = $id('addDebtorForm');
    const editForm = $id('editDebtorForm');
    const payForm = $id('payDebtorForm');

    const addAttachmentInput = $id('add_attachment_input');
    const addAttachmentPreview = $id('add_attachment_preview');
    const editAttachmentInput = $id('edit_attachment_input');
    const editAttachmentPreview = $id('edit_attachment_preview');

    const editCurrentAttachment = $id('edit_current_attachment');
    const editRemoveWrap = $id('edit_remove_attachment_wrap');
    const editRemoveCheckbox = $id('edit_remove_attachment');

    const addSubmitBtn = $id('addDebtorSubmitBtn');
    const editSubmitBtn = $id('editDebtorSubmitBtn');
    const paySubmitBtn = $id('payDebtorSubmitBtn');
    const payDebtorFormElement = payForm; // alias

    // --- File preview helper ---
    function filePreview(inputEl, targetEl, maxHeight = 160) {
        if (!inputEl || !targetEl) return;
        const f = inputEl.files && inputEl.files[0];
        if (!f) { targetEl.innerHTML = ''; return; }
        if (/image\//.test(f.type)) {
            const img = document.createElement('img'); img.className = 'img-fluid rounded'; img.style.maxHeight = maxHeight + 'px';
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; targetEl.innerHTML = ''; targetEl.appendChild(img); };
            reader.readAsDataURL(f);
        } else {
            targetEl.textContent = f.name + ' — ' + Math.round(f.size / 1024) + 'KB';
        }
    }
    if (addAttachmentInput) addAttachmentInput.addEventListener('change', () => filePreview(addAttachmentInput, addAttachmentPreview, 160));
    if (editAttachmentInput) editAttachmentInput.addEventListener('change', () => { filePreview(editAttachmentInput, editAttachmentPreview, 160); if (editRemoveCheckbox) editRemoveCheckbox.checked = false; });

    // --- Utils ---
    function sanitizeNumberRaw(val) {
        if (val === null || val === undefined) return '';
        const s = String(val).trim();
        if (!s) return '';
        const cleaned = s.replace(/[٬, ]+/g, '').replace(/[^\d.\-]/g, '');
        const parts = cleaned.split('.');
        const normalized = parts.length > 1 ? parts.shift() + '.' + parts.join('') : parts[0];
        const n = Number(normalized);
        return Number.isFinite(n) ? n : '';
    }

    // --- Footer buttons per tab (rely on earlier variables) ---
    function setFooterButtonsForTab(tabId) {
        if (tabId === 'add-debtor') {
            addSubmitBtn?.classList.remove('d-none');
            editSubmitBtn?.classList.add('d-none');
            paySubmitBtn?.classList.add('d-none');
        } else if (tabId === 'edit-debtor') {
            addSubmitBtn?.classList.add('d-none');
            editSubmitBtn?.classList.remove('d-none');
            paySubmitBtn?.classList.add('d-none');
        } else if (tabId === 'pay-debtor') {
            addSubmitBtn?.classList.add('d-none');
            editSubmitBtn?.classList.add('d-none');
            paySubmitBtn?.classList.remove('d-none');
        }
    }
    document.querySelectorAll('#debtorTab button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function (e) {
            const target = e.target.getAttribute('data-bs-target') || e.target.dataset.bsTarget;
            const tabId = (target || '').replace('#', '');
            setFooterButtonsForTab(tabId);
        });
    });
    setFooterButtonsForTab('add-debtor');

    // --- Search toggle (only behavior, no style changes) ---
    const toggleSearchBtn = $id('toggleSearchBtn');
    const searchArea = $id('searchArea');
    if (toggleSearchBtn && searchArea) {
        try {
            const inputs = Array.from(searchArea.querySelectorAll('input, select, textarea'));
            const any = inputs.some(i => (i.value || '').toString().trim() !== '');
            if (any) {
                searchArea.style.display = 'block';
                searchArea.setAttribute('aria-hidden', 'false');
            } else {
                if (!searchArea.style.display) searchArea.style.display = 'none';
                searchArea.setAttribute('aria-hidden', searchArea.style.display === 'block' ? 'false' : 'true');
            }
        } catch (e) {}
        toggleSearchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const opened = searchArea.style.display === 'block';
            searchArea.style.display = opened ? 'none' : 'block';
            searchArea.setAttribute('aria-hidden', opened ? 'true' : 'false');
            if (!opened) {
                const first = searchArea.querySelector('input:not([type=hidden]), select, textarea');
                if (first) first.focus();
            }
        });
    }

    // --- Export controls (show center message on start) ---
    const csvBtn = $id('exportCsvBtn');
    const pdfBtn = $id('exportPdfBtn');
    const perPaymentCheckbox = $id('exportPerPayment');
    [csvBtn, pdfBtn].forEach(btn => { if (!btn) return; const base = btn.getAttribute('data-base') || btn.getAttribute('href') || '#'; btn.setAttribute('data-base', base); });
    function updateUrlWithParam(url, key, value) {
        try {
            const u = new URL(url, window.location.origin);
            if (value === null || value === undefined || value === '') u.searchParams.delete(key);
            else u.searchParams.set(key, value);
            const wasAbsolute = /^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(url);
            return wasAbsolute ? u.toString() : (u.pathname + u.search + u.hash);
        } catch (e) {
            try { const a = document.createElement('a'); a.href = url; const sp = new URLSearchParams(a.search); if (value === null || value === undefined || value === '') sp.delete(key); else sp.set(key, value); return a.pathname + (sp.toString() ? '?' + sp.toString() : '') + (a.hash || ''); } catch (_) { return url; }
        }
    }
    function applyExportMode() { const enabled = perPaymentCheckbox && perPaymentCheckbox.checked; if (csvBtn) csvBtn.href = updateUrlWithParam(csvBtn.getAttribute('data-base') || csvBtn.href, 'per_payment', enabled ? '1' : null); if (pdfBtn) pdfBtn.href = updateUrlWithParam(pdfBtn.getAttribute('data-base') || pdfBtn.href, 'per_payment', enabled ? '1' : null); }
    try { if (new URL(window.location.href).searchParams.get('per_payment') === '1' && perPaymentCheckbox) perPaymentCheckbox.checked = true; } catch (e) {}
    perPaymentCheckbox?.addEventListener('change', applyExportMode);
    applyExportMode();
    [csvBtn, pdfBtn].forEach(b => { if (!b) return; b.addEventListener('click', () => showCenterMessage('جارٍ تجهيز الملف... إذا لم يبدأ التنزيل تلقائيًا، انتظر قليلاً.', { type: 'info', autoClose: 1400 })); });

    // --- Show modal (details) and attachment actions ---
    let currentShowDebtor = { id: null, attachment: '' };
    const showModalEl = $id('showDebtorModal');
    const showAttachmentArea = $id('show_debtor_attachment_area');
    const showDownloadLink = $id('show_debtor_download');
    const showDeleteForm = $id('show_delete_attachment_form');
    const showExportBtn = $id('show_debtor_export');
    const paymentsBody = $id('show_debtor_payments_body');
    const showDeleteBtn = $id('show_delete_attachment_btn');

    function openBootstrapModal(el) { if (!el) return null; try { return bootstrap.Modal.getOrCreateInstance(el); } catch (e) { return null; } }

    function openShowDebtorFromButton(btn) {
        // fill modal from dataset attributes (keeps behavior)
        const ds = safeDataset(btn);
        const id = ds.id || btn.getAttribute('data-id') || '';
        currentShowDebtor.id = id;
        currentShowDebtor.attachment = ds.attachment || btn.getAttribute('data-attachment') || '';
        const setText = (idOrEl, value) => { const el = $id(idOrEl); if (el) el.textContent = value ?? '-'; };
        setText('show_debtor_name', ds.debtor_name || btn.getAttribute('data-debtor_name') || '-');
        setText('show_debtor_address', ds.address || btn.getAttribute('data-address') || '-');
        setText('show_debtor_phone', ds.phone || btn.getAttribute('data-phone') || '-');
        const amount = ds.amount || btn.getAttribute('data-amount') || '';
        $id('show_debtor_amount') && ($id('show_debtor_amount').textContent = amount ? (+sanitizeNumberRaw(amount)).toFixed(2) + ' ج.م' : '-');
        const paid = ds.paid || btn.getAttribute('data-paid') || '';
        $id('show_debtor_paid') && ($id('show_debtor_paid').textContent = paid ? (+sanitizeNumberRaw(paid)).toFixed(2) + ' ج.م' : '-');
        const remaining = ds.remaining || btn.getAttribute('data-remaining') || '';
        $id('show_debtor_remaining') && ($id('show_debtor_remaining').textContent = remaining ? (+sanitizeNumberRaw(remaining)).toFixed(2) + ' ج.م' : '-');
        setText('show_debtor_due_date', ds.due_date || btn.getAttribute('data-due_date') || '-');
        const statusVal = ds.status || btn.getAttribute('data-status') || '';
        $id('show_debtor_status') && ($id('show_debtor_status').innerHTML = (statusVal === 'paid') ? '<span class="badge bg-success">مدفوع</span>' : '<span class="badge bg-warning text-dark">قيد الانتظار</span>');
        $id('show_debtor_notes') && ($id('show_debtor_notes').textContent = ds.notes || btn.getAttribute('data-notes') || '');

        // attachments
        if (showAttachmentArea) showAttachmentArea.innerHTML = '';
        if (currentShowDebtor.attachment) {
            const path = currentShowDebtor.attachment;
            const url = path.startsWith('http') ? path : (window.location.origin + '/storage_link/' + path);
            const ext = (path || '').split('.').pop().toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp'].includes(ext);
            if (isImage) {
                const img = document.createElement('img'); img.src = url; img.className = 'img-fluid rounded'; img.style.maxHeight = '400px'; showAttachmentArea.appendChild(img);
            } else {
                const wrap = document.createElement('div'); wrap.className = 'd-flex gap-2';
                wrap.innerHTML = `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-paperclip"></i> عرض المرفق</a>
                                  <button type="button" class="btn btn-sm btn-primary" data-show-open-edit-id="${id}">فتح للتعديل</button>`;
                showAttachmentArea.appendChild(wrap);
            }
            if (showDownloadLink) { showDownloadLink.href = '/my_debtors/' + encodeURIComponent(id) + '/attachment/download'; showDownloadLink.style.display = ''; }
            if (showDeleteForm) { showDeleteForm.action = '/my_debtors/' + encodeURIComponent(id) + '/attachment'; showDeleteForm.style.display = ''; }
        } else {
            if (showAttachmentArea) showAttachmentArea.innerHTML = '<span class="text-muted small">لا يوجد مرفق</span>';
            if (showDownloadLink) { showDownloadLink.href = '#'; showDownloadLink.style.display = 'none'; }
            if (showDeleteForm) showDeleteForm.style.display = 'none';
        }

        // payments
        let payments = [];
        const hidden = document.getElementById('details-' + id);
        if (hidden) {
            try { payments = JSON.parse(hidden.querySelector('.details-json')?.getAttribute('data-payments') || '[]'); } catch (e) { payments = []; }
        }
        if (paymentsBody) {
            paymentsBody.innerHTML = '';
            if (!payments.length) paymentsBody.innerHTML = '<tr><td colspan="5" class="text-center small text-muted">لا توجد دفعات بعد</td></tr>';
            else payments.forEach((p, idx) => {
                const tr = document.createElement('tr'); tr.className = 'text-center';
                const payer = p.payer_name || p.payername || p.user_name || '-';
                const amountText = p.amount ? (+sanitizeNumberRaw(p.amount)).toFixed(2) + ' ج.م' : '-';
                const date = p.payment_date || p.paymentDate || p.created_at || '-';
                const notes = p.notes || '-';
                tr.innerHTML = `<td>${idx+1}</td><td>${payer}</td><td>${amountText}</td><td>${date}</td><td class="text-start">${notes}</td>`;
                paymentsBody.appendChild(tr);
            });
        }

        if (showExportBtn) {
            const exportUrl = (btn.getAttribute('data-export-url') || '#');
            if (exportUrl && exportUrl !== '#') { showExportBtn.href = exportUrl; showExportBtn.classList.remove('disabled'); }
            else { showExportBtn.href = '#'; showExportBtn.classList.add('disabled'); }
        }

        // bind internal modal buttons
        setTimeout(() => {
            const openBtns = showAttachmentArea.querySelectorAll('[data-show-open-edit-id]');
            openBtns.forEach(b => {
                if (b._boundOpenEdit) return;
                b._boundOpenEdit = true;
                b.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const idVal = b.getAttribute('data-show-open-edit-id');
                    if (!idVal) return;
                    const editRowBtn = document.querySelector('.btn-edit-debtor[data-id="' + idVal + '"]');
                    if (editRowBtn) {
                        handleEditButton(editRowBtn);
                        openBootstrapModal(showModalEl)?.hide();
                        return;
                    }
                    openBootstrapModal(showModalEl)?.hide();
                    const dm = $id('debtorModal'); if (dm) openBootstrapModal(dm)?.show();
                    document.getElementById('edit-debtor-tab')?.click();
                    $id('edit_debtor_id') && ($id('edit_debtor_id').value = idVal);
                });
            });

            const delBtn = $id('show_delete_attachment_btn');
            if (delBtn && showDeleteForm && !delBtn._boundDelete) {
                delBtn._boundDelete = true;
                delBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    showConfirmModal('تأكيد الحذف', 'هل أنت متأكد من حذف هذا المرفق؟').then(confirmed => {
                        if (!confirmed) return;
                        try { if (typeof showDeleteForm.requestSubmit === 'function') showDeleteForm.requestSubmit(); else showDeleteForm.submit(); } catch (e) { const submitInside = showDeleteForm.querySelector('button[type="submit"], input[type="submit"]'); if (submitInside) submitInside.click(); }
                    });
                });
            }
        }, 30);

        openBootstrapModal(showModalEl)?.show();
    }

    // --- Row-level handlers and binding (reliable) ---
    function handleEditButton(btn) {
        try {
            const updateUrl = btn.getAttribute('data-update-url') || btn.dataset.updateUrl || '';
            if (editForm && updateUrl) editForm.setAttribute('action', updateUrl);

            document.getElementById('edit-debtor-tab')?.click();
            if (editForm) editForm.style.display = '';
            $id('edit_debtor_id') && ($id('edit_debtor_id').value = btn.getAttribute('data-id') || btn.dataset.id || '');
            $id('edit_debtor_name') && ($id('edit_debtor_name').value = btn.getAttribute('data-debtor_name') || btn.dataset.debtor_name || '');
            const rawAmount = btn.getAttribute('data-amount') || btn.dataset.amount || '';
            const amountVal = sanitizeNumberRaw(rawAmount);
            $id('edit_amount') && ($id('edit_amount').value = amountVal !== '' ? Number(amountVal).toFixed(2) : '');
            const dueVal = btn.getAttribute('data-due_date') || btn.dataset.due_date || '';
            if ($id('edit_due_date')) $id('edit_due_date').value = dueVal ? dueVal : '';
            $id('edit_status') && ($id('edit_status').value = btn.getAttribute('data-status') || btn.dataset.status || 'pending');
            $id('edit_notes') && ($id('edit_notes').value = btn.getAttribute('data-notes') || btn.dataset.notes || '');
            $id('edit_address') && ($id('edit_address').value = btn.getAttribute('data-address') || btn.dataset.address || '');
            $id('edit_phone') && ($id('edit_phone').value = btn.getAttribute('data-phone') || btn.dataset.phone || '');

            const attachmentPath = btn.getAttribute('data-attachment') || btn.dataset.attachment || '';
            if (attachmentPath) {
                const url = attachmentPath.startsWith('http') ? attachmentPath : (window.location.origin + '/storage_link/' + attachmentPath);
                const ext = (attachmentPath || '').split('.').pop().toLowerCase();
                const isImage = ['jpg','jpeg','png','gif','webp'].includes(ext);
                if (isImage) editCurrentAttachment && (editCurrentAttachment.innerHTML = `<a href="${url}" target="_blank"><img src="${url}" class="img-fluid rounded" style="max-height:140px;"></a>`);
                else editCurrentAttachment && (editCurrentAttachment.innerHTML = `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-paperclip"></i> ${attachmentPath.split('/').pop()}</a>`);
                if (editRemoveWrap) editRemoveWrap.style.display = '';
                if (editRemoveCheckbox) editRemoveCheckbox.checked = false;
            } else {
                if (editCurrentAttachment) editCurrentAttachment.innerHTML = '<span class="text-muted small">لا يوجد مرفق</span>';
                if (editRemoveWrap) editRemoveWrap.style.display = 'none';
                if (editRemoveCheckbox) editRemoveCheckbox.checked = false;
            }

            try { if (editAttachmentInput) editAttachmentInput.value = ''; } catch (e) {}
            if (editAttachmentPreview) editAttachmentPreview.innerHTML = '';
            setTimeout(() => setFooterButtonsForTab('edit-debtor'), 0);
            openBootstrapModal($id('debtorModal'))?.show();
        } catch (err) { console.error(err); }
    }

    function handlePayButton(btn) {
        try {
            const debtorId = btn.getAttribute('data-id');
            const remainingRaw = btn.getAttribute('data-remaining') || btn.dataset.remaining || '';
            const totalRaw = btn.getAttribute('data-amount') || btn.dataset.amount || '';
            const remaining = sanitizeNumberRaw(remainingRaw);
            const total = sanitizeNumberRaw(totalRaw);
            const submitUrl = btn.getAttribute('data-update-url') || btn.dataset.updateUrl || '';

            document.getElementById('pay-debtor-tab')?.click();
            if (payForm) {
                payForm.style.display = '';
                if (submitUrl && submitUrl !== '#') { payForm.setAttribute('action', submitUrl); payForm.setAttribute('method', 'POST'); }
                else { payForm.removeAttribute('action'); }
                $id('pay_debtor_id') && ($id('pay_debtor_id').value = debtorId);
                $id('pay_payer_name') && ($id('pay_payer_name').value = '');
                const setVal = (remaining !== '') ? remaining : (total !== '' ? total : '');
                if ($id('pay_amount')) {
                    $id('pay_amount').value = setVal !== '' ? Number(setVal).toFixed(2) : '';
                    if (remaining !== '') $id('pay_amount').setAttribute('max', Number(remaining).toFixed(2));
                    else if (total !== '') $id('pay_amount').setAttribute('max', Number(total).toFixed(2));
                    else $id('pay_amount').removeAttribute('max');
                }
                $id('pay_remaining_display') && ($id('pay_remaining_display').value = setVal !== '' ? Number(setVal).toFixed(2) + ' ج.م' : '');
                $id('pay_payment_date') && ($id('pay_payment_date').value = new Date().toISOString().slice(0, 10));
                $id('pay_notes') && ($id('pay_notes').value = '');
            }
            addSubmitBtn?.classList.add('d-none'); editSubmitBtn?.classList.add('d-none'); paySubmitBtn?.classList.remove('d-none');
            openBootstrapModal($id('debtorModal'))?.show();
            setTimeout(() => setFooterButtonsForTab('pay-debtor'), 0);
        } catch (err) { console.error(err); }
    }

    function bindRowButtons() {
        qsa('.btn-edit-debtor').forEach(btn => {
            if (btn._boundEdit) return;
            btn._boundEdit = true;
            btn.addEventListener('click', function (ev) {
                // allow default navigation only if href is a non-hash external link
                const isAnchor = (btn.tagName === 'A');
                const href = isAnchor ? (btn.getAttribute('href') || '') : '';
                const allowDefault = isAnchor && href && href !== '#' && !href.startsWith('javascript:');
                if (!allowDefault) { ev.preventDefault(); ev.stopPropagation(); }
                handleEditButton(btn);
            });
        });
        qsa('.btn-pay-debtor').forEach(btn => {
            if (btn._boundPay) return;
            btn._boundPay = true;
            btn.addEventListener('click', function (ev) {
                const isAnchor = (btn.tagName === 'A');
                const href = isAnchor ? (btn.getAttribute('href') || '') : '';
                const allowDefault = isAnchor && href && href !== '#' && !href.startsWith('javascript:');
                if (!allowDefault) { ev.preventDefault(); ev.stopPropagation(); }
                handlePayButton(btn);
            });
        });
        qsa('.btn-show-debtor').forEach(btn => {
            if (btn._boundShow) return;
            btn._boundShow = true;
            btn.addEventListener('click', function (ev) {
                const isAnchor = (btn.tagName === 'A');
                const href = isAnchor ? (btn.getAttribute('href') || '') : '';
                const allowDefault = isAnchor && href && href !== '#' && !href.startsWith('javascript:');
                if (!allowDefault) { ev.preventDefault(); ev.stopPropagation(); }
                openShowDebtorFromButton(btn);
            });
        });
    }
    bindRowButtons();
    const bindObserver = new MutationObserver(bindRowButtons);
    bindObserver.observe(document.body, { childList: true, subtree: true });

    // --- Bulk delete (center confirm) ---
    const selectAllEl = $id('selectAllRows');
    const bulkDeleteBtn = $id('bulkDeleteBtn');
    const bulkDeleteForm = $id('bulkDeleteForm');

    function getRowCheckboxes() { return Array.from(document.querySelectorAll('.row-checkbox')); }
    function updateBulkState() {
        const boxes = getRowCheckboxes();
        const checked = boxes.filter(b => b.checked).map(b => b.value);
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = checked.length === 0;
        const holder = $id('bulk_ids_holder'); if (holder) holder.value = checked.join(',');
        if (selectAllEl) {
            if (checked.length === boxes.length && boxes.length > 0) { selectAllEl.checked = true; selectAllEl.indeterminate = false; }
            else if (checked.length > 0) { selectAllEl.checked = false; selectAllEl.indeterminate = true; }
            else { selectAllEl.checked = false; selectAllEl.indeterminate = false; }
        }
    }
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('row-checkbox')) updateBulkState();
        if (e.target && e.target.id === 'selectAllRows') { getRowCheckboxes().forEach(cb => cb.checked = selectAllEl.checked); updateBulkState(); }
    });

    window.confirmBulkDelete = function (evt) {
        evt.preventDefault();
        const holder = $id('bulk_ids_holder');
        const raw = holder ? holder.value : '';
        const ids = raw ? raw.split(',').filter(Boolean) : [];
        if (!ids.length) { showCenterMessage('الرجاء اختيار عناصر للحذف أولاً.', { type: 'warning', autoClose: 2500 }); return false; }
        showConfirmModal('تأكيد الحذف', 'هل أنت متأكد أنك تريد حذف العناصر المحددة؟').then(confirmed => {
            if (!confirmed) return false;
            if (!bulkDeleteForm) { showCenterMessage('لم يتم تفعيل حذف متعدد في السيرفر.', { type: 'warning', autoClose: 2500 }); return false; }
            if (holder) holder.remove();
            ids.forEach(id => {
                const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; bulkDeleteForm.appendChild(input);
            });
            showCenterMessage('جاري حذف السجلات المحددة...', { type: 'info', autoClose: 2000 });
            bulkDeleteForm.submit();
            return true;
        });
        return false;
    };

    // --- Delete attachment (AJAX) fallback - form submit already handled by button binding ---
    if (showDeleteForm) {
        showDeleteForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            showConfirmModal('تأكيد الحذف', 'هل أنت متأكد من حذف هذا المرفق؟').then(confirmed => {
                if (!confirmed) return;
                const action = showDeleteForm.getAttribute('action');
                if (!action) { showCenterMessage('رابط حذف المرفق غير محدد', { type: 'danger', autoClose: 3000 }); return; }
                if (showDeleteBtn) { showDeleteBtn.disabled = true; showDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جارٍ الحذف'; }
                fetch(action, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(async res => { if (!res.ok) throw res; return res.json().catch(() => ({})); })
                .then(data => {
                    if (showAttachmentArea) showAttachmentArea.innerHTML = '<span class="text-muted small">لا يوجد مرفق</span>';
                    if (showDownloadLink) { showDownloadLink.href = '#'; showDownloadLink.style.display = 'none'; }
                    showDeleteForm.style.display = 'none';
                    if (editCurrentAttachment) editCurrentAttachment.innerHTML = '<span class="text-muted small">لا يوجد مرفق</span>';
                    if (editRemoveWrap) editRemoveWrap.style.display = 'none';
                    if (editRemoveCheckbox) editRemoveCheckbox.checked = false;
                    const removed = currentShowDebtor.attachment || '';
                    if (removed) document.querySelectorAll('[data-attachment]').forEach(el => { if (el.getAttribute('data-attachment') === removed) el.setAttribute('data-attachment', ''); });
                    showCenterMessage((data && data.message) ? data.message : 'تم حذف المرفق', { type: 'success', autoClose: 2500 });
                }).catch(async err => {
                    let txt = 'فشل حذف المرفق. حاول مرة أخرى.'; try { txt = await err.text(); } catch(e) {}
                    console.error(err); showCenterMessage(txt, { type: 'danger', autoClose: 3500 });
                }).finally(() => {
                    if (showDeleteBtn) { showDeleteBtn.disabled = false; showDeleteBtn.innerHTML = '<i class="bi bi-trash"></i> حذف المرفق'; }
                });
            });
        });
    }

    // --- Form submit feedback (center modal) ---
    if (addForm) addForm.addEventListener('submit', () => showCenterMessage('جاري إضافة المديون... يرجى الانتظار', { type: 'info', autoClose: 2000 }));
    if (editForm) editForm.addEventListener('submit', () => showCenterMessage('جاري حفظ التعديلات... يرجى الانتظار', { type: 'info', autoClose: 2000 }));
    if (payForm) payForm.addEventListener('submit', () => showCenterMessage('جاري إرسال الدفعة... يرجى الانتظار', { type: 'info', autoClose: 2000 }));
    qsa('form[action*="addToGoogle"]').forEach(f => f.addEventListener('submit', () => showCenterMessage('جاري الإرسال إلى تقويم جوجل... يرجى الانتظار', { type: 'info', autoClose: 2200 })));

    // --- Pay submit handler (keeps behavior, central messages) ---
    if (paySubmitBtn && payForm) {
        paySubmitBtn.addEventListener('click', function () {
            if (!payForm) return;
            const amtEl = $id('pay_amount');
            const amt = sanitizeNumberRaw(amtEl?.value || '') || 0;
            const max = parseFloat(amtEl?.getAttribute('max') || '0');
            if (isNaN(amt) || amt <= 0) { showCenterMessage('الرجاء إدخال مبلغ صالح أكبر من صفر.', { type: 'warning', autoClose: 2500 }); return; }
            if (max > 0 && amt > max) { amtEl.value = Number(max).toFixed(2); showCenterMessage('تم تعديل المبلغ للحد الأقصى المتاح.', { type: 'info', autoClose: 2000 }); }
            let action = payForm.getAttribute('action') || '';
            if (!action || action === '#') {
                const debtorId = $id('pay_debtor_id') ? $id('pay_debtor_id').value : '';
                if (debtorId) { action = '/my_debtors/' + encodeURIComponent(debtorId) + '/pay'; payForm.setAttribute('action', action); payForm.setAttribute('method', 'POST'); }
                else { showCenterMessage('لم يتم تعيين رابط إرسال الدفعة. أعد المحاولة.', { type: 'danger', autoClose: 3000 }); return; }
            }
            const orig = paySubmitBtn.innerHTML; paySubmitBtn.disabled = true; paySubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جارٍ الإرسال';
            try { showCenterMessage('جاري إرسال الدفعة... يرجى الانتظار', { type: 'info', autoClose: 2000 }); payForm.submit(); } catch (e) { showCenterMessage('حدث خطأ أثناء إرسال النموذج. حاول مرة أخرى.', { type: 'danger', autoClose: 3000 }); paySubmitBtn.disabled = false; paySubmitBtn.innerHTML = orig; }
        });
    }

    // --- Init focus/tab ---
    document.getElementById('add-debtor-tab')?.click();
    try { updateBulkState(); } catch (e) { /* ignore */ }
});
</script>
@endpush

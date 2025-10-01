@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" id="installmentsPage">
    <div class="container" style="max-width:1800px;">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <!-- Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <h2 class="h5 fw-bold mb-0 text-primary"><i class="bi bi-folder"></i> الأقساط</h2>
                        <div class="d-none d-md-inline-block text-success fw-bold" id="installmentsTotalBadge" style="font-size:1rem;">
                            الإجمالي الكلي: <span id="installmentsTotalValue">{{ number_format($total ?? 0, 2) }}</span> ج.م
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <button type="button" class="btn btn-success fw-bold" id="openAddInstallmentBtn" data-bs-toggle="modal" data-bs-target="#installmentModal">
                            <i class="bi bi-plus-circle"></i> إضافة قسط
                        </button>

                        <a href="{{ route('installments.index') }}" class="btn btn-outline-secondary fw-bold">
                            <i class="bi bi-arrow-clockwise"></i> إعادة تحميل
                        </a>

                        @if(Route::has('installments.bulkDestroy'))
                            <form id="bulkDeleteForm" method="POST" action="{{ route('installments.bulkDestroy') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="ids" id="bulk_ids_holder" value="">
                                <button type="submit" id="bulkDeleteBtn" disabled class="btn btn-danger fw-bold" onclick="return confirm('سيتم حذف العناصر المحددة. متابعة؟')">
                                    <i class="bi bi-trash"></i> حذف المختار
                                </button>
                            </form>
                        @else
                            <button class="btn btn-danger fw-bold" disabled title="Route installments.bulkDestroy غير متوفر"><i class="bi bi-trash"></i> حذف المختار</button>
                        @endif

                        @if (Route::has('installments.export'))
                            <a id="btnExportCsv" href="{{ route('installments.export', request()->all()) }}" class="btn btn-outline-success fw-bold"><i class="bi bi-file-earmark-excel"></i> CSV</a>
                        @else
                            <button class="btn btn-outline-success fw-bold" disabled><i class="bi bi-file-earmark-excel"></i> CSV</button>
                        @endif

                        @if (Route::has('installments.exportPdf'))
                            <a id="btnExportPdf" href="{{ route('installments.exportPdf', request()->all()) }}" class="btn btn-outline-danger fw-bold"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                        @else
                            <button class="btn btn-outline-danger fw-bold" disabled><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                        @endif
                    </div>
                </div>

                <!-- Advanced search (main page only) -->
                <div id="installmentsAdvancedSearchWrapper" class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">بحث متقدم</div>
                    <div>
                      <button class="btn btn-sm btn-outline-primary" type="button" id="openInstallmentsAdvancedSearchBtn">إظهار/إخفاء</button>
                    </div>
                  </div>

                  <div id="installmentsAdvancedSearchBody" style="display:none;">
                    <form id="installmentsAdvancedSearchForm" class="card card-body">
                      <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                          <label class="form-label small mb-1">كلمة بحث</label>
                          <input type="text" name="q" id="inst_search_q" class="form-control" placeholder="الاسم أو الملاحظة أو رقم" value="{{ request('q','') }}">
                        </div>

                        <div class="col-md-2">
                          <label class="form-label small mb-1">الهاتف</label>
                          <input type="text" name="phone" id="inst_search_phone" class="form-control" placeholder="رقم الهاتف" value="{{ request('phone','') }}">
                        </div>

                        <div class="col-md-3">
                          <label class="form-label small mb-1">العنوان</label>
                          <input type="text" name="address" id="inst_search_address" class="form-control" placeholder="المدينة/الشارع" value="{{ request('address','') }}">
                        </div>

                        <div class="col-md-2">
                          <label class="form-label small mb-1">الحالة</label>
                          <select name="status" id="inst_search_status" class="form-select">
                            <option value="" {{ request('status','')=='' ? 'selected':'' }}>الكل</option>
                            <option value="pending" {{ request('status','')=='pending' ? 'selected':'' }}>قيد الانتظار</option>
                            <option value="paid" {{ request('status','')=='paid' ? 'selected':'' }}>مدفوع</option>
                          </select>
                        </div>

                        <div class="col-md-2 text-end">
                          <button type="button" id="inst_advancedSearchApply" class="btn btn-primary">تطبيق</button>
                          <button type="button" id="inst_advancedSearchReset" class="btn btn-outline-secondary ms-1">مسح</button>
                        </div>

                        <div class="col-md-3">
                          <label class="form-label small mb-1">تاريخ من</label>
                          <input type="date" name="due_from" id="inst_search_due_from" class="form-control" value="{{ request('due_from','') }}">
                        </div>
                        <div class="col-md-3">
                          <label class="form-label small mb-1">إلى</label>
                          <input type="date" name="due_to" id="inst_search_due_to" class="form-control" value="{{ request('due_to','') }}">
                        </div>

                        <div class="col-md-2">
                          <label class="form-label small mb-1">مبلغ من</label>
                          <input type="number" name="amount_min" id="inst_search_amount_min" step="0.01" class="form-control" value="{{ request('amount_min','') }}">
                        </div>
                        <div class="col-md-2">
                          <label class="form-label small mb-1">إلى</label>
                          <input type="number" name="amount_max" id="inst_search_amount_max" step="0.01" class="form-control" value="{{ request('amount_max','') }}">
                        </div>

                        <div class="col-md-2">
                          <label class="form-label small mb-1">به مرفقات</label>
                          <select name="has_attachments" id="inst_search_has_attachments" class="form-select">
                            <option value="" {{ request('has_attachments','')=='' ? 'selected':'' }}>الكل</option>
                            <option value="1" {{ request('has_attachments','')=='1' ? 'selected':'' }}>نعم</option>
                            <option value="0" {{ request('has_attachments','')=='0' ? 'selected':'' }}>لا</option>
                          </select>
                        </div>

                      </div>
                    </form>
                  </div>
                </div>

                <!-- Mobile total -->
                <div class="d-block d-md-none mb-3">
                    <div class="alert alert-primary py-2 mb-0 text-center fw-bold">
                        الإجمالي الكلي: <span class="text-success">{{ number_format($total ?? 0, 2) }}</span> ج.م
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success text-center">{{ session('success') }}</div>
                @endif

                {{-- Add/Edit modal --}}
                <div class="modal fade" id="installmentModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> إدارة القسط</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                            </div>

                            <div class="modal-body">
                                <ul class="nav nav-tabs mb-3" id="installmentTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="add-installment-tab" data-bs-toggle="tab" data-bs-target="#add-installment" type="button" role="tab">إضافة</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="edit-installment-tab" data-bs-toggle="tab" data-bs-target="#edit-installment" type="button" role="tab">تعديل</button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Add -->
                                    <div class="tab-pane fade show active" id="add-installment" role="tabpanel" aria-labelledby="add-installment-tab">
                                        <form method="POST" action="{{ route('installments.store') }}" id="addInstallmentForm" enctype="multipart/form-data" autocomplete="off">
                                            @csrf
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">اسم القسط</label>
                                                    <input type="text" name="name" id="add_name" class="form-control" required value="{{ old('name') }}">
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">صاحب القسط</label>
                                                    <input type="text" name="owner_name" id="add_owner_name" class="form-control" value="{{ old('owner_name') }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">المبلغ الكلي</label>
                                                    <input type="number" step="0.01" name="total_amount" id="add_total_amount" class="form-control" required value="{{ old('total_amount') }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">عدد الأقساط</label>
                                                    <input type="number" name="installments_count" id="add_installments_count" class="form-control" value="{{ old('installments_count', 1) }}" min="1" required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">قيمة كل قسط (جنيه)</label>
                                                    <input type="number" step="0.01" name="installment_value" id="add_installment_value" class="form-control" value="{{ old('installment_value', '0.00') }}">
                                                    <div class="form-text">تحسب تلقائياً = إجمالي ÷ عدد الأقساط</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">رقم التليفون</label>
                                                    <input type="text" name="phone" id="add_phone" class="form-control" value="{{ old('phone') }}" placeholder="مثال: 01XXXXXXXXX">
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">العنوان</label>
                                                    <input type="text" name="address" id="add_address" class="form-control" value="{{ old('address') }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">المدفوع</label>
                                                    <input type="number" step="0.01" name="paid_amount" id="add_paid_amount" class="form-control" value="{{ old('paid_amount', 0) }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                                                    <input type="date" name="due_date" id="add_due_date" class="form-control" value="{{ old('due_date', \Carbon\Carbon::now()->toDateString()) }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">الحالة</label>
                                                    <select name="status" id="add_status" class="form-select">
                                                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                                        <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>مدفوع</option>
                                                    </select>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-bold">ملاحظات</label>
                                                    <textarea name="notes" id="add_notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-bold">مرفقات</label>
                                                    <input type="file" name="attachments[]" id="addInstallmentAttachments" class="form-control" multiple accept="image/*,application/pdf,application/*">
                                                    <div id="addInstallmentPreview" class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap"></div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Edit -->
                                    <div class="tab-pane fade" id="edit-installment" role="tabpanel" aria-labelledby="edit-installment-tab">
                                        <div class="alert alert-info" id="editInstallmentInfo">اختر "تعديل" من الجدول بالأسفل ليظهر نموذج التعديل هنا تلقائياً.</div>

                                        <form method="POST" id="editInstallmentForm" style="display:none;" enctype="multipart/form-data" autocomplete="off">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="id" id="edit_installment_id">

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">اسم القسط</label>
                                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">صاحب القسط</label>
                                                    <input type="text" name="owner_name" id="edit_owner_name" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">المبلغ الكلي</label>
                                                    <input type="number" step="0.01" name="total_amount" id="edit_total_amount" class="form-control" required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">عدد الأقساط</label>
                                                    <input type="number" name="installments_count" id="edit_installments_count" class="form-control" min="1">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">قيمة كل قسط (جنيه)</label>
                                                    <input type="number" step="0.01" name="installment_value" id="edit_installment_value" class="form-control" readonly>
                                                    <div class="form-text">تحسب تلقائياً = إجمالي ÷ عدد الأقساط</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">رقم التليفون</label>
                                                    <input type="text" name="phone" id="edit_phone" class="form-control" placeholder="مثال: 01XXXXXXXXX">
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">العنوان</label>
                                                    <input type="text" name="address" id="edit_address" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">المدفوع</label>
                                                    <input type="number" step="0.01" name="paid_amount" id="edit_paid_amount" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">تاريخ الاستحقاق</label>
                                                    <input type="date" name="due_date" id="edit_due_date" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">الحالة</label>
                                                    <select name="status" id="edit_status" class="form-select">
                                                        <option value="pending">قيد الانتظار</option>
                                                        <option value="paid">مدفوع</option>
                                                    </select>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-bold">ملاحظات</label>
                                                    <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-bold">مرفقات</label>
                                                    <input type="file" name="attachments[]" id="editInstallmentAttachments" class="form-control" multiple accept="image/*,application/pdf,application/*">
                                                    <div id="editInstallmentPreview" class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap"></div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between mt-3">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> حفظ التعديل</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                <button type="submit" class="btn btn-success" form="addInstallmentForm" id="addInstallmentSubmitBtn">
                                    <i class="bi bi-plus-circle"></i> إضافة
                                </button>
                                <button type="submit" class="btn btn-warning d-none" form="editInstallmentForm" id="editInstallmentSubmitBtn">
                                    <i class="bi bi-save"></i> حفظ التعديل
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

               <!-- Show Installment Modal - final layout per request -->
<div class="modal fade" id="showInstallmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" dir="rtl">
      <div class="modal-header bg-info text-white d-flex align-items-start justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-eye fs-4"></i>
          <div>
            <h5 class="modal-title mb-0">تفاصيل القسط</h5>
            <small id="showInstallmentSubtitle" class="text-white-50">عرض منسق للمعلومات، الحالة المالية وسجل الدفعات</small>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-light btn-sm" id="printInstallmentBtn" title="طباعة">
            <i class="bi bi-printer"></i> طباعة
          </button>

          <button type="button" class="btn btn-outline-light btn-sm" id="exportInstallmentBtn" title="تصدير PDF">
            <i class="bi bi-download"></i> تصدير
          </button>

          <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
      </div>

      <div class="modal-body">
        <div id="showInstallmentBody" class="container-fluid p-0">
          <!-- General Info & Financial Status: each as a neat table -->
          <div class="row g-3 mb-3">
            <!-- General Info Table (takes half width on large screens) -->
            <div class="col-12 col-lg-6">
              <div class="card h-100 shadow-sm">
                <div class="card-body p-3">
                  <h6 class="card-subtitle mb-3 text-muted">معلومات عامة</h6>

                  <table class="table table-sm table-striped mb-0 w-100">
                    <tbody>
                      <tr>
                        <th class="small text-muted" style="width:38%;">اسم القسط</th>
                        <td id="show_name" class="fw-semibold"></td>
                      </tr>
                      <tr>
                        <th class="small text-muted">صاحب القسط</th>
                        <td id="show_owner_name" class="fw-semibold"></td>
                      </tr>
                      <tr>
                        <th class="small text-muted">الهاتف</th>
                        <td id="show_phone" class="fw-semibold"></td>
                      </tr>
                      <tr>
                        <th class="small text-muted">العنوان</th>
                        <td id="show_address" class="fw-semibold"></td>
                      </tr>
                      <tr>
                        <th class="small text-muted">تاريخ الإنشاء</th>
                        <td id="show_created_at" class="fw-semibold"></td>
                      </tr>
                    </tbody>
                  </table>

                </div>
              </div>
            </div>

            <!-- Financial Status Table (half width) -->
            <div class="col-12 col-lg-6">
              <div class="card h-100 shadow-sm">
                <div class="card-body p-3">
                  <h6 class="card-subtitle mb-3 text-muted">الحالة المالية</h6>

                  <table class="table table-sm table-bordered text-center mb-0 align-middle w-100">
                    <thead class="table-light">
                      <tr>
                        <th>الإجمالي</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>الاستحقاق</th>
                        <th>الحالة</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td id="show_total_amount" class="fw-bold text-primary"></td>
                        <td id="show_paid_amount" class="fw-bold text-success"></td>
                        <td id="show_remaining_amount" class="fw-bold text-danger"></td>
                        <td id="show_due_date" class="fw-semibold"></td>
                        <td id="show_status" class="fw-semibold"></td>
                      </tr>
                    </tbody>
                  </table>

                </div>
              </div>
            </div>
          </div>

          <!-- Notes & Attachments -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-lg-7">
              <div class="card shadow-sm">
                <div class="card-body p-3">
                  <h6 class="card-subtitle mb-2">ملاحظات</h6>
                  <div id="show_notes" class="small text-muted" style="white-space:pre-wrap; min-height:80px;"></div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-5">
              <div class="card shadow-sm">
                <div class="card-body p-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-subtitle mb-0">المرفقات</h6>
                    <small class="text-muted small">انقر للعرض/التحميل</small>
                  </div>
                  <div id="show_attachments" class="d-flex flex-wrap gap-2" style="min-height:56px;"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payments table: single table only, full width of modal body -->
          <div class="row">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-body p-0">
                  <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <h6 class="mb-0">الدفعات المسجلة</h6>
                    <div class="small text-muted" id="paymentsCount">0 دفعة</div>
                  </div>

                  <div class="table-responsive" style="max-height:60vh; overflow:auto;">
                    <table class="table table-striped table-sm mb-0 align-middle text-center w-100">
                      <thead class="table-light position-sticky top-0" style="z-index:1;">
                       <tr>
                          <th style="width:160px">التاريخ</th>
                          <th class="text-start">اسم الدافع</th>
                          <th style="width:160px">المبلغ</th>
                          <th class="text-start">ملاحظات</th>
                        </tr>
                      </thead>
                      <tbody id="show_payments">
                        <!-- JS populates rows -->
                      </tbody>
                    </table>
                  </div>

                </div>
              </div>
            </div>
          </div>

        </div> <!-- /showInstallmentBody -->
      </div>

      <div class="modal-footer">
        <div class="me-auto small text-muted">آخر تعديل: <span id="show_updated_at" class="fw-semibold"></span></div>
        <button class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>
<!-- Reusable Pay Modal for installments -->
<div class="modal fade" id="payInstallmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash"></i> دفع قسط</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>

            <!-- action will be set dynamically by JS (data-pay-submit-url or /installments/{id}/pay) -->
            <form method="POST" id="payInstallmentForm" novalidate>
                @csrf

                <input type="hidden" name="installment_id" id="pay_installment_id">

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-bold">اسم القسط</label>
                        <input type="text" id="pay_installment_name" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">اسم الدافع</label>
                        <input type="text" name="payer_name" id="pay_payer_name" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">المتبقي</label>
                        <input type="text" id="pay_remaining_display" class="form-control text-success fw-bold" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">قيمة الدفع</label>
                        <input type="number" name="amount" id="pay_amount_input" step="0.01" min="0.01" class="form-control" required aria-describedby="payAmountHelp">
                        <div id="payAmountHelp" class="form-text">أدخل المبلغ المراد دفعه. الحد الأقصى سيضبط تلقائياً إلى المتبقي.</div>
                        <div class="invalid-feedback" id="payAmountError" style="display:none;">المبلغ غير صحيح.</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">تاريخ الدفع</label>
                        <input type="date" name="payment_date" id="pay_date_input" class="form-control" value="{{ \Carbon\Carbon::now()->toDateString() }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">ملاحظات</label>
                        <textarea name="notes" id="pay_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-cash"></i> دفع</button>
                </div>
            </form>
        </div>
    </div>
</div>

                <!-- Table card -->
<div class="card shadow-sm d-none d-md-block"> {{-- This card is for larger screens --}}
    <div class="card-header bg-light fw-bold"><i class="bi bi-credit-card"></i> جدول الأقساط</div>
    <div class="card-body p-0" style="overflow-x: auto;"> {{-- Changed to overflow-x: auto for better scroll experience --}}
        <div class="table-responsive">
            <table class="table table-striped align-middle text-center mb-0 table-sm" style="width:100%;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAllRows"></th>
                        <th style="width:50px;">#</th>
                        <th class="text-start" style="min-width: 120px;">اسم القسط</th>
                        <th class="d-none d-md-table-cell" style="min-width: 120px;">صاحب القسط</th>
                        <th style="min-width: 90px;">الكلي</th>
                        <th class="d-none d-sm-table-cell" style="min-width: 90px;">المدفوع</th>
                        <th class="d-none d-sm-table-cell" style="min-width: 90px;">المتبقي</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 100px;">الاستحقاق</th>
                        <th style="min-width: 80px;">الحالة</th>
                        <th style="min-width: 100px;">دفع</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 150px;">ملاحظات</th>

                        <!-- new visible columns -->
                        <th class="d-none d-md-table-cell" style="min-width: 120px;">الهاتف</th>
                        <th class="d-none d-md-table-cell" style="min-width: 150px;">العنوان</th>
                        <th class="d-none d-md-table-cell" style="width: 80px;">مرفق</th>

                        <th class="d-none d-sm-table-cell" style="width: 80px;">عرض</th>
                        <th class="d-none d-sm-table-cell" style="width: 80px;">تصدير</th>
                        <th class="d-none d-sm-table-cell" style="width: 80px;">تعديل</th>
                        <th class="d-none d-sm-table-cell" style="width: 80px;">حذف</th>
                        <th class="d-none d-sm-table-cell" style="width: 80px;">تقويم جوجل</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($installments as $i => $item)
                    @php
                        $attachments = [];
                        if(isset($item->attachments) && is_iterable($item->attachments)){
                            foreach($item->attachments as $a){
                                if(is_object($a)) $attachments[] = ['file_path'=>$a->file_path ?? '', 'original_name'=>$a->original_name ?? ''];
                                elseif(is_array($a)) $attachments[] = ['file_path'=>$a['file_path'] ?? '', 'original_name'=>$a['original_name'] ?? ''];
                                else $attachments[] = ['file_path'=>(string)$a, 'original_name'=>basename((string)$a)];
                            }
                        } elseif(\Illuminate\Support\Facades\Schema::hasTable('installment_attachments')) {
                            try {
                                $dbatts = \Illuminate\Support\Facades\DB::table('installment_attachments')->where('installment_id', $item->id)->get();
                                foreach($dbatts as $a) $attachments[] = ['file_path'=>$a->file_path ?? '', 'original_name'=>$a->original_name ?? ''];
                            } catch(\Throwable $e){}
                        }

                        $payments = [];
                        if(isset($item->payments) && is_iterable($item->payments)){
                            foreach($item->payments as $p){
                                $payments[] = [
                                    'id' => $p->id ?? null,
                                    'payer_name' => $p->payer_name ?? '',
                                    'amount' => number_format($p->amount ?? 0, 2, '.', ''),
                                    'payment_date' => is_object($p->payment_date) ? $p->payment_date->format('Y-m-d') : ($p->payment_date ?? ''),
                                    'notes' => $p->notes ?? ''
                                ];
                            }
                        } elseif(\Illuminate\Support\Facades\Schema::hasTable('installment_payments')) {
                            try {
                                $dbPays = \Illuminate\Support\Facades\DB::table('installment_payments')->where('installment_id', $item->id)->get();
                                foreach($dbPays as $p) $payments[] = ['id'=>$p->id,'payer_name'=>$p->payer_name,'amount'=>number_format($p->amount,2,'.',''),'payment_date'=>$p->payment_date,'notes'=>$p->notes ?? ''];
                            } catch(\Throwable $e){}
                        }

                        $perExportUrl = '';
                        if(Route::has('installments.exportSingle')){
                            $perExportUrl = route('installments.exportSingle', $item->id);
                        } elseif(Route::has('installments.exportPdf')){
                            $perExportUrl = route('installments.exportPdf', array_merge(request()->all(), ['id' => $item->id]));
                        }
                    @endphp

                    <tr>
                        <td><input type="checkbox" class="row-checkbox" value="{{ $item->id }}"></td>
                        <td class="text-start small">{{ ($installments->firstItem() ?? 0) + $i }}</td>
                        <td class="text-start text-truncate" style="max-width: 120px;">{{ $item->name }}</td>
                        <td class="d-none d-md-table-cell text-truncate" style="max-width: 120px;">{{ $item->owner_name ?? '-' }}</td>
                        <td>{{ number_format($item->total_amount, 2) }}</td>
                        <td class="d-none d-sm-table-cell">{{ number_format($item->paidAmount(), 2) }}</td>
                        <td class="d-none d-sm-table-cell">{{ number_format($item->remainingAmount(), 2) }}</td>
                        <td class="d-none d-lg-table-cell">{{ \Carbon\Carbon::parse($item->due_date)->format('Y-m-d') }}</td>
                        <td>
                            @if($item->status == 'paid')
                                <span class="badge bg-success">مدفوع</span>
                            @else
                                <span class="badge bg-danger">قيد الانتظار</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status != 'paid')
                                <button type="button"
                                    class="btn btn-success btn-sm btn-pay-installment"
                                    data-installment-id="{{ $item->id }}"
                                    data-installment-name="{{ $item->name }}"
                                    data-remaining="{{ number_format($item->remainingAmount(), 2, '.', '') }}"
                                    data-pay-submit-url="{{ \Illuminate\Support\Facades\Route::has('installments.pay.submit') ? route('installments.pay.submit', $item->id) : url('/installments/'.$item->id.'/pay') }}">
                                    <i class="bi bi-cash"></i> <span class="d-none d-lg-inline">دفع</span>
                                </button>
                            @else
                                <span class="text-success">--</span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell text-truncate" style="max-width: 150px;">{{ Str::limit($item->notes, 50) }}</td>

                        <!-- new visible columns -->
                        <td class="d-none d-md-table-cell text-truncate" style="max-width:120px;">{{ $item->phone ?? '-' }}</td>
                        <td class="d-none d-md-table-cell text-truncate" style="max-width:150px;">{{ $item->address ?? '-' }}</td>
                        <td class="d-none d-md-table-cell">
                            @php $first = $attachments[0] ?? null; @endphp
                            @if($first && !empty($first['file_path']))
                                @php $p = $first['file_path']; $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION)); @endphp
                                @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                    <a href="{{ asset('storage_link/'.$p) }}" target="_blank"><img src="{{ asset('storage_link/'.$p) }}" style="width:30px;height:30px;object-fit:cover;border-radius:4px"></a>
                                @elseif($ext === 'pdf')
                                    <a href="{{ asset('storage_link/'.$p) }}" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size:1.2rem;color:#c00"></i></a>
                                @else
                                    <a href="{{ asset('storage_link/'.$p) }}" target="_blank"><i class="bi bi-paperclip" style="font-size:1.2rem"></i></a>
                                @endif
                            @else
                                —
                            @endif
                        </td>

                        <td class="d-none d-sm-table-cell">
                            {{-- Show button --}}
                            <button type="button" class="btn btn-sm btn-info btn-show-installment"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->name }}"
                                data-owner_name="{{ $item->owner_name }}"
                                data-total_amount="{{ number_format($item->total_amount,2,'.','') }}"
                                data-installments_count="{{ $item->installments_count ?? '' }}"
                                data-installment_value="{{ $item->installment_value ?? '' }}"
                                data-paid_amount="{{ number_format($item->paidAmount(),2,'.','') }}"
                                data-remaining="{{ number_format($item->remainingAmount(),2,'.','') }}"
                                data-due_date="{{ $item->due_date ?? '' }}"
                                data-created_at="{{ optional($item->created_at)->toDateString() }}"
                                data-status="{{ $item->status }}"
                                data-notes="{{ $item->notes }}"
                                data-phone="{{ $item->phone ?? '' }}"
                                data-address="{{ $item->address ?? '' }}"
                                data-attachments='@json($attachments)'
                                data-payments='@json($payments)'
                                data-export-url="{{ $perExportUrl }}">
                                <i class="bi bi-eye"></i> <span class="d-none d-lg-inline">عرض</span>
                            </button>
                        </td>

                        <td class="d-none d-sm-table-cell">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-export-installment"
                                data-id="{{ $item->id }}"
                                data-export-url="{{ $perExportUrl }}">
                                <i class="bi bi-download"></i> <span class="d-none d-lg-inline">تصدير</span>
                            </button>
                        </td>

                        <td class="d-none d-sm-table-cell">
                            <button type="button" class="btn btn-sm btn-primary btn-edit-installment"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->name }}"
                                data-owner_name="{{ $item->owner_name }}"
                                data-total_amount="{{ $item->total_amount }}"
                                data-installments_count="{{ $item->installments_count ?? 1 }}"
                                data-installment_value="{{ $item->installment_value ?? ($item->installments_count ? number_format($item->total_amount / max(1,$item->installments_count),2,'.','') : '') }}"
                                data-paid_amount="{{ $item->paidAmount() }}"
                                data-due_date="{{ $item->due_date ?? '' }}"
                                data-created_at="{{ optional($item->created_at)->toDateString() }}"
                                data-status="{{ $item->status }}"
                                data-notes="{{ $item->notes }}"
                                data-phone="{{ $item->phone ?? '' }}"
                                data-address="{{ $item->address ?? '' }}"
                                data-attachments='@json($attachments)'
                                data-update-url="{{ route('installments.update', $item->id) }}">
                                <i class="bi bi-pencil-square"></i> <span class="d-none d-lg-inline">تعديل</span>
                            </button>
                        </td>

                        <td class="d-none d-sm-table-cell">
                            <form action="{{ route('installments.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> <span class="d-none d-lg-inline">حذف</span></button>
                            </form>
                        </td>

                        <td class="d-none d-sm-table-cell">
                            @if(auth()->user()->google_token ?? false)
                                <form action="{{ route('installments.addToGoogle', $item->id) }}" method="POST" style="display:inline;">
                                   @csrf
                                        <button class="btn-action outline-success btn-compact"><i class="bi bi-calendar-plus"></i> تقويم</button>
                                    </form>
                                @else
                                    <a href="{{ route('google.redirect') }}" class="btn-action outline-info btn-compact"><i class="bi bi-google"></i> ربط جوجل</a>
                                @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="text-center text-muted">لا توجد أقساط</td>
                    </tr>
                @endforelse
                </tbody>

                <tfoot>
                    <tr style="background:#f4f9ff; font-weight:bold;">
                        <td colspan="4" class="text-end">الإجمالي الكلي لكل النتائج</td>
                        <td class="text-success text-center">{{ number_format($total ?? 0, 2) }} ج.م</td>
                        <td colspan="15"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="p-3 d-flex justify-content-between align-items-center">
            <div>{{ $installments->links() }}</div>
            <div class="text-muted small">إجمالي الصفحة: {{ number_format($installments->sum(function($it){ return $it->total_amount; }) ?? 0, 2) }} ج.م</div>
        </div>
    </div>
</div>

                <!-- Mobile cards -->
<div class="d-block d-md-none mt-3">
    <div class="row g-3">
        @forelse($installments as $item)
            @php
                $attachmentsMobile = [];
                if(isset($item->attachments) && is_iterable($item->attachments)){
                    foreach($item->attachments as $a){
                        if(is_object($a)) $attachmentsMobile[] = ['file_path'=>$a->file_path ?? '', 'original_name'=>$a->original_name ?? ''];
                        elseif(is_array($a)) $attachmentsMobile[] = ['file_path'=>$a['file_path'] ?? '', 'original_name'=>$a['original_name'] ?? ''];
                        else $attachmentsMobile[] = ['file_path'=>(string)$a,'original_name'=>basename((string)$a)];
                    }
                }
                $paymentsMobile = [];
                if(isset($item->payments) && is_iterable($item->payments)){
                    foreach($item->payments as $p){
                        $paymentsMobile[] = ['id'=>$p->id ?? null,'payer_name'=>$p->payer_name ?? '','amount'=>number_format($p->amount ?? 0,2,'.',''),'payment_date'=>is_object($p->payment_date)?$p->payment_date->format('Y-m-d'):$p->payment_date,'notes'=>$p->notes ?? ''];
                    }
                }
                $perExportUrl = '#';
                if (\Route::has('installments.exportPdf')) {
                    $perExportUrl = route('installments.exportPdf', $item->id);
                }
            @endphp

            <div class="col-12">
                <div class="card shadow-sm installment-card" data-id="{{ $item->id }}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1 me-2" style="min-width: 0;">
                                <div class="fw-bold fs-6 text-truncate-custom">{{ $item->name }}</div>
                                <div class="small text-muted mt-1 text-truncate-custom">{{ Str::limit($item->owner_name ?? '-', 60) }}</div>
                                <div class="small text-muted mt-1">
                                    هاتف: <span class="text-truncate-custom d-inline-block" style="max-width: calc(50% - 20px);">{{ $item->phone ?? '-' }}</span>
                                    · عنوان: <span class="text-truncate-custom d-inline-block" style="max-width: calc(50% - 20px);">{{ Str::limit($item->address ?? '-', 40) }}</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="h6 mb-1 text-success">{{ number_format($item->total_amount, 2) }} ج.م</div>
                                <div class="small text-muted">استحقاق: {{ \Carbon\Carbon::parse($item->due_date)->format('Y-m-d') }}</div>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex flex-column text-start flex-grow-1" style="min-width: 0;">
                                <div class="small text-muted">مدفوع: <span class="fw-bold">{{ number_format($item->paidAmount(), 2) }}</span></div>
                                <div class="small text-muted">المتبقي: <span class="fw-bold text-danger">{{ number_format($item->remainingAmount(), 2) }}</span></div>
                            </div>

                            <div class="d-flex gap-1 flex-wrap justify-content-end action-buttons-mobile flex-shrink-0">
                                @if($item->status != 'paid')
                                    <button type="button"
                                        class="btn btn-success btn-sm btn-pay-installment"
                                        data-installment-id="{{ $item->id }}"
                                        data-installment-name="{{ $item->name }}"
                                        data-remaining="{{ number_format($item->remainingAmount(), 2, '.', '') }}"
                                        data-pay-submit-url="{{ \Illuminate\Support\Facades\Route::has('installments.pay.submit') ? route('installments.pay.submit', $item->id) : url('/installments/'.$item->id.'/pay') }}"
                                        title="دفع القسط">
                                        <i class="bi bi-cash"></i>
                                    </button>
                                @endif

                                <button class="btn btn-info btn-sm btn-show-installment"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-owner_name="{{ $item->owner_name }}"
                                    data-total_amount="{{ number_format($item->total_amount,2,'.','') }}"
                                    data-installments_count="{{ $item->installments_count ?? '' }}"
                                    data-installment_value="{{ $item->installment_value ?? '' }}"
                                    data-paid_amount="{{ number_format($item->paidAmount(),2,'.','') }}"
                                    data-remaining="{{ number_format($item->remainingAmount(),2,'.','') }}"
                                    data-due_date="{{ $item->due_date ?? '' }}"
                                    data-created_at="{{ optional($item->created_at)->toDateString() }}"
                                    data-status="{{ $item->status }}"
                                    data-notes="{{ $item->notes }}"
                                    data-phone="{{ $item->phone ?? '' }}"
                                    data-address="{{ $item->address ?? '' }}"
                                    data-attachments='@json($attachmentsMobile)'
                                    data-payments='@json($paymentsMobile)'
                                    data-export-url="{{ $perExportUrl }}"
                                    title="عرض التفاصيل">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button class="btn btn-outline-primary btn-sm btn-export-installment"
                                    data-id="{{ $item->id }}"
                                    data-export-url="{{ $perExportUrl }}"
                                    title="تصدير PDF">
                                    <i class="bi bi-download"></i>
                                </button>

                                <button class="btn btn-primary btn-sm btn-edit-installment"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-owner_name="{{ $item->owner_name }}"
                                    data-total_amount="{{ $item->total_amount }}"
                                    data-installments_count="{{ $item->installments_count ?? 1 }}"
                                    data-installment_value="{{ $item->installment_value ?? '' }}"
                                    data-paid_amount="{{ $item->paidAmount() }}"
                                    data-due_date="{{ $item->due_date ?? '' }}"
                                    data-created_at="{{ optional($item->created_at)->toDateString() }}"
                                    data-status="{{ $item->status }}"
                                    data-notes="{{ $item->notes }}"
                                    data-phone="{{ $item->phone ?? '' }}"
                                    data-address="{{ $item->address ?? '' }}"
                                    data-update-url="{{ route('installments.update', $item->id) }}"
                                    title="تعديل القسط">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form action="{{ route('installments.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسط؟')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="حذف القسط"><i class="bi bi-trash"></i></button>
                                </form>

                                @if(auth()->user()->google_token ?? false)
                                    <form action="{{ route('installments.addToGoogle', $item->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                       <button class="btn-action outline-success w-100"><i class="bi bi-calendar-plus"></i> إضافة لتقويم جوجل</button>
                                    </form>
                                @else
                                <a href="{{ route('google.redirect') }}" class="btn-action outline-info w-100 mt-2"><i class="bi bi-google"></i> ربط مع جوجل</a>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <div>
                                @if($item->status == 'paid')
                                    <span class="badge bg-success">مدفوع</span>
                                @else
                                    <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted p-4">لا توجد أقساط لعرضها.</div>
        @endforelse
    </div>

    @if ($installments->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $installments->links() }}
        </div>
    @endif
</div>

<div class="row mt-3 g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">إجماليات يومية (لكل تاريخ)</div>
            <div class="card-body p-2" style="max-height:320px;overflow:auto;">
                @if(isset($dailyTotals) && $dailyTotals->count())
                    <table class="table table-sm mb-0">
                        <thead><tr><th>التاريخ</th><th class="text-end">الإجمالي</th></tr></thead>
                        <tbody>
                            @foreach($dailyTotals as $d)
                                <tr><td>{{ $d['date'] }}</td><td class="text-end text-success">{{ number_format($d['total'],2) }} ج.م</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted p-2">لا توجد بيانات</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">إجماليات شهرية (لكل شهر)</div>
            <div class="card-body p-2" style="max-height:320px;overflow:auto;">
                @if(isset($monthlyTotals) && $monthlyTotals->count())
                    <table class="table table-sm mb-0">
                        <thead><tr><th>الشهر</th><th class="text-end">الإجمالي</th></tr></thead>
                        <tbody>
                            @foreach($monthlyTotals as $m)
                                <tr><td>{{ $m['label'] }} ({{ $m['month'] }})</td><td class="text-end text-success">{{ number_format($m['total'],2) }} ج.م</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted p-2">لا توجد بيانات</div>
                @endif
            </div>
        </div>
    </div>
</div>
                  
        </div>
    </div> <!-- /.container (max-width) -->
</div> <!-- /.container-fluid -->
<style>
/* App-like cards and responsive tweaks */
.installment-card { border-radius: 12px; }
.installment-card .card-body { padding: 12px; }
.table-responsive { -webkit-overflow-scrolling: touch; overflow-x:auto; }
.card-body { overflow: visible; }
@media (min-width: 1200px){
    .table { width: 100%; }
    .table th, .table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
}
@media (max-width:767.98px) {
    .table th, .table td { font-size: 0.94rem !important; padding: 0.28rem 0.12rem !important; }
    .table .btn, .table .badge { font-size: 0.93rem !important; padding: 0.14rem 0.5rem !important; }
}
@media (max-width:575.98px) {
    .table th, .table td { font-size: 0.9rem !important; padding: 0.2rem 0.07rem !important; }
    .btn { font-size: .86rem !important; }
}
.modal-lg { max-width: 900px; }
.modal-xl { max-width: 1100px; }
.text-nowrap { white-space: nowrap !important; }
@media (max-width: 767.98px) {
  .installment-card .card-body {
    overflow: hidden;
    padding: 10px;
  }
  .text-truncate-custom {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    max-width: 100%;
  }
  .text-break-all-custom {
    word-break: break-all;
    overflow-wrap: break-word;
    max-width: 100%;
    display: block;
  }
  .action-buttons-mobile {
    justify-content: center !important;
    width: 100%;
  }
  .action-buttons-mobile .btn {
    flex-grow: 1;
    max-width: 48px;
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .action-buttons-mobile .btn i {
      font-size: 1.1rem;
  }
  #show_installment_details_table th { width:120px; }
  #show_installment_payments_table thead, #show_installment_attachments_table thead { display:none; }
  #show_installment_payments_table tbody tr, #show_installment_attachments_table tbody tr {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px;
    border-bottom:1px solid #eee;
    align-items: flex-end;
  }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        // Helper shortcuts
        const $id = id => document.getElementById(id) || null;

        function safeFloat(v){
            if (v === null || v === undefined) return 0;
            const s = String(v).replace(/[٬, ]+/g,'').trim().replace(/٫/g, '.');
            const n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }
        function safeInt(v){
            const n = parseInt(String(v||'').replace(/[٬, ]+/g,'').trim(), 10);
            return isNaN(n) ? 0 : n;
        }
        function safeParseJSON(s){
            try{ return JSON.parse(s || '[]'); }catch(e){ return []; }
        }

        // Formatting helpers
        function fmtCurrency(v){
            const n = Number(v || 0);
            try {
                return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م';
            } catch(e) {
                return (n.toFixed ? n.toFixed(2) : String(n)) + ' ج.م';
            }
        }
        function fmtDate(d){
            if(!d) return '-';
            try {
                const str = String(d).trim();
                const match = str.match(/(\d{4})[-\/](\d{2})[-\/](\d{2})/);
                if(match) return `${match[1]}-${match[2]}-${match[3]}`;
                const dt = new Date(str);
                if(!isNaN(dt)) return dt.toISOString().slice(0,10);
                return str.split(' ')[0] || '-';
            } catch(e){
                return String(d).split(' ')[0] || '-';
            }
        }

        function formatPaymentsCount(n){
            if(!n || n === 0) return 'لا توجد دفعات';
            if(n === 1) return '1 دفعة';
            return n + ' دفعات';
        }

        function setStatusBadge(raw){
            const el = $id('show_status');
            if(!el) return;
            let key = String(raw || '').trim().toLowerCase();
            if(key === 'قيد الانتظار' || key === 'قيد' || key === 'waiting') key = 'pending';
            if(key === 'تم الدفع' || key === 'مدفوع' || key === 'paid') key = 'paid';
            let html = '<span class="badge bg-secondary">غير معروف</span>';
            if(key === 'pending') html = '<span class="badge bg-warning text-dark">قيد الانتظار</span>';
            if(key === 'paid') html = '<span class="badge bg-success">تم الدفع</span>';
            el.innerHTML = html;
        }

        // Calculators
        function calcAddInstallmentValue(){
            const totalEl = $id('add_total_amount'); const countEl = $id('add_installments_count'); const valueEl = $id('add_installment_value');
            if(!totalEl || !countEl || !valueEl) return;
            let total = safeFloat(totalEl.value), count = Math.max(1, safeInt(countEl.value) || 1);
            if(count<1){ count=1; countEl.value = 1; }
            const v = (count>0)?(total/count):0; valueEl.value = (isFinite(v)?v:0).toFixed(2);
        }
        function calcEditInstallmentValue(){
            const totalEl = $id('edit_total_amount'); const countEl = $id('edit_installments_count'); const valueEl = $id('edit_installment_value');
            if(!totalEl || !countEl || !valueEl) return;
            let total = safeFloat(totalEl.value), count = Math.max(1, safeInt(countEl.value) || 1);
            if(count<1){ count=1; countEl.value = 1; }
            const v = (count>0)?(total/count):0; valueEl.value = (isFinite(v)?v:0).toFixed(2);
        }

        // Live listeners for calculators
        $id('add_total_amount')?.addEventListener('input', calcAddInstallmentValue);
        $id('add_installments_count')?.addEventListener('input', calcAddInstallmentValue);
        $id('edit_total_amount')?.addEventListener('input', calcEditInstallmentValue);
        $id('edit_installments_count')?.addEventListener('input', calcEditInstallmentValue);

        // Previews
        function previewFilesFromList(list, previewEl){
            if(!previewEl) return; previewEl.innerHTML = '';
            (list||[]).forEach(a=>{
                const p = a.file_path || a.path || a.url || a || '';
                if(!p) return;
                const ext = (p.split('.').pop()||'').toLowerCase();
                const url = (p.startsWith('http') ? p : (window.location.origin + '/storage_link/' + p));
                if(['jpg','jpeg','png','gif','webp'].includes(ext)){
                    const aTag = document.createElement('a'); aTag.href = url; aTag.target='_blank'; aTag.rel='noopener noreferrer';
                    aTag.title = p.split('/').pop();
                    const img = document.createElement('img'); img.src = url;
                    img.style.width='60px'; img.style.height='60px'; img.style.objectFit='cover'; img.style.border='1.5px solid #ddd'; img.style.borderRadius='6px'; img.style.margin='3px';
                    aTag.appendChild(img); previewEl.appendChild(aTag);
                } else if(ext==='pdf'){
                    const aTag = document.createElement('a'); aTag.href = url; aTag.target='_blank'; aTag.rel='noopener noreferrer'; aTag.style.margin='6px';
                    aTag.innerHTML = '<i class="bi bi-file-earmark-pdf" style="font-size:2rem;color:#c00"></i>';
                    aTag.title = p.split('/').pop();
                    previewEl.appendChild(aTag);
                } else {
                    const aTag = document.createElement('a'); aTag.href = url; aTag.target='_blank'; aTag.rel='noopener noreferrer'; aTag.style.margin='6px';
                    aTag.innerHTML = '<i class="bi bi-paperclip" style="font-size:2rem"></i>';
                    aTag.title = p.split('/').pop();
                    previewEl.appendChild(aTag);
                }
            });
        }

        function previewInputFiles(inputEl, previewEl){
            if(!inputEl || !previewEl) return; previewEl.innerHTML = '';
            Array.from(inputEl.files||[]).forEach(file=>{
                if(file.type && file.type.startsWith('image/')){
                    const r = new FileReader(); r.onload = e => {
                        const img = document.createElement('img'); img.src = e.target.result;
                        img.style.width='60px'; img.style.height='60px'; img.style.objectFit='cover'; img.style.border='2px solid #999'; img.style.borderRadius='8px'; img.style.margin='3px';
                        previewEl.appendChild(img);
                    }; r.readAsDataURL(file);
                } else if(file.type === 'application/pdf'){
                    const sp = document.createElement('span'); sp.innerHTML = '<i class="bi bi-file-earmark-pdf" style="font-size:2.2rem;color:#c00"></i>'; previewEl.appendChild(sp);
                } else {
                    const sp = document.createElement('span'); sp.innerHTML = '<i class="bi bi-paperclip" style="font-size:2.2rem"></i>'; previewEl.appendChild(sp);
                }
            });
        }

        $id('addInstallmentAttachments')?.addEventListener('change', ()=> {
            const preview = $id('addInstallmentPreview'); previewInputFiles($id('addInstallmentAttachments'), preview);
        });
        $id('editInstallmentAttachments')?.addEventListener('change', ()=> {
            const preview = $id('editInstallmentPreview'); previewInputFiles($id('editInstallmentAttachments'), preview);
        });

        // Open add modal
        $id('openAddInstallmentBtn')?.addEventListener('click', function(){
            $id('add-installment-tab')?.click();
            const addForm = $id('addInstallmentForm');
            if(addForm) addForm.reset();
            if($id('add_installments_count')) $id('add_installments_count').value = 1;
            calcAddInstallmentValue();
            $id('addInstallmentSubmitBtn')?.classList.remove('d-none');
            $id('editInstallmentSubmitBtn')?.classList.add('d-none');
            if($id('editInstallmentForm')) $id('editInstallmentForm').style.display = 'none';
            if($id('editInstallmentInfo')) $id('editInstallmentInfo').style.display = '';
        });

        // Patch pay anchors and observe DOM
        function patchExistingPayAnchors(){
            document.querySelectorAll('a[data-installment-pay]').forEach(a=>{
                try{ a.removeAttribute('target'); }catch(e){}
                try{ a.setAttribute('rel','noopener'); }catch(e){}
                try{ if(!a.getAttribute('data-pay-url')) a.setAttribute('data-pay-url', a.getAttribute('href') || ''); }catch(e){}
            });
        }
        patchExistingPayAnchors();

        const payAnchorObserver = new MutationObserver(mutations => {
            for(const m of mutations){
                if(!m.addedNodes) continue;
                m.addedNodes.forEach(node => {
                    if(node.nodeType !== 1) return;
                    try {
                        if(node.matches && node.matches('a[data-installment-pay]')) {
                            node.removeAttribute('target'); node.setAttribute('rel','noopener'); if(!node.getAttribute('data-pay-url')) node.setAttribute('data-pay-url', node.getAttribute('href')||'');
                        }
                        node.querySelectorAll && node.querySelectorAll('a[data-installment-pay]').forEach(n=>{
                            n.removeAttribute('target'); n.setAttribute('rel','noopener'); if(!n.getAttribute('data-pay-url')) n.setAttribute('data-pay-url', n.getAttribute('href')||'');
                        });
                    } catch(e){}
                });
            }
        });
        payAnchorObserver.observe(document.documentElement || document.body, { childList: true, subtree: true });

        // Defensive touchstart interceptor (mobile)
        document.addEventListener('touchstart', function touchStartPayInterceptor(e){
            try {
                const el = e.target.closest ? e.target.closest('button.btn-pay-installment, a[data-installment-pay]') : null;
                if(!el) return;
                if(e.touches && e.touches.length > 1) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                if (el.tagName === 'A') {
                    try { el.removeAttribute('target'); } catch(_) {}
                    try { el.setAttribute('rel','noopener'); } catch(_) {}
                    try { if(!el.getAttribute('data-pay-url')) el.setAttribute('data-pay-url', el.getAttribute('href') || ''); } catch(_) {}
                }
                if (typeof handlePay === 'function') {
                    handlePay(el);
                } else {
                    const url = el.getAttribute('data-pay-url') || el.getAttribute('href') || (el.dataset && el.dataset.paySubmitUrl) || '';
                    if(url) window.location.href = url;
                }
            } catch (err) {
                console.error('touchStartPayInterceptor error', err);
            }
        }, { passive: false, capture: true });

        // exportInstallmentBtn click handler
        $id('exportInstallmentBtn')?.addEventListener('click', function(){
            const url = this.getAttribute('data-export-url') || '';
            if(url) {
                window.location.href = url;
            } else {
                const id = this.getAttribute('data-installment-id') || '';
                if(id) window.location.href = '/installments/' + id + '/export';
            }
        });

        // Delegated click handler
        document.addEventListener('click', function(e){
            try {
                const payBtn = e.target.closest ? e.target.closest('button.btn-pay-installment') : null;
                if (payBtn) {
                    if (e.metaKey || e.ctrlKey || e.shiftKey || (e.button && e.button !== 0)) return;
                    e.preventDefault();
                    handlePay(payBtn);
                    return;
                }
                const payA = e.target.closest ? e.target.closest('a[data-installment-pay]') : null;
                if (payA) {
                    if (e.metaKey || e.ctrlKey || e.shiftKey || (e.button && e.button !== 0)) return;
                    e.preventDefault();
                    try{ payA.removeAttribute('target'); }catch(e){}
                    try{ payA.setAttribute('rel','noopener'); }catch(e){}
                    const url = payA.getAttribute('data-pay-url') || payA.getAttribute('href') || '';
                    if(url) window.location.href = url;
                    return;
                }
                const showBtn = e.target.closest ? e.target.closest('.btn-show-installment') : null;
                if(showBtn){ e.preventDefault(); handleShow(showBtn); return; }
                const exportBtn = e.target.closest ? e.target.closest('.btn-export-installment') : null;
                if(exportBtn){ e.preventDefault(); handleExportInstallment(exportBtn); return; }
                const editBtn = e.target.closest ? e.target.closest('.btn-edit-installment') : null;
                if(editBtn){ e.preventDefault(); handleEdit(editBtn); return; }
            } catch(err){
                console.error('Delegated click handler error:', err);
            }
        }, false);

        // handlePay
        function handlePay(btn){
            const get = a => btn.getAttribute(a) || '';
            const id = get('data-installment-id') || (btn.dataset && btn.dataset.installmentId) || '';
            const name = get('data-installment-name') || (btn.dataset && btn.dataset.installmentName) || '';
            const remainingRaw = get('data-remaining') || (btn.dataset && btn.dataset.remaining) || '';
            const paySubmitUrl = get('data-pay-submit-url') || (btn.dataset && btn.dataset.paySubmitUrl) || '';
            if (id) {
                const modal = $id('payInstallmentModal');
                if (modal) {
                    $id('pay_installment_id').value = id;
                    $id('pay_installment_name').value = name;
                    $id('pay_remaining_display').value = (remainingRaw ? (parseFloat(String(remainingRaw).replace(',', '.')).toFixed(2) + ' ج.م') : '');
                    const amountInput = $id('pay_amount_input');
                    if (amountInput) {
                        const rem = parseFloat(String(remainingRaw).replace(',', '.')) || 0;
                        amountInput.value = rem > 0 ? rem.toFixed(2) : '';
                        amountInput.setAttribute('max', rem.toFixed(2));
                        amountInput.classList.remove('is-invalid');
                    }
                    const form = $id('payInstallmentForm');
                    const actionUrl = (paySubmitUrl && paySubmitUrl !== '') ? paySubmitUrl : ('/installments/' + id + '/pay');
                    if (form) form.setAttribute('action', actionUrl);
                    try { bootstrap.Modal.getOrCreateInstance(modal).show(); } catch(e){ console.error(e); }
                    return;
                }
                if (paySubmitUrl) {
                    window.location.href = paySubmitUrl;
                    return;
                }
            }
            if (paySubmitUrl) {
                window.location.href = paySubmitUrl;
                return;
            }
        }

        // Pay form AJAX submit
        (function(){
            const payForm = $id('payInstallmentForm');
            if (!payForm) return;
            payForm.addEventListener('submit', async function(e){
                e.preventDefault();
                const submitBtn = payForm.querySelector('button[type="submit"]');
                const origHtml = submitBtn ? submitBtn.innerHTML : null;
                try {
                    const amountEl = $id('pay_amount_input');
                    const idEl = $id('pay_installment_id');
                    const errEl = $id('payAmountError');
                    const remainingRaw = (idEl && document.querySelector('.btn-pay-installment[data-installment-id="'+idEl.value+'"]')?.getAttribute('data-remaining')) || '';
                    const remaining = parseFloat(String(remainingRaw).replace(',', '.')) || 0;
                    if (!amountEl) return;
                    let val = parseFloat(String(amountEl.value).replace(',', '.'));
                    if (isNaN(val) || val <= 0) {
                        amountEl.classList.add('is-invalid');
                        if (errEl) { errEl.textContent = 'رجاءً أدخل مبلغاً صحيحاً أكبر من صفر.'; errEl.style.display = ''; }
                        amountEl.focus();
                        return;
                    }
                    if (val > remaining && remaining > 0) {
                        amountEl.value = remaining.toFixed(2);
                        val = remaining;
                    }
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري...'; }
                    const action = payForm.getAttribute('action') || ('/installments/' + (idEl ? idEl.value : '') + '/pay');
                    const formData = new FormData(payForm);
                    const res = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });
                    let json = null;
                    const ct = res.headers.get('content-type') || '';
                    if (ct.indexOf('application/json') !== -1) {
                        try { json = await res.json(); } catch(e){ json = null; }
                    }
                    if (res.ok) {
                        try { bootstrap.Modal.getOrCreateInstance(document.getElementById('payInstallmentModal')).hide(); } catch(e){}
                        (function showTempAlert(msg, timeout=1500){
                            const a = document.createElement('div');
                            a.className = 'alert alert-success position-fixed';
                            a.style.zIndex = 99999; a.style.right = '16px'; a.style.top = '16px';
                            a.innerText = msg;
                            document.body.appendChild(a);
                            setTimeout(()=> a.remove(), timeout);
                        })('تم تسجيل الدفع بنجاح');
                        if (json && json.redirect) { window.location.href = json.redirect; return; }
                        setTimeout(()=> window.location.reload(), 700);
                        return;
                    }
                    if (res.status === 422 && json && json.errors) {
                        const firstField = Object.keys(json.errors)[0];
                        const firstMsg = json.errors[firstField] && json.errors[firstField][0] ? json.errors[firstField][0] : 'حدث خطأ في الإدخال';
                        if (errEl) { errEl.textContent = firstMsg; errEl.style.display = ''; }
                        if (amountEl) amountEl.classList.add('is-invalid');
                        return;
                    }
                    window.location.reload();
                } catch (err) {
                    console.error('Pay submit error', err);
                    window.location.reload();
                } finally {
                    if (submitBtn) { submitBtn.disabled = false; if (origHtml) submitBtn.innerHTML = origHtml; }
                }
            });
        })();

        // handleShow: fill modal with data
        function handleShow(btn){
            const get = a => btn.getAttribute(a) || '';

            if($id('show_name')) $id('show_name').textContent = get('data-name') || '-';
            if($id('show_owner_name')) $id('show_owner_name').textContent = get('data-owner_name') || '-';
            if($id('show_phone')) $id('show_phone').textContent = get('data-phone') || '-';
            if($id('show_address')) $id('show_address').textContent = get('data-address') || '-';

            const createdRaw = get('data-created_at') || get('data-created') || get('data-created-at') || '';
            const updatedRaw = get('data-updated_at') || get('data-updated') || get('data-updated-at') || get('data-modified') || '';
            if($id('show_created_at')) $id('show_created_at').textContent = createdRaw ? fmtDate(createdRaw) : '-';
            if($id('show_updated_at')) $id('show_updated_at').textContent = updatedRaw ? fmtDate(updatedRaw) : '-';

            const totalRaw = get('data-total_amount') || get('data-amount') || '';
            const paidRaw = get('data-paid_amount') || get('data-paid') || '';
            const remRaw = get('data-remaining') || '';
            if($id('show_total_amount')) $id('show_total_amount').textContent = fmtCurrency(totalRaw);
            if($id('show_paid_amount')) $id('show_paid_amount').textContent = fmtCurrency(paidRaw);
            if($id('show_remaining_amount')) $id('show_remaining_amount').textContent = fmtCurrency(remRaw);
            if($id('show_due_date')) $id('show_due_date').textContent = (get('data-due_date') || '').split(' ')[0] || '-';

            setStatusBadge(get('data-status') || '');

            if($id('show_notes')) $id('show_notes').textContent = get('data-notes') || '';

            const atts = safeParseJSON(btn.getAttribute('data-attachments') || btn.getAttribute('data-attachments-json') || '[]');
            previewFilesFromList(atts, $id('show_attachments'));

            // payments
            const payments = safeParseJSON(btn.getAttribute('data-payments') || '[]');
            const paymentsTbody = $id('show_payments');
            if(paymentsTbody){
                paymentsTbody.innerHTML = '';
                if(payments && payments.length){
                    payments.forEach(p => {
                        const tr = document.createElement('tr');

                        const tdDate = document.createElement('td');
                        tdDate.className = 'text-nowrap';
                        tdDate.textContent = fmtDate(p.payment_date || p.date || p.created_at || '');

                        const tdPayer = document.createElement('td');
                        tdPayer.className = 'text-start';
                        tdPayer.textContent = p.payer_name || p.payer || p.user_name || '-';

                        const tdAmount = document.createElement('td');
                        tdAmount.className = 'text-nowrap fw-bold text-success';
                        tdAmount.textContent = fmtCurrency(p.amount || p.value || 0);

                        const tdNotes = document.createElement('td');
                        tdNotes.className = 'text-start small text-muted';
                        tdNotes.textContent = p.notes || p.note || '';

                        tr.appendChild(tdDate);
                        tr.appendChild(tdPayer);
                        tr.appendChild(tdAmount);
                        tr.appendChild(tdNotes);

                        paymentsTbody.appendChild(tr);
                    });
                } else {
                    const tr = document.createElement('tr');
                    tr.className = 'no-payments';
                    const td = document.createElement('td');
                    td.colSpan = 4;
                    td.className = 'text-center small text-muted';
                    td.textContent = btn.getAttribute('data-payments-summary') || 'لا توجد دفعات مسجلة';
                    tr.appendChild(td);
                    paymentsTbody.appendChild(tr);
                }
                const realRows = Array.from(paymentsTbody.querySelectorAll('tr')).filter(r => !r.classList.contains('no-payments'));
                const cnt = realRows.length;
                const counterEl = $id('paymentsCount');
                if(counterEl) counterEl.textContent = formatPaymentsCount(cnt);
            }

            const exportUrl = get('data-export-url') || '';
            if($id('exportInstallmentBtn')){
                if(exportUrl) $id('exportInstallmentBtn').setAttribute('data-export-url', exportUrl);
                else $id('exportInstallmentBtn').removeAttribute('data-export-url');
                $id('exportInstallmentBtn').setAttribute('data-installment-id', get('data-id') || '');
            }

            const modal = $id('showInstallmentModal');
            if(modal) bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        // Observe show payments tbody for external changes
        (function observePaymentsTbody(){
            const tbody = $id('show_payments');
            const counterEl = $id('paymentsCount');
            if(!tbody || !counterEl) return;
            const mo = new MutationObserver(() => {
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => !r.classList.contains('no-payments'));
                counterEl.textContent = formatPaymentsCount(rows.length);
            });
            mo.observe(tbody, { childList:true, subtree:true, characterData:true });
        })();

        // export per-installment
        function handleExportInstallment(btn){
            const exportUrl = btn.getAttribute('data-export-url') || '';
            const id = btn.getAttribute('data-id') || '';
            if(exportUrl){ window.location.href = exportUrl; return; }
            const showBtn = document.querySelector('.btn-show-installment[data-id="'+id+'"]');
            if(showBtn){ handleShow(showBtn); setTimeout(()=> $id('printInstallmentBtn')?.click(), 450); }
        }

        // print handler
        $id('printInstallmentBtn')?.addEventListener('click', function(){
            const content = $id('showInstallmentBody'); if(!content) return;
            const logo = @json(asset('images/logo.png'));
            const w = window.open('', '_blank', 'width=900,height=700,scrollbars=yes');
            const html = `
                <html><head><meta charset="utf-8"><title>تفاصيل القسط</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>body{font-family: DejaVu Sans, sans-serif;direction:rtl;padding:20px;} .table td,.table th{vertical-align:middle;}</style>
                </head><body>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div><img src="${logo}" style="height:40px;" onerror="this.style.display='none'"></div>
                        <div style="text-align:center;"><h4>تفاصيل القسط</h4><small>${new Date().toLocaleString()}</small></div>
                    </div>
                    ${content.innerHTML}
                    <script>window.onload=function(){ window.print(); }<\/script>
                </body></html>`;
            w.document.open(); w.document.write(html); w.document.close();
        });

        // Edit handler
        function handleEdit(btn){
            const editForm = $id('editInstallmentForm');
            const updateUrl = btn.getAttribute('data-update-url') || '';
            if(editForm && updateUrl) editForm.setAttribute('action', updateUrl);

            $id('edit-installment-tab')?.click();
            if(editForm) editForm.style.display = '';
            $id('editInstallmentInfo') && ($id('editInstallmentInfo').style.display = 'none');

            $id('addInstallmentSubmitBtn')?.classList.add('d-none');
            $id('editInstallmentSubmitBtn')?.classList.remove('d-none');

            $id('edit_installment_id') && ($id('edit_installment_id').value = btn.getAttribute('data-id') || '');
            $id('edit_name') && ($id('edit_name').value = btn.getAttribute('data-name') || '');
            $id('edit_owner_name') && ($id('edit_owner_name').value = btn.getAttribute('data-owner_name') || '');
            $id('edit_total_amount') && ($id('edit_total_amount').value = btn.getAttribute('data-total_amount') || '');
            $id('edit_installments_count') && ($id('edit_installments_count').value = btn.getAttribute('data-installments_count') || 1);
            $id('edit_installment_value') && ($id('edit_installment_value').value = btn.getAttribute('data-installment_value') || '');
            $id('edit_paid_amount') && ($id('edit_paid_amount').value = btn.getAttribute('data-paid_amount') || 0);

            const dueRaw = btn.getAttribute('data-due_date') || '';
            const created = btn.getAttribute('data-created_at') || '';
            if($id('edit_due_date')) {
                if(dueRaw) $id('edit_due_date').value = dueRaw.split(' ')[0];
                else if(created) $id('edit_due_date').value = created.split(' ')[0];
                else $id('edit_due_date').value = new Date().toISOString().slice(0,10);
            }

            $id('edit_status') && ($id('edit_status').value = btn.getAttribute('data-status') || 'pending');
            $id('edit_notes') && ($id('edit_notes').value = btn.getAttribute('data-notes') || '');
            $id('edit_phone') && ($id('edit_phone').value = btn.getAttribute('data-phone') || '');
            $id('edit_address') && ($id('edit_address').value = btn.getAttribute('data-address') || '');

            const editPreview = $id('editInstallmentPreview');
            if(editPreview){
                const atts = safeParseJSON(btn.getAttribute('data-attachments') || '[]');
                if(atts.length) previewFilesFromList(atts, editPreview);
                else editPreview.innerHTML = '';
            }

            const modalEl = $id('installmentModal');
            if(modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        // Bulk selection
        const selectAll = $id('selectAllRows');
        function getRowCheckboxes(){ return Array.from(document.querySelectorAll('.row-checkbox')); }
        function updateBulkState(){
            const boxes = getRowCheckboxes();
            const checked = boxes.filter(b=>b.checked).map(b=>b.value);
            const holder = $id('bulk_ids_holder');
            if(holder) holder.value = checked.join(',');
            const bulkBtn = $id('bulkDeleteBtn');
            if(bulkBtn) bulkBtn.disabled = checked.length === 0;
            if(selectAll){
                if(checked.length === boxes.length && boxes.length>0){ selectAll.checked = true; selectAll.indeterminate = false; }
                else if(checked.length>0){ selectAll.checked = false; selectAll.indeterminate = true; }
                else { selectAll.checked = false; selectAll.indeterminate = false; }
            }
        }
        document.addEventListener('change', function(e){
            if(e.target && e.target.classList && e.target.classList.contains('row-checkbox')) updateBulkState();
            if(e.target && e.target.id === 'selectAllRows'){ getRowCheckboxes().forEach(cb=>cb.checked = selectAll.checked); updateBulkState(); }
        });

        // Init calculators
        calcAddInstallmentValue();
        calcEditInstallmentValue();

        /* -------------------------
           Advanced search panel + robust client-side multi-criteria filter
        ------------------------- */

        // Toggle advanced search panel
        const toggleBtn = $id('openInstallmentsAdvancedSearchBtn');
        const searchBody = $id('installmentsAdvancedSearchBody');
        if (toggleBtn && searchBody) {
            toggleBtn.addEventListener('click', function() {
                if (searchBody.style.display === 'none' || !searchBody.style.display) {
                    searchBody.style.display = 'block';
                } else {
                    searchBody.style.display = 'none';
                }
            });
        }

        // Normalize helpers
        function normalizeText(text) { return String(text || '').trim().toLowerCase(); }
        function normalizeDate(dateStr) {
            if (!dateStr) return null;
            const match = String(dateStr).match(/(\d{4})[-\/](\d{2})[-\/](\d{2})/);
            return match ? `${match[1]}-${match[2]}-${match[3]}` : null;
        }
        function parseDateToObj(dateStr){
            const norm = normalizeDate(dateStr);
            if(!norm) return null;
            const [y,m,d] = norm.split('-').map(Number);
            return new Date(y, m-1, d);
        }

        // Find the correct tbody for the installments list (exclude modal tables)
        function findInstallmentsTbody(){
            const allTbody = Array.from(document.querySelectorAll('tbody'));
            function insideModal(el){ return !!el.closest && (el.closest('#showInstallmentModal') || el.closest('.modal')); }
            // prefer tbody with known action buttons or data attributes
            for(const tb of allTbody){
                if(insideModal(tb)) continue;
                if(tb.querySelector('.btn-show-installment, .btn-edit-installment, .btn-pay-installment')) return tb;
                if(tb.querySelector('[data-installment-id], [data-id]')) return tb;
            }
            // prefer tbody where rows have multiple columns
            for(const tb of allTbody){
                if(insideModal(tb)) continue;
                const rows = Array.from(tb.querySelectorAll('tr'));
                if(!rows.length) continue;
                const avgTd = rows.slice(0,6).reduce((acc,r)=> acc + (Array.from(r.children).filter(c=>c.tagName==='TD').length), 0) / Math.min(6, rows.length);
                if(avgTd >= 2) return tb;
            }
            // fallback: first non-modal tbody with any rows
            for(const tb of allTbody){
                if(insideModal(tb)) continue;
                if(tb.querySelectorAll('tr').length) return tb;
            }
            // last resort: any tbody
            return document.querySelector('tbody');
        }

        // Robust getRowData: pulls data-* from button/tr/children and falls back to cell text
        function getRowData(tr){
            const data = {};
            function copyDataAttrs(el){
                if(!el) return;
                for(const k in (el.dataset || {})){
                    if(Object.prototype.hasOwnProperty.call(el.dataset, k)){
                        if(!(k in data)) data[k] = el.dataset[k];
                    }
                }
                for(const attr of (el.attributes || [])){
                    if(attr && attr.name && attr.name.startsWith('data-')){
                        const key = attr.name.slice(5);
                        if(!(key in data)) data[key] = attr.value;
                    }
                }
            }
            // prefer action buttons
            const btn = tr.querySelector('.btn-show-installment, .btn-edit-installment, .btn-pay-installment');
            copyDataAttrs(btn);
            // merge tr attributes
            copyDataAttrs(tr);
            // merge other children having data-*
            tr.querySelectorAll('[data-id], [data-name], [data-phone], [data-status], [data-due_date], [data-attachments]').forEach(ch => copyDataAttrs(ch));
            // fallback text
            const cells = Array.from(tr.children).filter(n => n.tagName === 'TD' || n.tagName === 'TH');
            data._fallback_text = cells.map(td => (td.textContent || '').trim()).join(' ').toLowerCase();
            // try to detect common fields
            if(!data.due_date){
                const maybeDateCell = cells.find(td => /\d{4}[-\/]\d{2}[-\/]\d{2}/.test(td.textContent || ''));
                if(maybeDateCell) data.due_date = (maybeDateCell.textContent || '').trim();
            }
            if(!data.total_amount && !data.amount){
                const maybeNumCell = cells.find(td => /[\d٠-٩,.\s]+/.test(td.textContent || ''));
                if(maybeNumCell) data.total_amount = (maybeNumCell.textContent || '').replace(/[^\d.,-]/g, '').trim();
            }
            return data;
        }

        function inDateRange(dateStr, from, to){
            if(!dateStr) return true;
            const rowDate = parseDateToObj(dateStr) || parseDateToObj(String(dateStr).split(' ')[0]);
            if(!rowDate) return true;
            const f = from ? parseDateToObj(from) : null;
            const t = to ? parseDateToObj(to) : null;
            if(f && rowDate < f) return false;
            if(t && rowDate > t) return false;
            return true;
        }

        // Main filter (debounced)
        let filterTimeout = null;
        function filterInstallments(){
            if(filterTimeout) clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                const hasAttEl = $id('inst_search_has_attachments');
                let hasAttachmentsVal = '';
                if(hasAttEl){
                    if(hasAttEl.type === 'checkbox') hasAttachmentsVal = hasAttEl.checked ? '1' : '';
                    else hasAttachmentsVal = hasAttEl.value || '';
                }

                const criteria = {
                    q: normalizeText($id('inst_search_q')?.value || ''),
                    phone: normalizeText($id('inst_search_phone')?.value || ''),
                    address: normalizeText($id('inst_search_address')?.value || ''),
                    status: normalizeText($id('inst_search_status')?.value || ''),
                    dueFrom: $id('inst_search_due_from')?.value || '',
                    dueTo: $id('inst_search_due_to')?.value || '',
                    amountMin: safeFloat($id('inst_search_amount_min')?.value),
                    amountMax: safeFloat($id('inst_search_amount_max')?.value),
                    hasAttachments: hasAttachmentsVal
                };

                const tbody = findInstallmentsTbody();
                if(!tbody){
                    console.warn('filterInstallments: لم أجد tbody لقائمة الأقساط.');
                    return;
                }
                console.debug('filterInstallments: using tbody:', tbody.tagName, tbody.id, tbody.className, 'rows:', tbody.querySelectorAll('tr').length);

                const allRows = Array.from(tbody.querySelectorAll('tr'));
                let visibleCount = 0;

                allRows.forEach(tr => {
                    if(!tr || (tr.textContent || '').includes('لا توجد أقساط')) return;

                    const data = getRowData(tr);
                    let visible = true;

                    // general text
                    if(criteria.q){
                        const searchIn = normalizeText((data.name || '') + ' ' + (data.owner_name || '') + ' ' + (data.notes || '') + ' ' + data._fallback_text);
                        visible = visible && searchIn.includes(criteria.q);
                    }

                    // phone
                    if(visible && criteria.phone){
                        const phoneField = normalizeText(data.phone || data._fallback_text);
                        visible = visible && phoneField.includes(criteria.phone);
                    }

                    // address
                    if(visible && criteria.address){
                        const addressField = normalizeText(data.address || data._fallback_text);
                        visible = visible && addressField.includes(criteria.address);
                    }

                    // status
                    if(visible && criteria.status){
                        const statusField = normalizeText(data.status || '');
                        if(statusField) visible = visible && (statusField === criteria.status || statusField.includes(criteria.status) || data._fallback_text.includes(criteria.status));
                        else visible = visible && data._fallback_text.includes(criteria.status);
                    }

                    // amount
                    if(visible && (criteria.amountMin > 0 || criteria.amountMax > 0)){
                        const amount = safeFloat(data.total_amount || data.amount || 0);
                        if(criteria.amountMin > 0) visible = visible && (amount >= criteria.amountMin);
                        if(criteria.amountMax > 0) visible = visible && (amount <= criteria.amountMax);
                    }

                    // date
                    if(visible && (criteria.dueFrom || criteria.dueTo)){
                        const rowDate = data.due_date || data.created_at || '';
                        visible = visible && inDateRange(rowDate, criteria.dueFrom, criteria.dueTo);
                    }

                    // attachments
                    if(visible && criteria.hasAttachments !== ''){
                        try {
                            const atts = safeParseJSON(data.attachments || '[]');
                            const hasAtts = Array.isArray(atts) && atts.length > 0;
                            const wantAtts = criteria.hasAttachments === '1';
                            visible = hasAtts === wantAtts;
                        } catch(e){
                            const hasAtts = !!(data.attachments && data.attachments !== '[]' && data.attachments !== 'false');
                            const wantAtts = criteria.hasAttachments === '1';
                            visible = hasAtts === wantAtts;
                        }
                    }

                    tr.style.display = visible ? '' : 'none';
                    if(visible) visibleCount++;
                });

                console.log('عدد النتائج: ' + visibleCount);
            }, 200);
        }

        // Bind apply/reset and auto-filtering
        const applyBtn = $id('inst_advancedSearchApply');
        const resetBtn = $id('inst_advancedSearchReset');

        if (applyBtn) {
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterInstallments();
                if ($id('installmentsAdvancedSearchBody')) $id('installmentsAdvancedSearchBody').style.display = 'none';
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const fields = [
                    'inst_search_q','inst_search_phone','inst_search_address',
                    'inst_search_status','inst_search_due_from','inst_search_due_to',
                    'inst_search_amount_min','inst_search_amount_max','inst_search_has_attachments'
                ];
                fields.forEach(id => {
                    const el = $id(id);
                    if(!el) return;
                    if(el.type === 'checkbox') el.checked = false;
                    else el.value = '';
                });
                const tbody = findInstallmentsTbody();
                if(tbody) tbody.querySelectorAll('tr').forEach(r=> r.style.display = '');
                filterInstallments();
            });
        }

        // Auto-filter when typing/changing
        const searchFields = [
            'inst_search_q','inst_search_phone','inst_search_address',
            'inst_search_status','inst_search_due_from','inst_search_due_to',
            'inst_search_amount_min','inst_search_amount_max','inst_search_has_attachments'
        ];
        searchFields.forEach(id => {
            const el = $id(id);
            if(el){
                el.addEventListener('input', filterInstallments);
                el.addEventListener('change', filterInstallments);
            }
        });

        // initial run after short delay so dynamic tables render
        setTimeout(()=> filterInstallments(), 350);

    } catch (err) {
        console.error('Installments page error:', err);
    }
});
</script>
@endpush

    @extends('layouts.app')

@section('title', 'Nhân viên')

@section('breadcrumb')
  <li class="breadcrumb-item">Admin</li>
  <li class="breadcrumb-item">Quản lý</li>
  <li class="breadcrumb-item active">Nhân viên</li>
@endsection

@section('content')

  {{-- SORT HELPER --}}
  @php
    $sort = request('sort', '');
    $dir  = request('dir', '');
    $nextDir = function($col) use ($sort, $dir) {
      if ($sort !== $col) return 'asc';
      if ($dir === 'asc')  return 'desc';
      return '';
    };
    $sortUrl = function($col) use ($sort, $dir, $nextDir) {
      $nd = $nextDir($col);
      if ($nd === '') return request()->fullUrlWithQuery(['sort' => '', 'dir' => '', 'page' => 1]);
      return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nd, 'page' => 1]);
    };
    $sortIcon = function($col) use ($sort, $dir) {
      if ($sort !== $col || $dir === '') {
        $icon = 'cil-swap-vertical';
      } elseif ($dir === 'asc') {
        $icon = 'cil-sort-alpha-down';
      } else {
        $icon = 'cil-sort-alpha-up';
      }
      return "<svg class=\"icon icon-sm ms-1\"><use xlink:href=\"" . asset('vendor/coreui/icons/sprites/free.svg#' . $icon) . "\"></use></svg>";
    };
  @endphp

  {{-- HEADER --}}
  <div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" onclick="openEmployeeModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- BẢNG NHÂN VIÊN --}}
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="fw-semibold flex-shrink-0">Nhân viên</span>
      <form method="GET" action="{{ route('master.employee.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">
        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã, tên nhân viên hoặc số điện thoại">
        </div>

        <select class="form-select" name="department_id" style="min-width:170px;flex:1" onchange="this.form.submit()">
          <option value="">Bộ phận</option>
          @foreach ($departments as $dept)
            <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>
              {{ $dept->name }}
            </option>
          @endforeach
        </select>

        <select class="form-select" name="status" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Trạng thái</option>
          @foreach (\App\Enums\ActiveStatus::options() as $val => $label)
            <option value="{{ $val }}" {{ request('status') === (string) $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>

        @php
          $hasFilter = request('search') || request('department_id') || (request('status') !== null && request('status') !== '');
        @endphp
        @if ($hasFilter)
          <a href="{{ route('master.employee.index') }}" class="btn btn-outline-secondary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter-x') }}"></use></svg>
          </a>
        @else
          <button type="submit" class="btn btn-primary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
          </button>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:4%">#</th>
              <th style="width:8%">
                <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã NV {!! $sortIcon('code') !!}
                </a>
              </th>
              <th>
                <a href="{{ $sortUrl('name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Họ tên {!! $sortIcon('name') !!}
                </a>
              </th>
              <th style="width:10%">Số điện thoại</th>
              <th style="width:10%">Bộ phận</th>
              <th style="width:8%">Vai trò</th>
              <th style="width:18%">Tài khoản</th>
              <th style="width:10%">Ghi chú</th>
              <th class="text-center" style="width:8%">Trạng thái</th>
              <th class="text-center" style="width:10%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($employees as $index => $emp)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($employees->currentPage() - 1) * $employees->perPage() + $index + 1 }}
                </td>
                <td>
                  <code class="text-primary fw-medium">{{ $emp->code ?? '-' }}</code>
                </td>
                <td class="fw-medium">{{ $emp->name ?? '-' }}</td>
                <td class="small">
                  @if ($emp->phone_number)
                    <span class="text-body text-decoration-none">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-phone') }}"></use></svg>
                      {{ $emp->phone_number }}
                    </span>
                  @else
                    -
                  @endif
                </td>
                <td class="small">{{ $emp->department->name ?? '-' }}</td>

                {{-- Vai trò --}}
                <td class="small">
                  @if ($emp->account)
                    @php $roleName = $emp->account->getRoleNames()->first(); @endphp
                    {{ $roleName ?? '-' }}
                  @else
                    -
                  @endif
                </td>

                {{-- Tài khoản --}}
                <td class="small">
                  @if ($emp->account)
                    <span class="text-body text-decoration-none">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
                      {{ $emp->account->username }}
                    </span>
                  @else
                    -
                  @endif
                </td>

                {{-- Ghi chú --}}
                <td class="small" title="{{ $emp->note }}">
                {{ truncate_text($emp->note) }}
                </td>

                {{-- Trạng thái nhân viên --}}
                <td class="text-center">
                  @if ($emp->status === \App\Enums\ActiveStatus::Active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngưng</span>
                  @endif
                </td>

                {{-- Thao tác --}}
                <td class="text-center">
                  {{-- Sửa hồ sơ --}}
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openEmployeeModal(
                            {{ $emp->id }},
                            '{{ addslashes($emp->code) }}',
                            '{{ addslashes($emp->name) }}',
                            '{{ addslashes($emp->phone_number ?? '') }}',
                            {{ $emp->department_id ?? 'null' }},
                            '{{ addslashes($emp->note ?? '') }}',
                            {{ $emp->status->value }}
                          )"
                          title="Sửa hồ sơ">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>

                  {{-- Tài khoản --}}
                  @if ($emp->account)
                    <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="openAccountModal(
                              {{ $emp->id }},
                              '{{ addslashes($emp->name) }}',
                              '{{ addslashes($emp->code ?? '') }}',
                              {
                                username: '{{ $emp->account->username }}',
                                role: '{{ $emp->account->getRoleNames()->first() }}',
                                status: {{ $emp->account->status->value ?? 1 }}
                              }
                            )"
                            title="Chỉnh sửa tài khoản">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use></svg>
                    </button>
                  @else
                    <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="openAccountModal(
                              {{ $emp->id }},
                              '{{ addslashes($emp->name) }}',
                              '{{ addslashes($emp->code ?? '') }}'
                            )"
                            title="Thêm tài khoản">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user-plus') }}"></use></svg>
                    </button>
                  @endif

                  {{-- Xóa --}}
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete(
                            {{ $emp->id }},
                            '{{ addslashes($emp->name) }}',
                            {{ $emp->account ? 'true' : 'false' }}
                          )"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use>
                  </svg>
                  Chưa có nhân viên nào
                  @if(request('search'))
                    <div class="small mt-1">Không tìm thấy kết quả cho "<strong>{{ request('search') }}</strong>"</div>
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $employees->firstItem() }}</strong>-<strong>{{ $employees->lastItem() }}</strong>
        trong tổng số <strong>{{ $employees->total() }}</strong> nhân viên
      </small>
      {{ $employees->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>.card-footer .pagination { margin-bottom: 0; }</style>
    </div>
  </div>

  {{-- ===== MODAL: HỒ SƠ NHÂN VIÊN ===== --}}
  <div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="employeeForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="empMethod" value="POST">
          <input type="hidden" name="id" id="empFormId" value="{{ old('id') }}">

          <div class="modal-header">
            <h5 class="modal-title" id="employeeModalLabel">Thêm nhân viên</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">
              <div class="col-sm-4">
                <label class="form-label fw-medium">Mã</label>
                <input type="text"
                       class="form-control text-uppercase {{ $errors->has('code') ? 'is-invalid' : '' }}"
                       name="code" id="empCode"
                       value="{{ old('code') }}"
                       placeholder="Tự động" maxlength="20"
                    onblur="checkEmployeeCodeUnique(this, 'empCodeError')">
              <div class="invalid-feedback" id="empCodeError">@error('code'){{ $message }}@enderror</div>
              </div>
              <div class="col-sm-8">
                <label class="form-label fw-medium">Tên <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       name="name" id="empName"
                       value="{{ old('name') }}"
                       placeholder="Nhập họ và tên" maxlength="100" required
                       oninput="this.classList.remove('is-invalid')">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Bộ phận <span class="text-danger">*</span></label>
                <select class="form-select {{ $errors->has('department_id') ? 'is-invalid' : '' }}"
                        name="department_id" id="empDepartment" required
                        onchange="this.classList.remove('is-invalid')">
                  <option value="">- Chọn bộ phận -</option>
                  @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ (string) old('department_id') === (string) $dept->id ? 'selected' : '' }}>
                      {{ $dept->name }}
                    </option>
                  @endforeach
                </select>
                @error('department_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Số điện thoại</label>
                <input type="text"
                      class="form-control {{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
                      name="phone_number" id="empPhone"
                      value="{{ old('phone_number') }}"
                      placeholder="Nhập số điện thoại" maxlength="20"

                      inputmode="numeric"
                      onkeydown="blockInvalidNumberKeys(event)"
                      onpaste="blockInvalidNumberPaste(event)"
                      oninput="sanitizeDigitsOnly(this)">

                @error('phone_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Ghi chú</label>
                <textarea class="form-control" name="note" id="empNote"
                          rows="2" maxlength="500">{{ old('note') }}</textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Trạng thái</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status"
                           id="empStatusActive" value="1" checked>
                    <label class="form-check-label text-success" for="empStatusActive">Hoạt động</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status"
                           id="empStatusInactive" value="0">
                    <label class="form-check-label text-secondary" for="empStatusInactive">Ngưng hoạt động</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="empSubmitBtn" class="btn btn-primary">
              <span id="empSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              <svg id="empSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="empSubmitLabel">Lưu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== MODAL: TÀI KHOẢN (dùng chung Thêm/Sửa) ===== --}}
  <div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="accountForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="accMethod" value="POST">
          <input type="hidden" name="employee_id" id="accEmployeeId">

          <div class="modal-header">
            <h5 class="modal-title" id="accountModalLabel">Thêm tài khoản</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info py-2 mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
              <strong id="accEmpName"></strong>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-medium">Tên đăng nhập <span class="text-danger" id="accUsernameRequired">*</span></label>
                  <input type="text" class="form-control @error('username') is-invalid @enderror" name="username" id="accUsername"
                        placeholder="Chỉ dùng chữ thường, số và @ _ ." maxlength="100" value="{{ old('username') }}"
                        oninput="sanitizeUsernameInput(this); this.classList.remove('is-invalid')">
                  @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium" id="accPasswordLabel">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password"
                      class="form-control @error('password') is-invalid @enderror @error('new_password') is-invalid @enderror"
                      name="password" id="accPassword" placeholder="Tối thiểu 8 ký tự" maxlength="500"
                      oninput="this.classList.remove('is-invalid'); document.getElementById('accPasswordConfirm').classList.remove('is-invalid')">
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('new_password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium">Xác nhận mật khẩu <span class="text-danger" id="accPasswordConfirmRequired">*</span></label>
                <input type="password"
                      class="form-control @error('password_confirmation') is-invalid @enderror @error('new_password_confirmation') is-invalid @enderror"
                      name="password_confirmation" id="accPasswordConfirm" placeholder="Nhập lại mật khẩu" maxlength="500"
                      oninput="this.classList.remove('is-invalid'); document.getElementById('accPassword').classList.remove('is-invalid')">
                @error('password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('new_password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select @error('role') is-invalid @enderror" name="role" id="accRole" required
                        onchange="this.classList.remove('is-invalid')">
                  <option value="">- Chọn vai trò -</option>
                  @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                  @endforeach
                </select>
                @error('role')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium">Trạng thái</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="account_status"
                           id="accStatusActive" value="1" checked>
                    <label class="form-check-label text-success" for="accStatusActive">Hoạt động</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="account_status"
                           id="accStatusInactive" value="0">
                    <label class="form-check-label text-secondary" for="accStatusInactive">Ngưng hoạt động</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== MODAL: XÓA NHÂN VIÊN ===== --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center px-4 pb-2">
          <svg class="icon icon-3xl text-danger mb-3">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
          <h6 class="fw-semibold mb-1">Xác nhận xóa</h6>
          <p class="text-body-secondary small mb-0">
            Bạn có chắc muốn xóa nhân viên<br>
            <strong id="deleteEmpName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Hành động này không thể hoàn tác.</p>
          <p id="deleteAccountWarning" class="text-warning small mt-1 d-none">
            <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
            Tài khoản đăng nhập của nhân viên này cũng sẽ bị xóa theo.
          </p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  const routeStore   = '{{ route('master.employee.store') }}';
  const routeBase    = '{{ url('master/employee') }}';
  const routeAccBase = '{{ url('master/employee') }}'; // /{id}/account

  // ===== VALIDATE THỦ CÔNG (thay cho required, vì form trong .modal đã bị
  // gán novalidate ở footer.blade.php để đồng bộ lỗi qua server). Các form ở
  // đây tự xoá hết .invalid-feedback mỗi lần mở lại modal (xem openEmployeeModal/
  // openAccountModal) nên helper này tạo lại div nếu cần, thay vì giả định nó
  // luôn tồn tại sẵn trong HTML. =====
  function showFieldError(el, msg) {
    el.classList.add('is-invalid');
    let feedback = el.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      el.insertAdjacentElement('afterend', feedback);
    }
    feedback.textContent = msg;
  }

  function clearFieldError(el) {
    el.classList.remove('is-invalid');
  }

  // ===== HỒ SƠ NHÂN VIÊN =====
  function openEmployeeModal(id = null, code = '', name = '', phone = '', departmentId = null, note = '', status = 1, keepErrors = false) {
    const modal   = new coreui.Modal(document.getElementById('employeeModal'));
    const form    = document.getElementById('employeeForm');
    const title   = document.getElementById('employeeModalLabel');
    const method  = document.getElementById('empMethod');
    const codeEl  = document.getElementById('empCode');

    setModalFormId('empFormId', id);

    if (!keepErrors) {
      form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    document.getElementById('empName').value       = name;
    document.getElementById('empPhone').value      = phone;
    document.getElementById('empDepartment').value = departmentId ?? '';
    document.getElementById('empNote').value       = note;
    document.getElementById(status == 1 ? 'empStatusActive' : 'empStatusInactive').checked = true;

    if (id) {
      title.textContent = 'Chỉnh sửa nhân viên';
      form.action        = `${routeBase}/${id}`;
      method.value       = 'PUT';
      codeEl.value        = code;
      codeEl.readOnly      = true;
      codeEl.classList.add('bg-body-secondary');
    } else {
      title.textContent = 'Thêm nhân viên';
      form.action        = routeStore;
      method.value       = 'POST';
      if (!keepErrors) form.reset();
      codeEl.value         = keepErrors ? code : '';
      codeEl.readOnly      = false;
      codeEl.classList.remove('bg-body-secondary');
      if (!keepErrors) document.getElementById('empStatusActive').checked = true;
    }

    modal.show();
    setTimeout(() => (id ? document.getElementById('empName') : codeEl).focus(), 300);
  }

  // Auto viết hoa mã NV
  document.getElementById('empCode').addEventListener('input', function () {
    sanitizeCodeInput(this);
    this.classList.remove('is-invalid');
    const errEl = document.getElementById('empCodeError');
    if (errEl) errEl.textContent = '';
  });

  // ===== code.unique (AJAX, dùng lúc blur ở ô Mã) =====
  // Không check khi field đang readonly (đang Sửa -> mã bị khoá, luôn là mã
  // hiện tại của chính nó nên không thể trùng) hoặc khi để trống (hệ thống sẽ
  // tự sinh mã, không cần kiểm tra).
  async function checkEmployeeCodeUnique(inputEl, errorId) {
  const code = inputEl.value.trim();

  inputEl.classList.remove('is-invalid');
  clearFieldErrorEl(inputEl, errorId);
  if (!code || inputEl.readOnly) return;

  try {
    const res = await fetch(
      `{{ route('master.employee.checkCode') }}?code=${encodeURIComponent(code)}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );

    if (!res.ok) {
      console.error(
        `[checkEmployeeCodeUnique] AJAX thất bại: HTTP ${res.status} ${res.statusText}. ` +
        `Việc kiểm tra trùng mã lúc blur bị bỏ qua, backend sẽ chặn thật lúc Lưu.`
      );
      return;
    }

    const data = await res.json();

    if (!document.body.contains(inputEl)) return;

    if (data.exists) {
      inputEl.classList.add('is-invalid');
      showFieldErrorEl(inputEl, errorId, 'Mã nhân viên đã tồn tại.');
    }
  } catch (err) {
    console.error('[checkEmployeeCodeUnique] Lỗi khi gọi AJAX kiểm tra mã:', err);
  }
}

    // Giống showFieldError/clearFieldError nhưng nhắm theo id cố định (errorId)
    // thay vì nextElementSibling, vì #empCodeError nằm trong div .col-sm-4 bọc
    // ngoài input chứ không phải sibling trực tiếp -> insertAdjacentElement
    // afterend vẫn đặt đúng chỗ vì cấu trúc HTML hiện tại đặt nó ngay sau input.
    function showFieldErrorEl(inputEl, errorId, msg) {
    let el = document.getElementById(errorId);
    if (!el) {
        el = document.createElement('div');
        el.className = 'invalid-feedback';
        el.id = errorId;
        inputEl.insertAdjacentElement('afterend', el);
    }
    el.textContent = msg;
    }

    function clearFieldErrorEl(inputEl, errorId) {
    const el = document.getElementById(errorId);
    if (el) el.textContent = '';
    }

  @if ($errors->hasAny(['code', 'name', 'phone_number', 'department_id', 'note', 'status']))
    // Bọc trong DOMContentLoaded để đảm bảo resources/js/crud-modal-helpers.js
    // (build qua Vite dạng module, luôn chạy sau khi HTML parse xong) đã sẵn sàng
    // trước khi openEmployeeModal() gọi setModalFormId().
    document.addEventListener('DOMContentLoaded', function () {
      openEmployeeModal(
      //   null,
        {{ old('id') ?: 'null' }},
        '{{ old("code") }}',
        '{{ addslashes(old("name")) }}',
        '{{ addslashes(old("phone_number")) }}',
        {{ old("department_id") ? old("department_id") : 'null' }},
        '{{ addslashes(old("note")) }}',
        {{ old("status", 1) }},
        true   {{-- keepErrors --}}
      );
    });
  @endif

  @if ($errors->hasAny(['username', 'password', 'password_confirmation', 'new_password', 'new_password_confirmation', 'role', 'account_status']))
    @php
      $errEmployeeId = old('employee_id') ?? request()->route('employee')?->id;
      $errEmployee   = $errEmployeeId ? \App\Models\Master\Employee::with('account.roles')->find($errEmployeeId) : null;
      $errAccount    = $errEmployee?->account;
    @endphp
    // Bọc trong DOMContentLoaded để đảm bảo module Vite đã sẵn sàng trước khi
    // openAccountModal() chạy (nhất quán với openEmployeeModal() ở trên).
    document.addEventListener('DOMContentLoaded', function () {
      openAccountModal(
        {{ $errEmployeeId ?? 'null' }},
        '{{ addslashes($errEmployee->name ?? '') }}',
        '{{ addslashes($errEmployee->code ?? '') }}',
        @if ($errAccount)
          {
            username: '{{ addslashes($errAccount->username) }}',
            role: '{{ $errAccount->getRoleNames()->first() }}',
            status: {{ $errAccount->status->value ?? 1 }}
          }
        @else
          null
        @endif
        ,
        true   {{-- keepErrors --}}
      );
    });
  @endif

  document.getElementById('employeeForm').addEventListener('submit', function (e) {
    const empCodeEl = document.getElementById('empCode');
    if (empCodeEl.classList.contains('is-invalid')) {
      e.preventDefault();
      empCodeEl.focus();
      return;
    }

    const nameEl = document.getElementById('empName');
    const deptEl = document.getElementById('empDepartment');

    clearFieldError(nameEl);
    clearFieldError(deptEl);

    let firstInvalid = null;

    if (!nameEl.value.trim()) {
      showFieldError(nameEl, 'Vui lòng nhập họ và tên.');
      firstInvalid = firstInvalid || nameEl;
    }
    if (!deptEl.value) {
      showFieldError(deptEl, 'Vui lòng chọn bộ phận.');
      firstInvalid = firstInvalid || deptEl;
    }

    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.focus();
      return;
    }

    // ===== CHẶN SUBMIT LIÊN TỤC =====
    const btn     = document.getElementById('empSubmitBtn');
    const spinner = document.getElementById('empSubmitSpinner');
    const icon    = document.getElementById('empSubmitIcon');
    const label   = document.getElementById('empSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  document.getElementById('employeeModal').addEventListener('hidden.coreui.modal', function () {
    const btn     = document.getElementById('empSubmitBtn');
    const spinner = document.getElementById('empSubmitSpinner');
    const icon    = document.getElementById('empSubmitIcon');
    const label   = document.getElementById('empSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });

  // ===== TÀI KHOẢN (dùng chung Thêm/Sửa) =====
  function openAccountModal(empId, empName, empCode, account = null, keepErrors = false) {
    const modal  = new coreui.Modal(document.getElementById('accountModal'));
    const form   = document.getElementById('accountForm');
    const title  = document.getElementById('accountModalLabel');
    const method = document.getElementById('accMethod');
    const userEl = document.getElementById('accUsername');

    if (!keepErrors) {
      form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
      form.reset();
    }

    document.getElementById('accEmployeeId').value = empId;
    form.action = `${routeAccBase}/${empId}/account`;
    document.getElementById('accEmpName').textContent = empCode ? `${empName} (${empCode})` : empName;

    if (account) {
      title.textContent = 'Chỉnh sửa tài khoản';
      method.value       = 'PUT';

      userEl.value    = account.username;
      userEl.disabled = true;
      userEl.removeAttribute('name');
      document.getElementById('accUsernameRequired').classList.add('d-none');

      document.getElementById('accPasswordLabel').innerHTML =
        'Mật khẩu mới <span class="text-body-secondary fw-normal">(để trống nếu không đổi)</span>';
      document.getElementById('accPassword').name = 'new_password';
      document.getElementById('accPasswordConfirm').name = 'new_password_confirmation';
      document.getElementById('accPasswordConfirmRequired').classList.add('d-none');

      document.getElementById('accRole').value = account.role;
      document.getElementById(account.status == 1 ? 'accStatusActive' : 'accStatusInactive').checked = true;
    } else {
      title.textContent = 'Thêm tài khoản';
      method.value       = 'POST';

      userEl.disabled = false;
      userEl.setAttribute('name', 'username');
      document.getElementById('accUsernameRequired').classList.remove('d-none');

      document.getElementById('accPasswordLabel').innerHTML = 'Mật khẩu <span class="text-danger">*</span>';
      document.getElementById('accPassword').name = 'password';
      document.getElementById('accPasswordConfirm').name = 'password_confirmation';
      document.getElementById('accPasswordConfirmRequired').classList.remove('d-none');

      document.getElementById('accStatusActive').checked = true;
    }

    modal.show();
    setTimeout(() => userEl.focus(), 300);
  }

  document.getElementById('accountForm').addEventListener('submit', function (e) {
    const isCreate      = document.getElementById('accMethod').value === 'POST';
    const userEl        = document.getElementById('accUsername');
    const passEl        = document.getElementById('accPassword');
    const passConfirmEl = document.getElementById('accPasswordConfirm');
    const roleEl        = document.getElementById('accRole');

    [userEl, passEl, passConfirmEl, roleEl].forEach(clearFieldError);

    let firstInvalid = null;
    const markInvalid = function (el, msg) {
      showFieldError(el, msg);
      firstInvalid = firstInvalid || el;
    };

    // Tên đăng nhập: chỉ bắt buộc khi Thêm mới (khi Sửa, field bị disable/đổi tên)
    if (isCreate && !userEl.value.trim()) {
      markInvalid(userEl, 'Vui lòng nhập tên đăng nhập.');
    } else if (isCreate && !/^[a-z0-9@_.]+$/.test(userEl.value.trim())) {
      // Phòng vệ thêm: bình thường sanitizeUsernameInput() đã lọc ký tự ngay
      // lúc gõ nên trường hợp này hiếm khi xảy ra (vd: giá trị bị set bằng
      // JS khác, autofill trình duyệt...). Khớp rule 'regex' trong StoreAccountRequest.
      markInvalid(userEl, 'Tên đăng nhập chỉ được chứa chữ thường, số và các ký tự @ _ .');
    }

    // Mật khẩu: bắt buộc khi Thêm mới; khi Sửa được phép để trống (không đổi mật khẩu)
    if (isCreate && !passEl.value) {
      markInvalid(passEl, 'Vui lòng nhập mật khẩu.');
    } else if (passEl.value && passEl.value.length < 8) {
      markInvalid(passEl, 'Mật khẩu phải có ít nhất 8 ký tự.');
    }

    // Xác nhận mật khẩu: bắt buộc khớp nếu có nhập mật khẩu
    if (passEl.value && passEl.value !== passConfirmEl.value) {
      markInvalid(passConfirmEl, 'Xác nhận mật khẩu không khớp.');
    } else if (isCreate && !passConfirmEl.value) {
      markInvalid(passConfirmEl, 'Vui lòng xác nhận mật khẩu.');
    }

    // Vai trò: luôn bắt buộc (cả Thêm lẫn Sửa)
    if (!roleEl.value) {
      markInvalid(roleEl, 'Vui lòng chọn vai trò.');
    }

    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.focus();
      return;
    }
  });

  // ===== XÓA NHÂN VIÊN =====
  function confirmDelete(id, name, hasAccount) {
    document.getElementById('deleteEmpName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;

    const warningEl = document.getElementById('deleteAccountWarning');
    if (warningEl) {
      warningEl.classList.toggle('d-none', !hasAccount);
    }

    new coreui.Modal(document.getElementById('deleteModal')).show();
  }
</script>
@endpush

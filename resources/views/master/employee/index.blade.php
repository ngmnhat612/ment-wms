@extends('layouts.app')

@section('title', 'Nhân viên')

@section('breadcrumb')
  <li class="breadcrumb-item">Admin</li>
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

                <td class="small">{{ $emp->note ?? '-' }}</td>

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
                    <button class="btn btn-sm btn-outline-info me-1"
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
                    <button class="btn btn-sm btn-outline-success me-1"
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
                       placeholder="Tự động" maxlength="50">
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-sm-8">
                <label class="form-label fw-medium">Tên <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       name="name" id="empName"
                       value="{{ old('name') }}"
                       placeholder="Nhập họ và tên" maxlength="200" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Bộ phận <span class="text-danger">*</span></label>
                <select class="form-select {{ $errors->has('department_id') ? 'is-invalid' : '' }}"
                        name="department_id" id="empDepartment" required>
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
                      placeholder="Nhập số điện thoại" maxlength="20">
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
                        placeholder="Chỉ dùng chữ thường, số và dấu chấm/gạch dưới" maxlength="100" value="{{ old('username') }}">
                  @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium" id="accPasswordLabel">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password"
                      class="form-control @error('password') is-invalid @enderror @error('new_password') is-invalid @enderror"
                      name="password" id="accPassword" placeholder="Tối thiểu 8 ký tự">
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
                      name="password_confirmation" id="accPasswordConfirm" placeholder="Nhập lại mật khẩu">
                @error('password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('new_password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select @error('role') is-invalid @enderror" name="role" id="accRole" required>
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

  // ===== HỒ SƠ NHÂN VIÊN =====
  function openEmployeeModal(id = null, code = '', name = '', phone = '', departmentId = null, note = '', status = 1, keepErrors = false) {
    const modal   = new coreui.Modal(document.getElementById('employeeModal'));
    const form    = document.getElementById('employeeForm');
    const title   = document.getElementById('employeeModalLabel');
    const method  = document.getElementById('empMethod');
    const codeEl  = document.getElementById('empCode');

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
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  @if ($errors->hasAny(['code', 'name', 'phone_number', 'department_id', 'note', 'status']))
    openEmployeeModal(
      null,
      '{{ old("code") }}',
      '{{ addslashes(old("name")) }}',
      '{{ addslashes(old("phone_number")) }}',
      {{ old("department_id") ? old("department_id") : 'null' }},
      '{{ addslashes(old("note")) }}',
      {{ old("status", 1) }},
      true   {{-- keepErrors --}}
    );
  @endif

  @if ($errors->hasAny(['username', 'password', 'password_confirmation', 'new_password', 'new_password_confirmation', 'role', 'account_status']))
    @php
      $errEmployeeId = old('employee_id') ?? request()->route('employee')?->id;
      $errEmployee   = $errEmployeeId ? \App\Models\Employee::with('account.roles')->find($errEmployeeId) : null;
      $errAccount    = $errEmployee?->account;
    @endphp
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
  @endif

  // ===== CHẶN SUBMIT LIÊN TỤC =====
  document.getElementById('employeeForm').addEventListener('submit', function () {
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
@extends('layouts.app')

@section('title', 'Nhân viên — Ment WMS')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Nhân viên & Tài khoản</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Nhân viên & Tài khoản</h4>
      <small class="text-body-secondary">Quản lý hồ sơ nhân viên và tài khoản đăng nhập</small>
    </div>
    <button class="btn btn-primary" onclick="openEmployeeModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm nhân viên
    </button>
  </div>

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-people') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng nhân viên</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang làm việc</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-info">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-info"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $accountCount }}</div>
            <div class="text-body-secondary small">Có tài khoản</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG NHÂN VIÊN --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách nhân viên</span>
      <form method="GET" action="{{ route('master.employee.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:260px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên, số điện thoại...">
        </div>
        <select class="form-select" name="status" style="width:140px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Đang làm</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nghỉ việc</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || (request('status') !== null && request('status') !== ''))
          <a href="{{ route('master.employee.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th style="width:100px">Mã NV</th>
              <th>Họ tên</th>
              <th style="width:140px">Số điện thoại</th>
              <th style="width:130px">Phòng ban</th>
              <th class="text-center" style="width:160px">Tài khoản</th>
              <th class="text-center" style="width:140px">Vai trò</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:150px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($employees as $index => $emp)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($employees->currentPage() - 1) * $employees->perPage() + $index + 1 }}
                </td>
                <td class="font-monospace fw-medium">{{ $emp->code }}</td>
                <td class="fw-medium">{{ $emp->name }}</td>
                <td class="text-body-secondary small">{{ $emp->phone_number ?? '—' }}</td>
                <td class="text-body-secondary small">{{ $emp->department ?? '—' }}</td>

                {{-- Tài khoản --}}
                <td class="text-center">
                  @if ($emp->account)
                    <span class="badge bg-info-subtle text-info border border-info-subtle"
                          title="Username: {{ $emp->account->username }}">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
                      {{ $emp->account->username }}
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
                      Chưa có TK
                    </span>
                  @endif
                </td>

                {{-- Vai trò --}}
                <td class="text-center">
                  @if ($emp->account)
                    <span class="{{ $emp->account->role->badgeClass() }}">
                      {{ $emp->account->role->label() }}
                    </span>
                  @else
                    <span class="text-body-secondary small">—</span>
                  @endif
                </td>

                {{-- Trạng thái nhân viên --}}
                <td class="text-center">
                  @if ($emp->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      Đang làm
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      Nghỉ việc
                    </span>
                  @endif
                </td>

                {{-- Thao tác --}}
                <td class="text-center">
                  {{-- Sửa hồ sơ --}}
                  <button class="btn btn-sm btn-outline-primary"
                          onclick="openEmployeeModal(
                            {{ $emp->id }},
                            '{{ addslashes($emp->code) }}',
                            '{{ addslashes($emp->name) }}',
                            '{{ addslashes($emp->phone_number ?? '') }}',
                            '{{ addslashes($emp->department ?? '') }}',
                            {{ $emp->status }}
                          )"
                          title="Sửa hồ sơ">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>

                  {{-- Tài khoản --}}
                  @if ($emp->account)
                    <button class="btn btn-sm btn-outline-info"
                            onclick="openUpdateAccountModal(
                              {{ $emp->id }},
                              '{{ addslashes($emp->name) }}',
                              '{{ $emp->account->username }}',
                              {{ $emp->account->role->value }}
                            )"
                            title="Quản lý tài khoản">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use></svg>
                    </button>
                  @else
                    <button class="btn btn-sm btn-outline-success"
                            onclick="openCreateAccountModal(
                              {{ $emp->id }},
                              '{{ addslashes($emp->name) }}'
                            )"
                            title="Tạo tài khoản">
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
                <td colspan="9" class="text-center text-body-secondary py-5">
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

    @if ($employees->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $employees->firstItem() }}–{{ $employees->lastItem() }}
          trong tổng số {{ $employees->total() }} nhân viên
        </small>
        {{ $employees->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
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
                <label class="form-label fw-medium">Mã NV <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace text-uppercase"
                       name="code" id="empCode" placeholder="NV001" maxlength="50" required>
              </div>
              <div class="col-sm-8">
                <label class="form-label fw-medium">Họ và tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="empName"
                       placeholder="Nguyễn Văn A" maxlength="200" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Số điện thoại</label>
                <input type="text" class="form-control" name="phone_number" id="empPhone"
                       placeholder="0901234567" maxlength="20">
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Phòng ban</label>
                <input type="text" class="form-control" name="department" id="empDepartment"
                       placeholder="Kho, Sản xuất, Kế toán..." maxlength="100">
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Trạng thái</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status"
                           id="empStatusActive" value="1" checked>
                    <label class="form-check-label text-success" for="empStatusActive">Đang làm việc</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="status"
                           id="empStatusInactive" value="0">
                    <label class="form-check-label text-secondary" for="empStatusInactive">Nghỉ việc</label>
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


  {{-- ===== MODAL: TẠO TÀI KHOẢN ===== --}}
  <div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="createAccountForm" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Tạo tài khoản đăng nhập</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info py-2 small mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
              Tạo tài khoản cho: <strong id="createAccEmpName"></strong>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-medium">Tên đăng nhập <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="username" id="createAccUsername"
                       placeholder="VD: nvkho01" maxlength="100" required>
                <div class="form-text">Chỉ dùng chữ thường, số và dấu chấm/gạch dưới</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password"
                       placeholder="Tối thiểu 8 ký tự" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password_confirmation"
                       placeholder="Nhập lại mật khẩu" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select" name="role" required>
                  <option value="">— Chọn vai trò —</option>
                  @foreach (\App\Enums\AccountRole::cases() as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-success">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user-plus') }}"></use></svg>
              Tạo tài khoản
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ===== MODAL: CẬP NHẬT TÀI KHOẢN ===== --}}
  <div class="modal fade" id="updateAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="updateAccountForm" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Quản lý tài khoản</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-secondary py-2 small mb-3">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
              Tài khoản của: <strong id="updateAccEmpName"></strong>
              &nbsp;·&nbsp;
              Username: <code id="updateAccUsername"></code>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-medium">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select" name="role" id="updateAccRole" required>
                  @foreach (\App\Enums\AccountRole::cases() as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">
                  Mật khẩu mới
                  <span class="text-body-secondary fw-normal">(để trống nếu không đổi)</span>
                </label>
                <input type="password" class="form-control" name="new_password"
                       placeholder="Tối thiểu 8 ký tự">
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-medium">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" name="new_password_confirmation"
                       placeholder="Nhập lại mật khẩu">
              </div>
            </div>

            <hr class="my-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-medium text-danger small">Xóa tài khoản đăng nhập</div>
                <div class="text-body-secondary" style="font-size:0.8rem">Hồ sơ nhân viên vẫn được giữ lại</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      onclick="confirmDeleteAccount()">
                <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                Xóa tài khoản
              </button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu thay đổi
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
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm"
                  data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Form ẩn DELETE tài khoản --}}
  <form id="deleteAccountForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
  </form>

@endsection

@push('scripts')
<script>
  const routeStore    = '{{ route('master.employee.store') }}';
  const routeBase     = '{{ url('master/employee') }}';
  const routeAccBase  = '{{ url('master/employee') }}'; // /{id}/account

  // ===== HỒ SƠ NHÂN VIÊN =====
  function openEmployeeModal(id = null, code = '', name = '', phone = '', department = '', status = 1) {
    const modal  = new coreui.Modal(document.getElementById('employeeModal'));
    const form   = document.getElementById('employeeForm');
    const title  = document.getElementById('employeeModalLabel');
    const method = document.getElementById('empMethod');

    form.reset();
    document.getElementById('empStatusActive').checked = true;

    if (id) {
      title.textContent = 'Chỉnh sửa hồ sơ nhân viên';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';
      document.getElementById('empCode').value        = code;
      document.getElementById('empName').value        = name;
      document.getElementById('empPhone').value       = phone;
      document.getElementById('empDepartment').value  = department;
      document.getElementById(status == 1 ? 'empStatusActive' : 'empStatusInactive').checked = true;
    } else {
      title.textContent = 'Thêm nhân viên';
      form.action       = routeStore;
      method.value      = 'POST';
    }

    modal.show();
    setTimeout(() => document.getElementById('empCode').focus(), 300);
  }

  // Auto viết hoa mã NV
  document.getElementById('empCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  // ===== TẠO TÀI KHOẢN =====
  function openCreateAccountModal(empId, empName) {
    const modal = new coreui.Modal(document.getElementById('createAccountModal'));
    const form  = document.getElementById('createAccountForm');
    form.action = `${routeAccBase}/${empId}/account`;
    form.reset();
    document.getElementById('createAccEmpName').textContent = empName;
    modal.show();
    setTimeout(() => document.getElementById('createAccUsername').focus(), 300);
  }

  // ===== CẬP NHẬT TÀI KHOẢN =====
  let currentEmpId = null;

  function openUpdateAccountModal(empId, empName, username, roleValue) {
    currentEmpId = empId;
    const modal = new coreui.Modal(document.getElementById('updateAccountModal'));
    const form  = document.getElementById('updateAccountForm');
    form.action = `${routeAccBase}/${empId}/account`;
    form.querySelector('[name=new_password]').value = '';
    form.querySelector('[name=new_password_confirmation]').value = '';
    document.getElementById('updateAccEmpName').textContent  = empName;
    document.getElementById('updateAccUsername').textContent = username;
    document.getElementById('updateAccRole').value = roleValue;
    modal.show();
  }

  function confirmDeleteAccount() {
    if (!currentEmpId) return;
    if (!confirm('Bạn có chắc muốn xóa tài khoản đăng nhập này?\nHồ sơ nhân viên sẽ được giữ lại.')) return;
    const form = document.getElementById('deleteAccountForm');
    form.action = `${routeAccBase}/${currentEmpId}/account`;
    form.submit();
  }

  // ===== XÓA NHÂN VIÊN =====
  function confirmDelete(id, name, hasAccount) {
    if (hasAccount) {
      alert(`Không thể xóa "${name}" vì đang có tài khoản đăng nhập.\nHãy xóa tài khoản trước.`);
      return;
    }
    document.getElementById('deleteEmpName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }
</script>
@endpush
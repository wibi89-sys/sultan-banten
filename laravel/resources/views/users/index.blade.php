@extends('layouts.app')

@section('title', 'Manajemen User - Sultan Banten')
@section('page_title')
    <i class="fas fa-user-cog me-2"></i>Manajemen User
@endsection

@push('styles')
<style>
    .role-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
    }
    .role-admin { background: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%); color: white; }
    .role-operator { background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%); color: white; }
    .role-viewer { background: #64748b; color: white; }
    .access-checkbox { margin-bottom: 6px; }
    .access-checkbox .form-check-label { font-size: 12px; }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-users-cog text-primary me-2"></i>Daftar Pengguna Sistem</h5>
            <p class="text-muted small mb-0">Kelola akun pengguna, peran, hak akses menu, dan penetapan UPT</p>
        </div>
        <button class="btn btn-primary btn-sm px-3 mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-user-plus me-1"></i> Tambah User
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="bg-primary bg-opacity-10">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="22%">Nama & Username</th>
                    <th width="12%">No. WA</th>
                    <th class="text-center" width="10%">Role</th>
                    <th width="20%">UPT Terkait</th>
                    <th width="21%">Hak Akses Ringkas</th>
                    <th class="text-center" width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                <tr>
                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">{!! $u->getAvatarHtml(32) !!}</div>
                            <div>
                                <div class="fw-bold text-dark">{{ $u->nama_lengkap }}</div>
                                <small class="text-muted"><i class="fas fa-user small me-1"></i>{{ $u->username }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $u->no_wa ?: '-' }}</td>
                    <td class="text-center">
                        <span class="role-badge role-{{ $u->role }}">
                            {{ ucfirst($u->role) }}
                        </span>
                    </td>
                    <td class="small fw-semibold text-secondary">
                        {{ $u->upt?->nama_upt ?? 'Semua UPT (Wilayah)' }}
                    </td>
                    <td class="small">
                        @if($u->isAdmin())
                            <span class="badge bg-success bg-opacity-25 text-success">Akses Penuh (Admin)</span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @if($u->akses_penghuni)<span class="badge bg-light text-dark border">Penghuni</span>@endif
                                @if($u->akses_overstaying)<span class="badge bg-light text-dark border">Overstay</span>@endif
                                @if($u->akses_integrasi)<span class="badge bg-light text-dark border">Integrasi</span>@endif
                                @if($u->akses_bebas_harian)<span class="badge bg-light text-dark border">Bebas</span>@endif
                                @if($u->akses_laporan)<span class="badge bg-light text-dark border">Laporan</span>@endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm py-0 px-2" onclick="editData({{ json_encode($u) }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if($u->id != auth()->id())
                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada pengguna terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-user-plus me-2"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-sm" required placeholder="Nama lengkap staf/operator">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control form-control-sm" required placeholder="Username unik login">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control form-control-sm" required minlength="6" placeholder="Minimal 6 karakter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                            <input type="text" name="no_wa" class="form-control form-control-sm" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="operator">Operator UPT</option>
                                <option value="viewer">Viewer (Hanya Lihat)</option>
                                <option value="admin">Administrator Wilayah</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penetapan UPT</label>
                            <select name="upt_id" class="form-select form-select-sm">
                                <option value="">-- Semua UPT / Kanwil --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Kosongkan jika admin/operator wilayah</small>
                        </div>
                    </div>

                    <h6 class="small fw-bold text-primary border-bottom pb-1 mb-2">Hak Akses Modul</h6>
                    <div class="row">
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_dashboard" id="add_ak_dash" checked>
                                <label class="form-check-label" for="add_ak_dash">Dashboard</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_penghuni" id="add_ak_peng" checked>
                                <label class="form-check-label" for="add_ak_peng">Data Penghuni</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_overstaying" id="add_ak_over" checked>
                                <label class="form-check-label" for="add_ak_over">Data Overstaying</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_integrasi" id="add_ak_integ" checked>
                                <label class="form-check-label" for="add_ak_integ">Data Integrasi</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_bebas_harian" id="add_ak_bebas" checked>
                                <label class="form-check-label" for="add_ak_bebas">Bebas Harian</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_laporan" id="add_ak_lap" checked>
                                <label class="form-check-label" for="add_ak_lap">Cetak Laporan</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_upt" id="add_ak_upt">
                                <label class="form-check-label" for="add_ak_upt">Master Data UPT</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_user" id="add_ak_user">
                                <label class="form-check-label" for="add_ak_user">Manajemen User</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_instansi" id="add_ak_inst">
                                <label class="form-check-label" for="add_ak_inst">Pengaturan Instansi</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title fs-6"><i class="fas fa-user-edit me-2"></i>Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username</label>
                            <input type="text" id="edit_username" class="form-control form-control-sm" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control form-control-sm" minlength="6" placeholder="Kosongkan jika tidak diganti">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                            <input type="text" name="no_wa" id="edit_no_wa" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-select form-select-sm" required>
                                <option value="operator">Operator UPT</option>
                                <option value="viewer">Viewer (Hanya Lihat)</option>
                                <option value="admin">Administrator Wilayah</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penetapan UPT</label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm">
                                <option value="">-- Semua UPT / Kanwil --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="small fw-bold text-primary border-bottom pb-1 mb-2">Hak Akses Modul</h6>
                    <div class="row">
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_dashboard" id="edit_ak_dash">
                                <label class="form-check-label" for="edit_ak_dash">Dashboard</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_penghuni" id="edit_ak_peng">
                                <label class="form-check-label" for="edit_ak_peng">Data Penghuni</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_overstaying" id="edit_ak_over">
                                <label class="form-check-label" for="edit_ak_over">Data Overstaying</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_integrasi" id="edit_ak_integ">
                                <label class="form-check-label" for="edit_ak_integ">Data Integrasi</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_bebas_harian" id="edit_ak_bebas">
                                <label class="form-check-label" for="edit_ak_bebas">Bebas Harian</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_laporan" id="edit_ak_lap">
                                <label class="form-check-label" for="edit_ak_lap">Cetak Laporan</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_upt" id="edit_ak_upt">
                                <label class="form-check-label" for="edit_ak_upt">Master Data UPT</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_user" id="edit_ak_user">
                                <label class="form-check-label" for="edit_ak_user">Manajemen User</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 access-checkbox">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="akses_instansi" id="edit_ak_inst">
                                <label class="form-check-label" for="edit_ak_inst">Pengaturan Instansi</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editData(u) {
        document.getElementById('formEdit').action = '/users/' + u.id;
        document.getElementById('edit_nama_lengkap').value = u.nama_lengkap;
        document.getElementById('edit_username').value = u.username;
        document.getElementById('edit_no_wa').value = u.no_wa || '';
        document.getElementById('edit_role').value = u.role;
        document.getElementById('edit_upt_id').value = u.upt_id || '';

        document.getElementById('edit_ak_dash').checked = !!u.akses_dashboard;
        document.getElementById('edit_ak_peng').checked = !!u.akses_penghuni;
        document.getElementById('edit_ak_over').checked = !!u.akses_overstaying;
        document.getElementById('edit_ak_integ').checked = !!u.akses_integrasi;
        document.getElementById('edit_ak_bebas').checked = !!u.akses_bebas_harian;
        document.getElementById('edit_ak_lap').checked = !!u.akses_laporan;
        document.getElementById('edit_ak_upt').checked = !!u.akses_upt;
        document.getElementById('edit_ak_user').checked = !!u.akses_user;
        document.getElementById('edit_ak_inst').checked = !!u.akses_instansi;

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush

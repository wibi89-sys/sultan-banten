@extends('layouts.app')

@section('title', 'Data Integrasi - Sultan Banten')
@section('page_title')
    <i class="fas fa-exchange-alt me-2"></i>Data Integrasi
@endsection

@push('styles')
<style>
    .table-usulan-header {
        background-color: #fef3c7 !important;
        color: #92400e !important;
    }
    .table-sk-header {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
    }
    .category-header {
        padding: 6px 12px;
        font-weight: 700;
        font-size: 13px;
        border-radius: 6px 6px 0 0;
    }
    .usulan-header {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        color: white;
    }
    .sk-header {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        color: white;
    }
    .input-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 6px;
        padding: 10px;
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-top: none;
        border-radius: 0 0 6px 6px;
    }
    .jumlah-box {
        margin-top: 8px;
        padding: 6px 12px;
        font-weight: 700;
        font-size: 13px;
        border-radius: 6px;
    }
    .jumlah-box.usulan {
        background: #fef3c7;
        color: #92400e;
    }
    .jumlah-box.sk {
        background: #d1fae5;
        color: #065f46;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-exchange-alt text-primary me-2"></i>Data Integrasi Narapidana Seluruh UPT</h5>
            <p class="text-muted small mb-0">Monitoring data usulan dan SK terbit Pembebasan Bersyarat (PB), Cuti Bersyarat (CB), CMB, CMK, dan Asimilasi</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            @if($timeRestriction['active'] && !$canInput)
            <span class="badge bg-warning text-dark px-3 py-2">
                <i class="fas fa-clock me-1"></i>
                Input: {{ $timeRestriction['jam_mulai'] }} - {{ $timeRestriction['jam_selesai'] }}
            </span>
            @endif
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAdd" {{ !$canInput ? 'disabled' : '' }}>
                <i class="fas fa-plus me-1"></i> Tambah Data
            </button>
        </div>
    </div>
    
    @if($timeRestriction['active'] && !$canInput && $timeRestrictionMessage)
    <div class="alert alert-warning py-2 mb-3">
        <i class="fas fa-exclamation-triangle me-1"></i>
        {{ $timeRestrictionMessage }}
    </div>
    @endif

    <!-- Filter Toolbar -->
    <div class="card mb-3 bg-light border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('integrasi.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulai }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $tanggalSampai }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">UPT</label>
                    <select name="upt_id" class="form-select form-select-sm" {{ $userUptFilter ? 'disabled' : '' }}>
                        <option value="0">-- Semua UPT --</option>
                        @foreach ($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('integrasi.index') }}" class="btn btn-secondary btn-sm flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr class="text-center align-middle">
                    <th rowspan="2" width="4%">No</th>
                    <th rowspan="2" width="9%">Tanggal</th>
                    <th rowspan="2" class="text-start" width="18%">UPT</th>
                    <th colspan="6" class="table-usulan-header">Usulan</th>
                    <th colspan="6" class="table-sk-header">SK Terbit</th>
                    <th rowspan="2" width="15%">Status / Keterangan</th>
                    <th rowspan="2" width="7%">Aksi</th>
                </tr>
                <tr class="text-center small">
                    <th class="table-usulan-header" width="3.5%">PB</th>
                    <th class="table-usulan-header" width="3.5%">CB</th>
                    <th class="table-usulan-header" width="3.5%">CMB</th>
                    <th class="table-usulan-header" width="3.5%">CMK</th>
                    <th class="table-usulan-header" width="3.5%">Asim</th>
                    <th class="table-usulan-header fw-bold" width="4.5%">Jml</th>
                    <th class="table-sk-header" width="3.5%">PB</th>
                    <th class="table-sk-header" width="3.5%">CB</th>
                    <th class="table-sk-header" width="3.5%">CMB</th>
                    <th class="table-sk-header" width="3.5%">CMK</th>
                    <th class="table-sk-header" width="3.5%">Asim</th>
                    <th class="table-sk-header fw-bold" width="4.5%">Jml</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumUPB = 0; $sumUCB = 0; $sumUCMB = 0; $sumUCMK = 0; $sumUAsim = 0; $sumTotalUsulan = 0;
                    $sumSKPB = 0; $sumSKCB = 0; $sumSKCMB = 0; $sumSKCMK = 0; $sumSKAsim = 0; $sumTotalSK = 0;
                @endphp
                @forelse ($integrasiList as $data)
                    @php
                        $totUsulan = $data->usulan_pb + $data->usulan_cb + $data->usulan_cmb + $data->usulan_cmk + $data->usulan_asimilasi;
                        $totSK = $data->sk_terbit_pb + $data->sk_terbit_cb + $data->sk_terbit_cmb + $data->sk_terbit_cmk + $data->sk_terbit_asimilasi;
                        $sisa = $totUsulan - $totSK;
                        
                        $sumUPB += $data->usulan_pb;
                        $sumUCB += $data->usulan_cb;
                        $sumUCMB += $data->usulan_cmb;
                        $sumUCMK += $data->usulan_cmk;
                        $sumUAsim += $data->usulan_asimilasi;
                        $sumTotalUsulan += $totUsulan;

                        $sumSKPB += $data->sk_terbit_pb;
                        $sumSKCB += $data->sk_terbit_cb;
                        $sumSKCMB += $data->sk_terbit_cmb;
                        $sumSKCMK += $data->sk_terbit_cmk;
                        $sumSKAsim += $data->sk_terbit_asimilasi;
                        $sumTotalSK += $totSK;
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $loop->iteration + ($integrasiList->currentPage() - 1) * $integrasiList->perPage() }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/y') }}</td>
                        <td class="fw-semibold text-dark">{{ $data->upt->nama_upt ?? '-' }}</td>
                        <td class="text-center">{{ $data->usulan_pb }}</td>
                        <td class="text-center">{{ $data->usulan_cb }}</td>
                        <td class="text-center">{{ $data->usulan_cmb }}</td>
                        <td class="text-center">{{ $data->usulan_cmk }}</td>
                        <td class="text-center">{{ $data->usulan_asimilasi }}</td>
                        <td class="text-center fw-bold" style="background:#fef3c7;">{{ $totUsulan }}</td>
                        <td class="text-center">{{ $data->sk_terbit_pb }}</td>
                        <td class="text-center">{{ $data->sk_terbit_cb }}</td>
                        <td class="text-center">{{ $data->sk_terbit_cmb }}</td>
                        <td class="text-center">{{ $data->sk_terbit_cmk }}</td>
                        <td class="text-center">{{ $data->sk_terbit_asimilasi }}</td>
                        <td class="text-center fw-bold" style="background:#d1fae5;">{{ $totSK }}</td>
                        <td>
                            @if($sisa > 0)
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>{{ $sisa }} belum SK</span>
                            @else
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Semua terbit SK</span>
                            @endif
                            @if($data->keterangan)
                                <div class="small text-muted mt-1">{{ Str::limit($data->keterangan, 30) }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 px-1.5" onclick="editData({{ json_encode($data) }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('integrasi.destroy', $data->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data integrasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm py-0 px-1.5" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center text-muted py-4">Belum ada data integrasi.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($integrasiList->count() > 0)
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">TOTAL (HALAMAN INI)</td>
                    <td class="text-center">{{ number_format($sumUPB) }}</td>
                    <td class="text-center">{{ number_format($sumUCB) }}</td>
                    <td class="text-center">{{ number_format($sumUCMB) }}</td>
                    <td class="text-center">{{ number_format($sumUCMK) }}</td>
                    <td class="text-center">{{ number_format($sumUAsim) }}</td>
                    <td class="text-center" style="background:#fef3c7;">{{ number_format($sumTotalUsulan) }}</td>
                    <td class="text-center">{{ number_format($sumSKPB) }}</td>
                    <td class="text-center">{{ number_format($sumSKCB) }}</td>
                    <td class="text-center">{{ number_format($sumSKCMB) }}</td>
                    <td class="text-center">{{ number_format($sumSKCMK) }}</td>
                    <td class="text-center">{{ number_format($sumSKAsim) }}</td>
                    <td class="text-center" style="background:#d1fae5;">{{ number_format($sumTotalSK) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $integrasiList->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Data Integrasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('integrasi.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                            <select name="upt_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}" {{ $userUptFilter == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="category-header usulan-header">
                                <i class="fas fa-file-alt me-1"></i> USULAN
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">PB</label>
                                    <input type="number" name="usulan_pb" id="add_u_pb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CB</label>
                                    <input type="number" name="usulan_cb" id="add_u_cb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMB</label>
                                    <input type="number" name="usulan_cmb" id="add_u_cmb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMK</label>
                                    <input type="number" name="usulan_cmk" id="add_u_cmk" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">Asim</label>
                                    <input type="number" name="usulan_asimilasi" id="add_u_asim" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                            </div>
                            <div class="jumlah-box usulan">
                                Total Usulan: <span id="add_jumlah_usulan">0</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="category-header sk-header">
                                <i class="fas fa-certificate me-1"></i> SK TERBIT
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">PB</label>
                                    <input type="number" name="sk_terbit_pb" id="add_sk_pb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CB</label>
                                    <input type="number" name="sk_terbit_cb" id="add_sk_cb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMB</label>
                                    <input type="number" name="sk_terbit_cmb" id="add_sk_cmb" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMK</label>
                                    <input type="number" name="sk_terbit_cmk" id="add_sk_cmk" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">Asim</label>
                                    <input type="number" name="sk_terbit_asimilasi" id="add_sk_asim" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                            </div>
                            <div class="jumlah-box sk">
                                Total SK Terbit: <span id="add_jumlah_sk">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label small">Keterangan</label>
                        <textarea name="keterangan" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
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
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data Integrasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm" required>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="category-header usulan-header">
                                <i class="fas fa-file-alt me-1"></i> USULAN
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">PB</label>
                                    <input type="number" name="usulan_pb" id="edit_u_pb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CB</label>
                                    <input type="number" name="usulan_cb" id="edit_u_cb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMB</label>
                                    <input type="number" name="usulan_cmb" id="edit_u_cmb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMK</label>
                                    <input type="number" name="usulan_cmk" id="edit_u_cmk" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">Asim</label>
                                    <input type="number" name="usulan_asimilasi" id="edit_u_asim" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                            </div>
                            <div class="jumlah-box usulan">
                                Total Usulan: <span id="edit_jumlah_usulan">0</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="category-header sk-header">
                                <i class="fas fa-certificate me-1"></i> SK TERBIT
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">PB</label>
                                    <input type="number" name="sk_terbit_pb" id="edit_sk_pb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CB</label>
                                    <input type="number" name="sk_terbit_cb" id="edit_sk_cb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMB</label>
                                    <input type="number" name="sk_terbit_cmb" id="edit_sk_cmb" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">CMK</label>
                                    <input type="number" name="sk_terbit_cmk" id="edit_sk_cmk" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">Asim</label>
                                    <input type="number" name="sk_terbit_asimilasi" id="edit_sk_asim" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                            </div>
                            <div class="jumlah-box sk">
                                Total SK Terbit: <span id="edit_jumlah_sk">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label small">Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control form-control-sm" rows="2"></textarea>
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
    function updateJumlah(prefix) {
        const u_pb = parseInt(document.getElementById(prefix + '_u_pb')?.value) || 0;
        const u_cb = parseInt(document.getElementById(prefix + '_u_cb')?.value) || 0;
        const u_cmb = parseInt(document.getElementById(prefix + '_u_cmb')?.value) || 0;
        const u_cmk = parseInt(document.getElementById(prefix + '_u_cmk')?.value) || 0;
        const u_asim = parseInt(document.getElementById(prefix + '_u_asim')?.value) || 0;
        const totU = u_pb + u_cb + u_cmb + u_cmk + u_asim;
        const uEl = document.getElementById(prefix + '_jumlah_usulan');
        if (uEl) uEl.textContent = totU;

        const sk_pb = parseInt(document.getElementById(prefix + '_sk_pb')?.value) || 0;
        const sk_cb = parseInt(document.getElementById(prefix + '_sk_cb')?.value) || 0;
        const sk_cmb = parseInt(document.getElementById(prefix + '_sk_cmb')?.value) || 0;
        const sk_cmk = parseInt(document.getElementById(prefix + '_sk_cmk')?.value) || 0;
        const sk_asim = parseInt(document.getElementById(prefix + '_sk_asim')?.value) || 0;
        const totSK = sk_pb + sk_cb + sk_cmb + sk_cmk + sk_asim;
        const skEl = document.getElementById(prefix + '_jumlah_sk');
        if (skEl) skEl.textContent = totSK;
    }

    function editData(data) {
        document.getElementById('formEdit').action = '/integrasi/' + data.id;
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_upt_id').value = data.upt_id;
        document.getElementById('edit_tanggal').value = data.tanggal ? data.tanggal.substring(0, 10) : '';

        document.getElementById('edit_u_pb').value = data.usulan_pb;
        document.getElementById('edit_u_cb').value = data.usulan_cb;
        document.getElementById('edit_u_cmb').value = data.usulan_cmb;
        document.getElementById('edit_u_cmk').value = data.usulan_cmk;
        document.getElementById('edit_u_asim').value = data.usulan_asimilasi;

        document.getElementById('edit_sk_pb').value = data.sk_terbit_pb;
        document.getElementById('edit_sk_cb').value = data.sk_terbit_cb;
        document.getElementById('edit_sk_cmb').value = data.sk_terbit_cmb;
        document.getElementById('edit_sk_cmk').value = data.sk_terbit_cmk;
        document.getElementById('edit_sk_asim').value = data.sk_terbit_asimilasi;

        document.getElementById('edit_keterangan').value = data.keterangan || '';
        updateJumlah('edit');

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush

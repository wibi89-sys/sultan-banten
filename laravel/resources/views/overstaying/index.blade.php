@extends('layouts.app')

@section('title', 'Data Overstaying - Sultan Banten')
@section('page_title')
    <i class="fas fa-clock me-2"></i>Data Overstaying
@endsection

@push('styles')
<style>
    .table-tahanan-header {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
    }
    .table-overstaying-header {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
    }
    .category-header {
        padding: 6px 12px;
        font-weight: 700;
        font-size: 13px;
        border-radius: 6px 6px 0 0;
    }
    .tahanan-header {
        background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
        color: white;
    }
    .overstaying-header {
        background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
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
    .jumlah-box.tahanan {
        background: #e0f2fe;
        color: #0369a1;
    }
    .jumlah-box.overstaying {
        background: #fee2e2;
        color: #b91c1c;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-clock text-primary me-2"></i>Data Overstaying Tahanan Seluruh UPT</h5>
            <p class="text-muted small mb-0">Monitoring data tahanan dan overstaying kategori AI sampai AV</p>
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

    <!-- Filter Tanggal & UPT -->
    <div class="card mb-3 bg-light border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('overstaying.index') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('overstaying.index') }}" class="btn btn-secondary btn-sm flex-fill">Reset</a>
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
                    <th colspan="6" class="table-tahanan-header">Tahanan</th>
                    <th colspan="6" class="table-overstaying-header">Overstaying</th>
                    <th rowspan="2" width="12%">Keterangan</th>
                    <th rowspan="2" width="7%">Aksi</th>
                </tr>
                <tr class="text-center small">
                    <th class="table-tahanan-header" width="3.5%">AI</th>
                    <th class="table-tahanan-header" width="3.5%">AII</th>
                    <th class="table-tahanan-header" width="3.5%">AIII</th>
                    <th class="table-tahanan-header" width="3.5%">AIV</th>
                    <th class="table-tahanan-header" width="3.5%">AV</th>
                    <th class="table-tahanan-header fw-bold" width="4.5%">Jml</th>
                    <th class="table-overstaying-header" width="3.5%">AI</th>
                    <th class="table-overstaying-header" width="3.5%">AII</th>
                    <th class="table-overstaying-header" width="3.5%">AIII</th>
                    <th class="table-overstaying-header" width="3.5%">AIV</th>
                    <th class="table-overstaying-header" width="3.5%">AV</th>
                    <th class="table-overstaying-header fw-bold" width="4.5%">Jml</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumTAI = 0; $sumTAII = 0; $sumTAIII = 0; $sumTAIV = 0; $sumTAV = 0; $sumTotalTahanan = 0;
                    $sumOAI = 0; $sumOAII = 0; $sumOAIII = 0; $sumOAIV = 0; $sumOAV = 0; $sumTotalOverstay = 0;
                @endphp
                @forelse ($overstayingList as $data)
                    @php
                        $totTahanan = $data->tahanan_ai + $data->tahanan_aii + $data->tahanan_aiii + $data->tahanan_aiv + $data->tahanan_av;
                        $totOverstay = $data->overstaying_ai + $data->overstaying_aii + $data->overstaying_aiii + $data->overstaying_aiv + $data->overstaying_av;
                        
                        $sumTAI += $data->tahanan_ai;
                        $sumTAII += $data->tahanan_aii;
                        $sumTAIII += $data->tahanan_aiii;
                        $sumTAIV += $data->tahanan_aiv;
                        $sumTAV += $data->tahanan_av;
                        $sumTotalTahanan += $totTahanan;

                        $sumOAI += $data->overstaying_ai;
                        $sumOAII += $data->overstaying_aii;
                        $sumOAIII += $data->overstaying_aiii;
                        $sumOAIV += $data->overstaying_aiv;
                        $sumOAV += $data->overstaying_av;
                        $sumTotalOverstay += $totOverstay;
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $loop->iteration + ($overstayingList->currentPage() - 1) * $overstayingList->perPage() }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/y') }}</td>
                        <td class="fw-semibold text-dark">{{ $data->upt->nama_upt ?? '-' }}</td>
                        <td class="text-center">{{ $data->tahanan_ai }}</td>
                        <td class="text-center">{{ $data->tahanan_aii }}</td>
                        <td class="text-center">{{ $data->tahanan_aiii }}</td>
                        <td class="text-center">{{ $data->tahanan_aiv }}</td>
                        <td class="text-center">{{ $data->tahanan_av }}</td>
                        <td class="text-center fw-bold" style="background:#e0f2fe;">{{ $totTahanan }}</td>
                        <td class="text-center">{{ $data->overstaying_ai }}</td>
                        <td class="text-center">{{ $data->overstaying_aii }}</td>
                        <td class="text-center">{{ $data->overstaying_aiii }}</td>
                        <td class="text-center">{{ $data->overstaying_aiv }}</td>
                        <td class="text-center">{{ $data->overstaying_av }}</td>
                        <td class="text-center fw-bold" style="background:#fee2e2;">{{ $totOverstay }}</td>
                        <td class="small text-muted" style="max-width: 150px;">{{ $data->keterangan ?: '-' }}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 px-1.5" onclick="editData({{ json_encode($data) }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('overstaying.destroy', $data->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data overstaying ini?')">
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
                        <td colspan="17" class="text-center text-muted py-4">Belum ada data overstaying.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($overstayingList->count() > 0)
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">TOTAL (HALAMAN INI)</td>
                    <td class="text-center">{{ number_format($sumTAI) }}</td>
                    <td class="text-center">{{ number_format($sumTAII) }}</td>
                    <td class="text-center">{{ number_format($sumTAIII) }}</td>
                    <td class="text-center">{{ number_format($sumTAIV) }}</td>
                    <td class="text-center">{{ number_format($sumTAV) }}</td>
                    <td class="text-center" style="background:#e0f2fe;">{{ number_format($sumTotalTahanan) }}</td>
                    <td class="text-center">{{ number_format($sumOAI) }}</td>
                    <td class="text-center">{{ number_format($sumOAII) }}</td>
                    <td class="text-center">{{ number_format($sumOAIII) }}</td>
                    <td class="text-center">{{ number_format($sumOAIV) }}</td>
                    <td class="text-center">{{ number_format($sumOAV) }}</td>
                    <td class="text-center" style="background:#fee2e2;">{{ number_format($sumTotalOverstay) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $overstayingList->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Data Overstaying</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('overstaying.store') }}">
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
                            <div class="category-header tahanan-header">
                                <i class="fas fa-user-lock me-1"></i> TAHANAN (Kategori A)
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">AI</label>
                                    <input type="number" name="tahanan_ai" id="add_t_ai" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AII</label>
                                    <input type="number" name="tahanan_aii" id="add_t_aii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIII</label>
                                    <input type="number" name="tahanan_aiii" id="add_t_aiii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIV</label>
                                    <input type="number" name="tahanan_aiv" id="add_t_aiv" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AV</label>
                                    <input type="number" name="tahanan_av" id="add_t_av" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                            </div>
                            <div class="jumlah-box tahanan">
                                Total Tahanan: <span id="add_jumlah_tahanan">0</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="category-header overstaying-header">
                                <i class="fas fa-clock me-1"></i> OVERSTAYING
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">AI</label>
                                    <input type="number" name="overstaying_ai" id="add_o_ai" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AII</label>
                                    <input type="number" name="overstaying_aii" id="add_o_aii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIII</label>
                                    <input type="number" name="overstaying_aiii" id="add_o_aiii" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIV</label>
                                    <input type="number" name="overstaying_aiv" id="add_o_aiv" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AV</label>
                                    <input type="number" name="overstaying_av" id="add_o_av" class="form-control form-control-sm" value="0" min="0" onchange="updateJumlah('add')">
                                </div>
                            </div>
                            <div class="jumlah-box overstaying">
                                Total Overstaying: <span id="add_jumlah_overstay">0</span>
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
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data Overstaying</h5>
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
                            <div class="category-header tahanan-header">
                                <i class="fas fa-user-lock me-1"></i> TAHANAN (Kategori A)
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">AI</label>
                                    <input type="number" name="tahanan_ai" id="edit_t_ai" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AII</label>
                                    <input type="number" name="tahanan_aii" id="edit_t_aii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIII</label>
                                    <input type="number" name="tahanan_aiii" id="edit_t_aiii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIV</label>
                                    <input type="number" name="tahanan_aiv" id="edit_t_aiv" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AV</label>
                                    <input type="number" name="tahanan_av" id="edit_t_av" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                            </div>
                            <div class="jumlah-box tahanan">
                                Total Tahanan: <span id="edit_jumlah_tahanan">0</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="category-header overstaying-header">
                                <i class="fas fa-clock me-1"></i> OVERSTAYING
                            </div>
                            <div class="input-grid">
                                <div>
                                    <label class="form-label small text-muted">AI</label>
                                    <input type="number" name="overstaying_ai" id="edit_o_ai" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AII</label>
                                    <input type="number" name="overstaying_aii" id="edit_o_aii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIII</label>
                                    <input type="number" name="overstaying_aiii" id="edit_o_aiii" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AIV</label>
                                    <input type="number" name="overstaying_aiv" id="edit_o_aiv" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                                <div>
                                    <label class="form-label small text-muted">AV</label>
                                    <input type="number" name="overstaying_av" id="edit_o_av" class="form-control form-control-sm" min="0" onchange="updateJumlah('edit')">
                                </div>
                            </div>
                            <div class="jumlah-box overstaying">
                                Total Overstaying: <span id="edit_jumlah_overstay">0</span>
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
        const t_ai = parseInt(document.getElementById(prefix + '_t_ai')?.value) || 0;
        const t_aii = parseInt(document.getElementById(prefix + '_t_aii')?.value) || 0;
        const t_aiii = parseInt(document.getElementById(prefix + '_t_aiii')?.value) || 0;
        const t_aiv = parseInt(document.getElementById(prefix + '_t_aiv')?.value) || 0;
        const t_av = parseInt(document.getElementById(prefix + '_t_av')?.value) || 0;
        const totalTahanan = t_ai + t_aii + t_aiii + t_aiv + t_av;
        const tEl = document.getElementById(prefix + '_jumlah_tahanan');
        if (tEl) tEl.textContent = totalTahanan;

        const o_ai = parseInt(document.getElementById(prefix + '_o_ai')?.value) || 0;
        const o_aii = parseInt(document.getElementById(prefix + '_o_aii')?.value) || 0;
        const o_aiii = parseInt(document.getElementById(prefix + '_o_aiii')?.value) || 0;
        const o_aiv = parseInt(document.getElementById(prefix + '_o_aiv')?.value) || 0;
        const o_av = parseInt(document.getElementById(prefix + '_o_av')?.value) || 0;
        const totalOverstay = o_ai + o_aii + o_aiii + o_aiv + o_av;
        const oEl = document.getElementById(prefix + '_jumlah_overstay');
        if (oEl) oEl.textContent = totalOverstay;
    }

    function editData(data) {
        document.getElementById('formEdit').action = '/overstaying/' + data.id;
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_upt_id').value = data.upt_id;
        document.getElementById('edit_tanggal').value = data.tanggal ? data.tanggal.substring(0, 10) : '';

        document.getElementById('edit_t_ai').value = data.tahanan_ai;
        document.getElementById('edit_t_aii').value = data.tahanan_aii;
        document.getElementById('edit_t_aiii').value = data.tahanan_aiii;
        document.getElementById('edit_t_aiv').value = data.tahanan_aiv;
        document.getElementById('edit_t_av').value = data.tahanan_av;

        document.getElementById('edit_o_ai').value = data.overstaying_ai;
        document.getElementById('edit_o_aii').value = data.overstaying_aii;
        document.getElementById('edit_o_aiii').value = data.overstaying_aiii;
        document.getElementById('edit_o_aiv').value = data.overstaying_aiv;
        document.getElementById('edit_o_av').value = data.overstaying_av;

        document.getElementById('edit_keterangan').value = data.keterangan || '';
        updateJumlah('edit');

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush

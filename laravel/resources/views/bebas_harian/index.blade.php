@extends('layouts.app')

@section('title', 'Data Bebas Harian - Sultan Banten')
@section('page_title', 'Data Bebas Harian')

@push('styles')
<style>
    .input-matrix-table {
        min-width: 860px;
        width: 100%;
    }
    .input-matrix-table th {
        text-align: center;
        vertical-align: middle;
        font-size: 12px;
        background: #f1f5f9;
        padding: 8px 4px;
        white-space: nowrap;
    }
    .input-matrix-table td {
        vertical-align: middle;
        padding: 4px 3px;
    }
    .input-matrix-table input[type="number"] {
        text-align: center;
        font-weight: 600;
        font-size: 13px;
        padding: 6px 3px !important;
        height: 36px;
        width: 100%;
        min-width: 48px;
        border-radius: 6px;
        -moz-appearance: textfield;
    }
    .input-matrix-table input[type="number"]::-webkit-outer-spin-button,
    .input-matrix-table input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .input-matrix-table input[type="text"] {
        font-size: 12px;
        height: 36px;
        padding: 6px 8px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="mb-1 fw-bold text-dark"><i class="fas fa-door-open me-2 text-primary"></i>Pencatatan Bebas Harian</h5>
            <p class="text-muted mb-0 small">Pencatatan harian WBP / Tahanan bebas (Bebas Murni, PB, CB, CMB, Asimilasi, BDH, Lainnya)</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus me-1"></i> Input Bebas Hari Ini
        </button>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('bebas-harian.index') }}" class="row g-3 align-items-end">
                @if(!$userUptFilter)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">UPT</label>
                    <select name="upt_id" class="form-select form-select-sm">
                        <option value="">-- Semua UPT --</option>
                        @foreach ($uptList as $upt)
                            <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>
                                {{ $upt->nama_upt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulai }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tanggal Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $tanggalSampai }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('bebas-harian.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>UPT</th>
                            <th class="text-center">Bebas Murni</th>
                            <th class="text-center">PB</th>
                            <th class="text-center">CB</th>
                            <th class="text-center">CMB</th>
                            <th class="text-center">Asimilasi</th>
                            <th class="text-center">BDH</th>
                            <th class="text-center">Lainnya</th>
                            <th class="text-center bg-light fw-bold">Total</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dataList as $row)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ indonesianDate($row->tanggal) }}</td>
                                <td>{{ $row->nama_upt }}</td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $row->total_bm }}</span></td>
                                <td class="text-center"><span class="badge bg-primary">{{ $row->total_pb }}</span></td>
                                <td class="text-center"><span class="badge bg-info">{{ $row->total_cb }}</span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark">{{ $row->total_cmb }}</span></td>
                                <td class="text-center"><span class="badge bg-success">{{ $row->total_asimilasi }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">{{ $row->total_bdh }}</span></td>
                                <td class="text-center"><span class="badge bg-dark">{{ $row->total_lainnya }}</span></td>
                                <td class="text-center fw-bold bg-light">
                                    <span class="badge bg-success fs-6">{{ $row->grand_total }}</span>
                                </td>
                                <td><small class="text-muted">{{ $row->keterangan ?? '-' }}</small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal({{ $row->upt_id }}, '{{ $row->tanggal }}', '{{ $row->nama_upt }}')" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('bebas-harian.destroy') }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus seluruh data bebas harian UPT ini pada tanggal {{ indonesianDate($row->tanggal) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="del_upt_id" value="{{ $row->upt_id }}">
                                        <input type="hidden" name="del_tanggal" value="{{ $row->tanggal }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Data">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i>
                                    Belum ada data bebas harian yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH DATA (GRID MATRIX) -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-door-open me-2"></i> Form Input Bebas Harian
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('bebas-harian.store') }}" id="formTambah">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-4 bg-light p-3 rounded">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">UPT Pemasyarakatan <span class="text-danger">*</span></label>
                            @if ($userUptFilter)
                                <input type="hidden" name="upt_id" value="{{ $userUptFilter }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->upt->nama_upt ?? '-' }}" readonly>
                            @else
                                <select name="upt_id" class="form-select" required>
                                    <option value="">-- Pilih UPT --</option>
                                    @foreach ($uptList as $upt)
                                        <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Bebas <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm input-matrix-table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 160px;">Klasifikasi Tindak Pidana</th>
                                    <th style="width: 75px;">Bebas Murni</th>
                                    <th style="width: 75px;">PB</th>
                                    <th style="width: 75px;">CB</th>
                                    <th style="width: 75px;">CMB</th>
                                    <th style="width: 75px;">Asimilasi</th>
                                    <th style="width: 75px;">BDH</th>
                                    <th style="width: 75px;">Lainnya</th>
                                    <th style="min-width: 160px;">Ket. Jika Lainnya</th>
                                    <th style="width: 70px;" class="text-center bg-secondary">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($klasifikasiList as $klasifikasi => $key)
                                    <tr data-key="{{ $key }}">
                                        <td class="fw-bold text-start ps-3">{{ $klasifikasi }}</td>
                                        <td><input type="number" name="bebas_murni_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="pb_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="cb_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="cmb_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="asimilasi_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="bdh_{{ $key }}" class="form-control form-control-sm matrix-input" min="0" value="0"></td>
                                        <td><input type="number" name="lainnya_{{ $key }}" class="form-control form-control-sm matrix-input input-lainnya" min="0" value="0" data-key="{{ $key }}"></td>
                                        <td><input type="text" name="ket_lainnya_{{ $key }}" class="form-control form-control-sm input-ket-lainnya" placeholder="Tulis rincian jika lainnya..." disabled id="ket_{{ $key }}"></td>
                                        <td class="text-center fw-bold row-total bg-light">0</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr class="table-primary text-dark">
                                    <td class="text-end pe-3">TOTAL KESELURUHAN:</td>
                                    <td class="text-center" id="total_bm">0</td>
                                    <td class="text-center" id="total_pb">0</td>
                                    <td class="text-center" id="total_cb">0</td>
                                    <td class="text-center" id="total_cmb">0</td>
                                    <td class="text-center" id="total_asimilasi">0</td>
                                    <td class="text-center" id="total_bdh">0</td>
                                    <td class="text-center" id="total_lainnya">0</td>
                                    <td></td>
                                    <td class="text-center fs-6 text-primary" id="grand_total_all">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Catatan / Keterangan Tambahan</label>
                        <textarea name="keterangan_global" class="form-control" rows="2" placeholder="Catatan tambahan mengenai pembebasan hari ini (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Simpan Data Bebas Harian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT DATA (GRID MATRIX) -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i> Edit Data Bebas Harian
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('bebas-harian.update') }}" id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_upt_id" id="edit_upt_id">
                <input type="hidden" name="edit_tanggal" id="edit_tanggal">
                
                <div class="modal-body">
                    <div class="alert alert-info py-2 d-flex justify-content-between align-items-center mb-3">
                        <div><strong>UPT:</strong> <span id="display_edit_upt">-</span></div>
                        <div><strong>Tanggal:</strong> <span id="display_edit_tanggal">-</span></div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm input-matrix-table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 160px;">Klasifikasi Tindak Pidana</th>
                                    <th style="width: 75px;">Bebas Murni</th>
                                    <th style="width: 75px;">PB</th>
                                    <th style="width: 75px;">CB</th>
                                    <th style="width: 75px;">CMB</th>
                                    <th style="width: 75px;">Asimilasi</th>
                                    <th style="width: 75px;">BDH</th>
                                    <th style="width: 75px;">Lainnya</th>
                                    <th style="min-width: 160px;">Ket. Jika Lainnya</th>
                                    <th style="width: 70px;" class="text-center bg-secondary">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($klasifikasiList as $klasifikasi => $key)
                                    <tr data-edit-key="{{ $key }}">
                                        <td class="fw-bold text-start ps-3">{{ $klasifikasi }}</td>
                                        <td><input type="number" name="edit_bebas_murni_{{ $key }}" id="edit_bm_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_pb_{{ $key }}" id="edit_pb_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_cb_{{ $key }}" id="edit_cb_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_cmb_{{ $key }}" id="edit_cmb_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_asimilasi_{{ $key }}" id="edit_asimilasi_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_bdh_{{ $key }}" id="edit_bdh_{{ $key }}" class="form-control form-control-sm matrix-edit-input" min="0" value="0"></td>
                                        <td><input type="number" name="edit_lainnya_{{ $key }}" id="edit_lainnya_{{ $key }}" class="form-control form-control-sm matrix-edit-input edit-input-lainnya" min="0" value="0" data-edit-key="{{ $key }}"></td>
                                        <td><input type="text" name="edit_ket_lainnya_{{ $key }}" id="edit_ket_{{ $key }}" class="form-control form-control-sm edit-input-ket-lainnya" placeholder="Tulis rincian jika lainnya..." disabled></td>
                                        <td class="text-center fw-bold edit-row-total bg-light">0</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr class="table-warning text-dark">
                                    <td class="text-end pe-3">TOTAL KESELURUHAN:</td>
                                    <td class="text-center" id="edit_total_bm">0</td>
                                    <td class="text-center" id="edit_total_pb">0</td>
                                    <td class="text-center" id="edit_total_cb">0</td>
                                    <td class="text-center" id="edit_total_cmb">0</td>
                                    <td class="text-center" id="edit_total_asimilasi">0</td>
                                    <td class="text-center" id="edit_total_bdh">0</td>
                                    <td class="text-center" id="edit_total_lainnya">0</td>
                                    <td></td>
                                    <td class="text-center fs-6 text-dark" id="edit_grand_total_all">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Catatan / Keterangan Tambahan</label>
                        <textarea name="edit_keterangan_global" id="edit_keterangan_global" class="form-control" rows="2" placeholder="Catatan tambahan mengenai pembebasan hari ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function calculateAddTotals() {
        let colBm = 0, colPb = 0, colCb = 0, colCmb = 0, colAsim = 0, colBdh = 0, colLain = 0, grandTotal = 0;

        document.querySelectorAll('#formTambah tbody tr').forEach(tr => {
            const bm = parseInt(tr.querySelector('input[name^="bebas_murni_"]')?.value) || 0;
            const pb = parseInt(tr.querySelector('input[name^="pb_"]')?.value) || 0;
            const cb = parseInt(tr.querySelector('input[name^="cb_"]')?.value) || 0;
            const cmb = parseInt(tr.querySelector('input[name^="cmb_"]')?.value) || 0;
            const asim = parseInt(tr.querySelector('input[name^="asimilasi_"]')?.value) || 0;
            const bdh = parseInt(tr.querySelector('input[name^="bdh_"]')?.value) || 0;
            const lain = parseInt(tr.querySelector('input[name^="lainnya_"]')?.value) || 0;

            const rowSum = bm + pb + cb + cmb + asim + bdh + lain;
            tr.querySelector('.row-total').textContent = rowSum;

            colBm += bm; colPb += pb; colCb += cb; colCmb += cmb;
            colAsim += asim; colBdh += bdh; colLain += lain;
            grandTotal += rowSum;
        });

        document.getElementById('total_bm').textContent = colBm;
        document.getElementById('total_pb').textContent = colPb;
        document.getElementById('total_cb').textContent = colCb;
        document.getElementById('total_cmb').textContent = colCmb;
        document.getElementById('total_asimilasi').textContent = colAsim;
        document.getElementById('total_bdh').textContent = colBdh;
        document.getElementById('total_lainnya').textContent = colLain;
        document.getElementById('grand_total_all').textContent = grandTotal;
    }

    document.querySelectorAll('#formTambah .matrix-input').forEach(input => {
        input.addEventListener('input', calculateAddTotals);
    });

    document.querySelectorAll('#formTambah .input-lainnya').forEach(input => {
        input.addEventListener('input', function() {
            const key = this.getAttribute('data-key');
            const ketInput = document.getElementById('ket_' + key);
            if (parseInt(this.value) > 0) {
                ketInput.disabled = false;
                ketInput.focus();
            } else {
                ketInput.disabled = true;
                ketInput.value = '';
            }
        });
    });

    function calculateEditTotals() {
        let colBm = 0, colPb = 0, colCb = 0, colCmb = 0, colAsim = 0, colBdh = 0, colLain = 0, grandTotal = 0;

        document.querySelectorAll('#formEdit tbody tr').forEach(tr => {
            const bm = parseInt(tr.querySelector('input[name^="edit_bebas_murni_"]')?.value) || 0;
            const pb = parseInt(tr.querySelector('input[name^="edit_pb_"]')?.value) || 0;
            const cb = parseInt(tr.querySelector('input[name^="edit_cb_"]')?.value) || 0;
            const cmb = parseInt(tr.querySelector('input[name^="edit_cmb_"]')?.value) || 0;
            const asim = parseInt(tr.querySelector('input[name^="edit_asimilasi_"]')?.value) || 0;
            const bdh = parseInt(tr.querySelector('input[name^="edit_bdh_"]')?.value) || 0;
            const lain = parseInt(tr.querySelector('input[name^="edit_lainnya_"]')?.value) || 0;

            const rowSum = bm + pb + cb + cmb + asim + bdh + lain;
            tr.querySelector('.edit-row-total').textContent = rowSum;

            colBm += bm; colPb += pb; colCb += cb; colCmb += cmb;
            colAsim += asim; colBdh += bdh; colLain += lain;
            grandTotal += rowSum;
        });

        document.getElementById('edit_total_bm').textContent = colBm;
        document.getElementById('edit_total_pb').textContent = colPb;
        document.getElementById('edit_total_cb').textContent = colCb;
        document.getElementById('edit_total_cmb').textContent = colCmb;
        document.getElementById('edit_total_asimilasi').textContent = colAsim;
        document.getElementById('edit_total_bdh').textContent = colBdh;
        document.getElementById('edit_total_lainnya').textContent = colLain;
        document.getElementById('edit_grand_total_all').textContent = grandTotal;
    }

    document.querySelectorAll('#formEdit .matrix-edit-input').forEach(input => {
        input.addEventListener('input', calculateEditTotals);
    });

    document.querySelectorAll('#formEdit .edit-input-lainnya').forEach(input => {
        input.addEventListener('input', function() {
            const key = this.getAttribute('data-edit-key');
            const ketInput = document.getElementById('edit_ket_' + key);
            if (parseInt(this.value) > 0) {
                ketInput.disabled = false;
            } else {
                ketInput.disabled = true;
                ketInput.value = '';
            }
        });
    });

    const klasifikasiKeyMap = {!! json_encode($klasifikasiList) !!};

    function openEditModal(uptId, tanggal, uptName) {
        fetch(`{{ route('bebas-harian.detail') }}?upt_id=${uptId}&tanggal=${tanggal}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_upt_id').value = uptId;
                document.getElementById('edit_tanggal').value = tanggal;
                document.getElementById('display_edit_tanggal').textContent = tanggal;
                document.getElementById('display_edit_upt').textContent = uptName;

                document.querySelectorAll('#formEdit input[type="number"]').forEach(el => el.value = 0);
                document.querySelectorAll('#formEdit input[type="text"]').forEach(el => {
                    el.value = '';
                    el.disabled = true;
                });
                document.getElementById('edit_keterangan_global').value = '';

                let globalKet = '';

                data.forEach(item => {
                    const key = klasifikasiKeyMap[item.klasifikasi_pidana];
                    if (key) {
                        if (document.getElementById('edit_bm_' + key)) document.getElementById('edit_bm_' + key).value = item.bebas_murni;
                        if (document.getElementById('edit_pb_' + key)) document.getElementById('edit_pb_' + key).value = item.pb;
                        if (document.getElementById('edit_cb_' + key)) document.getElementById('edit_cb_' + key).value = item.cb;
                        if (document.getElementById('edit_cmb_' + key)) document.getElementById('edit_cmb_' + key).value = item.cmb;
                        if (document.getElementById('edit_asimilasi_' + key)) document.getElementById('edit_asimilasi_' + key).value = item.asimilasi;
                        if (document.getElementById('edit_bdh_' + key)) document.getElementById('edit_bdh_' + key).value = item.bdh;
                        if (document.getElementById('edit_lainnya_' + key)) document.getElementById('edit_lainnya_' + key).value = item.lainnya;

                        const ketEl = document.getElementById('edit_ket_' + key);
                        if (ketEl && item.ket_lainnya) {
                            ketEl.value = item.ket_lainnya;
                            ketEl.disabled = false;
                        }
                    }
                    if (item.keterangan) globalKet = item.keterangan;
                });

                document.getElementById('edit_keterangan_global').value = globalKet;
                calculateEditTotals();
                new bootstrap.Modal(document.getElementById('modalEdit')).show();
            })
            .catch(err => {
                alert('Gagal mengambil data untuk diedit: ' + err.message);
            });
    }
</script>
@endpush

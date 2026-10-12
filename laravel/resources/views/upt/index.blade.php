@extends('layouts.app')

@section('title', 'Data UPT - Sultan Banten')
@section('page_title')
    <i class="fas fa-building me-2"></i>Data UPT
@endsection

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-building text-primary me-2"></i>Master Data Unit Pelaksana Teknis</h5>
            <p class="text-muted small mb-0">Kelola daftar UPT Pemasyarakatan wilayah Banten, kapasitas hunian, dan koordinat peta</p>
        </div>
        <button class="btn btn-primary btn-sm px-3 mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus me-1"></i> Tambah UPT
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="bg-primary bg-opacity-10">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="25%">Nama UPT</th>
                    <th width="30%">Alamat</th>
                    <th class="text-center" width="12%">Kapasitas</th>
                    <th class="text-center" width="16%">Koordinat (Lat, Lng)</th>
                    <th class="text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($uptList as $upt)
                <tr>
                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-building text-primary me-2 opacity-50"></i>
                        {{ $upt->nama_upt }}
                    </td>
                    <td class="small text-muted">{{ $upt->alamat ?: '-' }}</td>
                    <td class="text-center fw-bold text-primary">{{ number_format($upt->kapasitas, 0, ',', '.') }}</td>
                    <td class="text-center small text-muted">
                        @if($upt->latitude && $upt->longitude)
                            <span class="badge bg-light text-dark border">{{ $upt->latitude }}, {{ $upt->longitude }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm py-0 px-2" onclick="editData({{ json_encode($upt) }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('upt.destroy', $upt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus UPT ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data UPT.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus me-2"></i>Tambah UPT Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('upt.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama UPT <span class="text-danger">*</span></label>
                        <input type="text" name="nama_upt" class="form-control form-control-sm" required placeholder="Contoh: Lapas Kelas IIA Serang">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap UPT"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas Hunian <span class="text-danger">*</span></label>
                        <input type="number" name="kapasitas" class="form-control form-control-sm" required min="0" value="0">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Latitude</label>
                            <input type="text" name="latitude" class="form-control form-control-sm" placeholder="-6.123456">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Longitude</label>
                            <input type="text" name="longitude" class="form-control form-control-sm" placeholder="106.123456">
                        </div>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data UPT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama UPT <span class="text-danger">*</span></label>
                        <input type="text" name="nama_upt" id="edit_nama_upt" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kapasitas Hunian <span class="text-danger">*</span></label>
                        <input type="number" name="kapasitas" id="edit_kapasitas" class="form-control form-control-sm" required min="0">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Latitude</label>
                            <input type="text" name="latitude" id="edit_latitude" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Longitude</label>
                            <input type="text" name="longitude" id="edit_longitude" class="form-control form-control-sm">
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
    function editData(data) {
        document.getElementById('formEdit').action = '/upt/' + data.id;
        document.getElementById('edit_nama_upt').value = data.nama_upt;
        document.getElementById('edit_alamat').value = data.alamat || '';
        document.getElementById('edit_kapasitas').value = data.kapasitas;
        document.getElementById('edit_latitude').value = data.latitude || '';
        document.getElementById('edit_longitude').value = data.longitude || '';
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush

@extends('layouts.app')

@section('title', 'Daftar Pimpinan WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-user-tie text-primary me-2"></i>Daftar Pimpinan Penerima Laporan WA
@endsection

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-users-cog text-primary me-2"></i>Daftar Pimpinan</h5>
            <p class="text-muted small mb-0">Nomor WhatsApp pimpinan yang menerima laporan otomatis sesuai jadwal</p>
        </div>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddPimpinan">
            <i class="fas fa-plus me-1"></i> Tambah Pimpinan
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th width="5%">Urutan</th>
                    <th>Nama Pimpinan</th>
                    <th>Jabatan</th>
                    <th>Nomor WhatsApp</th>
                    <th>Status</th>
                    <th width="12%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pimpinanList as $p)
                <tr>
                    <td class="fw-bold text-center"><span class="badge bg-light text-dark border">{{ $p->urutan }}</span></td>
                    <td class="fw-semibold">{{ $p->nama }}</td>
                    <td>{{ $p->jabatan ?? '-' }}</td>
                    <td><code>{{ $p->no_wa }}</code></td>
                    <td>
                        <span class="badge {{ $p->aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditPimpinan{{ $p->id }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('wa.pimpinan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pimpinan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEditPimpinan{{ $p->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered text-start">
                                <div class="modal-content">
                                    <div class="modal-header py-2 bg-primary text-white">
                                        <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data Pimpinan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('wa.pimpinan.update', $p->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" name="nama" class="form-control form-control-sm" value="{{ $p->nama }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Jabatan</label>
                                                <input type="text" name="jabatan" class="form-control form-control-sm" value="{{ $p->jabatan }}" placeholder="Contoh: Kakanwil">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                                                <input type="text" name="no_wa" class="form-control form-control-sm" value="{{ $p->no_wa }}" placeholder="08xxxxxxxxxx" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Urutan Tampilan</label>
                                                <input type="number" name="urutan" class="form-control form-control-sm" value="{{ $p->urutan }}" min="0">
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="aktif" id="aktifEdit{{ $p->id }}" {{ $p->aktif ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="aktifEdit{{ $p->id }}">Status Aktif (Menerima Pesan)</label>
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
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada data pimpinan terdaftar</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Pimpinan -->
<div class="modal fade" id="modalAddPimpinan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Data Pimpinan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('wa.pimpinan.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama dan Gelar" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control form-control-sm" placeholder="Contoh: Kepala Kantor Wilayah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control form-control-sm" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Urutan Tampilan</label>
                        <input type="number" name="urutan" class="form-control form-control-sm" value="{{ count($pimpinanList) + 1 }}" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="aktif" id="aktifAdd" checked>
                        <label class="form-check-label small" for="aktifAdd">Status Aktif (Menerima Pesan)</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Tambah Pimpinan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

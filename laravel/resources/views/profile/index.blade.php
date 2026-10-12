@extends('layouts.app')

@section('title', 'Profil Saya - Sultan Banten')
@section('page_title')
    <i class="fas fa-user-circle me-2"></i>Profil Saya
@endsection

@push('styles')
<style>
    .profile-photo-container {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .profile-photo {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--primary-500, #3b82f6);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
    }
    
    .profile-photo-placeholder {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        border: 4px solid var(--primary-500, #3b82f6);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
    }
    
    .profile-photo-placeholder i {
        font-size: 55px;
        color: white;
    }
    
    .profile-name {
        font-size: 19px;
        font-weight: 700;
        margin-top: 15px;
        color: var(--text-primary);
    }
    
    .profile-role {
        font-size: 14px;
        color: var(--text-secondary);
        margin-top: 4px;
    }
    
    .info-label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 3px;
    }
    
    .info-value {
        font-size: 14px;
        color: var(--text-primary);
        font-weight: 500;
        margin-bottom: 14px;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Profile Photo & Info -->
    <div class="col-md-4">
        <div class="content-card mb-4">
            <div class="profile-photo-container">
                @if(!empty($user->foto) && file_exists(public_path($user->foto)))
                    <img src="{{ asset($user->foto) }}" alt="Foto Profil" class="profile-photo">
                @else
                    <div class="profile-photo-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
                
                <div class="profile-name">{{ $user->nama_lengkap ?? $user->username }}</div>
                <div class="profile-role">
                    <span class="badge bg-primary px-3 py-1.5">{{ ucfirst($user->role) }}</span>
                </div>
            </div>
            
            <!-- Photo Upload Form -->
            <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="photo" class="form-label small fw-semibold">Upload Foto Profil</label>
                    <input type="file" class="form-control form-control-sm @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/*" required>
                    @error('photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text small text-muted">Format: JPG, PNG, GIF, WebP. Maks: 2MB</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-upload me-1"></i> Upload Foto
                    </button>
                    @if(!empty($user->foto) && file_exists(public_path($user->foto)))
                    <form action="{{ route('profile.photo.delete') }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus foto profil?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus foto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </form>
        </div>
        
        <!-- User Info -->
        <div class="content-card">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-info-circle text-primary me-2"></i>Informasi Akun</h5>
            <div class="info-label">Username</div>
            <div class="info-value">{{ $user->username }}</div>
            
            <div class="info-label">Nama Lengkap</div>
            <div class="info-value">{{ $user->nama_lengkap ?? '-' }}</div>
            
            <div class="info-label">Role</div>
            <div class="info-value">
                <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
            </div>
            
            @if(!empty($user->upt?->nama_upt))
            <div class="info-label">UPT</div>
            <div class="info-value">{{ $user->upt->nama_upt }}</div>
            @endif

            @if(!empty($user->no_wa))
            <div class="info-label">Nomor WhatsApp</div>
            <div class="info-value">{{ $user->no_wa }}</div>
            @endif
            
            <div class="info-label">Terdaftar Sejak</div>
            <div class="info-value">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</div>
        </div>
    </div>
    
    <!-- Edit Forms -->
    <div class="col-md-8">
        <!-- Edit Profile -->
        <div class="content-card mb-4">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-user-edit text-primary me-2"></i>Edit Data Profil</h5>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required>
                    @error('nama_lengkap')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="no_wa" class="form-label small fw-semibold">Nomor WhatsApp</label>
                    <input type="text" class="form-control @error('no_wa') is-invalid @enderror" id="no_wa" name="no_wa" value="{{ old('no_wa', $user->no_wa) }}" placeholder="Contoh: 08123456789">
                    @error('no_wa')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="content-card">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-key text-warning me-2"></i>Ubah Password</h5>
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="current_password" class="form-label small fw-semibold">Password Saat Ini <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label small fw-semibold">Password Baru <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password" required minlength="6">
                    <div class="form-text small text-muted">Minimal 6 karakter</div>
                    @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('confirm_password') is-invalid @enderror" id="confirm_password" name="confirm_password" required minlength="6">
                    @error('confirm_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-warning px-4">
                    <i class="fas fa-key me-1"></i> Ubah Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

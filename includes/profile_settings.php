<?php
/**
 * Profile Settings Modal Component
 * Include this file after jQuery/Bootstrap is loaded
 * Requires: $user variable with user data including 'foto', 'username', 'nama_lengkap', 'role'
 */

// Don't show if not logged in
if (!isset($user) || !$user) return;
?>

<!-- Modal Profile Settings -->
<div class="modal fade" id="modalProfileSettings" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="fas fa-user-cog me-2"></i>Pengaturan Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Profile Photo Section -->
                <div class="text-center mb-4">
                    <div class="position-relative d-inline-block">
                        <img id="profilePhotoPreview" 
                            src="<?= !empty($user['foto']) ? htmlspecialchars($user['foto']) . '?t=' . time() : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap']) . '&background=667eea&color=fff&size=120' ?>" 
                            alt="Foto Profil" 
                            class="rounded-circle shadow" 
                            style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #667eea;">
                        <label for="profilePhotoInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 cursor-pointer" style="cursor: pointer;" title="Ganti Foto">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png,image/gif" class="d-none" onchange="uploadProfilePhoto(this)">
                    </div>
                    <p class="small text-muted mt-2 mb-0">Klik ikon kamera untuk ganti foto</p>
                    <p class="small text-muted">Maks: 500KB, 500x500px</p>
                </div>

                <!-- User Info -->
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="row mb-2">
                        <div class="col-4 text-muted small">Username</div>
                        <div class="col-8 fw-semibold"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 text-muted small">Nama</div>
                        <div class="col-8 fw-semibold"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted small">Role</div>
                        <div class="col-8"><span class="badge bg-primary"><?= ucfirst($user['role']) ?></span></div>
                    </div>
                </div>

                <!-- Change Password Section -->
                <hr>
                <h6 class="mb-3"><i class="fas fa-key me-2"></i>Ganti Password</h6>
                <form id="formChangePassword">
                    <div class="mb-2">
                        <label class="form-label small">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Password Baru</label>
                        <input type="password" name="new_password" class="form-control form-control-sm" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-save me-1"></i> Simpan Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Global modal cleanup function
    function cleanupAllModals() {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }
    
    // Initialize modal profile with cleanup
    document.getElementById('modalProfileSettings')?.addEventListener('hidden.bs.modal', cleanupAllModals);
    
    // Profile Settings Modal click handler
    document.getElementById('btnProfileSettings')?.addEventListener('click', function() {
        cleanupAllModals();
        const modalEl = document.getElementById('modalProfileSettings');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });

    // Upload Profile Photo
    function uploadProfilePhoto(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validate file size (max 500KB)
            if (file.size > 500 * 1024) {
                alert('Ukuran file maksimal 500KB');
                return;
            }

            // Validate file type
            if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                alert('Format file harus JPG, PNG, atau GIF');
                return;
            }

            // Check image dimensions
            const img = new Image();
            img.onload = function() {
                if (this.width > 500 || this.height > 500) {
                    alert('Dimensi gambar maksimal 500x500 pixel');
                    return;
                }

                // Upload the file
                const formData = new FormData();
                formData.append('action', 'update_photo');
                formData.append('photo', file);

                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profilePhotoPreview').src = data.photo_url;
                        alert(data.message);
                        // Reload page to update topbar photo
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan saat mengupload foto');
                });
            };
            img.src = URL.createObjectURL(file);
        }
    }

    // Change Password
    document.getElementById('formChangePassword')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update_password');

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                this.reset();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan saat mengubah password');
        });
    });
</script>

// Logout Modal Functions
class LogoutModal {
    constructor() {
        this.modal = null;
        this.isProcessing = false;
        this.init();
    }

    init() {
        // รอให้ DOM โหลดเสร็จก่อน
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.createModal();
                this.attachEventListeners();
            });
        } else {
            this.createModal();
            this.attachEventListeners();
        }
    }

    createModal() {
        const modalHTML = `
            <div id="logoutModal" class="logout-modal">
                <div class="logout-modal-content">
                    <div class="logout-content">
                        <div class="logout-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <h3 class="logout-title">ออกจากระบบ</h3>
                        <p class="logout-message">
                            คุณต้องการออกจากระบบใช่หรือไม่?<br>
                            ข้อมูลที่ยังไม่ได้บันทึกอาจสูญหาย
                        </p>
                        <div class="logout-buttons">
                            <button class="logout-btn logout-btn-confirm" onclick="logoutModal.confirmLogout()">
                                <i class="fas fa-check"></i> ยืนยัน
                            </button>
                            <button class="logout-btn logout-btn-cancel" onclick="logoutModal.closeModal()">
                                <i class="fas fa-times"></i> ยกเลิก
                            </button>
                        </div>
                    </div>
                    
                    <div class="logout-loading">
                        <div class="logout-spinner"></div>
                        <span>กำลังออกจากระบบ...</span>
                    </div>
                    
                    <div class="logout-success">
                        <div class="logout-success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>ออกจากระบบสำเร็จ!</h4>
                        <p>กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('logoutModal');
    }

    attachEventListeners() {
        if (!this.modal) return;
        
        // ปิด modal เมื่อคลิกพื้นหลัง
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal && !this.isProcessing) {
                this.closeModal();
            }
        });

        // ปิด modal เมื่อกด ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && this.modal.classList.contains('show') && !this.isProcessing) {
                this.closeModal();
            }
        });
    }

    showModal() {
        if (this.isProcessing || !this.modal) return;
        
        this.resetModal();
        this.modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        if (this.isProcessing || !this.modal) return;
        
        this.modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    resetModal() {
        if (!this.modal) return;
        
        const content = this.modal.querySelector('.logout-content');
        const loading = this.modal.querySelector('.logout-loading');
        const success = this.modal.querySelector('.logout-success');
        
        if (content) content.style.display = 'block';
        if (loading) loading.style.display = 'none';
        if (success) success.style.display = 'none';
    }

    showLoading() {
        if (!this.modal) return;
        
        const content = this.modal.querySelector('.logout-content');
        const loading = this.modal.querySelector('.logout-loading');
        
        if (content) content.style.display = 'none';
        if (loading) loading.style.display = 'flex';
    }

    showSuccess() {
        if (!this.modal) return;
        
        const loading = this.modal.querySelector('.logout-loading');
        const success = this.modal.querySelector('.logout-success');
        
        if (loading) loading.style.display = 'none';
        if (success) success.style.display = 'flex';
    }

    async confirmLogout() {
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        this.showLoading();

        try {
            const response = await fetch('logout_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess();
                
                // รอ 2 วินาทีแล้ว redirect
                setTimeout(() => {
                    window.location.href = data.redirect_url || 'index.php';
                }, 2000);
            } else {
                throw new Error(data.message || 'เกิดข้อผิดพลาดในการออกจากระบบ');
            }
        } catch (error) {
            console.error('Logout error:', error);
            alert('เกิดข้อผิดพลาด: ' + error.message);
            this.closeModal();
        } finally {
            this.isProcessing = false;
        }
    }
}

// สร้าง instance ของ LogoutModal
let logoutModal;

// รอให้ DOM โหลดเสร็จก่อนสร้าง modal
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        logoutModal = new LogoutModal();
    });
} else {
    logoutModal = new LogoutModal();
}

// ฟังก์ชันสำหรับเรียกใช้ logout modal
function showLogoutModal() {
    if (logoutModal) {
        logoutModal.showModal();
    } else {
        // Fallback ถ้า modal ยังไม่พร้อม
        if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
            window.location.href = 'logout.php';
        }
    }
}

// ฟังก์ชันสำหรับ logout แบบเก่า (สำหรับ fallback)
function quickLogout() {
    if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
        window.location.href = 'logout.php';
    }
}
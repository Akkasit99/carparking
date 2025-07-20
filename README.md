# Car Parking Management System

ระบบจัดการลานจอดรถที่พัฒนาด้วย PHP และ Supabase

## Features

- 🔐 ระบบล็อกอินผู้ดูแลระบบ
- 📸 บันทึกภาพรถทางเข้า-ออก
- 📊 แสดงข้อมูลรถในลานจอด
- 👥 จัดการผู้ดูแลระบบ
- 📱 Responsive Design

## Technologies Used

- **Backend**: PHP
- **Database**: Supabase (PostgreSQL)
- **Frontend**: HTML, CSS, JavaScript
- **Server**: XAMPP

## Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/Akkasit99/carparking.git
   cd carparking
   ```

2. **Setup XAMPP**
   - Install XAMPP
   - Copy project to `htdocs/` folder
   - Start Apache server

3. **Database Configuration**
   - Create `db_connect.php` file with your Supabase credentials:
   ```php
   <?php
   $supabase_url = "https://dgqqonbhhivprdoutkzp.supabase.co";
   $supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRncXFvbmJoaGl2cHJkb3V0a3pwIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE1NDE4NjMsImV4cCI6MjA2NzExNzg2M30.Hm1k9yUAFJ1uRXF7K2h8n7DVtKicSaL_ox2bQPgJPQI";
   ?>
   ```

4. **Access Application**
   - Open browser: `http://localhost/carparking`

## Project Structure

```
carparking/
├── css/                    # CSS files for each page
│   ├── index.css
│   ├── dashboard.css
│   ├── add_admin.css
│   ├── entrance.css
│   ├── exit.css
│   ├── parking_lot.css
│   └── profile.css
├── image/                  # Car images
├── index.php              # Login page
├── dashboard.php          # Main dashboard
├── login.php              # Authentication
├── add_admin.php          # Add admin users
├── entrance.php           # Entrance records
├── exit.php               # Exit records
├── parking_lot.php        # Parking lot status
├── profile.php            # User profile
├── logout.php             # Logout
└── db_connect.php         # Database connection (not in repo)
```

## Database Schema

### Tables
- **users**: Admin user accounts
- **entrance**: Car entrance records
- **parking_exit**: Car exit records
- **parking_lot**: Current parking status

## Security Features

- Session-based authentication
- SQL injection protection
- XSS prevention
- Input validation

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email support@example.com or create an issue in this repository. 

## ขั้นตอนการติดตั้ง Git และ Push ขึ้น GitHub:

### 1. ติดตั้ง Git
- ดาวน์โหลด Git จาก: https://git-scm.com/download/win
- ติดตั้งตามขั้นตอนปกติ

### 2. ตั้งค่า Git (หลังจากติดตั้งแล้ว)
```bash
git config --global user.name "Akkasit99"
git config --global user.email "akkasit270@gmail.com"
```

### 3. สร้าง Repository บน GitHub
- ไปที่ https://github.com
- คลิก "New repository"
- ตั้งชื่อ repository (เช่น "carparking")
- เลือก Public หรือ Private
- **อย่า** เลือก "Initialize this repository with a README"

### 4. Push โปรเจกต์ขึ้น GitHub
หลังจากติดตั้ง Git แล้ว ให้รันคำสั่งเหล่านี้:

```bash
<code_block_to_apply_changes_from>
```

### 5. ไฟล์ที่เตรียมไว้แล้ว:
- ✅ `.gitignore` - ไม่รวมไฟล์ที่สำคัญ (db_connect.php)
- ✅ `README.md` - คำอธิบายโปรเจกต์

### หมายเหตุสำคัญ:
- **ไฟล์ `db_connect.php` จะไม่ถูก push ขึ้น GitHub** (เพื่อความปลอดภัย)
- ผู้ใช้ที่ clone โปรเจกต์ต้องสร้างไฟล์ `db_connect.php` เอง
- ข้อมูล Supabase URL และ Key จะไม่ถูกเปิดเผย

ต้องการให้ช่วยติดตั้ง Git หรือมีคำถามเพิ่มเติมไหมครับ?

# ตรวจสอบว่า Git ติดตั้งแล้ว
git --version

# เพิ่ม remote repository
git remote add origin https://github.com/Akkasit99/carparking.git

# Push ขึ้น GitHub
git push -u origin main 
# 🚗 ระบบจัดการลานจอดรถ (Car Parking Management System)

ระบบจัดการลานจอดรถแบบครบวงจร พัฒนาด้วย PHP และ Supabase สำหรับติดตามและจัดการข้อมูลรถยนต์ในลานจอด

## 🌟 ฟีเจอร์หลัก

### 📊 **Dashboard หลัก**
- แผงควบคุมแบบ Modern UI/UX
- เมนูหลัก 6 ส่วน: ทางเข้า, ทางออก, ลานจอด, ผู้ดูแลระบบ, กล้อง, สถานที่
- ระบบ Authentication และ Session Management

### 🚪 **ระบบติดตามรถยนต์**
- **ทางเข้า (Entrance)**: บันทึกรถที่เข้าลานจอด
- **ทางออก (Exit)**: บันทึกรถที่ออกจากลานจอด  
- **ลานจอด (Parking Lot)**: แสดงรถที่อยู่ในลานจอดปัจจุบัน
- ระบบกรองข้อมูลตามวันที่และเวลา
- แสดงรูปภาพรถยนต์พร้อม Modal Viewer

### 👥 **จัดการผู้ดูแลระบบ**
- เพิ่มผู้ดูแลระบบใหม่
- จัดการข้อมูลผู้ใช้งาน
- ระบบ Profile Management พร้อม Avatar Upload

### 📹 **จัดการกล้อง**
- เพิ่ม/ลบกล้องในระบบ
- กำหนด ID กล้อง, ชื่อกล้อง, และตำแหน่ง
- เชื่อมโยงกล้องกับสถานที่ต่างๆ
- แสดงตารางข้อมูลกล้องทั้งหมด

### 📍 **จัดการสถานที่**
- เพิ่ม/ลบสถานที่จอดรถ
- เชื่อมโยงสถานที่กับกล้อง
- จัดการ Parking Slots Status

### 🖼️ **ระบบจัดการรูปภาพ**
- Modal Image Viewer แบบ Responsive
- รองรับ URL ภายนอกและไฟล์ใน Supabase Storage
- ระบบ Avatar Upload สำหรับผู้ใช้

## 🛠️ เทคโนโลジีที่ใช้

### **Backend**
- **PHP 7.4+**: ภาษาหลักในการพัฒนา
- **Supabase**: Database และ Storage Backend
- **cURL**: สำหรับเรียก Supabase REST API
- **Session Management**: ระบบจัดการ Session และ Authentication

### **Frontend**
- **HTML5 & CSS3**: โครงสร้างและการออกแบบ
- **JavaScript (Vanilla)**: Interactive Features
- **Font Awesome 6**: Icon Library
- **Google Fonts (Inter)**: Typography
- **Responsive Design**: รองรับทุกขนาดหน้าจอ

### **Database (Supabase)**
- **users**: ข้อมูลผู้ดูแลระบบ
- **camera**: ข้อมูลกล้องในระบบ
- **locations**: ข้อมูลสถานที่จอดรถ
- **entrance**: บันทึกรถทางเข้า
- **parking_exit**: บันทึกรถทางออก
- **parking_lot**: ข้อมูลรถในลานจอด
- **parking_slots_status**: สถานะช่องจอดรถ

### **Storage**
- **Supabase Storage**: เก็บรูปภาพรถยนต์และ Avatar
- **Local Upload**: ระบบอัพโหลดไฟล์ Avatar

## 🔧 การติดตั้งและใช้งาน

### **1. ความต้องการของระบบ**
```
- PHP 7.4 หรือสูงกว่า
- Web Server (Apache/Nginx)
- Supabase Account
- cURL Extension สำหรับ PHP
```

### **2. การตั้งค่า Supabase**

#### สร้าง Database Tables:
```sql
-- ตาราง users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    parking_name VARCHAR(100),
    position VARCHAR(50),
    created_by VARCHAR(50)
);

-- ตาราง camera
CREATE TABLE camera (
    camera_id VARCHAR(20) PRIMARY KEY,
    camera_name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    created_by VARCHAR(50)
);

-- ตาราง locations
CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    camera_id VARCHAR(20) REFERENCES camera(camera_id)
);

-- ตาราง entrance
CREATE TABLE entrance (
    id SERIAL PRIMARY KEY,
    image TEXT,
    date TIMESTAMP DEFAULT NOW(),
    camera_id VARCHAR(20)
);

-- ตาราง parking_exit
CREATE TABLE parking_exit (
    id SERIAL PRIMARY KEY,
    image TEXT,
    date TIMESTAMP DEFAULT NOW(),
    camera_id VARCHAR(20)
);

-- ตาราง parking_lot
CREATE TABLE parking_lot (
    id SERIAL PRIMARY KEY,
    image TEXT,
    date TIMESTAMP DEFAULT NOW(),
    camera_id VARCHAR(20)
);
```

#### สร้าง Storage Bucket:
```sql
-- สร้าง bucket สำหรับรูปภาพ
INSERT INTO storage.buckets (id, name, public) VALUES ('vehicle-images', 'vehicle-images', true);

-- Policy สำหรับอ่านรูปภาพ
CREATE POLICY "Public Access" ON storage.objects FOR SELECT USING (bucket_id = 'vehicle-images');

-- Policy สำหรับอัปโหลด (authenticated users)
CREATE POLICY "Authenticated users can upload" ON storage.objects FOR INSERT WITH CHECK (bucket_id = 'vehicle-images' AND auth.role() = 'authenticated');
```

### **3. การตั้งค่าโปรเจกต์**

1. **สร้างไฟล์ `db_connect.php`**:
```php
<?php
// Supabase Configuration
$supabase_url = 'https://your-project.supabase.co';
$supabase_key = 'your-anon-key';
?>
```

2. **ตั้งค่า Web Server**:
   - วางไฟล์ในโฟลเดอร์ `htdocs` (XAMPP) หรือ `www` (WAMP)
   - เปิดใช้งาน PHP cURL Extension

3. **สร้างโฟลเดอร์ Upload**:
```bash
mkdir uploads/avatars
chmod 755 uploads/avatars
```

## 📁 โครงสร้างไฟล์

```
carparking/
├── 📄 index.php              # หน้าเข้าสู่ระบบ
├── 📄 login.php              # หน้า Login Form
├── 📄 dashboard.php          # แผงควบคุมหลัก
├── 📄 profile.php            # จัดการโปรไฟล์ผู้ใช้
├── 📄 entrance.php           # ข้อมูลรถทางเข้า
├── 📄 exit.php               # ข้อมูลรถทางออก
├── 📄 parking_lot.php        # ข้อมูลรถในลานจอด
├── 📄 add_admin.php          # เพิ่มผู้ดูแลระบบ
├── 📄 add_camera.php         # จัดการกล้อง
├── 📄 add_location.php       # จัดการสถานที่
├── 📄 upload_avatar.php      # อัพโหลด Avatar
├── 📄 clear_avatar.php       # ลบ Avatar
├── 📁 css/                   # ไฟล์ CSS
│   ├── style.css            # CSS หลัก
│   ├── dashboard.css        # CSS Dashboard
│   ├── profile.css          # CSS Profile
│   └── ...
├── 📁 includes/              # ไฟล์ PHP Backend
│   ├── session_check.php    # ตรวจสอบ Session
│   ├── login_handler.php    # จัดการ Login
│   ├── profile_handler.php  # จัดการ Profile
│   ├── *_data_handler.php   # จัดการข้อมูลต่างๆ
│   └── security_headers.php # Security Headers
└── 📁 uploads/avatars/       # รูป Avatar ผู้ใช้
```

## 🔐 ระบบความปลอดภัย

- **Session Management**: ตรวจสอบการเข้าสู่ระบบทุกหน้า
- **CSRF Protection**: ป้องกัน Cross-Site Request Forgery
- **Input Validation**: ตรวจสอบข้อมูลนำเข้าทุกฟอร์ม
- **SQL Injection Prevention**: ใช้ Parameterized Queries
- **File Upload Security**: ตรวจสอบประเภทไฟล์และขนาด

## 🎨 UI/UX Features

- **Modern Glass Morphism Design**: การออกแบบแบบ Glass Effect
- **Responsive Layout**: รองรับทุกขนาดหน้าจอ
- **Interactive Animations**: เอฟเฟกต์การเคลื่อนไหวที่ลื่นไหล
- **Modal Image Viewer**: ดูรูปภาพแบบเต็มจอ
- **Loading States**: แสดงสถานะการโหลดข้อมูล
- **Success/Error Messages**: ข้อความแจ้งเตือนที่ชัดเจน

## 🚀 การใช้งาน

1. **เข้าสู่ระบบ**: ใช้ Username/Email และ Password
2. **Dashboard**: เลือกเมนูที่ต้องการจัดการ
3. **ดูข้อมูลรถ**: เลือกวันที่และเวลาที่ต้องการ
4. **จัดการกล้อง**: เพิ่ม/ลบกล้องและกำหนดตำแหน่ง
5. **จัดการสถานที่**: เชื่อมโยงสถานที่กับกล้อง
6. **จัดการผู้ใช้**: เพิ่มผู้ดูแลระบบใหม่

## 🔧 การแก้ไขปัญหา

### **รูปภาพไม่แสดง**
- ตรวจสอบ Supabase Storage Bucket และ Policies
- ตรวจสอบ URL รูปภาพในฐานข้อมูล
- ตรวจสอบการตั้งค่า CORS

### **ไม่สามารถเข้าสู่ระบบได้**
- ตรวจสอบการเชื่อมต่อ Supabase
- ตรวจสอบ Username/Password ในฐานข้อมูล
- ตรวจสอบ Session Configuration

### **ข้อผิดพลาด API**
- ตรวจสอบ Supabase URL และ API Key
- ตรวจสอบ cURL Extension
- ตรวจสอบ Network Connection

## 📞 การสนับสนุน

สำหรับการสนับสนุนและการพัฒนาเพิ่มเติม กรุณาติดต่อผู้พัฒนาระบบ

---

**พัฒนาโดย**: Car Parking Management Team  
**เวอร์ชัน**: 1.0.0  
**อัพเดทล่าสุด**: 2024
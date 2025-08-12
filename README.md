# ระบบจัดการลานจอดรถ

## การตั้งค่า Supabase Storage

### 1. สร้าง Storage Bucket
1. เข้าไปที่ Supabase Dashboard
2. ไปที่ Storage > Buckets
3. สร้าง bucket ใหม่ชื่อ `vehicle-images`
4. ตั้งค่าเป็น Public bucket

### 2. ตั้งค่า Storage Policies
สร้าง policy สำหรับให้อ่านรูปภาพได้:

```sql
-- Policy สำหรับอ่านรูปภาพ (public access)
CREATE POLICY "Public Access" ON storage.objects FOR SELECT USING (bucket_id = 'vehicle-images');

-- Policy สำหรับอัปโหลดรูปภาพ (authenticated users only)
CREATE POLICY "Authenticated users can upload" ON storage.objects FOR INSERT WITH CHECK (bucket_id = 'vehicle-images' AND auth.role() = 'authenticated');
```

### 3. โครงสร้าง Database
ตาราง `entrance`:
- `id` (int, primary key)
- `image` (text) - ชื่อไฟล์รูปภาพ
- `date` (timestamp)
- `camera_id` (text)

ตาราง `parking_exit`:
- `id` (int, primary key)
- `image` (text) - ชื่อไฟล์รูปภาพ
- `date` (timestamp)
- `camera_id` (text)

ตาราง `parking_lot`:
- `id` (int, primary key)
- `image` (text) - ชื่อไฟล์รูปภาพ
- `date` (timestamp)
- `camera_id` (text)

## การใช้งาน

### การอัปโหลดรูปภาพ
1. อัปโหลดรูปภาพไปยัง Supabase Storage bucket `vehicle-images`
2. บันทึกชื่อไฟล์ลงในฐานข้อมูล

### การแสดงรูปภาพ
ระบบรองรับการแสดงรูปภาพ 2 แบบ:

1. **URL จากภายนอก** (เช่น จากกล้องหรือระบบอื่น):
   - ระบบจะตรวจสอบว่าเป็น URL ที่ถูกต้อง
   - แสดงรูปภาพจาก URL นั้นโดยตรง

2. **ไฟล์ใน Supabase Storage**:
   - ระบบจะสร้าง URL จาก Supabase Storage
   - URL format: `https://[YOUR_SUPABASE_URL]/storage/v1/object/public/vehicle-images/[FILENAME]`

## การแก้ไขปัญหา

### รูปภาพไม่แสดง
1. ตรวจสอบว่า bucket `vehicle-images` ถูกสร้างแล้ว
2. ตรวจสอบ storage policies ว่าอนุญาตให้อ่านได้
3. ตรวจสอบว่าชื่อไฟล์ในฐานข้อมูลตรงกับไฟล์ใน storage

### การตั้งค่า CORS (ถ้าจำเป็น)
หากมีปัญหา CORS ให้เพิ่ม policy:
```sql
CREATE POLICY "CORS Policy" ON storage.objects FOR SELECT USING (bucket_id = 'vehicle-images');
```

## ไฟล์ที่เกี่ยวข้อง
- `entrance.php` - แสดงข้อมูลรถทางเข้า
- `exit.php` - แสดงข้อมูลรถทางออก  
- `parking_lot.php` - แสดงข้อมูลรถในลานจอด
- `db_connect.php` - การเชื่อมต่อฐานข้อมูล 
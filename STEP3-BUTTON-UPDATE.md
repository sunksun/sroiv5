## ✅ การปรับปรุง Step 3 เสร็จสิ้น

### 🔄 การเปลี่ยนแปลงที่ทำ:

#### 1. **ปรับปุ่มใน Modal:**

```html
<!-- เดิม -->
<button type="submit" class="btn btn-primary">
  เสร็จสิ้น: ดูสรุป Impact Chain <i class="fas fa-check"></i>
</button>

<!-- ใหม่ -->
<button type="submit" class="btn btn-success">
  <i class="fas fa-arrow-right"></i> ถัดไป: เลือกผลลัพธ์ (Step 4)
</button>
```

#### 2. **ปรับ Flow การทำงาน:**

```php
// เดิม: ไปยัง Summary ทันที
header("location: summary.php?project_id=" . $project_id);

// ใหม่: ไปยัง Step 4
header("location: step4-outcome.php?project_id=" . $project_id);
```

#### 3. **ปรับ Progress Indicator:**

- เพิ่ม Step 4 และ Step 5 (สรุป) ใน progress bar
- เปลี่ยน width จาก 25% เป็น 20% (5 steps)
- เพิ่มป้ายกำกับ "4. ผลลัพธ์" และ "5. สรุป"

#### 4. **ปรับคำแนะนำ:**

- เพิ่มข้อความ "หลังจากนั้นจะไปยัง Step 4 เลือกผลลัพธ์"

### 📋 ผลลัพธ์:

✅ **ปุ่มใหม่**: ชัดเจนและบอกขั้นตอนถัดไป  
✅ **Flow ถูกต้อง**: Strategy → Activity → Output → **Outcome** → Summary  
✅ **Progress ชัดเจน**: แสดง 5 ขั้นตอนทั้งหมด  
✅ **UX ดีขึ้น**: ผู้ใช้รู้ว่าอยู่ขั้นตอนไหนและต่อไปต้องทำอะไร

### 🔗 การต่อเนื่อง:

ตอนนี้เมื่อผู้ใช้เลือกผลผลิตใน Step 3 และกรอกรายละเอียดเสร็จ ระบบจะ:

1. บันทึกข้อมูลลงฐานข้อมูล
2. ไปยัง `step4-outcome.php`
3. ให้ผู้ใช้เลือกผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก

**พร้อมพัฒนา Step 4 ต่อได้เลย! 🚀**

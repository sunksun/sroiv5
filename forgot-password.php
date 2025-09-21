<?php
session_start();
require_once "config.php";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .contact-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .contact-item i {
            width: 20px;
            margin-right: 10px;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <!-- Logo -->
            <div class="text-center mb-4">
                <img src="assets/imgs/lru.png" alt="LRU Logo" class="img-fluid" style="max-height: 100px;">
            </div>
            <h2 class="text-center mb-4">ลืมรหัสผ่าน</h2>

            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> วิธีการรีเซ็ตรหัสผ่าน</h5>
                <p class="mb-0">
                    หากคุณลืมรหัสผ่าน กรุณาติดต่อผู้ดูแลระบบเพื่อขอรีเซ็ตรหัสผ่านใหม่
                </p>
            </div>

            <div class="contact-info">
                <h6 class="mb-3"><i class="fas fa-user-shield"></i> ติดต่อผู้ดูแลระบบ</h6>
                
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>โทรศัพท์: 042-123456 ต่อ 1234</span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>อีเมล: admin@lru.ac.th</span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>เวลาทำการ: จันทร์-ศุกร์ 08:30-16:30 น.</span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>สถานที่: อาคารบริหาร ชั้น 2 ห้อง 201</span>
                </div>
            </div>

            <div class="alert alert-warning">
                <h6><i class="fas fa-shield-alt"></i> เพื่อความปลอดภัย</h6>
                <ul class="mb-0">
                    <li>ผู้ดูแลระบบจะตรวจสอบตัวตนก่อนรีเซ็ตรหัสผ่าน</li>
                    <li>คุณจะได้รับรหัสผ่านชั่วคราว</li>
                    <li>กรุณาเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> กลับไปเข้าสู่ระบบ
                </a>
            </div>

            <!-- ข้อมูลเพิ่มเติมสำหรับผู้ดูแลระบบ -->
            <div class="mt-4 p-3 bg-light rounded">
                <h6 class="text-muted">สำหรับผู้ดูแลระบบ:</h6>
                <p class="text-muted small mb-0">
                    เข้าสู่ระบบด้วยบัญชี Admin และไปที่เมนู "จัดการผู้ใช้" เพื่อรีเซ็ตรหัสผ่าน
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>
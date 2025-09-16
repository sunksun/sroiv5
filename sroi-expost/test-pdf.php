<?php
// Test simple PDF generation
set_time_limit(60);
ini_set('memory_limit', '128M');

require_once '../vendor/autoload.php';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font_size' => 14
    ]);
    
    $html = '<h1>Test PDF</h1><p>การทดสอบ PDF พื้นฐาน</p>';
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('test.pdf', \Mpdf\Output\Destination::INLINE);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
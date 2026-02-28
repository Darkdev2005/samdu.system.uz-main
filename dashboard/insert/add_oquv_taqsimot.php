<?php
    include_once '../config.php';
    $db = new Database();
    header('Content-Type: application/json');

    $yuklama_id  = isset($_POST['yuklama_id']) ? (int)$_POST['yuklama_id'] : 0;
    $type        = trim($_POST['type'] ?? '');
    $taqsimotlar = json_decode($_POST['taqsimotlar'] ?? '[]', true);

    if ($yuklama_id <= 0 || empty($type) || !is_array($taqsimotlar)) {
        echo json_encode([
            'success' => false,
            'message' => 'Noto‘g‘ri ma’lumot yuborildi'
        ]);
        exit;
    }
    $success = false;
    foreach ($taqsimotlar as $t) {
        $teacher_id = (int)$t['oqituvchi_id'];
        $soat       = (float)$t['soat_soni'];
        if ($teacher_id <= 0 || $soat <= 0) continue;

        $exists = $db->get_data_by_table('taqsimotlar', [
            'oquv_reja_id' => $yuklama_id,
            'teacher_id'   => $teacher_id,
            'type'         => $type
        ]);
        if ($exists) {
            // 🔁 UPDATE
            $res = $db->update('taqsimotlar',
                ['soat' => $soat + $exists['soat']], 'id = '. $exists['id']
            );
        } else {
            $res = $db->insert('taqsimotlar', [
                'oquv_reja_id' => $yuklama_id,
                'teacher_id'   => $teacher_id,
                'soat'         => $soat,
                'type'         => $type
            ]);
        }
        if ($res) $success = true;
    }

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Taqsimot saqlandi' : 'Saqlashda xatolik'
    ]);
?>

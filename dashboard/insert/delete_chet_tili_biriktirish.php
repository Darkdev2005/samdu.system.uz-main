<?php
    // Izoh: Chet tili biriktirishlarini (fan + semestr bo'yicha) o'chirish.
    include_once '../config.php';
    header('Content-Type: application/json');
    $db = new Database();

    $rowId = (int) ($_POST['id'] ?? 0);
    $fanId = (int) ($_POST['fan_id'] ?? 0);
    $semestrId = (int) ($_POST['semestr_id'] ?? 0);
    $semestrNum = (int) ($_POST['semestr_num'] ?? 0);

    if ($rowId > 0) {
        $db->query("
            DELETE FROM chet_tili_guruhlar
            WHERE id = $rowId
        ");
    } elseif ($fanId > 0 && $semestrNum > 0) {
        // Izoh: Fan + semestr raqami bo'yicha guruhni o'chirish.
        $db->query("
            DELETE ct FROM chet_tili_guruhlar ct
            JOIN semestrlar s ON s.id = ct.semestr_id
            WHERE ct.fan_id = $fanId AND s.semestr = $semestrNum
        ");
    } elseif ($fanId > 0 && $semestrId > 0) {
        // Izoh: Eski format uchun fallback.
        $db->query("
            DELETE FROM chet_tili_guruhlar
            WHERE fan_id = $fanId AND semestr_id = $semestrId
        ");
    } else {
        echo json_encode(['success' => false, 'message' => 'Ma\'lumotlar to\'liq emas']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Chet tili biriktirishlari o\'chirildi']);
?>

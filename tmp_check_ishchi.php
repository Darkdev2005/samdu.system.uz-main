<?php
require_once __DIR__ . '/dashboard/config.php';
$db = new Database();

$code = 'TEST_TANLOV_' . date('His');
$baseName = 'Test Tanlov Fan';
$variantNames = ['Test Variant A', 'Test Variant B'];
$izoh = 'codex test';

$semestrRes = $db->query("SELECT id, semestr FROM semestrlar ORDER BY id ASC LIMIT 1");
$semestrRow = $semestrRes ? mysqli_fetch_assoc($semestrRes) : null;
if (!$semestrRow) { echo "No semestrlar found\n"; exit(1); }
$semestr_id = (int)$semestrRow['id'];

$kafRes = $db->query("SELECT id, name FROM kafedralar ORDER BY id ASC LIMIT 1");
$kafRow = $kafRes ? mysqli_fetch_assoc($kafRes) : null;
if (!$kafRow) { echo "No kafedralar found\n"; exit(1); }
$kafedra_id = (int)$kafRow['id'];

$darsRes = $db->query("SELECT id, name FROM dars_soat_turlar ORDER BY id ASC LIMIT 1");
$darsRow = $darsRes ? mysqli_fetch_assoc($darsRes) : null;
if (!$darsRow) { echo "No dars_soat_turlar found\n"; exit(1); }
$dars_tur_id = (int)$darsRow['id'];

$base = $db->get_data_by_table('fanlar', [
    'fan_code' => $code,
    'fan_name' => $baseName,
    'semestr_id' => $semestr_id,
    'tanlov_fan' => 1,
    'kafedra_id' => 0
]);
$base_id = $base ? (int)$base['id'] : 0;
if ($base_id === 0) {
    $base_id = (int)$db->insert('fanlar', [
        'fan_code' => $code,
        'fan_name' => $baseName,
        'kafedra_id' => 0,
        'semestr_id' => $semestr_id,
        'tanlov_fan' => 1
    ]);
}

if ($base_id === 0) { echo "Failed to create base fan\n"; exit(1); }

$oquvInsert = $db->insert('oquv_rejalar', [
    'fan_id' => $base_id,
    'dars_tur_id' => $dars_tur_id,
    'dars_soat' => 10,
    'izoh' => $izoh
]);

$variant_ids = [];
foreach ($variantNames as $vName) {
    $variant = $db->get_data_by_table('fanlar', [
        'fan_code' => $code,
        'fan_name' => $vName,
        'kafedra_id' => $kafedra_id,
        'semestr_id' => $semestr_id,
        'tanlov_fan' => 1
    ]);
    $vid = $variant ? (int)$variant['id'] : 0;
    if ($vid === 0) {
        $vid = (int)$db->insert('fanlar', [
            'fan_code' => $code,
            'fan_name' => $vName,
            'kafedra_id' => $kafedra_id,
            'semestr_id' => $semestr_id,
            'tanlov_fan' => 1
        ]);
    }
    if ($vid > 0) { $variant_ids[] = $vid; }
}

$ishchiRow = $db->get_data_by_table('ishchi_oquv_reja', [
    'base_fan_id' => $base_id,
    'semestr_id' => $semestr_id
]);
$ishchi_id = $ishchiRow ? (int)$ishchiRow['id'] : 0;
if ($ishchi_id === 0) {
    $ishchi_id = (int)$db->insert('ishchi_oquv_reja', [
        'base_fan_id' => $base_id,
        'semestr_id' => $semestr_id
    ]);
}

if ($ishchi_id > 0) {
    $db->query("DELETE FROM ishchi_oquv_reja_variants WHERE ishchi_reja_id = $ishchi_id");
    foreach ($variant_ids as $vid) {
        $db->insert('ishchi_oquv_reja_variants', [
            'ishchi_reja_id' => $ishchi_id,
            'fan_id' => $vid
        ]);
    }
}

$check = $db->query("
    SELECT fb.fan_code, fb.fan_name, s.semestr,
           GROUP_CONCAT(fv.fan_name ORDER BY fv.fan_name SEPARATOR ' | ') AS variants
    FROM ishchi_oquv_reja ior
    JOIN fanlar fb ON fb.id = ior.base_fan_id
    JOIN semestrlar s ON s.id = ior.semestr_id
    LEFT JOIN ishchi_oquv_reja_variants iv ON iv.ishchi_reja_id = ior.id
    LEFT JOIN fanlar fv ON fv.id = iv.fan_id
    WHERE ior.id = $ishchi_id
    GROUP BY ior.id
");
$checkRow = $check ? mysqli_fetch_assoc($check) : null;

$output = [
    'semestr_id' => $semestr_id,
    'base_fan_id' => $base_id,
    'variant_ids' => $variant_ids,
    'ishchi_id' => $ishchi_id,
    'oquv_rejalar_insert_id' => $oquvInsert,
    'preview' => $checkRow
];

echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

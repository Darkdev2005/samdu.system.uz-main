<?php
include_once '../config.php';
$db = new Database();

$filters = [];
if (!empty($_POST['kafedra_id'])) {
    $filters['kafedra_id'] = (int)$_POST['kafedra_id'];
}
if (!empty($_POST['semestr'])) {
    $filters['semestr'] = (int)$_POST['semestr'];
}
if (!empty($_POST['oqituvchi_id'])) {
    $filters['oqituvchi_id'] = (int)$_POST['oqituvchi_id'];
}

// Izoh: Semestr juftligini aniqlash (1-2, 3-4, 5-6, 7-8).
$pairStart = 1;
if (!empty($filters['semestr'])) {
    $s = (int)$filters['semestr'];
    $pairStart = ($s % 2 === 0) ? $s - 1 : $s;
}
$pairEnd = $pairStart + 1;

// Izoh: Filterlar uchun SQL bo'laklari (oqituvchi/kafedra).
$whereTeacher = '';
$whereKafedra = '';
if (!empty($filters['oqituvchi_id'])) {
    $whereTeacher = " AND o.id = " . (int)$filters['oqituvchi_id'];
}
if (!empty($filters['kafedra_id'])) {
    $whereKafedra = " AND o.kafedra_id = " . (int)$filters['kafedra_id'];
}

// Izoh: Ta'lim yo'nalishi bo'yicha guruhlar (raqam va talabalar soni).
$sql = "
    WITH guruh_agg AS (
        SELECT
            yonalish_id,
            GROUP_CONCAT(DISTINCT guruh_nomer ORDER BY guruh_nomer SEPARATOR '-') AS guruh_raqami,
            SUM(soni) AS talabalar_soni
        FROM guruhlar
        GROUP BY yonalish_id
    )
    SELECT
        t.teacher_id,
        o.fio,
        o.lavozim,
        o.stavka,
        iu.name AS ilmiy_unvon,
        id.name AS ilmiy_daraja,
        isht.name AS shtat_turi,
        y.name AS talim_yonalishi,
        y.code AS yonalish_code,
        ga.guruh_raqami,
        ga.talabalar_soni,
        MAX(y.patok_soni) AS patok_soni,
        MAX(y.kattaguruh_soni) AS kattaguruh_soni,
        MAX(y.kichikguruh_soni) AS kichikguruh_soni,
        tsh.name AS oquv_shakli,
        s.semestr,
        FLOOR((s.semestr + 1)/2) AS kurs,
        f.id AS fan_id,
        f.fan_code,
        f.fan_name,
        r.dars_tur_id,
        NULL AS qoshimcha_id,
        SUM(t.soat) AS soat,
        'A' AS type
    FROM taqsimotlar t
    JOIN oquv_rejalar r ON r.id = t.oquv_reja_id
    JOIN fanlar f ON f.id = r.fan_id
    JOIN semestrlar s ON s.id = f.semestr_id
    JOIN yonalishlar y ON y.id = s.yonalish_id
    JOIN talim_shakllar tsh ON tsh.id = y.talim_shakli_id
    JOIN guruh_agg ga ON ga.yonalish_id = y.id
    JOIN oqituvchilar o ON o.id = t.teacher_id
    LEFT JOIN ilmiy_unvonlar iu ON iu.id = o.ilmiy_unvon_id
    LEFT JOIN ilmiy_darajalar id ON id.id = o.ilmiy_daraja_id
    LEFT JOIN ish_turlar isht ON isht.id = o.ishtur_id
    WHERE t.type = 'A'
      AND s.semestr IN ($pairStart, $pairEnd)
      $whereTeacher
      $whereKafedra
    GROUP BY t.teacher_id, s.semestr, f.id, r.dars_tur_id

    UNION ALL

    SELECT
        t.teacher_id,
        o.fio,
        o.lavozim,
        o.stavka,
        iu.name AS ilmiy_unvon,
        id.name AS ilmiy_daraja,
        isht.name AS shtat_turi,
        y.name AS talim_yonalishi,
        y.code AS yonalish_code,
        ga.guruh_raqami,
        ga.talabalar_soni,
        MAX(y.patok_soni) AS patok_soni,
        MAX(y.kattaguruh_soni) AS kattaguruh_soni,
        MAX(y.kichikguruh_soni) AS kichikguruh_soni,
        tsh.name AS oquv_shakli,
        s.semestr,
        FLOOR((s.semestr + 1)/2) AS kurs,
        0 AS fan_id,
        '' AS fan_code,
        qf.fan_name,
        NULL AS dars_tur_id,
        qf.qoshimcha_dars_id AS qoshimcha_id,
        SUM(t.soat) AS soat,
        'Q' AS type
    FROM taqsimotlar t
    JOIN qoshimcha_oquv_rejalar q ON q.id = t.oquv_reja_id
    JOIN qoshimcha_fanlar qf ON qf.id = q.qoshimcha_fanid
    JOIN semestrlar s ON s.id = qf.semestr_id
    JOIN yonalishlar y ON y.id = s.yonalish_id
    JOIN talim_shakllar tsh ON tsh.id = y.talim_shakli_id
    JOIN guruh_agg ga ON ga.yonalish_id = y.id
    JOIN oqituvchilar o ON o.id = t.teacher_id
    LEFT JOIN ilmiy_unvonlar iu ON iu.id = o.ilmiy_unvon_id
    LEFT JOIN ilmiy_darajalar id ON id.id = o.ilmiy_daraja_id
    LEFT JOIN ish_turlar isht ON isht.id = o.ishtur_id
    WHERE t.type = 'Q'
      AND s.semestr IN ($pairStart, $pairEnd)
      $whereTeacher
      $whereKafedra
    GROUP BY t.teacher_id, s.semestr, qf.id, qf.qoshimcha_dars_id
";

$result = $db->query($sql);
$teachers = [];

function initRow() {
    return [
        'fan_name' => '',
        'fan_code' => '',
        'talim_yonalishi' => '',
        'yonalish_code' => '',
        'guruh_raqami' => '',
        'oquv_shakli' => '',
        'kurs' => '',
        'semestr' => 0,
        'talabalar_soni' => 0,
        'individ_talaba' => 0,
        'potok_soni' => 0,
        'kichik_guruh' => 0,
        // semestr1
        's1_maruza' => 0, 's1_amaliy' => 0, 's1_lab' => 0, 's1_seminar' => 0, 's1_konsult' => 0, 's1_oraliq' => 0, 's1_yakuniy' => 0,
        // semestr2
        's2_maruza' => 0, 's2_amaliy' => 0, 's2_lab' => 0, 's2_seminar' => 0, 's2_konsult' => 0, 's2_oraliq' => 0, 's2_yakuniy' => 0,
        // qo'shimcha
        'kurs_ishi' => 0,
        'kurs_loyiha' => 0,
        'oquv_ped' => 0,
        'uzluksiz' => 0,
        'dala_otm' => 0,
        'dala_tash' => 0,
        'ishlab_chiq' => 0,
        'bmi' => 0,
        'ilmiy_tadqiqot' => 0,
        'ilmiy_ped' => 0,
        'ilmiy_staj' => 0,
        'tayanch_dok' => 0,
        'katta_ilmiy' => 0,
        'stajyor' => 0,
        'ochiq_dars' => 0,
        'yadak' => 0,
        'boshqa' => 0,
        'jami' => 0
    ];
}

function addToRow(&$row, $field, $value) {
    $row[$field] = ($row[$field] ?? 0) + (float)$value;
}

if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $tid = (int)$r['teacher_id'];
        if (!isset($teachers[$tid])) {
            $teachers[$tid] = [
                'fio' => $r['fio'] ?? '-',
                'lavozim' => $r['lavozim'] ?? '-',
                'stavka' => $r['stavka'] ?? '1',
                'ilmiy_unvon' => $r['ilmiy_unvon'] ?? '-',
                'ilmiy_daraja' => $r['ilmiy_daraja'] ?? '-',
                'shtat_turi' => $r['shtat_turi'] ?? '-',
                'rows' => []
            ];
        }

        $semestr = (int)$r['semestr'];
        $type = $r['type'];
        $fanName = trim($r['fan_name'] ?? '');
        $fanCode = trim($r['fan_code'] ?? '');
        $yonalishCode = trim($r['yonalish_code'] ?? '');
        $guruh = trim($r['guruh_raqami'] ?? '');

        if ($type === 'A') {
            $key = "A|{$semestr}|{$r['fan_id']}|{$yonalishCode}|{$guruh}";
        } else {
            $qId = (int)$r['qoshimcha_id'];
            $key = "Q|{$semestr}|{$qId}|{$fanName}|{$yonalishCode}|{$guruh}";
        }

        if (!isset($teachers[$tid]['rows'][$key])) {
            $row = initRow();
            $row['fan_name'] = $fanName;
            $row['fan_code'] = $fanCode;
            $row['talim_yonalishi'] = $r['talim_yonalishi'] ?? '-';
            $row['yonalish_code'] = $yonalishCode;
            $row['guruh_raqami'] = $guruh;
            $row['oquv_shakli'] = $r['oquv_shakli'] ?? '-';
            $row['kurs'] = $r['kurs'] ?? '';
            $row['semestr'] = $semestr;
            $row['talabalar_soni'] = $r['talabalar_soni'] ?? 0;
            $row['individ_talaba'] = $r['kattaguruh_soni'] ?? 0;
            $row['potok_soni'] = $r['patok_soni'] ?? 0;
            $row['kichik_guruh'] = $r['kichikguruh_soni'] ?? 0;
            $teachers[$tid]['rows'][$key] = $row;
        }

        $row = $teachers[$tid]['rows'][$key];
        $soat = (float)$r['soat'];

        if ($type === 'A') {
            $darsTur = (int)$r['dars_tur_id'];
            $prefix = ($semestr == $pairStart) ? 's1_' : 's2_';
            if ($darsTur === 1) addToRow($row, $prefix . 'maruza', $soat);
            if ($darsTur === 2) addToRow($row, $prefix . 'amaliy', $soat);
            if ($darsTur === 3) addToRow($row, $prefix . 'lab', $soat);
            if ($darsTur === 4) addToRow($row, $prefix . 'seminar', $soat);
            if ($darsTur === 5) addToRow($row, $prefix . 'konsult', $soat);
        } else {
            $qId = (int)$r['qoshimcha_id'];
            $prefix = ($semestr == $pairStart) ? 's1_' : 's2_';
            if ($qId === 20) addToRow($row, $prefix . 'oraliq', $soat);
            if ($qId === 21) addToRow($row, $prefix . 'yakuniy', $soat);
            if ($qId === 1) addToRow($row, 'kurs_ishi', $soat);
            if ($qId === 2) addToRow($row, 'kurs_loyiha', $soat);
            if ($qId === 3) addToRow($row, 'oquv_ped', $soat);
            if ($qId === 4) addToRow($row, 'uzluksiz', $soat);
            if ($qId === 5) addToRow($row, 'dala_otm', $soat);
            if ($qId === 6) addToRow($row, 'dala_tash', $soat);
            if ($qId === 7) addToRow($row, 'ishlab_chiq', $soat);
            if ($qId === 8) addToRow($row, 'bmi', $soat);
            if ($qId === 9) addToRow($row, 'ilmiy_tadqiqot', $soat);
            if ($qId === 10) addToRow($row, 'ilmiy_ped', $soat);
            if ($qId === 11) addToRow($row, 'ilmiy_staj', $soat);
            if ($qId === 12) addToRow($row, 'tayanch_dok', $soat);
            if ($qId === 13) addToRow($row, 'katta_ilmiy', $soat);
            if ($qId === 14) addToRow($row, 'stajyor', $soat);
            if ($qId === 15) addToRow($row, 'ochiq_dars', $soat);
            if ($qId === 16) addToRow($row, 'yadak', $soat);
            if ($qId === 17) addToRow($row, 'boshqa', $soat);
        }

        $teachers[$tid]['rows'][$key] = $row;
    }
}

function format_soat($val) {
    $num = (float)$val;
    if ($num == 0.0) return '';
    $formatted = number_format($num, 1, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

function row_total($row) {
    $sum = 0;
    foreach ($row as $k => $v) {
        if (strpos($k, 's1_') === 0 || strpos($k, 's2_') === 0) $sum += (float)$v;
    }
    $sum += (float)$row['kurs_ishi'] + (float)$row['kurs_loyiha'] + (float)$row['oquv_ped'] + (float)$row['uzluksiz'];
    $sum += (float)$row['dala_otm'] + (float)$row['dala_tash'] + (float)$row['ishlab_chiq'] + (float)$row['bmi'];
    $sum += (float)$row['ilmiy_tadqiqot'] + (float)$row['ilmiy_ped'] + (float)$row['ilmiy_staj'];
    $sum += (float)$row['tayanch_dok'] + (float)$row['katta_ilmiy'] + (float)$row['stajyor'];
    $sum += (float)$row['ochiq_dars'] + (float)$row['yadak'] + (float)$row['boshqa'];
    return $sum;
}

// Izoh: Jadval sarlavhasi (rasmdagi ko'rinish) uchun umumiy ustunlar soni.
$colspan = 8 + 6 + 6 + 18;
?>

<?php if (empty($teachers)): ?>
    <div class="table-container-wrapper">
        <div class="table-header">
            <div class="table-title">
                <h3>O'qituvchilar soat taqsimoti (<?= $pairStart ?>-<?= $pairEnd ?> semestr)</h3>
                <span class="badge">0 ta</span>
            </div>
        </div>
        <div class="table-wrapper">
            <table id="yuklamaTable">
                <tbody>
                    <tr>
                        <td colspan="43" style="text-align:center;padding:16px;">Ma'lumotlar mavjud emas</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($teachers as $tIndex => $teacher): ?>
        <?php
            $sumKeys = [
                'talabalar_soni', 'individ_talaba', 'potok_soni', 'kichik_guruh',
                's1_maruza', 's1_amaliy', 's1_lab', 's1_seminar', 's1_konsult', 's1_oraliq', 's1_yakuniy',
                's2_maruza', 's2_amaliy', 's2_lab', 's2_seminar', 's2_konsult', 's2_oraliq', 's2_yakuniy',
                'kurs_ishi', 'kurs_loyiha', 'oquv_ped', 'uzluksiz', 'dala_otm', 'dala_tash', 'ishlab_chiq',
                'bmi', 'ilmiy_tadqiqot', 'ilmiy_ped', 'ilmiy_staj', 'tayanch_dok', 'katta_ilmiy', 'stajyor',
                'ochiq_dars', 'yadak', 'boshqa'
            ];
            $totals = array_fill_keys($sumKeys, 0);
            $s1Boshqa = 0;
            $s2Boshqa = 0;
            foreach ($teacher['rows'] as $r) {
                foreach ($sumKeys as $k) {
                    $totals[$k] += (float)($r[$k] ?? 0);
                }
                $rowSem = (int)($r['semestr'] ?? 0);
                $rowAud = 0;
                if ($rowSem === $pairStart) {
                    $rowAud = (float)$r['s1_maruza'] + (float)$r['s1_amaliy'] + (float)$r['s1_lab'] + (float)$r['s1_seminar'] + (float)$r['s1_konsult'];
                    $s1Boshqa += (row_total($r) - $rowAud);
                } elseif ($rowSem === $pairEnd) {
                    $rowAud = (float)$r['s2_maruza'] + (float)$r['s2_amaliy'] + (float)$r['s2_lab'] + (float)$r['s2_seminar'] + (float)$r['s2_konsult'];
                    $s2Boshqa += (row_total($r) - $rowAud);
                }
            }
            $s1Aud = $totals['s1_maruza'] + $totals['s1_amaliy'] + $totals['s1_lab'] + $totals['s1_seminar'] + $totals['s1_konsult'];
            $s2Aud = $totals['s2_maruza'] + $totals['s2_amaliy'] + $totals['s2_lab'] + $totals['s2_seminar'] + $totals['s2_konsult'];
            $jamiAud = $s1Aud + $s2Aud;
            $jamiBoshqa = $s1Boshqa + $s2Boshqa;
            $totalYuklama = $jamiAud + $jamiBoshqa;
            $tableId = $tIndex === 0 ? 'yuklamaTable' : 'yuklamaTable_' . $tIndex;
            $rowNum = 1;
        ?>
        <div class="table-container-wrapper">
            <div class="table-header">
                <div class="table-title">
                    <h3>O'qituvchilar soat taqsimoti (<?= $pairStart ?>-<?= $pairEnd ?> semestr)</h3>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="<?= $tableId ?>" class="oqituvchi-taqsimot-table">
                    <colgroup>
                        <col style="width: 36px;">
                        <col style="width: 240px;">
                        <col style="width: 220px;">
                        <col style="width: 150px;">
                        <col style="width: 70px;">
                        <col style="width: 45px;">
                        <col style="width: 50px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 55px;">
                        <col style="width: 55px;">
                        <col span="32" style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr class="summary-head">
                            <th colspan="12">Professor-o'qituvchi lavozimi</th>
                            <th colspan="11">F.I.Sh.</th>
                            <th colspan="2">Shtat birligi</th>
                            <th colspan="2">Shtat turi</th>
                            <th colspan="2">Ilmiy darajasi</th>
                            <th colspan="2">Ilmiy unvoni</th>
                            <th colspan="2"><?= $pairStart ?>-sem aud.</th>
                            <th colspan="1"><?= format_soat($s1Aud) ?></th>
                            <th colspan="2"><?= $pairEnd ?>-sem aud.</th>
                            <th colspan="1"><?= format_soat($s2Aud) ?></th>
                            <th colspan="2">Jami aud.</th>
                            <th colspan="1"><?= format_soat($jamiAud) ?></th>
                            <th colspan="2">Jami yuklama</th>
                            <th colspan="1"><?= format_soat($totalYuklama) ?></th>
                        </tr>
                        <tr class="summary-head">
                            <th colspan="12"><?= htmlspecialchars($teacher['lavozim']) ?></th>
                            <th colspan="11"><?= htmlspecialchars($teacher['fio']) ?></th>
                            <th colspan="2"><?= htmlspecialchars($teacher['stavka']) ?></th>
                            <th colspan="2"><?= htmlspecialchars($teacher['shtat_turi']) ?></th>
                            <th colspan="2"><?= htmlspecialchars($teacher['ilmiy_daraja']) ?></th>
                            <th colspan="2"><?= htmlspecialchars($teacher['ilmiy_unvon']) ?></th>
                            <th colspan="2">boshqa</th>
                            <th colspan="1"><?= format_soat($s1Boshqa) ?></th>
                            <th colspan="2">boshqa</th>
                            <th colspan="1"><?= format_soat($s2Boshqa) ?></th>
                            <th colspan="2">Jami boshqa.</th>
                            <th colspan="1"><?= format_soat($jamiBoshqa) ?></th>
                            <th colspan="2">&nbsp;</th>
                            <th colspan="1">&nbsp;</th>
                        </tr>
                        <tr>
                            <th rowspan="3">T/r</th>
                            <th rowspan="3">O'qitiladigan fan va boshqa turdagi o'quv ishlari mazmuni</th>
                            <th rowspan="3">Ta'lim yo'nalishi</th>
                            <th rowspan="3" class="vertical">Guruh raqami</th>
                            <th rowspan="3" class="vertical">O'quv shakli (k., kech., s.)</th>
                            <th rowspan="3" class="vertical">Kurs</th>
                            <th rowspan="3" class="vertical">Semestr</th>
                            <th rowspan="3" class="vertical">Talabalar soni</th>
                            <th rowspan="3" class="vertical">Individ. biriktirilgan talabalar soni</th>
                            <th rowspan="3" class="vertical">Potoklar soni</th>
                            <th rowspan="3" class="vertical">Kichik guruh soni</th>

                            <th colspan="7"><?= $pairStart ?>-semestr</th>
                            <th colspan="7"><?= $pairEnd ?>-semestr</th>

                            <th rowspan="3" class="vertical">Kurs ishi va himoyasi</th>
                            <th rowspan="3" class="vertical">Kurs loyihasi va himoyasi</th>

                            <th colspan="5">Malakaviy amaliyot</th>

                            <th rowspan="3" class="vertical">BMI rahbarligi</th>

                            <th colspan="3">Magistratura</th>
                            <th colspan="3">Doktorantura</th>

                            <th rowspan="3" class="vertical">YaDAK</th>
                            <th rowspan="3" class="vertical">Ochiq dars</th>
                            <th rowspan="3" class="vertical">Boshqa soatlar</th>
                            <th rowspan="3" class="vertical">JAMI</th>
                        </tr>
                        <tr>
                            <th colspan="5">Auditoriya soatlari</th>
                            <th rowspan="2" class="vertical">ON</th>
                            <th rowspan="2" class="vertical">YN</th>

                            <th colspan="5">Auditoriya soatlari</th>
                            <th rowspan="2" class="vertical">ON</th>
                            <th rowspan="2" class="vertical">YN</th>

                            <th rowspan="2" class="vertical">O'quv-pedagogik amaliyot</th>
                            <th rowspan="2" class="vertical">Uzluksiz malakaviy amaliyot</th>
                            <th rowspan="2" class="vertical">Dala amaliyoti (OTM hududida)</th>
                            <th rowspan="2" class="vertical">Dala amaliyoti (OTM hududidan tashqarida)</th>
                            <th rowspan="2" class="vertical">Ishlab chiqarish amaliyoti</th>

                            <th rowspan="2" class="vertical">Ilmiy-tadqiqot ishi</th>
                            <th rowspan="2" class="vertical">Ilmiy-pedagogik ish</th>
                            <th rowspan="2" class="vertical">Ilmiy stajirovka</th>

                            <th rowspan="2" class="vertical">Tayanch doktorantura</th>
                            <th rowspan="2" class="vertical">Katta ilmiy tadqiqotchi</th>
                            <th rowspan="2" class="vertical">Stajyor-tadqiqotchi</th>
                        </tr>
                        <tr>
                            <th class="vertical">Ma'ruza</th>
                            <th class="vertical">Amaliy mashg'ulot</th>
                            <th class="vertical">Laboratoriya</th>
                            <th class="vertical">Seminar</th>
                            <th class="vertical">Konsultatsiya</th>

                            <th class="vertical">Ma'ruza</th>
                            <th class="vertical">Amaliy mashg'ulot</th>
                            <th class="vertical">Laboratoriya</th>
                            <th class="vertical">Seminar</th>
                            <th class="vertical">Konsultatsiya</th>
                        </tr>
                        <tr>
                            <?php for ($n = 1; $n <= 43; $n++): ?>
                                <th style="font-weight: normal; font-size: 11px;"><?= $n ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teacher['rows'] as $row): ?>
                            <?php $rowJami = row_total($row); ?>
                            <tr>
                                <td><?= $rowNum++ ?></td>
                                <td class="left fan-nomi"><?= htmlspecialchars($row['fan_name']) ?></td>
                                <td class="left"><?= htmlspecialchars($row['yonalish_code'] . ' - ' . $row['talim_yonalishi']) ?></td>
                                <td class="wrap"><?= htmlspecialchars($row['guruh_raqami']) ?></td>
                                <td><?= htmlspecialchars($row['oquv_shakli']) ?></td>
                                <td><?= htmlspecialchars($row['kurs']) ?></td>
                                <td><?= htmlspecialchars($row['semestr']) ?></td>
                                <td><?= htmlspecialchars($row['talabalar_soni']) ?></td>
                                <td><?= htmlspecialchars($row['individ_talaba']) ?></td>
                                <td><?= htmlspecialchars($row['potok_soni']) ?></td>
                                <td><?= htmlspecialchars($row['kichik_guruh']) ?></td>
                                <td><?= format_soat($row['s1_maruza']) ?></td>
                                <td><?= format_soat($row['s1_amaliy']) ?></td>
                                <td><?= format_soat($row['s1_lab']) ?></td>
                                <td><?= format_soat($row['s1_seminar']) ?></td>
                                <td><?= format_soat($row['s1_konsult']) ?></td>
                                <td><?= format_soat($row['s1_oraliq']) ?></td>
                                <td><?= format_soat($row['s1_yakuniy']) ?></td>
                                <td><?= format_soat($row['s2_maruza']) ?></td>
                                <td><?= format_soat($row['s2_amaliy']) ?></td>
                                <td><?= format_soat($row['s2_lab']) ?></td>
                                <td><?= format_soat($row['s2_seminar']) ?></td>
                                <td><?= format_soat($row['s2_konsult']) ?></td>
                                <td><?= format_soat($row['s2_oraliq']) ?></td>
                                <td><?= format_soat($row['s2_yakuniy']) ?></td>
                                <td><?= format_soat($row['kurs_ishi']) ?></td>
                                <td><?= format_soat($row['kurs_loyiha']) ?></td>
                                <td><?= format_soat($row['oquv_ped']) ?></td>
                                <td><?= format_soat($row['uzluksiz']) ?></td>
                                <td><?= format_soat($row['dala_otm']) ?></td>
                                <td><?= format_soat($row['dala_tash']) ?></td>
                                <td><?= format_soat($row['ishlab_chiq']) ?></td>
                                <td><?= format_soat($row['bmi']) ?></td>
                                <td><?= format_soat($row['ilmiy_tadqiqot']) ?></td>
                                <td><?= format_soat($row['ilmiy_ped']) ?></td>
                                <td><?= format_soat($row['ilmiy_staj']) ?></td>
                                <td><?= format_soat($row['tayanch_dok']) ?></td>
                                <td><?= format_soat($row['katta_ilmiy']) ?></td>
                                <td><?= format_soat($row['stajyor']) ?></td>
                                <td><?= format_soat($row['yadak']) ?></td>
                                <td><?= format_soat($row['ochiq_dars']) ?></td>
                                <td><?= format_soat($row['boshqa']) ?></td>
                                <td><strong><?= format_soat($rowJami) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td></td>
                            <td class="left"><strong>Jami</strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?= format_soat($totals['talabalar_soni']) ?></td>
                            <td><?= format_soat($totals['individ_talaba']) ?></td>
                            <td><?= format_soat($totals['potok_soni']) ?></td>
                            <td><?= format_soat($totals['kichik_guruh']) ?></td>
                            <td><?= format_soat($totals['s1_maruza']) ?></td>
                            <td><?= format_soat($totals['s1_amaliy']) ?></td>
                            <td><?= format_soat($totals['s1_lab']) ?></td>
                            <td><?= format_soat($totals['s1_seminar']) ?></td>
                            <td><?= format_soat($totals['s1_konsult']) ?></td>
                            <td><?= format_soat($totals['s1_oraliq']) ?></td>
                            <td><?= format_soat($totals['s1_yakuniy']) ?></td>
                            <td><?= format_soat($totals['s2_maruza']) ?></td>
                            <td><?= format_soat($totals['s2_amaliy']) ?></td>
                            <td><?= format_soat($totals['s2_lab']) ?></td>
                            <td><?= format_soat($totals['s2_seminar']) ?></td>
                            <td><?= format_soat($totals['s2_konsult']) ?></td>
                            <td><?= format_soat($totals['s2_oraliq']) ?></td>
                            <td><?= format_soat($totals['s2_yakuniy']) ?></td>
                            <td><?= format_soat($totals['kurs_ishi']) ?></td>
                            <td><?= format_soat($totals['kurs_loyiha']) ?></td>
                            <td><?= format_soat($totals['oquv_ped']) ?></td>
                            <td><?= format_soat($totals['uzluksiz']) ?></td>
                            <td><?= format_soat($totals['dala_otm']) ?></td>
                            <td><?= format_soat($totals['dala_tash']) ?></td>
                            <td><?= format_soat($totals['ishlab_chiq']) ?></td>
                            <td><?= format_soat($totals['bmi']) ?></td>
                            <td><?= format_soat($totals['ilmiy_tadqiqot']) ?></td>
                            <td><?= format_soat($totals['ilmiy_ped']) ?></td>
                            <td><?= format_soat($totals['ilmiy_staj']) ?></td>
                            <td><?= format_soat($totals['tayanch_dok']) ?></td>
                            <td><?= format_soat($totals['katta_ilmiy']) ?></td>
                            <td><?= format_soat($totals['stajyor']) ?></td>
                            <td><?= format_soat($totals['yadak']) ?></td>
                            <td><?= format_soat($totals['ochiq_dars']) ?></td>
                            <td><?= format_soat($totals['boshqa']) ?></td>
                            <td><strong><?= format_soat($totalYuklama) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

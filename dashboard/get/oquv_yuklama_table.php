<?php
include_once '../config.php';
$db = new Database();

$filters = [];
if (isset($_POST['kafedra_id']) && !empty($_POST['kafedra_id'])) {
    $filters['kafedra_id'] = (int)$_POST['kafedra_id'];
}
if (isset($_POST['semestr']) && !empty($_POST['semestr'])) {
    $filters['semestr'] = (int)$_POST['semestr'];
}

$oquv_yuklamalar = $db->get_oquv_yuklamalar($filters);
$qoshimcha_yuklamalar = $db->get_qoshimcha_oquv_yuklamalar($filters);
?>

<div class="table-container-wrapper">
    <div class="zoom-controls">
        <button class="zoom-btn" onclick="zoomOut()" title="Kichiklashtirish">-</button>
        <button class="zoom-btn" onclick="resetZoom()" title="Asl o'lcham">100%</button>
        <button class="zoom-btn" onclick="zoomIn()" title="Kattalashtirish">+</button>
        <div class="zoom-level" id="zoomLevel">100%</div>
    </div>
    
    <div class="table-title">
        O'ZBEKISTON RESPUBLIKASI OLIY TA'LIM MUASSASASI<br>
        O'QITUVCHILARNING O'QUV YUKLAMASI
    </div>
    
    <div class="table-wrapper">
        <table id="yuklamaTable">
            <thead>
                <tr>
                    <th rowspan="3">№</th>
                    <th rowspan="3">O'qitiladigan fan va boshqa turdagi o'quv ishlari</th>
                    <th rowspan="3">Ta'lim yo'nalishi</th>
                    <th rowspan="3" class="vertical">Guruh raqami</th>
                    <th rowspan="3" class="vertical">O'quv shakli</th>
                    <th rowspan="3" class="vertical">Kurs</th>
                    <th rowspan="3" class="vertical">Semestr</th>
                    <th rowspan="3" class="vertical">Talabalar soni</th>
                    <th rowspan="3" class="vertical">Potoklar soni</th>
                    <th rowspan="3" class="vertical">Guruhlar soni</th>
                    <th rowspan="3" class="vertical">Kichik guruhlar soni</th>

                    <th colspan="8">O'quv soatlari</th>
                    <th colspan="2">Reyting nazorati</th>

                    <th rowspan="3" class="vertical">Kurs ishi va himoyasi</th>
                    <th rowspan="3" class="vertical">Kurs loyihasi va himoyasi</th>

                    <th colspan="5">Malakaviy amaliyot</th>

                    <th rowspan="3" class="vertical">BMI rahbarligi</th>

                    <th colspan="3">Magistratura</th>
                    <th colspan="3">Doktorantura</th>

                    <th rowspan="3" class="vertical">Ochiq dars</th>
                    <th rowspan="3" class="vertical">Yakuniy davlat attestatsiyasi</th>
                    <th rowspan="3" class="vertical">Boshqa soatlar</th>
                    <th rowspan="3" class="vertical">JAMI</th>
                    <th rowspan="3" class="vertical">Kafedra</th>
                </tr>

                <tr>
                    <th colspan="4">O'quv reja bo'yicha</th>
                    <th colspan="4">Amalda bajarilgan</th>

                    <th rowspan="2" class="vertical">Oraliq nazorat</th>
                    <th rowspan="2" class="vertical">Yakuniy nazorat</th>

                    <th rowspan="2" class="vertical">O'quv-pedagogik amaliyot</th>
                    <th rowspan="2" class="vertical">Uzluksiz malakaviy amaliyot</th>
                    <th rowspan="2" class="vertical">Dala amaliyoti</th>
                    <th rowspan="2" class="vertical">Dala amaliyoti (OTM)</th>
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
                    <th class="vertical">Amaliy</th>
                    <th class="vertical">Laboratoriya</th>
                    <th class="vertical">Seminar</th>

                    <th class="vertical">Ma'ruza</th>
                    <th class="vertical">Amaliy</th>
                    <th class="vertical">Laboratoriya</th>
                    <th class="vertical">Seminar</th>
                </tr>
            </thead>
            
            <tbody>
                <?php 
                $counter = 1;
                if (!empty($oquv_yuklamalar)):
                    foreach ($oquv_yuklamalar as $row): 
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td class="left"><?= htmlspecialchars($row['fan_name']) ?></td>
                    <td class="left"><?= htmlspecialchars($row['yonalish_code'] . ' – ' . $row['talim_yonalishi']) ?></td>
                    <td><?= htmlspecialchars($row['guruh_raqami']) ?></td>
                    <td><?= $row['oquv_shakli'] ?></td>
                    <td><?= $row['kurs'] ?></td>
                    <td><?= $row['semestr'] ?></td>
                    <td><?= $row['talabalar_soni'] ?></td>
                    <td><?= $row['patok_soni'] ?></td>
                    <td><?= $row['kattaguruh_soni'] ?></td>
                    <td><?= $row['kichikguruh_soni'] ?></td>
                    <!-- O'quv reja bo'yicha -->
                    <td><?= $row['maruza_soat'] ?></td>
                    <td><?= $row['amaliy_soat'] ?></td>
                    <td><?= $row['laboratoriya_soat'] ?></td>
                    <td><?= $row['seminar_soat'] ?></td>
                    <!-- Amalda bajarilgan -->
                    <td><?= $row['amalda_maruz'] ?></td>
                    <td><?= $row['amalda_amaliy'] ?></td>
                    <td><?= $row['amalda_lab'] ?></td>
                    <td><?= $row['amalda_seminar'] ?></td>
                    <!-- Reyting nazorati -->
                    <td></td>
                    <td></td>
                    <!-- Kurs ishlari -->
                    <td></td>
                    <td></td>
                    <!-- Malakaviy amaliyot -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- BMI rahbarligi -->
                    <td></td>
                    <!-- Magistratura -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- Doktorantura -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- Qo'shimcha soatlar -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- JAMI soat -->
                    <td><?= $row['jami_soat'] ?></td>
                    <td class="left"><?= htmlspecialchars($row['kafedra_nomi'] ?? '') ?></td>
                </tr>
                <?php 
                    endforeach;
                    foreach ($qoshimcha_yuklamalar as $row):
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td class="left"><?= htmlspecialchars($row['fan_nomi']) ?></td>
                    <td class="left"><?= htmlspecialchars($row['yonalish_code'] . ' – ' . $row['talim_yonalishi']) ?></td>
                    <td><?= htmlspecialchars($row['guruh_raqami']) ?></td>
                    <td><?= $row['oquv_shakli'] ?></td>
                    <td><?= $row['kurs'] ?></td>
                    <td><?= $row['semestr'] ?></td>
                    <td><?= $row['talabalar_soni'] ?></td>
                    <td><?= $row['patok_soni'] ?></td>
                    <td><?= $row['kattaguruh_soni'] ?></td>
                    <td><?= $row['kichikguruh_soni'] ?></td>
                    <!-- O'quv reja bo'yicha -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- Amalda bajarilgan -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <!-- Reyting nazorati -->
                    <td><?= $row['oraliq_nazorat'] ?></td>
                    <td><?= $row['yakuniy_nazorat'] ?></td>
                    <!-- Kurs ishlari -->
                    <td><?= ($row['kurs_ishi'] ?? 0) > 0 ? 'K' : '' ?></td>
                    <td><?= ($row['kurs_loyiha'] ?? 0) > 0 ? 'K' : '' ?></td>
                    <!-- Malakaviy amaliyot -->
                    <td><?= $row['oquv_ped_amaliyot'] ?></td>
                    <td><?= $row['uzluksiz_malakaviy'] ?></td>
                    <td><?= $row['dala_amaliyoti_otm'] ?></td>
                    <td><?= $row['dala_amaliyoti_tashqarida'] ?></td>
                    <td><?= $row['ishlab_chiqarish'] ?></td>
                    <!-- BMI rahbarligi -->
                    <td><?= $row['bmi_rahbarligi'] ?></td>
                    <!-- Magistratura -->
                    <td><?= $row['ilmiy_tadqiqot_ishi'] ?></td>
                    <td><?= $row['ilmiy_pedagogik_ishi'] ?></td>
                    <td><?= $row['ilmiy_stajirovka'] ?></td>
                    <!-- Doktorantura -->
                    <td><?= $row['tayanch_doktorantura']  ?></td>
                    <td><?= $row['katta_ilmiy_tadqiqotchi']  ?></td>
                    <td><?= $row['stajyor_tadqiqotchi']  ?></td>
                    
                    <td><?= $row['ochiq_dars'] ?></td>
                    <td><?= $row['yadak'] ?></td>
                    <td><?= $row['boshqa_soatlar'] ?></td>
                    
                    <!-- JAMI soat -->
                    <td><?= $row['jami_soat'] ?></td>
                    <td class="left"><?= htmlspecialchars($row['kafedra_nomi'] ?? '') ?></td>
                </tr>
                <?php 
                    endforeach;
                else: ?>
                <tr>
                    <td colspan="38" style="text-align: center; padding: 20px;">
                        <i class="fas fa-info-circle"></i> Ma'lumotlar mavjud emas
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

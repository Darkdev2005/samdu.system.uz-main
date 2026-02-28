<?php
    // Izoh: Chet tili fanlarini yo'nalish + semestr bo'yicha biriktirish sahifasi.
    include_once 'config.php';
    $db = new Database();

    $fanOptionsBySemestr = [];
    // Izoh: Chet tili fanlari (tanlov_fan = 3) semestr_id bo'yicha ajratib olinadi.
    // Izoh: Biriktirishda faqat kafedra biriktirilgan fanlar ko'rsatiladi.
    $fanResult = $db->query("
        SELECT f.id, f.fan_name, f.fan_code, f.semestr_id, f.kafedra_id,
               k.name AS kafedra_name,
               s.semestr AS semestr_num,
               y.name AS yonalish_name,
               y.kirish_yili AS yonalish_yili
        FROM fanlar f
        LEFT JOIN kafedralar k ON k.id = f.kafedra_id
        LEFT JOIN semestrlar s ON s.id = f.semestr_id
        LEFT JOIN yonalishlar y ON y.id = s.yonalish_id
        WHERE f.tanlov_fan = 3 AND f.kafedra_id > 0
        ORDER BY f.fan_name, y.name, y.kirish_yili, f.id DESC
    ");
    if ($fanResult) {
        $seenFanIds = [];
        while ($row = mysqli_fetch_assoc($fanResult)) {
            $semestrId = (int) ($row['semestr_id'] ?? 0);
            if ($semestrId <= 0) {
                continue;
            }
            $fanId = (int) ($row['id'] ?? 0);
            if ($fanId <= 0 || isset($seenFanIds[$fanId])) {
                continue;
            }
            $seenFanIds[$fanId] = true;

            $label = trim($row['fan_name']);
            if (!empty($row['kafedra_name'])) {
                $label .= ' (' . $row['kafedra_name'] . ')';
            } else {
                $label .= ' (Kafedra belgilanmagan)';
            }

            $yonalishLabel = trim($row['yonalish_name'] ?? '');
            $yonalishYili = trim($row['yonalish_yili'] ?? '');
            if ($yonalishLabel !== '') {
                $label .= ' — ' . $yonalishLabel;
                if ($yonalishYili !== '') {
                    $label .= ' - ' . $yonalishYili;
                }
            }

            if (!isset($fanOptionsBySemestr[$semestrId])) {
                $fanOptionsBySemestr[$semestrId] = '';
            }
            $fanOptionsBySemestr[$semestrId] .= '<option value="' . $fanId . '">' . htmlspecialchars($label) . '</option>';
        }
    }

    $semestrlar = $db->get_semestrlar();
    $dars_soat_turlari = $db->get_data_by_table_all('dars_soat_turlar');
    $kafedralar = $db->get_data_by_table_all('kafedralar');
    // Izoh: Chet tili fan select uchun faqat o'quv rejada yaratilgan fanlar olinadi (semestr bo'yicha).
    $chet_tili_fanlar = $db->get_data_by_table_all('fanlar', 'WHERE tanlov_fan = 3 AND (kafedra_id = 0 OR kafedra_id IS NULL OR kafedra_id = "")');
    $chetTiliOptionsBySemestr = [];
    $chetSeen = [];
    foreach ($chet_tili_fanlar as $fan) {
        $semestrId = (int) ($fan['semestr_id'] ?? 0);
        $code = trim($fan['fan_code'] ?? '');
        $name = trim($fan['fan_name'] ?? '');
        if ($semestrId <= 0 || $code === '' || $name === '') {
            continue;
        }
        $key = $semestrId . '|' . $code . '|' . $name;
        if (isset($chetSeen[$key])) {
            continue;
        }
        $chetSeen[$key] = true;
        $safeCode = htmlspecialchars($code);
        $safeName = htmlspecialchars($name);
        if (!isset($chetTiliOptionsBySemestr[$semestrId])) {
            $chetTiliOptionsBySemestr[$semestrId] = '';
        }
        $chetTiliOptionsBySemestr[$semestrId] .= "<option value=\"{$safeCode}\" data-name=\"{$safeName}\">{$safeCode} - {$safeName}</option>";
    }
    $semestrOptions = '';
    foreach ($semestrlar as $s) {
        $yonalishName = trim($s['yonalish_name'] ?? '');
        $kirishYili = trim($s['kirish_yili'] ?? '');
        $semestrNum = trim($s['semestr'] ?? '');

        $labelParts = [];
        if ($yonalishName !== '') {
            $labelParts[] = $yonalishName;
        }
        if ($kirishYili !== '') {
            $labelParts[] = $kirishYili;
        }
        $label = implode(' - ', $labelParts);
        if ($semestrNum !== '') {
            $label = ($label !== '' ? $label . ' - ' : '') . $semestrNum . '-semestr';
        }
        if ($label === '') {
            $label = 'Semestr: ' . (int)$s['id'];
        }
        $semestrOptions .= '<option value="' . (int)$s['id'] . '">' . htmlspecialchars($label) . '</option>';
    }
    if ($semestrOptions === '') {
        $semestrOptions = '<option value="" disabled>Semestr topilmadi</option>';
    }

    // Izoh: Yo'nalishlar ro'yxatini map qilib olamiz.
    $yonalishlarMap = [];
    $yonalishlar = $db->get_data_by_table_all('yonalishlar');
    foreach ($yonalishlar as $y) {
        $label = trim($y['name'] ?? '');
        $yil = trim($y['kirish_yili'] ?? '');
        if ($yil !== '') {
            $label .= ' - ' . $yil;
        }
        $yonalishlarMap[(int)$y['id']] = $label;
    }

    // Izoh: Biriktirilgan chet tili fanlari ro'yxati (UI uchun birlashtiriladi: fan_id + semestr raqami).
    $guruhRows = [];
    $guruhResult = $db->query("
        SELECT
            ct.fan_id,
            f.fan_code,
            f.fan_name,
            km.name AS kafedra_name,
            s.semestr AS semestr_num,
            GROUP_CONCAT(DISTINCT ct.yonalish_id ORDER BY ct.yonalish_id SEPARATOR ',') AS yonalish_ids,
            GROUP_CONCAT(DISTINCT ct.semestr_id ORDER BY ct.semestr_id SEPARATOR ',') AS semestr_ids,
            GROUP_CONCAT(DISTINCT ct.source_fan_ids ORDER BY ct.source_fan_ids SEPARATOR ',') AS source_fan_ids,
            MIN(ct.create_at) AS create_at
        FROM chet_tili_guruhlar ct
        JOIN fanlar f ON f.id = ct.fan_id
        LEFT JOIN kafedralar km ON km.id = f.kafedra_id
        JOIN semestrlar s ON s.id = ct.semestr_id
        GROUP BY ct.fan_id, s.semestr
        ORDER BY create_at DESC
    ");
    if ($guruhResult) {
        while ($row = mysqli_fetch_assoc($guruhResult)) {
            $yonalishList = [];
            $idsRaw = trim($row['yonalish_ids'] ?? '');
            if ($idsRaw !== '') {
                $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
                foreach ($ids as $id) {
                    if (isset($yonalishlarMap[$id])) {
                        $yonalishList[] = $yonalishlarMap[$id];
                    }
                }
            }
            $row['yonalishlar'] = $yonalishList;
            $guruhRows[] = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Chet tili fanlari</title>
    <link rel="stylesheet" href="../assets/css/dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .tab-header {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }
        .tab-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .tab-btn.active {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-navbar">
                <h1>Chet tili fanlari</h1>
            </header>
            <div class="content-container">
                <!-- Izoh: Chet tili fanlari uchun 2 ta tab -->
                <div class="tab-header">
                    <button type="button" class="tab-btn active" data-tab="chet-tab-yaratish">Chet tili fanini yaratish</button>
                    <button type="button" class="tab-btn" data-tab="chet-tab-biriktirish">Chet tilini biriktirish</button>
                </div>

                <div id="chet-tab-yaratish" class="tab-content active">
                    <form id="chetTiliYaratishForm" class="card">
                        <h3 class="section-title">Umumiy ma'lumot</h3>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Semestr</label>
                                <select class="form-control" name="semestr_id" id="chetSemestrSelect" required>
                                    <option value="">Tanlang</option>
                                        <?php foreach ($semestrlar as $s): 
                                            $short = '';
                                            $words = preg_split('/\s+/u', trim($s['yonalish_name']));
                                            foreach ($words as $w) {
                                                $short .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
                                            }
                                        ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= $short . '_' . $s['kirish_yili'] . ' - ' . $s['semestr'] . '-semestr'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="chetRejaWrapper">
                            <div class="reja-card" data-index="0">
                                <div class="tanlovfan-actions">
                                    <input type="hidden" name="tanlov_fan[0]" value="3" class="tanlov-input">
                                    <input type="hidden" name="tanlov_fan_code[0]" class="chet-code-input">
                                    <input type="hidden" name="tanlov_fan_base_nomi[0]" class="chet-base-input">
                                    <button type="button" class="btn btn-outline btn-sm fanTypeToggle active" disabled>
                                        <i class="fas fa-check-circle"></i> Chet tili
                                    </button>
                                </div>

                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label>Chet tili (kod + nomi)</label>
                                        <select class="form-control chet-tili-select" name="tanlov_fan_base[0]" required>
                                            <option value="">Tanlang</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="tanlov-fan-item" data-tanlov-index="0">
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label>Chet tili nomi</label>
                                            <input type="text" class="form-control" name="tanlov_fan_nomi[0][]" placeholder="Masalan: English 1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Kafedra</label>
                                            <select class="form-control" name="tanlov_kafedra_id[0][]" required>
                                                <option value="">Tanlang</option>
                                                <?php foreach ($kafedralar as $k): ?>
                                                    <option value="<?= $k['id'] ?>">
                                                        <?= htmlspecialchars($k['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="tanlov-fan-actions mb-3">
                                        <button type="button" class="btn btn-outline btn-sm addChetTiliFan">
                                            <i class="fas fa-plus"></i> Yana variant
                                        </button>
                                        
                                        <button type="button" class="btn btn-danger btn-sm removeChetTiliFan">
                                            <i class="fas fa-times"></i> O'chirish
                                        </button>
                                    </div>
                                </div>

                                <div class="darsSoatWrapper">
                                    <div class="form-grid-2 dars-soat-row">
                                        <div class="form-group">
                                            <label>Dars turi</label>
                                            <select class="form-control" name="dars_turi[0][]" required>
                                                <option value="">Tanlang</option>
                                                <?php foreach ($dars_soat_turlari as $d): ?>
                                                    <option value="<?= $d['id'] ?>">
                                                        <?= htmlspecialchars($d['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="dars-soat-actions">
                                        <button type="button" class="btn btn-outline btn-sm addChetDarsSoat">
                                            <i class="fas fa-plus"></i>
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm removeChetDarsSoat">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="reja-actions">
                                    <button type="button" class="btn btn-outline btn-sm addChetReja">
                                        <i class="fas fa-plus"></i> Yana fan
                                    </button>

                                    <button type="button" class="btn btn-danger btn-sm removeChetReja">
                                        <i class="fas fa-times"></i> O'chirish
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label>Izoh</label>
                            <textarea class="form-control"
                                    name="izoh"
                                    rows="3"
                                    placeholder="O'quv reja bo'yicha umumiy izoh..."></textarea>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Saqlash
                            </button>
                        </div>
                    </form>
                </div>

                <div id="chet-tab-biriktirish" class="tab-content">
                    <form id="chetTiliForm" class="card">
                        <h3 class="section-title">Umumiy ma'lumot</h3>
                        <div class="form-group">
                            <label>Yo'nalish + semestr va fan</label>
                            <div id="yonalishWrapper">
                                <div class="yonalish-item">
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <select class="form-control yonalish-select" name="semestr_ids[]" required>
                                                <option value="">Tanlang</option>
                                                <?php echo $semestrOptions; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <select class="form-control fan-select" name="fan_ids[]" required>
                                                <option value="">Tanlang</option>
                                            </select>
                                        </div>
                                        <div class="dars-soat-actions">
                                            <button type="button" class="btn btn-outline btn-sm addYonalish">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm removeYonalish">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Biriktirish
                            </button>
                        </div>
                    </form>

                    <div class="table-container mt-4">
                        <div class="table-header">
                        <div class="table-title">
                            <h3>Biriktirilgan chet tili fanlari</h3>
                                <span class="badge"><?php echo count($guruhRows); ?> ta</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Fan kodi</th>
                                        <th>Fan nomi</th>
                                        <th>Yo'nalishlar</th>
                                        <th>Semestr</th>
                                        <th>Yaratilgan sana</th>
                                        <th>Harakatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($guruhRows) === 0): ?>
                                        <tr>
                                            <td colspan="6">Biriktirilgan fanlar topilmadi</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($guruhRows as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['fan_code']); ?></td>
                                                <td><?php echo htmlspecialchars($row['fan_name']); ?></td>
                                            <td>
                                                <?php
                                                    $yonList = $row['yonalishlar'] ?? [];
                                                    $yonText = count($yonList) > 0 ? implode(' | ', $yonList) : '-';
                                                    echo htmlspecialchars($yonText);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['semestr_num']); ?></td>
                                            <td><?php echo htmlspecialchars($row['create_at']); ?></td>
                                            <td>
                                                <button
                                                    class="btn btn-sm btn-danger deleteChetTiliBtn"
                                                    data-fan-id="<?php echo (int)$row['fan_id']; ?>"
                                                    data-semestr-num="<?php echo (int)$row['semestr_num']; ?>"
                                                >
                                                    <i class="fas fa-trash-alt"></i> O'chirish
                                                </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const semestrOptions = `<?php echo $semestrOptions; ?>`;
        let fanOptionsBySemestr = <?php echo json_encode($fanOptionsBySemestr, JSON_UNESCAPED_UNICODE); ?>;

        let chetFanIndex = 0;

        $(document).ready(function() {
            // Izoh: Tablarni boshqarish va holatini saqlash.
            function setActiveTab(tabId) {
                if (!tabId) return;
                $('.tab-btn').removeClass('active');
                $('.tab-btn[data-tab="' + tabId + '"]').addClass('active');
                $('.tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
            }

            const savedTab = localStorage.getItem('chetTiliActiveTab') || 'chet-tab-yaratish';
            setActiveTab(savedTab);

            $('.tab-btn').on('click', function() {
                const target = $(this).data('tab');
                localStorage.setItem('chetTiliActiveTab', target);
                setActiveTab(target);
            });

            // Izoh: Chet tili fanini yaratish selectlari.
            $('#chetSemestrSelect').select2({
                placeholder: "Semestrni tanlang",
                allowClear: true,
                width: '100%',
            });

            initializeChetSelect2($('#chetRejaWrapper .reja-card:first'));
            const semestrId = $('#chetSemestrSelect').val();
            renderChetOptions($('#chetRejaWrapper .reja-card:first').find('.chet-tili-select'), semestrId);

            // Izoh: Yo'nalish selectlariga select2 qo'llash.
            $('.yonalish-select').select2({
                placeholder: "Yo'nalishni tanlang",
                allowClear: true,
                width: '100%',
            });

            // Izoh: Fan selectlariga select2 qo'llash.
            $('.fan-select').select2({
                placeholder: "Chet tili fanini tanlang",
                allowClear: true,
                width: '100%',
            });
        });

        // Izoh: Chet tili semestri tanlanganda fanlar ro'yxatini yangilash.
        $('#chetSemestrSelect').on('change', function() {
            const semestrId = $(this).val();
            $('.chet-tili-select').each(function() {
                renderChetOptions($(this), semestrId);
            });
        });

        // Izoh: Yo'nalish+semestr selectini to'ldirish.
        function fillYonalishOptions(select) {
            select.empty().append(new Option('Tanlang', '', false, false));
            select.append(semestrOptions);
        }

        // Izoh: Semestr bo'yicha chet tili fanlarini chiqarish.
        function renderFanOptionsBySemestr(select, semestrId) {
            select.empty().append(new Option('Tanlang', '', false, false));
            if (!semestrId) {
                select.val(null).trigger('change');
                return;
            }
            if (fanOptionsBySemestr[semestrId]) {
                select.append(fanOptionsBySemestr[semestrId]);
            } else {
                select.append(new Option("Chet tili fan topilmadi", "", false, false));
            }
            select.val(null).trigger('change');
        }

        // Izoh: 2‑tab fan ro'yxatini AJAX bilan yangilash (reloadsiz).
        function refreshChetTiliOptions() {
            return fetch('get/chet_tili_options.php')
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) return;
                    fanOptionsBySemestr = data.fanOptionsBySemestr || {};

                    $('#yonalishWrapper .yonalish-item').each(function() {
                        const semestrId = $(this).find('.yonalish-select').val();
                        const fanSelect = $(this).find('.fan-select');
                        renderFanOptionsBySemestr(fanSelect, semestrId);
                    });
                });
        }

        // Izoh: Yo'nalish selectini + bilan ko'paytirish.
        $(document).on('click', '.addYonalish', function() {
            const wrapper = $('#yonalishWrapper');
            const newItem = $(`
                <div class="yonalish-item mt-2">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <select class="form-control yonalish-select" name="semestr_ids[]" required>
                                <option value="">Tanlang</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select class="form-control fan-select" name="fan_ids[]" required>
                                <option value="">Tanlang</option>
                            </select>
                        </div>
                        <div class="dars-soat-actions">
                            <button type="button" class="btn btn-outline btn-sm addYonalish">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm removeYonalish">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `);

            wrapper.append(newItem);
            const newSemestr = newItem.find('.yonalish-select');
            const newFan = newItem.find('.fan-select');

            fillYonalishOptions(newSemestr);
            newSemestr.select2({
                placeholder: "Yo'nalishni tanlang",
                allowClear: true,
                width: '100%',
            });

            newFan.select2({
                placeholder: "Chet tili fanini tanlang",
                allowClear: true,
                width: '100%',
            });
        });

        // Izoh: Yo'nalish+semestr tanlanganda fan ro'yxatini yangilash.
        $(document).on('change', '.yonalish-select', function() {
            const semestrId = $(this).val();
            const row = $(this).closest('.yonalish-item');
            const fanSelect = row.find('.fan-select');
            if (semestrId) {
                renderFanOptionsBySemestr(fanSelect, semestrId);
            } else {
                renderFanOptionsBySemestr(fanSelect, null);
            }
        });

        // Izoh: Yo'nalish selectini olib tashlash (kamida 1 ta qoladi).
        $(document).on('click', '.removeYonalish', function() {
            const items = $('#yonalishWrapper .yonalish-item');
            if (items.length > 1) {
                const item = $(this).closest('.yonalish-item');
                item.find('select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                item.remove();
            }
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

        // Izoh: Chet tili biriktirish ma'lumotini serverga yuborish.
        $('#chetTiliForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            fetch('insert/add_chet_tili_biriktirish.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message || "Biriktirildi" });
                    this.reset();
                    $('#yonalishWrapper .yonalish-item:gt(0)').remove();
                    $('#yonalishWrapper .yonalish-select').val(null).trigger('change');
                    $('#yonalishWrapper .fan-select').val(null).trigger('change');
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Xatolik yuz berdi' });
                }
            })
            .catch(() => {
                Toast.fire({ icon: 'error', title: "Server bilan bog'lanib bo'lmadi" });
            });
        });

        const chetKafedralarOptions = `<?php foreach ($kafedralar as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['name']) ?></option>
        <?php endforeach; ?>`;

        const chetDarsTurlariOptions = `<?php foreach ($dars_soat_turlari as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>`;

        const chetTiliOptionsBySemestr = <?php echo json_encode($chetTiliOptionsBySemestr, JSON_UNESCAPED_UNICODE); ?>;

        function buildChetCard(index) {
            return `
                <div class="tanlovfan-actions">
                    <input type="hidden" name="tanlov_fan[${index}]" value="3" class="tanlov-input">
                    <input type="hidden" name="tanlov_fan_code[${index}]" class="chet-code-input">
                    <input type="hidden" name="tanlov_fan_base_nomi[${index}]" class="chet-base-input">
                    <button type="button" class="btn btn-outline btn-sm fanTypeToggle active" disabled>
                        <i class="fas fa-check-circle"></i> Chet tili
                    </button>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Chet tili (kod + nomi)</label>
                        <select class="form-control chet-tili-select" name="tanlov_fan_base[${index}]" required>
                            <option value="">Tanlang</option>
                        </select>
                    </div>
                </div>

                <div class="tanlov-fan-item" data-tanlov-index="0">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Chet tili nomi</label>
                            <input type="text" class="form-control" name="tanlov_fan_nomi[${index}][]" placeholder="Masalan: English 1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Kafedra</label>
                            <select class="form-control" name="tanlov_kafedra_id[${index}][]" required>
                                <option value="">Tanlang</option>
                                ${chetKafedralarOptions}
                            </select>
                        </div>
                    </div>
                    
                    <div class="tanlov-fan-actions mb-3">
                        <button type="button" class="btn btn-outline btn-sm addChetTiliFan">
                            <i class="fas fa-plus"></i> Yana variant
                        </button>
                        
                        <button type="button" class="btn btn-danger btn-sm removeChetTiliFan">
                            <i class="fas fa-times"></i> O'chirish
                        </button>
                    </div>
                </div>

                <div class="darsSoatWrapper">
                    <div class="form-grid-2 dars-soat-row">
                        <div class="form-group">
                            <label>Dars turi</label>
                            <select class="form-control" name="dars_turi[${index}][]" required>
                                <option value="">Tanlang</option>
                                ${chetDarsTurlariOptions}
                            </select>
                        </div>
                    </div>
                    <div class="dars-soat-actions">
                        <button type="button" class="btn btn-outline btn-sm addChetDarsSoat">
                            <i class="fas fa-plus"></i>
                        </button>

                        <button type="button" class="btn btn-danger btn-sm removeChetDarsSoat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="reja-actions">
                    <button type="button" class="btn btn-outline btn-sm addChetReja">
                        <i class="fas fa-plus"></i> Yana fan
                    </button>

                    <button type="button" class="btn btn-danger btn-sm removeChetReja">
                        <i class="fas fa-times"></i> O'chirish
                    </button>
                </div>
            `;
        }

        function renderChetOptions(select, semestrId) {
            select.empty().append(new Option('Tanlang', '', false, false));
            if (!semestrId) {
                select.val(null).trigger('change');
                return;
            }
            if (chetTiliOptionsBySemestr[semestrId]) {
                select.append(chetTiliOptionsBySemestr[semestrId]);
            } else {
                select.append(new Option("Chet tili fan topilmadi", "", false, false));
            }
            select.val(null).trigger('change');
        }

        $(document).on('click', '.addChetReja', function() {
            chetFanIndex++;
            const newCard = $(`<div class="reja-card" data-index="${chetFanIndex}"></div>`);
            $('#chetRejaWrapper').append(newCard);
            newCard.html(buildChetCard(chetFanIndex));
            initializeChetSelect2(newCard);
            const semestrId = $('#chetSemestrSelect').val();
            renderChetOptions(newCard.find('.chet-tili-select'), semestrId);
        });

        $(document).on('click', '.addChetTiliFan', function() {
            const card = $(this).closest('.reja-card');
            const index = card.data('index');
            const tanlovWrapper = $(this).closest('.tanlov-fan-item');
            const tanlovIndex = parseInt(tanlovWrapper.data('tanlov-index')) + 1;
            
            const newTanlovItem = $(`
                <div class="tanlov-fan-item mt-3" data-tanlov-index="${tanlovIndex}">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Chet tili nomi</label>
                            <input type="text" class="form-control" name="tanlov_fan_nomi[${index}][]" placeholder="Masalan: English 1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Kafedra</label>
                            <select class="form-control" name="tanlov_kafedra_id[${index}][]" required>
                                <option value="">Tanlang</option>
                                ${chetKafedralarOptions}
                            </select>
                        </div>
                    </div>
                    
                    <div class="tanlov-fan-actions mb-3">
                        <button type="button" class="btn btn-outline btn-sm addChetTiliFan">
                            <i class="fas fa-plus"></i> Yana variant
                        </button>
                        
                        <button type="button" class="btn btn-danger btn-sm removeChetTiliFan">
                            <i class="fas fa-times"></i> O'chirish
                        </button>
                    </div>
                </div>
            `);
            
            tanlovWrapper.after(newTanlovItem);
            initializeChetSelect2(newTanlovItem);
        });

        $(document).on('click', '.removeChetTiliFan', function() {
            const tanlovItems = $(this).closest('.reja-card').find('.tanlov-fan-item');
            if (tanlovItems.length > 1) {
                $(this).closest('.tanlov-fan-item').remove();
            }
        });

        $(document).on('click', '.addChetDarsSoat', function() {
            const card = $(this).closest('.reja-card');
            const wrapper = $(this).closest('.darsSoatWrapper');
            const index = card.data('index');
            
            const newRow = $(`
                <div class="form-grid-2 dars-soat-row">
                    <div class="form-group">
                        <label>Dars turi</label>
                        <select class="form-control" name="dars_turi[${index}][]" required>
                            <option value="">Tanlang</option>
                            ${chetDarsTurlariOptions}
                        </select>
                    </div>
                </div>
            `);
            
            newRow.insertBefore(wrapper.find('.dars-soat-actions'));
        });

        $(document).on('click', '.removeChetDarsSoat', function() {
            const wrapper = $(this).closest('.darsSoatWrapper');
            const rows = wrapper.find('.dars-soat-row');
            
            if (rows.length > 1) {
                rows.last().remove();
            }
        });

        $(document).on('click', '.removeChetReja', function() {
            const rejas = $('#chetRejaWrapper .reja-card');
            if (rejas.length > 1) {
                const rejaToRemove = $(this).closest('.reja-card');
                
                rejaToRemove.find('select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                
                rejaToRemove.remove();
                
                reorganizeChetIndexes();
            }
        });

        function reorganizeChetIndexes() {
            chetFanIndex = -1;
            $('#chetRejaWrapper .reja-card').each(function(newIndex) {
                chetFanIndex = newIndex;
                $(this).data('index', newIndex);
                const card = $(this);
                
                card.find('input[name^="tanlov_fan["]').attr('name', `tanlov_fan[${newIndex}]`);
                // Izoh: Chet tili select va input nomlarini indeks bo'yicha yangilash.
                card.find('input[name^="tanlov_fan_code["]').attr('name', `tanlov_fan_code[${newIndex}]`);
                card.find('input[name^="tanlov_fan_base_nomi["]').attr('name', `tanlov_fan_base_nomi[${newIndex}]`);
                card.find('select[name^="tanlov_fan_base["]').attr('name', `tanlov_fan_base[${newIndex}]`);
                card.find('input[name^="tanlov_fan_nomi["]').attr('name', `tanlov_fan_nomi[${newIndex}][]`);
                card.find('select[name^="tanlov_kafedra_id["]').attr('name', `tanlov_kafedra_id[${newIndex}][]`);
                card.find('select[name^="dars_turi["]').attr('name', `dars_turi[${newIndex}][]`);
                card.find('input[name^="dars_soati["]').attr('name', `dars_soati[${newIndex}][]`);
            });
        }

        function initializeChetSelect2(container) {
            setTimeout(() => {
                container.find('select').each(function() {
                    const name = $(this).attr('name') || '';

                    if (name.startsWith('dars_turi')) return;

                    if ($(this).hasClass('chet-tili-select')) {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({
                                placeholder: "Chet tili fanni tanlang",
                                allowClear: true,
                                width: '100%',
                            });
                        }
                        return;
                    }

                    if (name.includes('kafedra')) {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({
                                placeholder: "Kafedrani tanlang",
                                allowClear: true,
                                width: '100%',
                            });
                        }
                    }
                });
            }, 10);
        }

        // Izoh: Chet tili selectdan kod va nomni hidden inputlarga yozish.
        $(document).on('change', '.chet-tili-select', function() {
            const selected = $(this).find('option:selected');
            const code = $(this).val() || '';
            const baseName = selected.data('name') || '';
            const card = $(this).closest('.reja-card');
            const codeInput = card.find('.chet-code-input');
            const baseInput = card.find('.chet-base-input');

            codeInput.val(code);
            baseInput.val(baseName);
        });

        $('#chetTiliYaratishForm').on('submit', function(e) {
            e.preventDefault();
            // Izoh: Chet tili kodi va nomi select change hodisasida hidden inputga yoziladi.
            
            const formData = new FormData(this);
            
            fetch('insert/add_oquv_reja.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message || 'Chet tili muvaffaqiyatli saqlandi'
                    });

                    // Izoh: Formani tozalash va 2‑tabni ochish.
                    this.reset();
                    $('#chetSemestrSelect').val(null).trigger('change');
                    
                    $('#chetRejaWrapper .reja-card:gt(0)').each(function() {
                        $(this).find('select').each(function() {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                        });
                        $(this).remove();
                    });
                    
                    chetFanIndex = 0;
                    
                    const firstCard = $('#chetRejaWrapper .reja-card:first');
                    firstCard.data('index', 0);
                    firstCard.html(buildChetCard(0));
                    initializeChetSelect2(firstCard);

                    // Izoh: Sahifa shu tabda qoladi, 2‑tab ro'yxati esa AJAX bilan yangilanadi.
                    refreshChetTiliOptions();
                    
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Xatolik yuz berdi'
                    });
                }
            })
            .catch(() => {
                Toast.fire({
                    icon: 'error',
                    title: "Server bilan bog'lanib bo'lmadi"
                });
            });
        });

        // Izoh: Chet tili guruhlarini o'chirish.
        $(document).on('click', '.deleteChetTiliBtn', function() {
            const fanId = $(this).data('fan-id');
            const semestrNum = $(this).data('semestr-num');
            if (!fanId || !semestrNum) return;

            Swal.fire({
                title: "O'chirishni tasdiqlaysizmi?",
                text: "Bu amal orqaga qaytmaydi",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ha, o'chirish",
                cancelButtonText: "Bekor qilish"
            }).then((result) => {
                if (!result.isConfirmed) return;

                const formData = new FormData();
                formData.append('fan_id', fanId);
                formData.append('semestr_num', semestrNum);

                fetch('insert/delete_chet_tili_biriktirish.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Toast.fire({ icon: 'success', title: data.message || "O'chirildi" });
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        Toast.fire({ icon: 'error', title: data.message || 'Xatolik yuz berdi' });
                    }
                })
                .catch(() => {
                    Toast.fire({ icon: 'error', title: "Server bilan bog'lanib bo'lmadi" });
                });
            });
        });
    </script>
    <script src="../assets/js/app.js"></script>
</body>
</html>

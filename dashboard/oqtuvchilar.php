<?php 
    include_once 'config.php';
    $db = new Database();
    $fakultetlar = $db->get_data_by_table_all('fakultetlar');
    $kafedralar = $db->get_data_by_table_all('kafedralar');
    $ilmiy_unvonlar = $db->get_data_by_table_all('ilmiy_unvonlar');
    $ilmiy_darajalar = $db->get_data_by_table_all('ilmiy_darajalar');
    $ish_turlar = $db->get_data_by_table_all('ish_turlar');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O‘qituvchilar - O‘quv Qo‘llanma</title>

    <link rel="stylesheet" href="../assets/css/oquv_yuklama_style.css">
    <link rel="stylesheet" href="../assets/css/dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>

    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-navbar">
                <div class="navbar-left">
                    <h1>O‘qituvchilar</h1>
                    <p class="navbar-subtitle">O‘qituvchilarni boshqarish bo‘limi</p>
                </div>
                <div class="navbar-right">
                    <button class="btn btn-primary" id="addOqituvchiBtn">
                        <i class="fas fa-plus"></i> O‘qituvchi qo‘shish
                    </button>
                </div>
            </header>

            <div class="content-container">
                <div class="filter-container">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label><i class="fas fa-building-columns me-2"></i>Fakultet</label>
                            <select class="form-control" id="fakultetFilter">
                                <option value="">Barcha fakultetlar</option>
                                <?php foreach ($fakultetlar as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building me-2"></i>Kafedra</label>
                            <select class="form-control" id="kafedraFilter">
                                <option value="">Avval fakultetni tanlang</option>                                
                            </select>
                        </div>                        
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter me-2"></i>Filtrlash
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-redo me-2"></i>Tozalash
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Barcha o‘qituvchilar</h3>
                            <span class="badge" id="totalOqituvchilar">0 ta</span>
                        </div>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchOqituvchi" placeholder="Qidirish...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>F.I.O</th>
                                    <th>Fakultet</th>
                                    <th>Kafedra</th>
                                    <th>Lavozim</th>
                                    <th>Stavka</th>
                                    <th>Ish tur nomi</th>
                                    <th>Ilmiy unvon</th>
                                    <th>Ilmiy daraja</th>
                                    <th>Harakatlar</th>
                                </tr>
                            </thead>
                            <tbody id="oqituvchilarTable">
                                <!-- AJAX orqali to‘ldiriladi -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- MODAL -->
    <div class="modal" id="oqituvchiModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>O‘qituvchi qo‘shish</h3>
                <button class="modal-close" id="closeOqituvchiModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="oqituvchiForm">

                    <div class="form-group">
                        <label>Fakultet</label>
                        <select class="form-control" name="fakultet_id" id="fakultetSelect" required>
                            <option value="">Tanlang</option>
                            <?php foreach ($fakultetlar as $f): ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kafedra</label>
                        <select class="form-control" name="kafedra_id" id="kafedraSelect" required>
                            <option value="">Avval fakultetni tanlang</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>F.I.O</label>
                        <input type="text" class="form-control" name="fio" required>
                    </div>
                    <div class="form-group">
                        <label>Lavozimi</label>
                        <input type="text" class="form-control" name="lavozim" required>
                    </div>
                    <div class="form-group">
                        <label>Shtat birligi</label>
                        <select class="form-control" name="stavka" id="stavkaSelect">
                            <option value="">Tanlang</option>
                            <option value="0.25">0.25</option>
                            <option value="0.5">0.5</option>
                            <option value="0.75">0.75</option>
                            <option value="1.0">1.0</option>
                            <option value="1.25">1.25</option>
                            <option value="1.5">1.5</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Shtat turi</label>
                        <select class="form-control" name="ishtur_id" id="ishturSelect" required>
                            <option value="">Tanlang</option>
                            <?php foreach ($ish_turlar as $t): ?>
                                <option value="<?= $t['id'] ?>">
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ilmiy unvon</label>
                        <select class="form-control" name="ilmiy_unvon_id">
                            <option value="">Tanlang</option>
                            <?php foreach ($ilmiy_unvonlar as $u): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ilmiy daraja</label>
                        <select class="form-control" name="ilmiy_daraja_id">
                            <option value="">Tanlang</option>
                            <?php foreach ($ilmiy_darajalar as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelOqituvchiBtn">Bekor qilish</button>
                <button class="btn btn-primary" id="saveOqituvchiBtn">Saqlash</button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="../assets/js/app.js"></script>

    <script>
        const allKafedralar = <?php echo json_encode($kafedralar, JSON_UNESCAPED_UNICODE); ?>;
                                
        $(document).ready(function() {
            $('#kafedraFilter, #fakultetFilter, #kafedraSelect, #fakultetSelect').select2({
                placeholder: "Tanlang",
                allowClear: true,
                width: '100%'
            });

            toggleStavkaRequired();
            $('#ishturSelect').on('change', toggleStavkaRequired);
        });

        function toggleStavkaRequired() {
            const selectedText = $('#ishturSelect option:selected').text().trim().toLowerCase();
            const isSoatbay = selectedText.includes('soatbay');
            const stavkaSelect = $('#stavkaSelect');
            if (isSoatbay) {
                stavkaSelect.prop('required', false);
            } else {
                stavkaSelect.prop('required', true);
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            initModal();
            loadOqituvchilar();
            initSearch();
        });
        $('#fakultetSelect').on('change', function() {
            const fakultetId = $(this).val();
            const kafedraSelect = $('#kafedraSelect');
            kafedraSelect.empty().append('<option value="">Tanlang</option>');

            const filteredKafedralar = allKafedralar.filter(k => k.fakultet_id == fakultetId);
            filteredKafedralar.forEach(k => {
                kafedraSelect.append(
                    `<option value="${k.id}">${k.name}</option>`
                );
            });
        });
        $('#fakultetFilter').on('change', function() {
            const fakultetId = $(this).val();
            const kafedraFilter = $('#kafedraFilter');
            kafedraFilter.empty().append('<option value="">Barcha kafedralar</option>');

            const filteredKafedralar = allKafedralar.filter(k => k.fakultet_id == fakultetId);
            filteredKafedralar.forEach(k => {
                kafedraFilter.append(
                    `<option value="${k.id}">${k.name}</option>`
                );
            });
        });
        function applyFilters() {
            const fakultetId = $('#fakultetFilter').val();
            const kafedraId  = $('#kafedraFilter').val();

            $('#oqituvchilarTable tr').each(function () {
                const row = $(this);

                const rowFakultet = row.find('td:eq(2)').text().trim();
                const rowKafedra  = row.find('td:eq(3)').text().trim();

                let show = true;

                if (fakultetId) {
                    const selectedFakultetText =
                        $('#fakultetFilter option:selected').text().trim();
                    if (rowFakultet !== selectedFakultetText) {
                        show = false;
                    }
                }

                if (kafedraId) {
                    const selectedKafedraText =
                        $('#kafedraFilter option:selected').text().trim();
                    if (rowKafedra !== selectedKafedraText) {
                        show = false;
                    }
                }

                row.toggle(show);
            });
        }
        function resetFilters() {
            $('#fakultetFilter').val(null).trigger('change');
            $('#kafedraFilter').val(null).trigger('change');
            $('#oqituvchilarTable tr').show();
        }
        function loadOqituvchilar() {
            fetch('get/oqituvchilar_table.php')
                .then(res => res.text())
                .then(html => {
                    const table = document.getElementById('oqituvchilarTable');
                    table.innerHTML = html;
                    document.getElementById('totalOqituvchilar').textContent =
                        table.children.length + ' ta';
                });
        }

        function initModal() {
            const modal = document.getElementById('oqituvchiModal');
            document.getElementById('addOqituvchiBtn').onclick = () => modal.classList.add('show');
            document.getElementById('closeOqituvchiModal').onclick = () => modal.classList.remove('show');
            document.getElementById('cancelOqituvchiBtn').onclick = () => modal.classList.remove('show');
        }

        function initSearch() {
            const input = document.getElementById('searchOqituvchi');
            const table = document.getElementById('oqituvchilarTable');

            input.addEventListener('input', () => {
                const val = input.value.toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });

        document.getElementById('saveOqituvchiBtn').addEventListener('click', () => {
            const form = document.getElementById('oqituvchiForm');
            const data = new FormData(form);

            fetch('insert/add_oqituvchi.php', {
                method: 'POST',
                body: data
            })
            .then(res => res.json())
            .then(r => {
                if (r.success) {
                    Toast.fire({ icon: 'success', title: 'O‘qituvchi saqlandi' });
                    form.reset();
                    document.getElementById('oqituvchiModal').classList.remove('show');
                    loadOqituvchilar();
                } else {
                    Toast.fire({ icon: 'error', title: r.message });
                }
            });
        });
    </script>
</body>
</html>

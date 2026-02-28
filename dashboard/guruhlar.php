<?php
include_once 'config.php';
$db = new Database();
$yonalishlar = $db->get_data_by_table_all('yonalishlar');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guruhlar - O‘quv Qo‘llanma</title>

    <link rel="stylesheet" href="../assets/css/dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-navbar">
                <div class="navbar-left">
                    <h1>Guruhlar</h1>
                    <p class="navbar-subtitle">Guruhlarni boshqarish bo‘limi</p>
                </div>
                <div class="navbar-right">
                    <button class="btn btn-primary" id="addGuruhBtn">
                        <i class="fas fa-plus"></i> Guruh qo‘shish
                    </button>
                </div>
            </header>

            <div class="content-container">
                <div class="table-container">

                    <div class="table-header">
                        <div class="table-title">
                            <h3>Barcha guruhlar</h3>
                            <span class="badge" id="totalGuruhlar">0 ta</span>
                        </div>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchGuruh" placeholder="Qidirish...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Yo‘nalish</th>
                                <th>Guruh nomeri</th>
                                <th>Talaba soni</th>
                                <th>Yaratilgan sana</th>
                                <th>Harakatlar</th>
                            </tr>
                            </thead>
                            <tbody id="guruhlarTable">
                                <!-- fetch orqali -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal" id="guruhModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Guruh qo‘shish</h3>
                <button class="modal-close" id="closeGuruhModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="guruhForm">

                    <div class="form-group">
                        <label>
                            <i class="fas fa-graduation-cap"></i> Yo‘nalish
                        </label>
                        <select class="form-control" name="yonalish_id" required>
                            <option value="">Tanlang</option>
                            <?php foreach ($yonalishlar as $y): ?>
                                <option value="<?= $y['id'] ?>">
                                    <?= htmlspecialchars($y['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-users"></i> Guruh nomeri
                        </label>
                        <input type="text" class="form-control" name="guruh_nomi" placeholder="DI-101" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-friends"></i> Talaba soni
                        </label>
                        <input type="number" class="form-control" name="talaba_soni" min="1" required>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelGuruhBtn">Bekor qilish</button>
                <button class="btn btn-primary" id="saveGuruhBtn">Saqlash</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/app.js"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

        document.addEventListener('DOMContentLoaded', () => {
            initGuruhModal();
            initGuruhSearch();
            loadGuruhlar();
        });

        function loadGuruhlar() {
            fetch('get/guruhlar_table.php')
                .then(res => res.text())
                .then(html => {
                    document.getElementById('guruhlarTable').innerHTML = html;
                    document.getElementById('totalGuruhlar').textContent =
                        document.getElementById('guruhlarTable').children.length + ' ta';
                })
                .catch(() => {
                    document.getElementById('guruhlarTable').innerHTML =
                        '<tr><td colspan="6">Xatolik yuz berdi</td></tr>';
                });
        }

        function initGuruhModal() {
            const modal = document.getElementById('guruhModal');

            document.getElementById('addGuruhBtn').onclick = () => modal.classList.add('show');
            document.getElementById('closeGuruhModal').onclick =
            document.getElementById('cancelGuruhBtn').onclick = () => modal.classList.remove('show');

            window.onclick = e => { if (e.target === modal) modal.classList.remove('show'); };
        }

        function initGuruhSearch() {
            const input = document.getElementById('searchGuruh');
            const table = document.getElementById('guruhlarTable');

            input.addEventListener('input', () => {
                const val = input.value.toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        }

        document.getElementById('saveGuruhBtn').addEventListener('click', () => {
            const form = document.getElementById('guruhForm');
            const formData = new FormData(form);

            if (!form.checkValidity()) {
                Toast.fire({ icon: 'error', title: 'Barcha maydonlarni to‘ldiring!' });
                return;
            }

            fetch('insert/add_guruh.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    form.reset();
                    document.getElementById('guruhModal').classList.remove('show');
                    loadGuruhlar();
                } else {
                    Toast.fire({ icon: 'error', title: data.message });
                }
            })
            .catch(() => {
                Toast.fire({ icon: 'error', title: 'Server bilan bog‘lanib bo‘lmadi' });
            });
        });
    </script>

</body>
</html>

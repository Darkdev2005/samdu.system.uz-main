<?php
include_once 'config.php';
$db = new Database();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qo‘shimcha dars turlari - O‘quv Qo‘llanma</title>

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
                    <h1>Qo‘shimcha dars turlari</h1>
                    <p class="navbar-subtitle">Qo‘shimcha dars turlarini boshqarish</p>
                </div>
                <div class="navbar-right">
                    <button class="btn btn-primary" id="addDarsTuriBtn">
                        <i class="fas fa-plus"></i> Qo‘shish
                    </button>
                </div>
            </header>
            <div class="content-container">
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Barcha qo‘shimcha dars turlari</h3>
                            <span class="badge" id="totalDarsTuri">0 ta</span>
                        </div>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchDarsTuri" placeholder="Qidirish...">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nomi</th>
                                <th>Koifisent</th>
                                <th>Yaratilgan sana</th>
                                <th>Harakatlar</th>
                            </tr>
                            </thead>
                            <tbody id="darsTuriTable">
                            <!-- fetch orqali -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </main>
    </div>
    <div class="modal" id="darsTuriModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Qo‘shimcha dars turi qo‘shish</h3>
                <button class="modal-close" id="closeDarsTuriModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="darsTuriForm">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-book"></i> Nomi
                        </label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-percent"></i> Koifisent
                        </label>
                        <input type="number"
                            class="form-control"
                            name="koifesent"
                            step="0.01"
                            min="0"
                            placeholder="Masalan: 1.25"
                            required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelDarsTuriBtn">Bekor qilish</button>
                <button class="btn btn-primary" id="saveDarsTuriBtn">Saqlash</button>
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
            initModal();
            initSearch();
            loadDarsTurlari();
        });

        function loadDarsTurlari() {
            fetch('get/qoshimcha_dars_turlari_table.php')
                .then(res => res.text())
                .then(html => {
                    const tbody = document.getElementById('darsTuriTable');
                    tbody.innerHTML = html;
                    document.getElementById('totalDarsTuri').textContent =
                        tbody.children.length + ' ta';
                })
                .catch(() => {
                    document.getElementById('darsTuriTable').innerHTML =
                        '<tr><td colspan="5">Xatolik yuz berdi</td></tr>';
                });
        }

        function initModal() {
            const modal = document.getElementById('darsTuriModal');

            document.getElementById('addDarsTuriBtn').onclick = () => modal.classList.add('show');
            document.getElementById('closeDarsTuriModal').onclick =
            document.getElementById('cancelDarsTuriBtn').onclick =
                () => modal.classList.remove('show');

            window.onclick = e => { if (e.target === modal) modal.classList.remove('show'); };
        }

        function initSearch() {
            const input = document.getElementById('searchDarsTuri');
            const table = document.getElementById('darsTuriTable');

            input.addEventListener('input', () => {
                const val = input.value.toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        }

        document.getElementById('saveDarsTuriBtn').addEventListener('click', () => {
            const form = document.getElementById('darsTuriForm');

            if (!form.checkValidity()) {
                Toast.fire({ icon: 'error', title: 'Barcha maydonlarni to‘ldiring!' });
                return;
            }

            const formData = new FormData(form);

            fetch('insert/add_qoshimcha_dars_turi.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    form.reset();
                    document.getElementById('darsTuriModal').classList.remove('show');
                    loadDarsTurlari();
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

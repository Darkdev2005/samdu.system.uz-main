<?php
include_once 'config.php';
$db = new Database();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Semestrlar - O‘quv Qo‘llanma</title>

    <link rel="stylesheet" href="../assets/css/dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-navbar">
                <div class="navbar-left">
                    <h1>Semestrlar</h1>
                    <p class="navbar-subtitle">
                        Yo‘nalishlar asosida semestrlarni avtomatik yaratish
                    </p>
                </div>
                <div class="navbar-right">
                    <button class="btn btn-primary" id="generateSmestrBtn">
                        <i class="fas fa-calendar-week"></i> Semestrlarni avtomatik yaratish
                    </button>
                </div>
            </header>

            <div class="content-container">
                <div class="table-container">

                    <div class="table-header">
                        <div class="table-title">
                            <h3>Barcha semestrlar</h3>
                            <span class="badge" id="totalSmestrlar">0 ta</span>
                        </div>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchSemestr" placeholder="Qidirish...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fakultet</th>
                                <th>Yo‘nalish</th>
                                <th>Semestr</th>
                                <th>Yaratilgan sana</th>
                                <th>Harakatlar</th>
                            </tr>
                            </thead>
                            <tbody id="smestrlarTable">
                                <!-- fetch orqali -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadSmestrlar();
            initSearch();
        });

        function loadSmestrlar() {
            fetch('get/smestrlar_table.php')
                .then(res => res.text())
                .then(html => {
                    const tbody = document.getElementById('smestrlarTable');
                    tbody.innerHTML = html;

                    document.getElementById('totalSmestrlar').textContent =
                        tbody.children.length + ' ta';
                })
                .catch(() => {
                    document.getElementById('smestrlarTable').innerHTML =
                        '<tr><td colspan="5">Xatolik yuz berdi</td></tr>';
                });
        }

        function initSearch() {
            const input = document.getElementById('searchSemestr');
            const table = document.getElementById('smestrlarTable');

            input.addEventListener('input', () => {
                const value = input.value.toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(value)
                        ? ''
                        : 'none';
                });
            });
        }

        document.getElementById('generateSmestrBtn').addEventListener('click', () => {

            Swal.fire({
                title: 'Tasdiqlaysizmi?',
                text: 'Barcha yo‘nalishlar uchun semestrlar avtomatik yaratiladi',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ha, yaratilsin',
                cancelButtonText: 'Bekor qilish'
            }).then(result => {

                if (!result.isConfirmed) return;

                fetch('insert/add_smestr.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Toast.fire({
                                icon: 'success',
                                title: data.message
                            });
                            loadSmestrlar();
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: data.message
                            });
                        }
                    })
                    .catch(() => {
                        Toast.fire({
                            icon: 'error',
                            title: 'Server bilan bog‘lanib bo‘lmadi'
                        });
                    });

            });
        });
    </script>

</body>
</html>

document.addEventListener('DOMContentLoaded', () => {

    const clockElement = document.getElementById('realtime-clock');
    //TOGGLE SIDEBAR RESPONSIVE ---
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainWrapper = document.querySelector('.main-wrapper');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        //Sembunyikan sidebar saat mengklik area konten
        mainWrapper.addEventListener('click', (event) => {
            if (sidebar.classList.contains('show') && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    let visitTrendChartInstance = null;
    let guestCategoryChartInstance = null;
    let allDashboardData = null;

    // 1. Fungsi Jam Real-time
    function updateClock() {
        if (clockElement) {
            const now = new Date();
            const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const dateString = now.toLocaleDateString('id-ID', dateOptions);
            const timeString = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
            clockElement.textContent = `${dateString} | ${timeString}`;
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Fungsi utama untuk memuat semua data dashboard
    async function loadDashboardData() {
        try {
            const response = await fetch('php/fetch_dashboard_data.php');
            allDashboardData = await response.json();

            updateStatCards(allDashboardData);
            updateTamuTerbaru(allDashboardData.tamu_terbaru);
            updateNotifikasi(allDashboardData.notifikasi);
            initVisitTrendChart(allDashboardData.grafik_kunjungan.harian);
            initGuestCategoryChart(allDashboardData.kategori_tamu);

        } catch (error) {
            console.error('Gagal memuat data dashboard:', error);
        }
    }

    // 2. Update Stat Cards
    function updateStatCards(data) {
        document.getElementById('tamu-hari-ini').textContent = data.tamu_hari_ini || 0;
        document.getElementById('tamu-minggu-ini').textContent = data.tamu_minggu_ini || 0;
        document.getElementById('sedang-berkunjung').textContent = data.sedang_berkunjung || 0;
        document.getElementById('kunjungan-selesai-hari-ini').textContent = data.kunjungan_selesai_hari_ini || 0;
    }

    // 3. Update Tabel Tamu Terbaru
    function updateTamuTerbaru(tamu) {
        const tbody = document.getElementById('tamu-terbaru-body');
        tbody.innerHTML = '';
        if (tamu.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Tidak ada tamu terbaru.</td></tr>';
            return;
        }
        tamu.forEach(t => {
            tbody.innerHTML += `
                <tr>
                    <td>${t.name}</td>
                    <td>${t.instansi}</td>
                    <td>${t.tujuan}</td>
                    <td>${t.waktu}</td>
                </tr>
            `;
        }); 
    }

    // 4. Update Notifikasi Real-time
    function updateNotifikasi(notifikasi) {
        const list = document.getElementById('notifikasi-list');
        list.innerHTML = '';
        if (notifikasi.length === 0) {
            list.innerHTML = '<li><p>Tidak ada notifikasi baru.</p></li>';
            return;
        }
        notifikasi.forEach(n => {
            let message = '';
            if (n.status === 'Sedang Berkunjung') {
                message = `<strong>${n.name}</strong> dari <strong>${n.instansi || 'pribadi'}</strong> baru saja check-in.`;
            } else if (n.status === 'Menunggu Verifikasi') {
                message = `<strong>${n.name}</strong> menunggu verifikasi.`;
            } else {
                message = `Kunjungan <strong>${n.name}</strong> telah selesai.`;
            }
            
            // Menggunakan 'waktu_lalu' yang sudah diformat dari PHP
            list.innerHTML += `
                <li>
                    <p>${message}</p>
                    <span>${n.waktu_lalu}</span>
                </li>
            `;
        });
    }

    // 5. Inisialisasi Grafik Kunjungan
    function initVisitTrendChart(chartData) {
        const ctx = document.getElementById('visitTrendChart');
        if (visitTrendChartInstance) {
            visitTrendChartInstance.destroy();
        }
        visitTrendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Jumlah Kunjungan',
                    data: chartData.values,
                    fill: true,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    tension: 0.3
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
    
    // 6. Inisialisasi Chart Kategori Tamu
    function initGuestCategoryChart(chartData) {
        const ctx = document.getElementById('guestCategoryChart');
        if (guestCategoryChartInstance) {
            guestCategoryChartInstance.destroy();
        }
        guestCategoryChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Kategori Tamu',
                    data: chartData.values,
                    backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#95a5a6', '#9b59b6'],
                    hoverOffset: 4
                }]
            },
            options: { responsive: true }
        });
    }

    // 7. Event Listener untuk Filter Grafik
    document.getElementById('filter-harian').addEventListener('click', () => {
        initVisitTrendChart(allDashboardData.grafik_kunjungan.harian);
        setActiveFilter(event.target);
    });
    document.getElementById('filter-mingguan').addEventListener('click', () => {
        initVisitTrendChart(allDashboardData.grafik_kunjungan.mingguan);
        setActiveFilter(event.target);
    });
    document.getElementById('filter-bulanan').addEventListener('click', () => {
        initVisitTrendChart(allDashboardData.grafik_kunjungan.bulanan);
        setActiveFilter(event.target);
    });
    
    function setActiveFilter(activeButton) {
        document.querySelectorAll('.chart-filter .filter-btn').forEach(button => {
            button.classList.remove('active');
        });
        activeButton.classList.add('active');
    }

    // Panggil fungsi utama untuk memuat data
    loadDashboardData();
});
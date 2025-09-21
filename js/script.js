document.addEventListener('DOMContentLoaded', () => {

    // --- Variabel Global untuk Berbagai Halaman ---
    const clockElement = document.getElementById('realtime-clock');
    
    // Variabel untuk Halaman Input Tamu (index.html)
    const guestbookForm = document.getElementById('guestbook-form');
    const formMessage = document.getElementById('form-message');
    const kategoriSelect = document.getElementById('kategori');
    const salesProposalUpload = document.getElementById('sales-proposal-upload');
    const kategoriLainnyaWrapper = document.getElementById('kategori-lainnya-wrapper');

    // Variabel untuk Halaman Daftar Tamu (daftar_tamu.html)
    const entryListContainer = document.getElementById('entry-list');
    const searchInput = document.getElementById('search-input');
    const dateFilter = document.getElementById('date-filter');
    const statusFilter = document.getElementById('status-filter');
    const filterBtn = document.getElementById('filter-btn');

    // --- 1. FUNGSI UNTUK SEMUA HALAMAN ---
    // Fungsi untuk menampilkan jam real-time
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


    // --- 2. LOGIKA KHUSUS UNTUK HALAMAN INPUT TAMU (index.html) ---
    if (guestbookForm) {
        guestbookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(guestbookForm);
            
            try {
                const response = await fetch(guestbookForm.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json(); // Sekarang kita mengharapkan JSON

                if (result.success) {
                    formMessage.textContent = "Terima kasih! Data Anda berhasil dikirim.";
                    formMessage.className = 'success';
                    guestbookForm.reset();
                } else {
                    formMessage.textContent = "Gagal mengirim data. Error: " + result.message;
                    formMessage.className = 'error';
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                formMessage.textContent = "Terjadi kesalahan koneksi. Mohon coba lagi nanti.";
                formMessage.className = 'error';
            }
        });
    }


    // --- 3. LOGIKA KHUSUS UNTUK HALAMAN DAFTAR TAMU (daftar_tamu.html) ---
    let allEntries = [];

    async function fetchGuestbookEntries() {
        if (!entryListContainer) return;
        try {
            const response = await fetch('php/fetch_entries.php');
            allEntries = await response.json();
            displayEntries(allEntries);
        } catch (error) {
            console.error('Gagal memuat entri:', error);
            entryListContainer.innerHTML = '<p class="error-message">Gagal memuat data.</p>';
        }
    }

    if (kategoriSelect) {
        kategoriSelect.addEventListener('change', () => {
            if (kategoriSelect.value === 'Sales Visit') {
                salesProposalUpload.style.display = 'block'; // Tampilkan input PDF
            } else {
                salesProposalUpload.style.display = 'none'; // Sembunyikan lagi jika pilihan lain
            }

            if (kategoriSelect.value === 'Lainnya') {
                kategoriLainnyaWrapper.style.display = 'block';
            } else {
                kategoriLainnyaWrapper.style.display = 'none';
            }
        });
    }

    function displayEntries(entries) {
        if (!entryListContainer) return;
        entryListContainer.innerHTML = '';

        if (entries.length === 0) {
            entryListContainer.innerHTML = '<p class="no-entries">Tidak ada tamu yang cocok dengan kriteria Anda.</p>';
            return;
        }

        //Daftar 5 ikon yang akan digunakan ---
    const userIcons = ['user', 'briefcase', 'coffee', 'award', 'anchor'];

        entries.forEach((entry, index) => { 
        const entryCard = document.createElement('div');
        entryCard.classList.add('entry-card');

            // Pilih ikon secara bergiliran
        const iconName = userIcons[index % userIcons.length]; // Ini akan berputar dari 0 sampai 4

            const formattedDate = new Date(entry.date_submitted).toLocaleString('id-ID', {
                day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            let statusClass = 'status-' + entry.status.toLowerCase().replace(/ /g, '-');
            
            entryCard.innerHTML = `
            <div class="entry-header">
                <div class="user-info">
                    <div class="user-icon-placeholder">
                        <i data-feather="${iconName}"></i>
                    </div>
                    <div>
                        <h3>${entry.name}</h3>
                        <span class="instansi">${entry.instansi || '<i>Instansi tidak diisi</i>'}</span>
                    </div>
                </div>
                <span class="status ${statusClass}">${entry.status}</span>
            </div>
            <div class="entry-body">
                <p class="tujuan-title">Tujuan Kunjungan:</p>
                <p class="tujuan-text">${entry.tujuan}</p>
            </div>
            <div class="entry-footer">
                <small>Didaftarkan pada: ${formattedDate}</small>
                <div class="entry-actions">
                    ${entry.status === 'Menunggu Verifikasi' ? `<a href="#" class="action-btn" data-action="confirm" data-id="${entry.id}" title="Konfirmasi"><i data-feather="check-square"></i></a>` : ''}
                    <a href="edit_tamu.html?id=${entry.id}" class="action-btn" title="Edit & Atur Jadwal"><i data-feather="edit"></i></a>
                    <a href="#" class="action-btn" data-action="delete" data-id="${entry.id}" title="Hapus"><i data-feather="trash-2"></i></a>
                </div>
            </div>
        `;
        entryListContainer.appendChild(entryCard);
        });
        
        feather.replace();
    }

    function filterAndSortEntries() {
        let filteredEntries = [...allEntries];
        const searchValue = searchInput.value.toLowerCase();
        const dateValue = dateFilter.value;
        const statusValue = statusFilter.value;

        if (searchValue) {
            filteredEntries = filteredEntries.filter(entry =>
                entry.name.toLowerCase().includes(searchValue) ||
                (entry.instansi && entry.instansi.toLowerCase().includes(searchValue))
            );
        }
        if (dateValue) {
            filteredEntries = filteredEntries.filter(entry => entry.date_submitted.startsWith(dateValue));
        }
        if (statusValue) {
            filteredEntries = filteredEntries.filter(entry => entry.status === statusValue);
        }
        displayEntries(filteredEntries);
    }
    
    if (entryListContainer) {
    entryListContainer.addEventListener('click', (e) => {
        const target = e.target.closest('.action-btn');
        if (!target) return;

        const action = target.dataset.action;
        const id = target.dataset.id;

        // Hanya panggil preventDefault untuk aksi yang ditangani oleh JavaScript
        if (action === 'confirm' || action === 'delete') {
            e.preventDefault(); // Pindahkan ke dalam kondisi ini
            
            if (action === 'confirm') {
                confirmGuest(id);
            } else if (action === 'delete') {
                deleteGuest(id);
            }
        }
    });
    }   

    async function confirmGuest(id) {
        if (!confirm('Anda yakin ingin mengonfirmasi tamu ini? Status akan diubah menjadi "Dikonfirmasi". Anda bisa mengatur jadwal di halaman edit.')) return;
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('php/update_status.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Tamu berhasil dikonfirmasi!');
                fetchGuestbookEntries();
            } else {
                alert('Gagal: ' + result.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan koneksi.');
        }
    }

    async function deleteGuest(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus data tamu ini secara permanen?')) return;
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('php/delete_entry.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Data tamu berhasil dihapus.');
                fetchGuestbookEntries();
            } else {
                 alert('Gagal menghapus data: ' + result.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan koneksi.');
        }
    }

    if (filterBtn) {
        filterBtn.addEventListener('click', filterAndSortEntries);
    }
    
    if (entryListContainer) {
        fetchGuestbookEntries();
    }

    // TOGGLE SIDEBAR RESPONSIVE
    function initializeMenuToggle() {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mainWrapper = document.querySelector('.main-wrapper');

        if (menuToggle && sidebar && mainWrapper) {
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation(); // Mencegah klik menyebar ke mainWrapper
                sidebar.classList.toggle('show');
            });

            mainWrapper.addEventListener('click', () => {
                if (sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });
        }
    }
    initializeMenuToggle();
});

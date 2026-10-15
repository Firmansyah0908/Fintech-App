<header class="header">
    <div class="header-right">
        <span class="date"><?= tanggalIndo(date('Y-m-d')) ?></span>
        
        <div class="notification">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </div>
        
        <div class="user-profile">
            <div class="avatar">AP</div>
            <div class="user-info">
                <h4><?= isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Aditya Pratama' ?></h4>
                <p>Pemilik bisnis</p>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <a href="logout.php" style="margin-left: 16px; color: var(--red); font-size: 13px; text-decoration: none; font-weight: 500;">Logout</a>
    </div>
</header>
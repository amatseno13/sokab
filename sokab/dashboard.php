<?php
/**
 * SOKAB - Dashboard
 * Identik struktur dengan SAMAWA
 */

require_once 'includes/check_session.php';
requireLogin();

$user = getCurrentUser();
$user_name = $user['nama_lengkap'];
$user_role = $user['role'];
$username  = $user['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOKAB - SAKIP Online BPS Bima</title>

     <!-- Favicon - Logo BPS Asli -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon.png">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="shortcut icon" href="assets/favicon.ico">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        /* Header */
        header {
            background: white;
            padding: 1.5rem 3rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .logo-text h1 {
            font-size: 1.8rem;
            color: #1e293b;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 0.75rem;
            color: #718096;
            margin-top: -3px;
        }

        .year-badge {
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* User Section */
        .user-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #718096;
            background: #e2e8f0;
            padding: 2px 10px;
            border-radius: 10px;
            margin-top: 2px;
        }

        .btn-logout {
            background: linear-gradient(135deg, #fc8181, #f56565);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 101, 101, 0.3);
        }

        /* Container */
        .container {
            display: flex;
            min-height: calc(100vh - 90px);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            color: #718096;
        }

        .menu-list {
            padding: 0.5rem 0 1rem;
        }

        .menu-section-header {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a0aec0;
            padding: 1rem 1.5rem 0.3rem;
            margin-top: 0.3rem;
        }

        .menu-item {
            margin-bottom: 0.15rem;
        }

        .menu-item.menu-sub .menu-link {
            padding-left: 1.2rem;
        }

        .menu-item.menu-sub .menu-icon {
            font-size: 1rem;
            width: 28px;
            height: 28px;
        }

        .menu-item.menu-sub .menu-text h3 {
            font-size: 0.82rem;
        }

        .menu-item.menu-sub .menu-text p {
            font-size: 0.68rem;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .menu-link:hover {
            background: #edf2f7;
        }

        .menu-link.active {
            background: #0ea5e9;
            color: white;
        }

        .menu-link.has-dropdown.active {
            background: #1e40af;
        }

        .menu-icon {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .menu-link.active .menu-icon {
            background: white;
        }

        .menu-text h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.1rem;
        }

        .menu-text p {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .menu-arrow {
            margin-left: auto;
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .menu-item.open .menu-arrow {
            transform: rotate(90deg);
        }

        /* Submenu Level 1 (Tahun) */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #f7fafc;
        }

        .menu-item.open .submenu {
            max-height: 1200px;
        }

        .submenu-item {
            padding: 0.7rem 1.5rem 0.7rem 4.5rem;
            color: #4a5568;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .submenu-item:hover {
            background: #e2e8f0;
            padding-left: 5rem;
        }

        .submenu-item.active {
            background: #bee3f8;
            color: #1e293b;
            font-weight: 600;
            border-left: 3px solid #0ea5e9;
        }

        .submenu-item.has-sub {
            cursor: pointer;
        }

        .submenu-arrow {
            font-size: 0.7rem;
            transition: transform 0.3s;
        }

        .submenu-item.open .submenu-arrow {
            transform: rotate(90deg);
        }

        /* Submenu Level 2 (Triwulan) */
        .sub-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #edf2f7;
        }

        .submenu-item.open .sub-submenu {
            max-height: 600px;
        }

        .sub-submenu-item {
            padding: 0.6rem 1.5rem 0.6rem 1rem;
            color: #4a5568;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .sub-submenu-item:hover {
            background: #cbd5e0;
        }

        .sub-submenu-item.active {
            background: #90cdf4;
            color: #1e293b;
            font-weight: 600;
            border-left: 3px solid #1e40af;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2.5rem 3rem;
            overflow-y: auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-banner {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .page-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .page-title {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .page-title .highlight {
            background: linear-gradient(135deg, #ed8936, #f6ad55);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-description {
            font-size: 1rem;
            color: #718096;
            line-height: 1.6;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Section Title */
        .section-title {
            font-size: 1.4rem;
            color: #1e293b;
            margin: 2.5rem 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 28px;
            background: linear-gradient(180deg, #ed8936, #f6ad55);
            border-radius: 2px;
        }

        /* Document Grid */
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .doc-card {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }

        .doc-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: #0ea5e9;
        }

        .doc-icon-wrapper {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .doc-card:hover .doc-icon-wrapper {
            background: linear-gradient(135deg, #0ea5e9, #1e40af);
            transform: scale(1.1);
        }

        .doc-content h3 {
            font-size: 1.05rem;
            color: #1e293b;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        .doc-content p {
            font-size: 0.85rem;
            color: #718096;
            line-height: 1.5;
        }

        /* Home Page */
        .hero {
            text-align: center;
            padding: 3rem 0;
        }

        .hero-image {
            width: 100%;
            max-width: 800px;
            height: 350px;
            margin: 0 auto 2.5rem;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero h1 {
            font-size: 3rem;
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero .highlight {
            background: linear-gradient(135deg, #ed8936, #f6ad55);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.1rem;
            color: #718096;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin: 3rem 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #0ea5e9, #1e40af);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(66, 153, 225, 0.3);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-8px);
        }

        /* ===== USER TABLE ===== */
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .user-table th { padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; }
        .user-table tbody tr { border-bottom: 1px solid #e2e8f0; transition: all 0.2s; }
        .user-table tbody tr:hover { background: #f7fafc; }
        .user-table td { padding: 0.75rem 1rem; font-size: 0.85rem; }
        .user-table .role-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .user-table .role-admin { background: #fbd38d; color: #744210; }
        .user-table .role-user { background: #bee3f8; color: #1e293b; }
        .user-table .btn-user-action { border: none; border-radius: 6px; padding: 5px 10px; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; margin: 0 2px; }
        .user-table .btn-edit-user { background: #bee3f8; color: #1e293b; }
        .user-table .btn-edit-user:hover { background: #0ea5e9; color: white; }
        .user-table .btn-reset-pw { background: #fed7d7; color: #c53030; }
        .user-table .btn-reset-pw:hover { background: #fc8181; color: white; }
        .user-table .btn-delete-user { background: #ffdede; color: #c53030; }
        .user-table .btn-delete-user:hover { background: #f56565; color: white; }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #e6fffa, #f0fff4);
            padding: 2rem;
            border-radius: 16px;
            margin-top: 2rem;
            border-left: 4px solid #48bb78;
        }

        .info-box h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-box p {
            color: #4a5568;
            line-height: 1.8;
        }

        /* Content Pages */
        .content-page {
            display: none;
        }

        .content-page.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .doc-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .doc-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 1.5rem;
            }

            header {
                padding: 1rem 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        /* ===== JADWAL SAKIP ===== */
        .jadwal-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        /* Kalender */
        .kalender-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .kalender-card h3 {
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* FullCalendar custom */
        #kalender { font-family: 'Poppins', sans-serif; }
        .fc-toolbar-title { font-size: 1rem !important; font-weight: 600 !important; color: #1e293b !important; }
        .fc-button-primary { background: #0ea5e9 !important; border-color: #0ea5e9 !important; font-size: 0.8rem !important; padding: 4px 10px !important; }
        .fc-button-primary:hover { background: #1e293b !important; border-color: #1e293b !important; }
        .fc-button-active { background: #1e293b !important; border-color: #1e293b !important; }
        .fc-event { border: none !important; font-size: 0.75rem !important; padding: 2px 5px !important; border-radius: 4px !important; cursor: pointer; }
        .fc-day-today { background: #ebf8ff !important; }
        .fc-col-header-cell { background: #f7fafc; font-size: 0.8rem; }
        .fc-daygrid-day-number { font-size: 0.82rem; color: #4a5568; }

        /* Panel jadwal kanan */
        .jadwal-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .jadwal-panel-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .jadwal-panel-card h3 {
            font-size: 0.95rem;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-tambah-jadwal {
            background: #0ea5e9;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-tambah-jadwal:hover { background: #1e293b; }

        /* List jadwal */
        .jadwal-list { display: flex; flex-direction: column; gap: 0.6rem; max-height: 340px; overflow-y: auto; }
        .jadwal-list::-webkit-scrollbar { width: 4px; }
        .jadwal-list::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }

        .jadwal-item {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            padding: 0.7rem;
            border-radius: 10px;
            background: #f7fafc;
            border-left: 4px solid #0ea5e9;
            transition: all 0.2s;
        }
        .jadwal-item:hover { background: #edf2f7; }

        .jadwal-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .jadwal-info { flex: 1; min-width: 0; }
        .jadwal-info .judul { font-size: 0.82rem; font-weight: 600; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .jadwal-info .tanggal { font-size: 0.72rem; color: #718096; margin-top: 2px; }

        .jadwal-actions { display: flex; gap: 4px; flex-shrink: 0; }

        .btn-status {
            border: none; border-radius: 6px; font-size: 0.68rem; padding: 3px 7px;
            cursor: pointer; font-family: 'Poppins', sans-serif; font-weight: 500; transition: all 0.2s;
        }
        .btn-status-belum  { background: #fed7d7; color: #c53030; }
        .btn-status-proses { background: #fef3c7; color: #92400e; }
        .btn-status-selesai{ background: #c6f6d5; color: #276749; }
        .btn-status:hover  { opacity: 0.75; }

        .btn-edit-item { background: #bee3f8; color: #1e293b; border: none; border-radius: 6px; padding: 3px 7px; font-size: 0.68rem; cursor: pointer; transition: all 0.2s; margin-right: 4px; }
        .btn-edit-item:hover { background: #0ea5e9; color: white; }
        .btn-hapus-item { background: #fed7d7; color: #c53030; border: none; border-radius: 6px; padding: 3px 7px; font-size: 0.68rem; cursor: pointer; transition: all 0.2s; }
        .btn-hapus-item:hover { background: #fc8181; color: white; }

        /* Status badge */
        .status-badge {
            display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.68rem; font-weight: 600;
        }
        .status-belum   { background: #fed7d7; color: #c53030; }
        .status-proses  { background: #fef3c7; color: #92400e; }
        .status-selesai { background: #c6f6d5; color: #276749; }

        /* Kategori badge */
        .kat-badge { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 0.65rem; font-weight: 600; background: #bee3f8; color: #1e293b; margin-right: 4px; }

        /* Summary stats */
        .jadwal-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 0.6rem; margin-bottom: 0.8rem; }
        .jstat { text-align: center; padding: 0.6rem; border-radius: 10px; }
        .jstat .num { font-size: 1.4rem; font-weight: 700; }
        .jstat .lbl { font-size: 0.68rem; color: #718096; margin-top: 2px; }
        .jstat-belum   { background: #fff5f5; }
        .jstat-proses  { background: #fffbeb; }
        .jstat-selesai { background: #f0fff4; }
        .jstat-belum   .num { color: #c53030; }
        .jstat-proses  .num { color: #92400e; }
        .jstat-selesai .num { color: #276749; }

        /* ===== MODAL TAMBAH/DETAIL JADWAL ===== */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }

        .modal-box {
            background: white; border-radius: 16px; padding: 2rem;
            max-width: 480px; width: 90%; max-height: 90vh; overflow-y: auto;
            animation: modalIn 0.25s ease-out;
        }
        @keyframes modalIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }

        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { font-size: 1.1rem; color: #1e293b; }
        .modal-close { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #718096; }

        .modal-form .form-group { margin-bottom: 1rem; }
        .modal-form label { display: block; font-size: 0.82rem; font-weight: 600; color: #2d3748; margin-bottom: 5px; }
        .modal-form input,
        .modal-form select,
        .modal-form textarea {
            width: 100%; padding: 9px 12px; border: 2px solid #e2e8f0; border-radius: 9px;
            font-size: 0.88rem; font-family: 'Poppins', sans-serif; transition: all 0.2s;
        }
        .modal-form input:focus,
        .modal-form select:focus,
        .modal-form textarea:focus { outline: none; border-color: #0ea5e9; }

        .modal-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }
        .btn-submit-modal {
            width: 100%; padding: 11px; background: linear-gradient(135deg, #1e293b, #0ea5e9);
            color: white; border: none; border-radius: 9px; font-size: 0.95rem;
            font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; margin-top: 0.5rem;
            transition: all 0.3s;
        }
        .btn-submit-modal:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(66,153,225,0.3); }

        /* ===== POPUP DEADLINE ===== */
        .popup-deadline {
            display: none; position: fixed; bottom: 24px; right: 24px;
            background: white; border-radius: 16px; padding: 1.2rem 1.5rem;
            max-width: 340px; width: 90%; z-index: 999;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-left: 5px solid #f56565;
            animation: popupIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .popup-deadline.show { display: block; }

        @keyframes popupIn {
            from { opacity:0; transform:translateX(100px); }
            to   { opacity:1; transform:translateX(0); }
        }

        .popup-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; }
        .popup-header h4 { color: #c53030; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
        .popup-close { background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #718096; }

        .popup-item { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; }
        .popup-item:last-child { border: none; }
        .popup-item-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .popup-item-info .pjudul { font-size: 0.82rem; font-weight: 600; color: #2d3748; }
        .popup-item-info .pdate  { font-size: 0.72rem; color: #718096; }
        .popup-item-info .psisa  { font-size: 0.72rem; font-weight: 600; }
        .sisa-danger { color: #c53030; }
        .sisa-warning { color: #d97706; }

        .popup-footer { margin-top: 0.8rem; text-align: right; }
        .popup-dismiss { background: #f56565; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-size: 0.78rem; cursor: pointer; font-family: 'Poppins', sans-serif; font-weight: 600; }

        /* Responsive jadwal */
        @media (max-width: 1100px) {
            .jadwal-wrapper { grid-template-columns: 1fr; }
        }

        /* ═══ PAGE HEADER BAR ═══ */
        .page-header-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.2rem;
            border-bottom: 2px solid #edf2f7;
            flex-wrap: wrap;
        }

        .page-header-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }

        .page-subtitle {
            font-size: 0.82rem;
            color: #718096;
            margin: 0;
        }

        .btn-upload-lakin {
            margin-left: auto;
            background: #1e293b;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }
        .btn-upload-lakin:hover { background: #1a365d; }

        /* ═══ GDRIVE CONTAINER ═══ */
        .gdrive-container, .lakin-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .gdrive-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .gdrive-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-tahun {
            padding: 5px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            background: white;
            color: #4a5568;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }
        .filter-tahun.active, .filter-tahun:hover {
            background: #1e293b; color: white; border-color: #1e293b;
        }

        .btn-tambah-dok {
            background: #0ea5e9;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }
        .btn-tambah-dok:hover { background: #1e293b; }

        .dok-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .dok-card {
            background: white;
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            transition: all 0.2s;
            position: relative;
        }
        .dok-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); transform: translateY(-2px); }

        .dok-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
        .dok-card-title { font-size: 0.88rem; font-weight: 700; color: #1a202c; flex: 1; }
        .dok-card-tahun {
            font-size: 0.7rem; font-weight: 700;
            background: #ebf8ff; color: #1e3a8a;
            padding: 2px 8px; border-radius: 10px;
            white-space: nowrap; flex-shrink: 0;
        }
        .dok-card-ket { font-size: 0.76rem; color: #718096; }

        .dok-card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 0.6rem; border-top: 1px solid #f0f4f8; }

        .btn-buka-gdrive {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: #1e293b; color: white;
            padding: 5px 14px; border-radius: 8px;
            font-size: 0.76rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-buka-gdrive:hover { background: #1a365d; }

        .dok-admin-actions { display: flex; gap: 0.4rem; }
        .btn-dok-edit, .btn-dok-hapus {
            background: none; border: none; cursor: pointer;
            font-size: 0.9rem; padding: 4px;
            border-radius: 6px; transition: all 0.2s;
        }
        .btn-dok-edit:hover  { background: #ebf8ff; }
        .btn-dok-hapus:hover { background: #fff5f5; }

        .loading-state {
            text-align: center; padding: 3rem; color: #a0aec0; font-size: 0.88rem;
        }

        .empty-dok {
            grid-column: 1 / -1;
            text-align: center; padding: 3rem 1rem;
            color: #a0aec0; font-size: 0.88rem;
        }
        .empty-dok .empty-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* ═══ LAKIN FILE CARDS ═══ */
        .lakin-card {
            background: white;
            border-radius: 14px;
            padding: 1rem 1.4rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s;
        }
        .lakin-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }

        .lakin-file-icon { font-size: 1.8rem; flex-shrink: 0; }

        .lakin-file-body { flex: 1; min-width: 0; }
        .lakin-file-title { font-size: 0.88rem; font-weight: 700; color: #1a202c; }
        .lakin-file-meta { font-size: 0.74rem; color: #718096; margin-top: 2px; }

        .lakin-file-actions { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; }

        .btn-download {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: #48bb78; color: white;
            padding: 6px 14px; border-radius: 8px;
            font-size: 0.76rem; font-weight: 600;
            text-decoration: none; border: none; cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s;
        }
        .btn-download:hover { background: #38a169; }

        .btn-hapus-lakin {
            background: none; border: none; cursor: pointer;
            font-size: 1rem; padding: 6px;
            border-radius: 8px; transition: all 0.2s;
            color: #fc8181;
        }
        .btn-hapus-lakin:hover { background: #fff5f5; color: #c53030; }

        /* ═══ MODAL GDRIVE TAMBAH ═══ */
        .gdrive-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,30,60,0.45);
            backdrop-filter: blur(3px);
            z-index: 3000;
            align-items: center; justify-content: center;
        }
        .gdrive-modal-overlay.show { display: flex; }

        .gdrive-modal {
            background: white;
            border-radius: 20px;
            width: 500px; max-width: 92vw;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: modalPop 0.28s cubic-bezier(0.34,1.56,0.64,1);
            overflow: hidden;
        }

        .gdrive-modal-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid #edf2f7;
            background: #f8fafc;
        }
        .gdrive-modal-head h3 { font-size: 1rem; font-weight: 700; color: #1a202c; margin:0; }

        .gdrive-modal-body { padding: 1.4rem 1.6rem; display: flex; flex-direction: column; gap: 1rem; }
        .gdrive-modal-foot { padding: 1rem 1.6rem; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 0.6rem; }

        .form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #4a5568; margin-bottom: 0.3rem; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            color: #2d3748;
            transition: border 0.2s;
            background: white;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(66,153,225,0.15);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }

        .btn-save { background: #1e293b; color: white; border: none; padding: 8px 22px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .btn-save:hover { background: #1a365d; }
        .btn-cancel { background: #edf2f7; color: #4a5568; border: none; padding: 8px 18px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .btn-cancel:hover { background: #e2e8f0; }

        /* Upload LAKIN modal */
        .file-input-wrapper { position: relative; }
        .file-input-wrapper input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .file-input-display {
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            padding: 1.2rem;
            text-align: center;
            font-size: 0.82rem;
            color: #718096;
            cursor: pointer;
            transition: all 0.2s;
        }
        .file-input-display:hover { border-color: #0ea5e9; color: #1e293b; }
        .file-input-display.has-file { border-color: #48bb78; color: #276749; background: #f0fff4; }


        /* ═══ FOOTER ═══ */
        .sidebar-footer {
            padding: 1.2rem;
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            margin-top: 1rem;
        }

        .sidebar-footer .logo-footer {
            width: 48px;
            height: 48px;
            margin: 0 auto 0.6rem;
            background: linear-gradient(135deg, #1e293b 0%, #1e40af 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 12px rgba(44,82,130,0.2);
        }

        .sidebar-footer .copyright {
            font-size: 0.72rem;
            color: #718096;
            line-height: 1.5;
            font-weight: 500;
        }

        .sidebar-footer .copyright strong {
            color: #1e293b;
            font-weight: 600;
        }



        /* ═══ MAIN FOOTER FIXED ═══ */
        .main-footer {
            position: fixed;
            bottom: 0;
            right: 0;
            left: 280px; /* Lebar sidebar */
            padding: 0.8rem 2rem;
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            z-index: 100;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        }

        .main-footer .copyright-main {
            font-size: 0.8rem;
            color: #718096;
        }

        .main-footer .copyright-main strong {
            color: #1e293b;
            font-weight: 600;
        }

        /* Tambah padding bawah main content agar tidak tertutup footer */
        .main-content {
            padding-bottom: 60px;
        }
        /* Upload Method Toggle */
        .upload-method-toggle {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        
        .method-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .method-btn:hover {
            border-color: #4285f4;
            background: #f8f9fa;
        }
        
        .method-btn.active {
            border-color: #4285f4;
            background: #e8f0fe;
            color: #1967d2;
        }
        
        #gdriveUploadArea input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        #gdriveUploadArea input:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }
        
        /* Badge Method */
        .badge-gdrive, .badge-file {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 500;
            border-radius: 4px;
            margin-left: 8px;
        }
        
        .badge-gdrive {
            background: #34a853;
            color: white;
        }
        
        .badge-file {
            background: #4285f4;
            color: white;
        }

    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <div class="logo"><img src="data:image/png;base64,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" alt="BPS" style="width:100%;height:100%;object-fit:contain;"></div>
            <div class="logo-text">
                <h1>SOKAB</h1>
                <p>SAKIP Online BPS Kota Bima</p>
            </div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="user-role"><?php echo ucfirst($user_role); ?></span>
            </div>
            <a href="includes/logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">
                <span>🚪</span> Keluar
            </a>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Menu Dokumen</h2>
                <p>BPS Kota Bima</p>
            </div>

            <div class="menu-list">

                <!-- BERANDA -->
                <div class="menu-item">
                    <div class="menu-link active" onclick="showHome()">
                        <div class="menu-icon">🏠</div>
                        <div class="menu-text"><h3>Beranda</h3><p>Halaman Utama</p></div>
                    </div>
                </div>

                <!-- PERENCANAAN (header) -->
                <div class="menu-section-header">📋 Perencanaan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('renstra')">
                        <div class="menu-icon">🌱</div>
                        <div class="menu-text"><h3>Renstra</h3><p>Rencana Strategis</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('perjanjian-kinerja')">
                        <div class="menu-icon">📝</div>
                        <div class="menu-text"><h3>Perjanjian Kinerja</h3><p>Dokumen Komitmen</p></div>
                    </div>
                </div>

                <!-- PENGUKURAN (header) -->
                <div class="menu-section-header">📊 Pengukuran</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-kinerja')">
                        <div class="menu-icon">📈</div>
                        <div class="menu-text"><h3>Monitoring Capaian Kinerja</h3><p>Triwulanan</p></div>
                    </div>
                </div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('monitoring-renstra')">
                        <div class="menu-icon">🎯</div>
                        <div class="menu-text"><h3>Monitoring Capaian Renstra</h3><p>Capaian Akhir Renstra</p></div>
                    </div>
                </div>



                <!-- PELAPORAN (header) -->
                <div class="menu-section-header">📄 Pelaporan</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link has-dropdown" onclick="toggleSubmenu(this)">
                        <div class="menu-icon">⚒️</div>
                        <div class="menu-text"><h3>LAKIN</h3><p>Laporan Akuntabilitas</p></div>
                        <span class="menu-arrow">▶</span>
                    </div>
                    <div class="submenu">
                        <div class="submenu-item" onclick="showPage('lakin-draft')">📝 Draft</div>
                        <div class="submenu-item" onclick="showPage('lakin-final')">✅ Final</div>
                    </div>
                </div>

                <!-- EVALUASI (header) -->
                <div class="menu-section-header">🔍 Evaluasi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('permindok')">
                        <div class="menu-icon">📄</div>
                        <div class="menu-text"><h3>Permintaan Dokumen</h3><p>Permindok by Tahun</p></div>
                    </div>
                </div>
                <!-- -->

                <!-- APLIKASI MONITORING (header) -->
                <div class="menu-section-header">💻 Aplikasi Monitoring</div>
                <div class="menu-item menu-sub">
                    <a href="https://esr.menpan.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon">🔍</div>
                        <div class="menu-text"><h3>ESR</h3><p>Evaluasi Sistem Review</p></div>
                    </a>
                </div>
                <div class="menu-item menu-sub">
                    <a href="https://sinergi.web.bps.go.id" target="_blank" class="menu-link">
                        <div class="menu-icon">🤝</div>
                        <div class="menu-text"><h3>SINERGI</h3><p>Kolaborasi Unit</p></div>
                    </a>
                </div>

                <!-- MATERI & PANDUAN -->
                <div class="menu-section-header">📚 Referensi</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('materi-panduan')">
                        <div class="menu-icon">✨</div>
                        <div class="menu-text"><h3>Materi &amp; Panduan</h3><p>Referensi SAKIP</p></div>
                    </div>
                </div>

            </div>

            <?php if ($user_role === 'admin'): ?>
                <!-- ADMIN (header) -->
                <div class="menu-section-header">⚙️ Admin</div>
                <div class="menu-item menu-sub">
                    <div class="menu-link" onclick="showPage('kelola-user')">
                        <div class="menu-icon">👥</div>
                        <div class="menu-text"><h3>Kelola User</h3><p>Manajemen Pengguna</p></div>
                    </div>
                </div>
                <?php endif; ?>

            <div class="sidebar-footer">
                <div class="logo-footer"><img src="data:image/png;base64,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" alt="BPS" style="width:32px;height:32px;object-fit:contain;"></div>
                
                

                <div class="copyright">
                    <strong>BPS Kota Bima</strong><br>
                    SOKAB v1.0 · © 2026
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- ═══════════════════════════════ HOME ═══════════════════════════════ -->
            <div id="page-home" class="content-page active">
                <div class="hero" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); border-radius: 16px; padding: 2.5rem 3rem; margin-bottom: 0;">
                    <h1 style="color:white; font-size:2rem; margin-bottom:0.5rem"><span style="color:#f6ad55">SOKAB</span> — SAKIP Online BPS Kota Bima</h1>
                    <p style="color:rgba(255,255,255,0.85); font-size:0.95rem; margin:10">Sistem Manajemen SAKIP Terintegrasi BPS Kota Bima</p>
                </div>

                <!-- Stats ringkas -->
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-number" id="statBelum">-</div><div class="stat-label">Belum Mulai</div></div>
                    <div class="stat-card"><div class="stat-icon">🔄</div><div class="stat-number" id="statProses">-</div><div class="stat-label">Sedang Berjalan</div></div>
                    <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number" id="statSelesai">-</div><div class="stat-label">Selesai</div></div>
                    <div class="stat-card"><div class="stat-icon">🚨</div><div class="stat-number" id="statDeadline">-</div><div class="stat-label">Deadline 30 Hari</div></div>
                </div>

                <!-- Kalender + Panel Jadwal -->
                <div class="jadwal-wrapper">
                    <!-- Kalender -->
                    <div class="kalender-card">
                        <h3>📅 Kalender Jadwal SAKIP</h3>
                        <div id="kalender"></div>
                    </div>

                    <!-- Panel kanan: jadwal mendatang + kelola -->
                    <div class="jadwal-panel">

                        <!-- Kelola jadwal (admin only) -->
                        <?php if ($user_role === 'admin'): ?>
                        <div class="jadwal-panel-card">
                            <h3>
                                ⚙️ Kelola Jadwal
                                <button class="btn-tambah-jadwal" onclick="bukaModalTambah()">+ Tambah</button>
                            </h3>
                            <div class="jadwal-list" id="listSemuaJadwal">
                                <div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Memuat jadwal...</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Jadwal mendatang -->
                        <div class="jadwal-panel-card">
                            <h3>🔔 Deadline Mendatang <span style="font-size:0.72rem;color:#718096;font-weight:400">(30 hari)</span></h3>
                            <div class="jadwal-list" id="listUpcoming">
                                <div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Memuat...</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════ RENSTRA ═══════════════════════════════ -->
            <div id="page-renstra" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#e9d8fd">🌱</div>
                    <div><h2 class="page-title">Renstra</h2><p class="page-subtitle">Rencana Strategis BPS Kota Bima</p></div>
                </div>
                <div id="content-renstra" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ PERJANJIAN KINERJA ═══════════════════ -->
            <div id="page-perjanjian-kinerja" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#bee3f8">📝</div>
                    <div><h2 class="page-title">Perjanjian Kinerja</h2><p class="page-subtitle">Dokumen Komitmen Kinerja BPS Kota Bima</p></div>
                </div>
                <div id="content-perjanjian-kinerja" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ MONITORING KINERJA ════════════════════ -->
            <div id="page-monitoring-kinerja" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#c6f6d5">📈</div>
                    <div><h2 class="page-title">Monitoring Capaian Kinerja</h2><p class="page-subtitle">Laporan Monitoring Periodik</p></div>
                </div>
                <!-- Container Dokumen Monitoring -->
                <div id="content-monitoring-kinerja" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
                
                <!-- Container IKSS (Terpisah agar tidak tertimpa) -->
                <div id="container-ikss" class="gdrive-container" style="margin-top: 2rem;">
                    <div class="card-grid">
                        <div class="stat-card" style="grid-column: 1/-1; background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <div>
                                    <h3 style="font-size: 1.25rem; color: #1e293b; margin-bottom: 0.5rem; font-weight: 600;">📊 Tabel IKSS</h3>
                                    <p style="color: #64748b; font-size: 0.9rem;">Indikator Kinerja Sasaran Strategis</p>
                                </div>
                                <div style="display: flex; gap: 0.75rem; align-items: center;">
                                    <select id="ikssFilterTahun" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; background: white;">
                                        <!-- Tahun akan di-generate oleh JavaScript -->
                                    </select>
                                    <button id="ikssFilterTW1" class="ikss-tw-btn active" data-tw="TW I" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: #1e40af; color: white; cursor: pointer; font-weight: 500;">TW I</button>
                                    <button id="ikssFilterTW2" class="ikss-tw-btn" data-tw="TW II" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW II</button>
                                    <button id="ikssFilterTW3" class="ikss-tw-btn" data-tw="TW III" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW III</button>
                                    <button id="ikssFilterTW4" class="ikss-tw-btn" data-tw="TW IV" style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; background: white; color: #64748b; cursor: pointer;">TW IV</button>
                                    <?php if($user_role==='admin'): ?>
                                    <button onclick="kelolaIKSS()" style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500;">⚙️ Kelola IKSS</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Pagination controls -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <span style="color: #64748b; font-size: 0.9rem;">Tampilkan:</span>
                                    <select id="ikssPerPage" style="padding: 0.35rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.85rem;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="-1">Semua</option>
                                    </select>
                                    <span style="color: #64748b; font-size: 0.9rem;">per halaman</span>
                                </div>
                                <div id="ikssPaginationInfo" style="color: #64748b; font-size: 0.9rem;"></div>
                                <div id="ikssPaginationButtons" style="display: flex; gap: 0.5rem;"></div>
                            </div>
                            
                            <div style="overflow-x: auto;">
                                <table id="tableIKSS" style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                        <tr>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 50px;">No</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569;">Sasaran Kegiatan</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569;">Indikator Kinerja</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 200px;">Link Dokumen Sumber</th>
                                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #475569; width: 250px;">Link Bukti Tindak Lanjut TW Sebelumnya</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ikssTableBody">
                                        <tr>
                                            <td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                                Memuat data IKSS...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination Container -->
                            <div id="paginationContainer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════ MONITORING RENSTRA ════════════════════ -->
            <div id="page-monitoring-renstra" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fefcbf">🎯</div>
                    <div><h2 class="page-title">Monitoring Capaian Renstra</h2><p class="page-subtitle">Laporan Capaian Renstra</p></div>
                </div>
                <div id="content-monitoring-renstra" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ PERMINDOK ═══════════════════════════════ -->
            <div id="page-permindok" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#dbeafe">📄</div>
                    <div><h2 class="page-title">Permintaan Dokumen</h2><p class="page-subtitle">Kelola permintaan dokumen berdasarkan tahun</p></div>
                </div>

                <!-- Toolbar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <label style="font-weight: 500; color: #64748b;">Tahun:</label>
                        <select id="filterPermindokTahun" 
                                onchange="loadPermindokData(this.value)"
                                style="padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.95rem; cursor: pointer;">
                        </select>
                        <script>
                        // Populate tahun IMMEDIATELY (inline)
                        (function() {
                            const sel = document.getElementById('filterPermindokTahun');
                            const cy = new Date().getFullYear();
                            for (let y = cy + 5; y >= 2020; y--) {
                                const opt = document.createElement('option');
                                opt.value = y;
                                opt.text = y;
                                if (y === cy) opt.selected = true;
                                sel.add(opt);
                            }
                        })();
                        </script>
                    </div>
                    
                    <?php if ($user_role === 'admin'): ?>
                    <button onclick="kelolaPermindok()" 
                            style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                        ⚙️ Kelola Permindok
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Main Table -->
                <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <!-- Pagination Controls Top -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="color: #64748b; font-size: 0.875rem;">Tampilkan:</span>
                            <select onchange="changePermindokRowsPerPage(this.value)" 
                                    style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span style="color: #64748b; font-size: 0.875rem;">per halaman</span>
                        </div>
                        <div id="permindokPaginationInfo" style="color: #64748b; font-size: 0.875rem;"></div>
                    </div>

                    <!-- Table -->
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <th style="padding: 1rem; text-align: center; border-top-left-radius: 8px; width: 80px; font-size: 0.9rem;">No</th>
                                    <th style="padding: 1rem; text-align: center; font-size: 0.9rem;">Judul Permindok</th>
                                    <th style="padding: 1rem; text-align: center; border-top-right-radius: 8px; width: 250px; font-size: 0.9rem;">Link Permindok</th>
                                </tr>
                            </thead>
                            <tbody id="permindokTableBody" style="background: white;">
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
                                        <div style="font-size: 1.1rem; font-weight: 500; color: #64748b;">Loading data...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls Bottom -->
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 1.5rem; gap: 0.5rem;" id="permindokPagination"></div>
                </div>
            </div>

                                    <!-- ═══════════════════════════════ LAKIN DRAFT ═══════════════════════════ -->
            <div id="page-lakin-draft" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fefcbf">📝</div>
                    <div><h2 class="page-title">LAKIN — Draft</h2><p class="page-subtitle">Draft Laporan Akuntabilitas Kinerja Instansi Pemerintah</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('draft')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-draft" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ LAKIN FINAL ═══════════════════════════ -->
            <div id="page-lakin-final" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#c6f6d5">✅</div>
                    <div><h2 class="page-title">LAKIN — Final</h2><p class="page-subtitle">LAKIN Final yang telah disahkan</p></div>
<?php if($user_role==='admin'): ?>
                    <button class="btn-upload-lakin" onclick="bukaUploadLakin('final')">⬆️ Upload File</button>
                    <?php endif; ?>
                </div>
                <div id="content-lakin-final" class="lakin-container">
                    <div class="loading-state">Memuat file...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ EVALUASI PERMINDOK ═══════════════════ -->
            <div id="page-evaluasi-permindok" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#e9d8fd">📑</div>
                    <div><h2 class="page-title">Evaluasi Per Permindok</h2><p class="page-subtitle">Evaluasi berdasarkan Permenpan RB</p></div>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn-tambah-jadwal" onclick="bukaModalUploadPermindok()">+ Upload Dokumen</button>
                    <?php endif; ?>
                </div>
                <div id="content-evaluasi-permindok">
                    <table id="table-permindok" class="data-table" style="margin-top:1rem">
                        <thead>
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Judul Dokumen</th>
                                <th style="width:100px;text-align:center">Tahun</th>
                                <th style="width:120px;text-align:center">Metode</th>
                                <th style="width:150px;text-align:center">Tanggal Upload</th>
                                <th style="width:120px;text-align:center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" style="text-align:center;padding:2rem">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══════════════════════════════ EVALUASI TAHUN ═══════════════════════ -->
            <div id="page-evaluasi-tahun" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fed7d7">📅</div>
                    <div><h2 class="page-title">Evaluasi Per Tahun</h2><p class="page-subtitle">Rekap evaluasi tahunan SAKIP</p></div>
                </div>
                <div id="content-evaluasi-tahun" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
            </div>

            <!-- ═══════════════════════════════ MATERI & PANDUAN ═════════════════════ -->
            <div id="page-materi-panduan" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#bee3f8">✨</div>
                    <div><h2 class="page-title">Materi &amp; Panduan</h2><p class="page-subtitle">Referensi dan panduan pelaksanaan SAKIP</p></div>
                </div>
                <div id="content-materi-panduan" class="gdrive-container">
                    <div class="loading-state">Memuat dokumen...</div>
                </div>
            </div>

            <?php if ($user_role === 'admin'): ?>
            <!-- ═══════════════════════════════ KELOLA USER ═══════════════════════════ -->
            <div id="page-kelola-user" class="content-page">
                <div class="page-header-bar">
                    <div class="page-header-icon" style="background:#fbd38d">👥</div>
                    <div><h2 class="page-title">Kelola User</h2><p class="page-subtitle">Manajemen Pengguna Sistem</p></div>
                    <button class="btn-tambah-jadwal" onclick="bukaModalTambahUser()">+ Tambah User</button>
                </div>
                
                <div class="content-wrap">
                    <!-- Stats User -->
                    <div class="stats-grid" style="margin-bottom:1.5rem">
                        <div class="stat-card stat-blue">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <div class="stat-label">Total User</div>
                                <div class="stat-value" id="statTotalUser">0</div>
                            </div>
                        </div>
                        <div class="stat-card stat-green">
                            <div class="stat-icon">👑</div>
                            <div class="stat-info">
                                <div class="stat-label">Admin</div>
                                <div class="stat-value" id="statAdmin">0</div>
                            </div>
                        </div>
                        <div class="stat-card stat-orange">
                            <div class="stat-icon">👤</div>
                            <div class="stat-info">
                                <div class="stat-label">User</div>
                                <div class="stat-value" id="statUser">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Table User -->
                    <div style="background:white;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);overflow:hidden">
                        <table class="user-table" id="tableUser">
                            <thead>
                                <tr>
                                    <th style="width:50px">No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th style="width:100px">Role</th>
                                    <th style="width:180px">Last Login</th>
                                    <th style="width:200px;text-align:center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <tr><td colspan="6" style="text-align:center;padding:2rem;color:#999">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>


        <footer class="main-footer">
            <div class="copyright-main">
                <strong>BPS Kota Bima</strong> · Manajemen & Monitoring SAKIP Online · © 2026
            </div>
        </footer>
        </main>
    </div>

    <!-- Modal Tambah/Edit Jadwal -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalJadwalTitle">📅 Tambah Jadwal SAKIP</h2>
                <button class="modal-close" onclick="tutupModal()">✕</button>
            </div>
            <form class="modal-form" id="formTambahJadwal">
                <input type="hidden" id="inp_jadwal_id">
                <div class="form-group">
                    <label>Judul Jadwal *</label>
                    <input type="text" id="inp_judul" placeholder="Contoh: FRA TW1 2026" required>
                </div>
                <div class="form-group">
                    <label>Kategori *</label>
                    <select id="inp_kategori">
                        <option value="FRA">FRA</option>
                        <option value="KKPK">KKPK</option>
                        <option value="POK">POK</option>
                        <option value="PKPT">PKPT</option>
                        <option value="Renstra">Renstra</option>
                        <option value="LAKIN">LAKIN</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai *</label>
                        <input type="date" id="inp_mulai" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Selesai *</label>
                        <input type="date" id="inp_selesai" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea id="inp_deskripsi" rows="3" placeholder="Keterangan tambahan..."></textarea>
                </div>
                <button type="submit" class="btn-submit-modal" id="btnSimpanJadwal">💾 Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <!-- Modal Detail Jadwal (klik event kalender) -->
    <div class="modal-overlay" id="modalDetail">
        <div class="modal-box">
            <div class="modal-header">
                <h2>📋 Detail Jadwal</h2>
                <button class="modal-close" onclick="document.getElementById('modalDetail').classList.remove('show')">✕</button>
            </div>
            <div id="detailKonten"></div>
        </div>
    </div>

    <!-- Popup Deadline -->
    <div class="popup-deadline" id="popupDeadline">
        <div class="popup-header">
            <h4>🚨 Deadline Dalam 30 Hari!</h4>
            <button class="popup-close" onclick="tutupPopup()">✕</button>
        </div>
        <div id="popupList"></div>
        <div class="popup-footer">
            <button class="popup-dismiss" onclick="tutupPopup()">Saya Mengerti</button>
        </div>
    </div>

    <?php if ($user_role === 'admin'): ?>
    <!-- Modal Tambah/Edit User -->
    <div class="modal-overlay" id="modalUser">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalUserTitle">👤 Tambah User</h2>
                <button class="modal-close" onclick="tutupModalUser()">✕</button>
            </div>
            <form class="modal-form" id="formUser">
                <input type="hidden" id="inp_user_id">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="inp_username" placeholder="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" id="inp_nama_lengkap" placeholder="Nama Lengkap" required>
                </div>
                <div class="form-group" id="groupPassword">
                    <label>Password *</label>
                    <input type="password" id="inp_password" placeholder="Minimal 6 karakter" autocomplete="new-password">
                    <small style="color:#666;display:block;margin-top:5px">Minimal 6 karakter</small>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select id="inp_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit-modal" id="btnSimpanUser">💾 Simpan User</button>
            </form>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal-overlay" id="modalResetPassword">
        <div class="modal-box" style="max-width:400px">
            <div class="modal-header">
                <h2>🔑 Reset Password</h2>
                <button class="modal-close" onclick="tutupModalResetPassword()">✕</button>
            </div>
            <form class="modal-form" id="formResetPassword">
                <input type="hidden" id="inp_reset_user_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="inp_reset_username" readonly style="background:#f7fafc">
                </div>
                <div class="form-group">
                    <label>Password Baru *</label>
                    <input type="password" id="inp_new_password" placeholder="Minimal 6 karakter" required autocomplete="new-password">
                    <small style="color:#666;display:block;margin-top:5px">Minimal 6 karakter</small>
                </div>
                <button type="submit" class="btn-submit-modal">🔑 Reset Password</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script>
        // ===== DATA & STATE =====
        let semuaJadwal = [];
        let kalender;
        const isAdmin = <?= json_encode($user_role === 'admin') ?>;

        // ===== INIT =====
        document.addEventListener('DOMContentLoaded', function() {
            initKalender();
            loadSemuaJadwal();
            loadUpcoming();
            setTimeout(cekPopupDeadline, 1500);
        });

        // ===== KALENDER =====
        function initKalender() {
            const el = document.getElementById('kalender');
            kalender = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                buttonText: { today: 'Hari Ini', month: 'Bulan', list: 'Daftar' },
                height: 480,
                events: function(info, success, fail) {
                    fetch('api/jadwal.php?action=kalender')
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                success(res.data.map(j => ({
                                    id: j.id,
                                    title: j.judul,
                                    start: j.start,
                                    end: j.end,
                                    color: j.color,
                                    extendedProps: { status: j.status, kategori: j.kategori }
                                })));
                            }
                        }).catch(fail);
                },
                eventClick: function(info) {
                    const e = info.event;
                    tampilDetail(e.id, e.title, e.startStr, e.endStr, e.extendedProps.status, e.extendedProps.kategori, e.backgroundColor);
                },
                eventMouseEnter: function(info) {
                    info.el.style.opacity = '0.85';
                    info.el.title = info.event.title;
                },
                eventMouseLeave: function(info) {
                    info.el.style.opacity = '1';
                }
            });
            kalender.render();
        }

        // ===== LOAD SEMUA JADWAL =====
        function loadSemuaJadwal() {
            fetch('api/jadwal.php')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    semuaJadwal = res.data;
                    updateStats(res.data);
                    if (isAdmin) renderListJadwal(res.data);
                });
        }

        // ===== UPDATE STATS =====
        function updateStats(data) {
            const belum   = data.filter(j => j.status === 'belum').length;
            const proses  = data.filter(j => j.status === 'proses').length;
            const selesai = data.filter(j => j.status === 'selesai').length;
            const now     = new Date();
            const in30    = new Date(); in30.setDate(in30.getDate() + 30);
            const deadline = data.filter(j => {
                const end = new Date(j.tanggal_selesai);
                return end >= now && end <= in30 && j.status !== 'selesai';
            }).length;
            document.getElementById('statBelum').textContent   = belum;
            document.getElementById('statProses').textContent  = proses;
            document.getElementById('statSelesai').textContent = selesai;
            document.getElementById('statDeadline').textContent = deadline;
        }

        // ===== RENDER LIST JADWAL (admin) =====
        function renderListJadwal(data) {
            const el = document.getElementById('listSemuaJadwal');
            if (!data.length) {
                el.innerHTML = '<div style="text-align:center;padding:1rem;color:#718096;font-size:0.85rem">Belum ada jadwal</div>';
                return;
            }
            el.innerHTML = data.map(j => `
                <div class="jadwal-item" style="border-left-color:${j.warna}">
                    <div class="jadwal-dot" style="background:${j.warna}"></div>
                    <div class="jadwal-info">
                        <div class="judul">${escHtml(j.judul)}</div>
                        <div class="tanggal">
                            <span class="kat-badge">${j.kategori}</span>
                            ${formatTgl(j.tanggal_mulai)} – ${formatTgl(j.tanggal_selesai)}
                        </div>
                    </div>
                    <div class="jadwal-actions">
                        <button class="btn-status btn-status-${j.status}" onclick="gantiStatus(${j.id}, '${j.status}')" title="Ganti status">
                            ${labelStatus(j.status)}
                        </button>
                        <button class="btn-edit-item" onclick="editJadwal(${j.id})" title="Edit">✏️</button>
                        <button class="btn-hapus-item" onclick="hapusJadwal(${j.id})" title="Hapus">🗑️</button>
                    </div>
                </div>
            `).join('');
        }

        // ===== LOAD UPCOMING =====
        function loadUpcoming() {
            fetch('api/jadwal.php?action=upcoming')
                .then(r => r.json())
                .then(res => {
                    const el = document.getElementById('listUpcoming');
                    if (!res.success || !res.data.length) {
                        el.innerHTML = '<div style="text-align:center;padding:1rem;color:#48bb78;font-size:0.85rem">✅ Tidak ada deadline dalam 30 hari ke depan</div>';
                        return;
                    }
                    const now = new Date();
                    el.innerHTML = res.data.map(j => {
                        const sisa = Math.ceil((new Date(j.tanggal_selesai) - now) / 86400000);
                        const cls  = sisa <= 7 ? 'sisa-danger' : 'sisa-warning';
                        return `
                        <div class="jadwal-item" style="border-left-color:${j.warna}">
                            <div class="jadwal-dot" style="background:${j.warna}"></div>
                            <div class="jadwal-info">
                                <div class="judul">${escHtml(j.judul)}</div>
                                <div class="tanggal">
                                    <span class="kat-badge">${j.kategori}</span>
                                    Deadline: ${formatTgl(j.tanggal_selesai)}
                                </div>
                                <div class="psisa ${cls}">⏰ ${sisa} hari lagi</div>
                            </div>
                            <span class="status-badge status-${j.status}">${labelStatus(j.status)}</span>
                        </div>`;
                    }).join('');
                });
        }

        // ===== POPUP DEADLINE =====
        function cekPopupDeadline() {
            if (sessionStorage.getItem('sokab_popup_dismissed')) return;
            fetch('api/jadwal.php?action=upcoming')
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data.length) return;
                    const now = new Date();
                    const list = document.getElementById('popupList');
                    list.innerHTML = res.data.slice(0, 4).map(j => {
                        const sisa = Math.ceil((new Date(j.tanggal_selesai) - now) / 86400000);
                        const cls  = sisa <= 7 ? 'sisa-danger' : 'sisa-warning';
                        return `
                        <div class="popup-item">
                            <div class="popup-item-dot" style="background:${j.warna}"></div>
                            <div class="popup-item-info">
                                <div class="pjudul">${escHtml(j.judul)}</div>
                                <div class="pdate">Deadline: ${formatTgl(j.tanggal_selesai)}</div>
                                <div class="psisa ${cls}">⏰ ${sisa} hari lagi</div>
                            </div>
                        </div>`;
                    }).join('');
                    document.getElementById('popupDeadline').classList.add('show');
                });
        }

        function tutupPopup() {
            document.getElementById('popupDeadline').classList.remove('show');
            sessionStorage.setItem('sokab_popup_dismissed', '1');
        }

        // ===== MODAL TAMBAH =====
        function bukaModalTambah() {
            // Reset form untuk mode tambah baru
            document.getElementById('formTambahJadwal').reset();
            document.getElementById('inp_jadwal_id').value = '';
            document.getElementById('modalJadwalTitle').textContent = '📅 Tambah Jadwal SAKIP';
            document.getElementById('btnSimpanJadwal').textContent = '💾 Simpan Jadwal';
            document.getElementById('modalTambah').classList.add('show');
        }

        function editJadwal(id) {
            // Cari data jadwal dari array semuaJadwal
            const jadwal = semuaJadwal.find(j => j.id == id);
            if (!jadwal) {
                showToast('❌ Data jadwal tidak ditemukan', 'error');
                return;
            }

            // Isi form dengan data yang akan diedit
            document.getElementById('inp_jadwal_id').value = jadwal.id;
            document.getElementById('inp_judul').value = jadwal.judul;
            document.getElementById('inp_kategori').value = jadwal.kategori;
            document.getElementById('inp_mulai').value = jadwal.tanggal_mulai;
            document.getElementById('inp_selesai').value = jadwal.tanggal_selesai;
            document.getElementById('inp_deskripsi').value = jadwal.deskripsi || '';
            
            // Ubah judul modal dan text button
            document.getElementById('modalJadwalTitle').textContent = '✏️ Edit Jadwal SAKIP';
            document.getElementById('btnSimpanJadwal').textContent = '💾 Update Jadwal';
            
            // Buka modal
            document.getElementById('modalTambah').classList.add('show');
        }

        function tutupModal() {
            document.getElementById('modalTambah').classList.remove('show');
            document.getElementById('formTambahJadwal').reset();
            document.getElementById('inp_jadwal_id').value = '';
        }

        document.getElementById('formTambahJadwal').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('inp_jadwal_id').value;
            const isEdit = !!id; // true jika ada id (mode edit)
            
            const payload = {
                judul:          document.getElementById('inp_judul').value.trim(),
                kategori:       document.getElementById('inp_kategori').value,
                tanggal_mulai:  document.getElementById('inp_mulai').value,
                tanggal_selesai:document.getElementById('inp_selesai').value,
                deskripsi:      document.getElementById('inp_deskripsi').value.trim(),
            };

            // Validasi tanggal
            if (payload.tanggal_selesai < payload.tanggal_mulai) {
                showToast('❌ Tanggal selesai tidak boleh lebih awal dari tanggal mulai', 'error');
                return;
            }
            
            // Jika edit, tambahkan id ke payload
            if (isEdit) {
                payload.id = parseInt(id);
            }
            
            const action = isEdit ? 'edit' : 'tambah';
            const successMsg = isEdit ? '✅ Jadwal berhasil diperbarui!' : '✅ Jadwal berhasil ditambahkan!';
            
            fetch(`api/jadwal.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    tutupModal();
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast(successMsg, 'success');
                } else {
                    showToast('❌ ' + res.message, 'error');
                }
            })
            .catch(err => {
                showToast('❌ Terjadi kesalahan: ' + err.message, 'error');
            });
        });

        // ===== GANTI STATUS =====
        function gantiStatus(id, statusSaat) {
            const urutan = ['belum', 'proses', 'selesai'];
            const next   = urutan[(urutan.indexOf(statusSaat) + 1) % urutan.length];
            fetch('api/jadwal.php?action=update_status', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, status: next})
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast(`🔄 Status diubah ke: ${labelStatus(next)}`, 'success');
                }
            });
        }

        // ===== HAPUS JADWAL =====
        function hapusJadwal(id) {
            if (!confirm('Yakin ingin menghapus jadwal ini?')) return;
            fetch('api/jadwal.php?action=hapus', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    kalender.refetchEvents();
                    loadSemuaJadwal();
                    loadUpcoming();
                    showToast('🗑️ Jadwal dihapus', 'success');
                }
            });
        }

        // ===== DETAIL JADWAL =====
        function tampilDetail(id, judul, start, end, status, kategori, warna) {
            document.getElementById('detailKonten').innerHTML = `
                <div style="border-left:5px solid ${warna};padding:1rem;background:#f7fafc;border-radius:10px;margin-bottom:1rem">
                    <div style="font-size:1rem;font-weight:700;color:#2d3748;margin-bottom:6px">${escHtml(judul)}</div>
                    <div style="font-size:0.82rem;color:#718096">
                        <span class="kat-badge">${kategori}</span>
                        📅 ${formatTgl(start)} – ${formatTgl(end || start)}
                    </div>
                    <div style="margin-top:8px"><span class="status-badge status-${status}">${labelStatus(status)}</span></div>
                </div>
                ${isAdmin ? `
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-top:0.5rem">
                    ${['belum','proses','selesai'].map(s => `
                    <button onclick="gantiStatus(${id},'${s==='selesai'?'proses':s==='proses'?'belum':'selesai'}'); document.getElementById('modalDetail').classList.remove('show')"
                        style="padding:8px;border:none;border-radius:8px;cursor:pointer;font-family:inherit;font-size:0.8rem;font-weight:600;
                        background:${s==='belum'?'#fed7d7':s==='proses'?'#fef3c7':'#c6f6d5'};
                        color:${s==='belum'?'#c53030':s==='proses'?'#92400e':'#276749'}">
                        ${labelStatus(s)}
                    </button>`).join('')}
                </div>` : ''}
            `;
            document.getElementById('modalDetail').classList.add('show');
        }

        // ===== TOAST =====
        function showToast(msg, type) {
            const t = document.createElement('div');
            t.style.cssText = `position:fixed;bottom:80px;right:24px;padding:10px 18px;border-radius:10px;font-size:0.85rem;font-weight:600;z-index:9999;animation:popupIn 0.3s ease;
                background:${type==='success'?'#c6f6d5':'#fed7d7'};color:${type==='success'?'#276749':'#c53030'};box-shadow:0 4px 15px rgba(0,0,0,0.15)`;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        // ===== UTILS =====
        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function formatTgl(tgl) {
            if (!tgl) return '-';
            const d = new Date(tgl);
            return d.toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'});
        }

        function labelStatus(s) {
            return {belum:'Belum', proses:'Proses', selesai:'Selesai'}[s] || s;
        }

        // Tutup modal klik overlay
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === el) el.classList.remove('show');
            });
        });

        // ===== FUNGSI SIDEBAR =====
        function toggleSubmenu(element) {
            const menuItem = element.parentElement;
            const wasOpen  = menuItem.classList.contains('open');

            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));

            if (!wasOpen) {
                menuItem.classList.add('open');
                element.classList.add('active');
            }
        }

        function toggleSubSubmenu(element, event) {
            if (event) event.stopPropagation();
            const wasOpen = element.classList.contains('open');

            const parent = element.closest('.submenu');
            if (parent) {
                parent.querySelectorAll('.submenu-item.has-sub').forEach(i => {
                    if (i !== element) i.classList.remove('open');
                });
            }

            wasOpen ? element.classList.remove('open') : element.classList.add('open');
        }

        function showPage(pageId) {
            document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));
            const target = document.getElementById('page-' + pageId);
            if (target) target.classList.add('active');

            document.querySelectorAll('.sub-submenu-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.submenu-item:not(.has-sub)').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.menu-item.menu-sub .menu-link').forEach(l => l.classList.remove('active'));

            if (event && event.currentTarget) event.currentTarget.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Load konten dinamis
            loadPageContent(pageId);
        }

        function showHome() {
            document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));
            document.getElementById('page-home').classList.add('active');

            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.submenu-item').forEach(i => i.classList.remove('open'));
            document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));

            document.querySelector('.menu-item:first-child .menu-link').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ═══════════════════════════════════════════════
        // SISTEM DOKUMEN GDRIVE
        // ═══════════════════════════════════════════════
        // isAdmin sudah dideklarasikan di atas

        // Map pageId ke menu_key API
        const PAGE_MENU_MAP = {
            'renstra':             'renstra',
            'perjanjian-kinerja':  'perjanjian_kinerja',
            'monitoring-kinerja':  'monitoring_kinerja',
            'monitoring-renstra':  'monitoring_renstra',
            'evaluasi-permindok':  'evaluasi_permindok',
            'materi-panduan':      'materi_panduan',
        };

        const LAKIN_PAGES = ['lakin-draft', 'lakin-final'];
        let dokumenCache = {};  // cache per menu_key
        let lakinCache   = {};  // cache per tipe

        // Panggil saat showPage
        function loadPageContent(pageId) {
            // IKSS page (monitoring-kinerja)
            if (pageId === 'monitoring-kinerja') {
                // Load IKSS dari ikss_functions.js
                if (typeof loadIKSSData === 'function') {
                    setTimeout(() => loadIKSSData('TW I'), 300);
                }
                // Juga load dokumen monitoring kinerja (kalau ada)
                loadGdriveContent(pageId, 'monitoring_kinerja');
                return;
            }
            
            // Cloud Link pages
            if (PAGE_MENU_MAP[pageId]) {
                loadGdriveContent(pageId, PAGE_MENU_MAP[pageId]);
            }
            // LAKIN pages
            if (pageId === 'lakin-draft')  loadLakinContent('draft');
            if (pageId === 'lakin-final')  loadLakinContent('final');
            // User management page
            if (pageId === 'kelola-user' && isAdmin) loadUsers();
        }

        // ── GDRIVE LOADER ──────────────────────────────
        function loadGdriveContent(pageId, menuKey, forceReload) {
            const container = document.getElementById('content-' + pageId);
            if (!container) return;

            if (dokumenCache[menuKey] && !forceReload) {
                renderGdrive(container, menuKey, dokumenCache[menuKey]);
                return;
            }

            container.innerHTML = '<div class="loading-state">⏳ Memuat dokumen...</div>';

            fetch('api/dokumen.php?menu=' + menuKey)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        dokumenCache[menuKey] = res.data;
                        renderGdrive(container, menuKey, res.data);
                    } else {
                        container.innerHTML = '<div class="loading-state">⚠️ Gagal memuat: ' + escHtml(res.message) + '</div>';
                    }
                })
                .catch(() => container.innerHTML = '<div class="loading-state">⚠️ Koneksi gagal</div>');
        }

        // State filter aktif per menuKey
        const filterState = {};

        function renderGdrive(container, menuKey, data) {
            const tahunSet = [...new Set(data.map(d => d.tahun).filter(Boolean))].sort((a,b) => b-a);
            const isMonKin = (menuKey === 'monitoring_kinerja');

            // Baris filter triwulan (khusus monitoring_kinerja)
            const twRow = isMonKin ? `
                <div class="gdrive-filter tw-filter" id="filter-tw-${menuKey}" style="margin-top:0.5rem">
                    <button class="filter-tahun active" onclick="filterTriwulan('${menuKey}', null, this)">Semua TW</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW1', this)">TW I</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW2', this)">TW II</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW3', this)">TW III</button>
                    <button class="filter-tahun" onclick="filterTriwulan('${menuKey}', 'TW4', this)">TW IV</button>
                </div>` : '';

            const toolbar = `
                <div class="gdrive-toolbar" style="flex-direction:column;align-items:flex-start;gap:0.5rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                        <div class="gdrive-filter" id="filter-${menuKey}">
                            <button class="filter-tahun active" onclick="filterTahun('${menuKey}', null, this)">Semua</button>
                            ${tahunSet.map(t => `<button class="filter-tahun" onclick="filterTahun('${menuKey}', ${t}, this)">${t}</button>`).join('')}
                        </div>
                        ${isAdmin ? `<button class="btn-tambah-dok" onclick="bukaGdriveModal('${menuKey}')">+ Tambah</button>` : ''}
                    </div>
                    ${twRow}
                </div>`;

            const grid = `<div class="dok-grid" id="grid-${menuKey}"></div>`;
            container.innerHTML = toolbar + grid;

            // Reset state filter
            filterState[menuKey] = { tahun: null, tw: null };
            applyFilter(menuKey);
        }

        function filterTahun(menuKey, tahun, btn) {
            btn.closest('.gdrive-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (!filterState[menuKey]) filterState[menuKey] = {};
            filterState[menuKey].tahun = tahun;
            applyFilter(menuKey);
        }

        function filterTriwulan(menuKey, tw, btn) {
            btn.closest('.tw-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (!filterState[menuKey]) filterState[menuKey] = {};
            filterState[menuKey].tw = tw;
            applyFilter(menuKey);
        }

        function applyFilter(menuKey) {
            const state  = filterState[menuKey] || {};
            const data   = dokumenCache[menuKey] || [];
            let filtered = data;

            if (state.tahun) filtered = filtered.filter(d => d.tahun == state.tahun);
            if (state.tw)    filtered = filtered.filter(d => (d.sub_kategori || '').startsWith(state.tw));

            renderDokGrid(menuKey, filtered, state.tahun);
        }

        function renderDokGrid(menuKey, data, tahun) {
            const grid = document.getElementById('grid-' + menuKey);
            if (!grid) return;

            if (!data.length) {
                grid.innerHTML = `<div class="empty-dok"><div class="empty-icon">📂</div><div>Belum ada dokumen${tahun ? ' untuk tahun ' + tahun : ''}</div>
                    ${isAdmin ? `<br><button class="btn-tambah-dok" onclick="bukaGdriveModal('${menuKey}')">+ Tambah Dokumen</button>` : ''}</div>`;
                return;
            }

            grid.innerHTML = data.map(d => {
                const uploadMethod = d.upload_method || 'gdrive';
                const methodBadge = uploadMethod === 'file' 
                    ? '<span class="badge-file">📁 File</span>' 
                    : '<span class="badge-gdrive">☁️ Link</span>';
                const btnAction = uploadMethod === 'file'
                    ? `<a href="api/dokumen.php?action=download&id=${d.id}" class="btn-buka-gdrive" target="_blank">⬇️ Download</a>`
                    : `<a href="${escHtml(d.url_gdrive || '')}" target="_blank" class="btn-buka-gdrive">🔗 Buka</a>`;
                
                return `
                <div class="dok-card">
                    <div class="dok-card-header">
                        <div class="dok-card-title">${escHtml(d.judul)} ${methodBadge}</div>
                        ${d.tahun ? `<span class="dok-card-tahun">${d.tahun}</span>` : ''}
                    </div>
                    ${d.sub_kategori ? (() => {
                        if (menuKey === 'monitoring_kinerja') {
                            const parts = d.sub_kategori.split('|');
                            const tw  = parts[0] ? `<span style="background:#ebf8ff;color:#1e3a8a;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:700;margin-right:4px">${escHtml(parts[0])}</span>` : '';
                            const kat = parts[1] ? `<span style="font-size:0.74rem;color:#0ea5e9;font-weight:600">${escHtml(parts[1])}</span>` : '';
                            return `<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">${tw}${kat}</div>`;
                        }
                        return `<div style="font-size:0.74rem;color:#0ea5e9;font-weight:600">${escHtml(d.sub_kategori)}</div>`;
                    })() : ''}
                    ${d.keterangan ? `<div class="dok-card-ket">${escHtml(d.keterangan)}</div>` : ''}
                    ${uploadMethod === 'file' && d.filesize_human ? `<div style="font-size:0.75rem;color:#666;margin-top:4px">📊 ${d.filesize_human}</div>` : ''}
                    <div class="dok-card-footer">
                        ${btnAction}
                        ${isAdmin ? `<div class="dok-admin-actions">
                            <button class="btn-dok-edit" onclick="editDokumen(${d.id},'${menuKey}')" title="Edit">✏️</button>
                            <button class="btn-dok-hapus" onclick="hapusDokumen(${d.id},'${menuKey}')" title="Hapus">🗑️</button>
                        </div>` : ''}
                    </div>
                </div>`;
            }).join('');
        }

        // ── MODAL GDRIVE ────────────────────────────────
        let currentMenuKey = null;

        function bukaGdriveModal(menuKey, editData) {
            currentMenuKey = menuKey;
            document.getElementById('gdrivemodalMenuKey').value = menuKey;
            document.getElementById('gdrivemodalId').value = editData ? editData.id : '';
            document.getElementById('gdrivemodalTitle').textContent = editData ? 'Edit Dokumen' : 'Tambah Dokumen';
            document.getElementById('gdrivemodalJudul').value  = editData ? editData.judul : '';
            document.getElementById('gdrivemodalTahun').value  = editData ? (editData.tahun || '') : new Date().getFullYear();
            document.getElementById('gdrivemodalUrl').value    = editData ? (editData.url_gdrive || '') : '';
            document.getElementById('gdrivemodalKet').value    = editData ? (editData.keterangan || '') : '';

            // Tampilkan field Triwulan hanya untuk monitoring_kinerja
            const isMonKinerja = (menuKey === 'monitoring_kinerja');
            document.getElementById('fieldTriwulan').style.display = isMonKinerja ? '' : 'none';

            if (isMonKinerja) {
                // sub_kategori format: "TW1|Nama Kategori" atau hanya triwulan
                const parts = editData ? (editData.sub_kategori || '').split('|') : ['', ''];
                document.getElementById('gdrivemodalTriwulan').value = parts[0] || '';
                document.getElementById('gdrivemodalSubkat').value   = parts[1] || '';
            } else {
                document.getElementById('gdrivemodalTriwulan').value = '';
                document.getElementById('gdrivemodalSubkat').value   = editData ? (editData.sub_kategori || '') : '';
            }

            // Reset upload method toggle & hide saat edit (tidak bisa ganti metode saat edit)
            const uploadMethodGroup = document.getElementById('dokumenUploadMethodGroup');
            if (editData) {
                // Hide upload method selection saat edit
                uploadMethodGroup.style.display = 'none';
                document.getElementById('dokumenFileUploadArea').style.display = 'none';
                document.getElementById('dokumenGdriveUploadArea').style.display = 'none';
            } else {
                // Show upload method selection & reset ke Link default
                uploadMethodGroup.style.display = 'block';
                toggleDokumenUploadMethod('gdrive');
            }

            document.getElementById('gdrivemodalOverlay').classList.add('show');
        }

        function tutupGdriveModal() {
            document.getElementById('gdrivemodalOverlay').classList.remove('show');
        }

        function simpanDokumen() {
            const id = document.getElementById('gdrivemodalId').value;
            const menuKey = document.getElementById('gdrivemodalMenuKey').value;
            const judul = document.getElementById('gdrivemodalJudul').value.trim();
            const uploadMethod = document.querySelector('#dokumenUploadMethodGroup .method-btn.active')?.dataset.method || 'gdrive';

            if (!judul) { 
                showToast('Judul wajib diisi', 'error'); 
                return; 
            }

            // Validasi berdasarkan metode
            if (uploadMethod === 'file') {
                const file = document.getElementById('gdrivemodalFile').files[0];
                if (!file && !id) { // Jika tambah baru (bukan edit)
                    showToast('File harus dipilih', 'error'); 
                    return; 
                }
            } else {
                const url = document.getElementById('gdrivemodalUrl').value.trim();
                if (!url) { 
                    showToast('URL Link harus diisi', 'error'); 
                    return; 
                }
                if (false && !url.includes('drive.google.com')) {
                    showToast('Link tidak valid', 'error'); 
                    return;
                }
            }

            // Gabungkan triwulan + kategori jika monitoring kinerja
            let subKat = document.getElementById('gdrivemodalSubkat').value.trim();
            if (menuKey === 'monitoring_kinerja') {
                const tw = document.getElementById('gdrivemodalTriwulan').value;
                subKat = tw ? tw + (subKat ? '|' + subKat : '') : subKat;
            }

            // Jika edit, gunakan JSON (tidak upload file ulang)
            if (id) {
                const payload = {
                    id, 
                    judul,
                    tahun: parseInt(document.getElementById('gdrivemodalTahun').value) || null,
                    sub_kategori: subKat,
                    keterangan: document.getElementById('gdrivemodalKet').value.trim(),
                };

                fetch('api/dokumen.php?action=edit', { 
                    method:'POST', 
                    headers:{'Content-Type':'application/json'}, 
                    body: JSON.stringify(payload) 
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        tutupGdriveModal();
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
                return;
            }

            // Jika tambah baru, gunakan FormData
            const fd = new FormData();
            fd.append('action', 'tambah');
            fd.append('menu_key', menuKey);
            fd.append('judul', judul);
            fd.append('tahun', document.getElementById('gdrivemodalTahun').value || '');
            fd.append('sub_kategori', subKat);
            fd.append('keterangan', document.getElementById('gdrivemodalKet').value.trim());
            fd.append('upload_method', uploadMethod);

            if (uploadMethod === 'file') {
                const file = document.getElementById('gdrivemodalFile').files[0];
                fd.append('file', file);
            } else {
                const url = document.getElementById('gdrivemodalUrl').value.trim();
                fd.append('url_gdrive', url);
            }

            fetch('api/dokumen.php', { method:'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        tutupGdriveModal();
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
        }

        function toggleDokumenUploadMethod(method) {
            // Toggle active button
            document.querySelectorAll('#dokumenUploadMethodGroup .method-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.method === method);
            });
            
            // Toggle upload areas
            const fileArea = document.getElementById('dokumenFileUploadArea');
            const gdriveArea = document.getElementById('dokumenGdriveUploadArea');
            
            if (method === 'file') {
                fileArea.style.display = 'block';
                gdriveArea.style.display = 'none';
                document.getElementById('gdrivemodalUrl').value = '';
            } else {
                fileArea.style.display = 'none';
                gdriveArea.style.display = 'block';
                document.getElementById('gdrivemodalFile').value = '';
                document.getElementById('dokumenFileDisplay').textContent = '📎 Klik untuk pilih file';
            }
        }

        function updateDokumenFileDisplay() {
            const input = document.getElementById('gdrivemodalFile');
            const display = document.getElementById('dokumenFileDisplay');
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2); // MB
                display.textContent = `📎 ${fileName} (${fileSize} MB)`;
            } else {
                display.textContent = '📎 Klik untuk pilih file';
            }
        }

        function editDokumen(id, menuKey) {
            const item = (dokumenCache[menuKey] || []).find(d => d.id == id);
            if (item) bukaGdriveModal(menuKey, item);
        }

        function hapusDokumen(id, menuKey) {
            if (!confirm('Yakin hapus dokumen ini?')) return;
            fetch('api/dokumen.php?action=hapus', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        delete dokumenCache[menuKey];
                        const pageId = Object.keys(PAGE_MENU_MAP).find(k => PAGE_MENU_MAP[k] === menuKey);
                        if (pageId) loadGdriveContent(pageId, menuKey, true);
                    } else { showToast(res.message, 'error'); }
                });
        }

        // ── LAKIN UPLOAD ────────────────────────────────
        function loadLakinContent(tipe) {
            const container = document.getElementById('content-lakin-' + tipe);
            if (!container) {
                console.error('LAKIN container not found:', 'content-lakin-' + tipe);
                return;
            }
            container.innerHTML = '<div class="loading-state">⏳ Memuat file...</div>';

            console.log(`📥 Loading LAKIN ${tipe}...`);
            fetch(`api/lakin.php?tipe=${tipe}`)
                .then(r => {
                    console.log('LAKIN response status:', r.status);
                    return r.json();
                })
                .then(res => {
                    console.log('LAKIN data:', res);
                    if (res.success) {
                        lakinCache[tipe] = res.data;
                        console.log(`✅ LAKIN ${tipe}: ${res.data.length} files loaded`);
                        renderLakinList(tipe, res.data);
                    } else {
                        console.error('LAKIN error:', res.message);
                        container.innerHTML = `<div class="loading-state">⚠️ Gagal memuat: ${escHtml(res.message || 'Unknown error')}</div>`;
                    }
                })
                .catch(err => {
                    console.error('LAKIN fetch error:', err);
                    container.innerHTML = `<div class="loading-state">⚠️ Koneksi gagal. Cek Console (F12) untuk detail.</div>`;
                });
        }

        function renderLakinList(tipe, data) {
            const container = document.getElementById('content-lakin-' + tipe);
            if (!container) return;

            // Filter tahun
            const tahunSet = [...new Set(data.map(d => d.tahun))].sort((a,b) => b-a);
            const toolbar = `
                <div class="gdrive-toolbar">
                    <div class="gdrive-filter" id="lakin-filter-${tipe}">
                        <button class="filter-tahun active" onclick="filterLakinTahun('${tipe}', null, this)">Semua</button>
                        ${tahunSet.map(t => `<button class="filter-tahun" onclick="filterLakinTahun('${tipe}', ${t}, this)">${t}</button>`).join('')}
                    </div>
                </div>`;

            const list = `<div id="lakin-list-${tipe}" class="lakin-container"></div>`;
            container.innerHTML = toolbar + list;
            renderLakinItems(tipe, data, null);
        }

        function filterLakinTahun(tipe, tahun, btn) {
            btn.closest('.gdrive-filter').querySelectorAll('.filter-tahun').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const data = (lakinCache[tipe] || []).filter(d => tahun ? d.tahun == tahun : true);
            renderLakinItems(tipe, data, tahun);
        }

        function renderLakinItems(tipe, data, tahun) {
            const el = document.getElementById('lakin-list-' + tipe);
            if (!el) return;
            if (!data.length) {
                el.innerHTML = `<div class="empty-dok"><div class="empty-icon">📄</div><div>Belum ada file ${tipe}</div></div>`;
                return;
            }
            const extIcon = {pdf:'📕', doc:'📘', docx:'📘', xlsx:'📗', xls:'📗', gdrive:'☁️'};
            el.innerHTML = data.map(f => {
                const uploadMethod = f.upload_method || 'file';
                const ext = uploadMethod === 'gdrive' ? 'gdrive' : f.original_name.split('.').pop().toLowerCase();
                const icon = extIcon[ext] || '📄';
                const methodBadge = uploadMethod === 'gdrive' 
                    ? '<span class="badge-gdrive">☁️ Link</span>' 
                    : '<span class="badge-file">📁 File</span>';
                return `
                <div class="lakin-card">
                    <div class="lakin-file-icon">${icon}</div>
                    <div class="lakin-file-body">
                        <div class="lakin-file-title">${escHtml(f.judul)} ${methodBadge}</div>
                        <div class="lakin-file-meta">
                            ${f.tahun} · ${f.original_name} ${uploadMethod === 'file' ? '· ' + f.filesize_human : ''}
                            ${f.keterangan ? ' · ' + escHtml(f.keterangan) : ''}
                        </div>
                    </div>
                    <div class="lakin-file-actions">
                        <a href="api/lakin.php?action=download&id=${f.id}" class="btn-download" target="_blank">
                            ${uploadMethod === 'gdrive' ? '🔗 Buka' : '⬇️ Download'}
                        </a>
                        ${isAdmin ? `<button class="btn-hapus-lakin" onclick="hapusLakin(${f.id},'${tipe}')" title="Hapus">🗑️</button>` : ''}
                    </div>
                </div>`;
            }).join('');
        }

        function bukaUploadLakin(tipe) {
            document.getElementById('lakinUploadTipe').value = tipe;
            document.getElementById('lakinUploadTitle').textContent = 'Upload LAKIN ' + (tipe === 'draft' ? 'Draft' : 'Final');
            document.getElementById('lakinJudul').value = '';
            document.getElementById('lakinTahun').value = new Date().getFullYear();
            document.getElementById('lakinKet').value   = '';
            document.getElementById('lakinFile').value  = '';
            document.getElementById('fileDisplay').textContent = '📎 Klik untuk pilih file';
            document.getElementById('fileDisplay').classList.remove('has-file');
            document.getElementById('lakinUploadOverlay').classList.add('show');
        }

        function tutupUploadLakin() {
            document.getElementById('lakinUploadOverlay').classList.remove('show');
        }

        function updateFileDisplay() {
            const file = document.getElementById('lakinFile').files[0];
            const disp = document.getElementById('fileDisplay');
            if (file) {
                disp.textContent = '✅ ' + file.name;
                disp.classList.add('has-file');
            } else {
                disp.textContent = '📎 Klik untuk pilih file';
                disp.classList.remove('has-file');
            }
        }

        function uploadLakin() {
            const tipe   = document.getElementById('lakinUploadTipe').value;
            const judul  = document.getElementById('lakinJudul').value.trim();
            const tahun  = document.getElementById('lakinTahun').value;
            const ket    = document.getElementById('lakinKet').value.trim();
            const uploadMethod = document.querySelector('.method-btn.active').dataset.method;

            if (!judul || !tahun) { 
                showToast('Judul dan tahun wajib diisi', 'error'); 
                return; 
            }

            // Validasi berdasarkan metode
            if (uploadMethod === 'file') {
                const file = document.getElementById('lakinFile').files[0];
                if (!file) { 
                    showToast('File harus dipilih', 'error'); 
                    return; 
                }
            } else {
                const gdriveLink = document.getElementById('lakinGdriveLink').value.trim();
                if (!gdriveLink) { 
                    showToast('Link Cloud Storage harus diisi', 'error'); 
                    return; 
                }
                if (false && !gdriveLink.includes('drive.google.com')) {
                    showToast('Link tidak valid', 'error'); 
                    return;
                }
            }

            const btn = document.getElementById('btnUploadLakin');
            btn.textContent = '⏳ Mengupload...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('judul', judul);
            fd.append('tahun', tahun);
            fd.append('tipe', tipe);
            fd.append('keterangan', ket);
            fd.append('upload_method', uploadMethod);

            if (uploadMethod === 'file') {
                const file = document.getElementById('lakinFile').files[0];
                fd.append('file', file);
            } else {
                const gdriveLink = document.getElementById('lakinGdriveLink').value.trim();
                fd.append('gdrive_link', gdriveLink);
            }

            fetch('api/lakin.php', { method:'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.textContent = '⬆️ Upload';
                    btn.disabled = false;
                    if (res.success) {
                        showToast('LAKIN berhasil diupload!', 'success');
                        tutupUploadLakin();
                        lakinCache[tipe] = null;
                        loadLakinContent(tipe);
                    } else { showToast(res.message, 'error'); }
                })
                .catch(() => { btn.textContent = '⬆️ Upload'; btn.disabled = false; showToast('Upload gagal', 'error'); });
        }

        function toggleUploadMethod(method) {
            // Toggle active button
            document.querySelectorAll('.method-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.method === method);
            });
            
            // Toggle upload areas
            const fileArea = document.getElementById('fileUploadArea');
            const gdriveArea = document.getElementById('gdriveUploadArea');
            
            if (method === 'file') {
                fileArea.style.display = 'block';
                gdriveArea.style.display = 'none';
                document.getElementById('lakinGdriveLink').value = '';
            } else {
                fileArea.style.display = 'none';
                gdriveArea.style.display = 'block';
                document.getElementById('lakinFile').value = '';
                document.getElementById('fileDisplay').textContent = '📎 Klik untuk pilih file';
            }
        }

        function hapusLakin(id, tipe) {
            if (!confirm('Yakin hapus file ini?')) return;
            fetch('api/lakin.php?action=hapus', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        lakinCache[tipe] = null;
                        loadLakinContent(tipe);
                    } else { showToast(res.message, 'error'); }
                });
        }

        // Tutup modal klik overlay (with safety check)
        const gdrivemodalOverlay = document.getElementById('gdrivemodalOverlay');
        if (gdrivemodalOverlay) {
            gdrivemodalOverlay.addEventListener('click', function(e) {
                if (e.target === this) tutupGdriveModal();
            });
        }
        
        const lakinUploadOverlay = document.getElementById('lakinUploadOverlay');
        if (lakinUploadOverlay) {
            lakinUploadOverlay.addEventListener('click', function(e) {
                if (e.target === this) tutupUploadLakin();
            });
        }



        <?php if ($user_role === 'admin'): ?>
        <?php include 'includes/user_management.js'; ?>
        <?php endif; ?>

        // Show detail jadwal (popup saat klik event)
        function showDetailJadwal(jadwal) {
            const modal = document.getElementById('modalDetail');
            if (!modal) {
                console.error('Modal detail tidak ditemukan');
                return;
            }
            
            const konten = document.getElementById('detailKonten');
            if (!konten) {
                console.error('Konten detail tidak ditemukan');
                return;
            }
            
            const mulai = new Date(jadwal.tanggal_mulai);
            const selesai = new Date(jadwal.tanggal_selesai);
            const now = new Date();
            
            let statusBadge = '';
            if (now < mulai) {
                statusBadge = '<span style="background:#fbd38d;color:#744210;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">⏳ Belum Mulai</span>';
            } else if (now >= mulai && now <= selesai) {
                statusBadge = '<span style="background:#9ae6b4;color:#22543d;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">🔄 Sedang Berjalan</span>';
            } else {
                statusBadge = '<span style="background:#fc8181;color:#742a2a;padding:4px 12px;border-radius:12px;font-size:0.85rem;font-weight:600">✅ Selesai</span>';
            }
            
            konten.innerHTML = `
                <div style="padding:1.5rem">
                    <h3 style="margin-bottom:1rem;color:#1e293b">${escHtml(jadwal.judul)}</h3>
                    ${statusBadge}
                    <div style="margin-top:1.5rem">
                        <p style="margin-bottom:0.5rem"><strong>Kategori:</strong> ${escHtml(jadwal.kategori)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Tanggal Mulai:</strong> ${formatTgl(jadwal.tanggal_mulai)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Tanggal Selesai:</strong> ${formatTgl(jadwal.tanggal_selesai)}</p>
                        <p style="margin-bottom:0.5rem"><strong>Dibuat oleh:</strong> ${escHtml(jadwal.created_by || 'Admin')}</p>
                        ${jadwal.catatan ? `<p style="margin-top:1rem"><strong>Catatan:</strong><br>${escHtml(jadwal.catatan)}</p>` : ''}
                    </div>
                </div>
            `;
            
            modal.classList.add('show');
        }

    
        // ==================== IKSS FUNCTIONS ====================
        
        // IKSS Functions sudah dipindahkan ke assets/js/ikss_functions.js


    </script>

    <!-- Modal Tambah/Edit Dokumen (File OR Link) -->
    <div class="gdrive-modal-overlay" id="gdrivemodalOverlay">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="gdrivemodalTitle">Tambah Dokumen</h3>
                <button class="stat-modal-close" onclick="tutupGdriveModal()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="gdrivemodalId">
                <input type="hidden" id="gdrivemodalMenuKey">
                <div class="form-group">
                    <label>Judul Dokumen *</label>
                    <input type="text" id="gdrivemodalJudul" placeholder="Contoh: Renstra 2025-2029">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun</label>
                        <input type="number" id="gdrivemodalTahun" placeholder="2026" min="2000" max="2099">
                    </div>
                    <div class="form-group" id="fieldTriwulan" style="display:none">
                        <label>Triwulan</label>
                        <select id="gdrivemodalTriwulan">
                            <option value="">-- Pilih --</option>
                            <option value="TW1">Triwulan I</option>
                            <option value="TW2">Triwulan II</option>
                            <option value="TW3">Triwulan III</option>
                            <option value="TW4">Triwulan IV</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" id="gdrivemodalSubkat" placeholder="Contoh: FRA, Renstra, dll">
                </div>
                
                <!-- Pilihan Metode Upload -->
                <div class="form-group" id="dokumenUploadMethodGroup">
                    <label>Pilih Metode Upload *</label>
                    <div class="upload-method-toggle">
                        <button type="button" class="method-btn" data-method="file" onclick="toggleDokumenUploadMethod('file')">
                            📁 Upload File
                        </button>
                        <button type="button" class="method-btn active" data-method="gdrive" onclick="toggleDokumenUploadMethod('gdrive')">
                            ☁️ Link Cloud Storage
                        </button>
                    </div>
                </div>
                
                <!-- Area Upload File -->
                <div class="form-group" id="dokumenFileUploadArea" style="display:none;">
                    <label>File (PDF/Word/Excel/Image, max 20MB) *</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-display" id="dokumenFileDisplay">📎 Klik untuk pilih file</div>
                        <input type="file" id="gdrivemodalFile" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg" onchange="updateDokumenFileDisplay()">
                    </div>
                </div>
                
                <!-- Area Link Cloud Storage -->
                <div class="form-group" id="dokumenGdriveUploadArea">
                    <label>URL Link *</label>
                    <input type="url" id="gdrivemodalUrl" placeholder="https://drive.google.com/ atau https://onedrive.live.com/ ...">
                    <small style="color:#666;display:block;margin-top:5px;">
                        💡 Pastikan file sudah di-share "Anyone with the link"
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea id="gdrivemodalKet" rows="2" placeholder="Opsional"></textarea>
                </div>
            </div>
            <div class="gdrive-modal-foot">
                <button class="btn-cancel" onclick="tutupGdriveModal()">Batal</button>
                <button class="btn-save" onclick="simpanDokumen()">💾 Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Upload LAKIN -->
    <div class="gdrive-modal-overlay" id="lakinUploadOverlay">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="lakinUploadTitle">Upload LAKIN Draft</h3>
                <button class="stat-modal-close" onclick="tutupUploadLakin()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="lakinUploadTipe">
                <div class="form-group">
                    <label>Judul *</label>
                    <input type="text" id="lakinJudul" placeholder="Contoh: LAKIN 2025 Draft v1">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun *</label>
                        <input type="number" id="lakinTahun" placeholder="2025" min="2000" max="2099">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" id="lakinKet" placeholder="Opsional">
                    </div>
                </div>
                
                <!-- Pilihan Metode Upload -->
                <div class="form-group">
                    <label>Pilih Metode Upload *</label>
                    <div class="upload-method-toggle">
                        <button type="button" class="method-btn active" data-method="file" onclick="toggleUploadMethod('file')">
                            📁 Upload File
                        </button>
                        <button type="button" class="method-btn" data-method="gdrive" onclick="toggleUploadMethod('gdrive')">
                            ☁️ Link Cloud Storage
                        </button>
                    </div>
                </div>
                
                <!-- Area Upload File -->
                <div class="form-group" id="fileUploadArea">
                    <label>File (PDF/Word/Excel, maks 20MB) *</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-display" id="fileDisplay">📎 Klik untuk pilih file</div>
                        <input type="file" id="lakinFile" accept=".pdf,.doc,.docx,.xlsx,.xls" onchange="updateFileDisplay()">
                    </div>
                </div>
                
                <!-- Area Link Cloud Storage -->
                <div class="form-group" id="gdriveUploadArea" style="display:none;">
                    <label>Link Cloud Storage *</label>
                    <input type="url" id="lakinGdriveLink" placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)">
                    <small style="color:#666;display:block;margin-top:5px;">
                        💡 Pastikan file sudah di-share "Anyone with the link"
                    </small>
                </div>
            </div>
            <div class="gdrive-modal-foot">
                <button class="btn-cancel" onclick="tutupUploadLakin()">Batal</button>
                <button class="btn-save" id="btnUploadLakin" onclick="uploadLakin()">⬆️ Upload</button>
            </div>
        </div>
    </div>



    <!-- Modal Edit Link IKSS -->
    <div class="gdrive-modal-overlay" id="modalIKSS">
        <div class="gdrive-modal">
            <div class="gdrive-modal-head">
                <h3 id="modalIKSSTitle">Edit Link IKSS</h3>
                <button class="stat-modal-close" onclick="tutupModalIKSS()">✕</button>
            </div>
            <div class="gdrive-modal-body">
                <input type="hidden" id="ikssIdEdit">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">IKSS:</label>
                    <div id="ikssNamaDisplay" style="padding: 0.75rem; background: #f8fafc; border-radius: 8px; color: #475569; font-size: 0.9rem;"></div>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link Dokumen Sumber</label>
                    <input type="url" id="ikssLinkDokumen" 
                           placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.25rem;">
                        Terima semua link cloud storage
                    </small>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link Tindak Lanjut TW Sebelumnya</label>
                    <input type="url" id="ikssLinkTindakLanjut" 
                           placeholder="https://... (Google Drive, OneDrive, Dropbox, dll)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.25rem;">
                        Terima semua link cloud storage
                    </small>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="tutupModalIKSS()" 
                            style="padding: 0.75rem 1.5rem; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: EDIT LINK DOKUMEN SUMBER ═══════════════ -->
    <div id="modalEditLinkDokumen" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">✏️ Edit Link Dokumen Sumber</h3>
                <button onclick="tutupModalEditDokumen()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editDokIkssId">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">IKSS:</label>
                    <div id="editDokSasaran" style="padding: 0.75rem; background: #f8fafc; border-radius: 6px; color: #1e293b; font-size: 0.9rem; line-height: 1.5;"></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="editDokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Dokumen Sumber:</label>
                    <input type="url" id="editDokLink" placeholder="https://drive.google.com/... atau https://onedrive.com/..." 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <p style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">Terima semua link cloud storage (Google Drive, OneDrive, Dropbox, dll)</p>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalEditDokumen()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkDokumen()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: EDIT LINK TINDAK LANJUT ═══════════════ -->
    <div id="modalEditLinkTindakLanjut" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">✏️ Edit Link Tindak Lanjut TW Sebelumnya</h3>
                <button onclick="tutupModalEditTindakLanjut()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editTLIkssId">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">IKSS:</label>
                    <div id="editTLSasaran" style="padding: 0.75rem; background: #f8fafc; border-radius: 6px; color: #1e293b; font-size: 0.9rem; line-height: 1.5;"></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="editTLLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Tindak Lanjut:</label>
                    <input type="url" id="editTLLink" placeholder="https://drive.google.com/... atau https://onedrive.com/..." 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <p style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">Terima semua link cloud storage (Google Drive, OneDrive, Dropbox, dll)</p>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalEditTindakLanjut()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanLinkTindakLanjut()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: KELOLA IKSS (ADMIN) ═══════════════ -->
    <div id="modalKelolaIKSS" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 95%; max-width: 1200px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">⚙️ Kelola IKSS Master</h3>
                <button onclick="tutupModalKelolaIKSS()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: #64748b; margin: 0;">Kelola data master IKSS (tambah, edit, hapus)</p>
                    <button onclick="tambahIKSS()" style="padding: 0.75rem 1.5rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        ➕ Tambah IKSS
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 60px;">No</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Sasaran Kegiatan</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Indikator Kinerja</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 100px;">Target</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Status</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="ikssManageTableBody">
                            <tr><td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: FORM IKSS (TAMBAH/EDIT) ═══════════════ -->
    <div id="modalFormIKSS" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="formIKSSTitle" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Tambah IKSS Baru</h3>
                <button onclick="tutupModalFormIKSS()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="formIKSSId">
                
                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSNomor" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Nomor: <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="formIKSSNomor" placeholder="Contoh: 1, 2, 3, dst" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSSasaran" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Sasaran Kegiatan: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formIKSSSasaran" rows="3" placeholder="Contoh: Terwujudnya Penyediaan Data dan Insight Statistik..." 
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="formIKSSIndikator" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Indikator Kinerja: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formIKSSIndikator" rows="3" placeholder="Contoh: Persentase Publikasi/Laporan Statistik..." 
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="formIKSSTarget" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Target: <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="formIKSSTarget" placeholder="Contoh: 100 Persen, 74.45 Poin" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="tutupModalFormIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="simpanIKSS()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: EDIT LINK PERMINDOK ═══════════════ -->
    <div id="modalEditPermindokLink" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Edit Link Permindok</h3>
                <button onclick="closeModal('modalEditPermindokLink')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="editPermindokId">
                
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                    <label style="display: block; font-weight: 500; color: #64748b; font-size: 0.875rem; margin-bottom: 0.25rem;">Judul:</label>
                    <div id="editPermindokJudul" style="color: #1e293b; font-size: 1rem;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="editPermindokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Permindok:</label>
                    <input type="url" 
                           id="editPermindokLink" 
                           placeholder="https://drive.google.com/... atau https://onedrive.live.com/..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <small style="color: #64748b;">Kosongkan jika belum ada link</small>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="closeModal('modalEditPermindokLink')" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="savePermindokLink()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: KELOLA PERMINDOK (ADMIN) ═══════════════ -->
    <div id="modalKelolaPermindok" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 95%; max-width: 1200px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">⚙️ Kelola Permindok Master</h3>
                <button onclick="closeModal('modalKelolaPermindok')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <p style="color: #64748b; margin: 0;">Kelola data master Permindok (tambah, edit, hapus)</p>
                    <button onclick="showFormPermindok('create')" style="padding: 0.75rem 1.5rem; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        ➕ Tambah Permindok
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 60px;">No</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Tahun</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #475569;">Judul</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Link</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 80px;">Status</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #475569; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="kelolaPermindokTableBody">
                            <tr><td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ MODAL: FORM PERMINDOK (TAMBAH/EDIT) ═══════════════ -->
    <div id="modalFormPermindok" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="formPermindokTitle" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Tambah Permindok Baru</h3>
                <button onclick="closeModal('modalFormPermindok')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div style="padding: 1.5rem;">
                <input type="hidden" id="formPermindokMode">
                <input type="hidden" id="formPermindokId">
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokNomor" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Nomor: <span style="color: #ef4444;">*</span></label>
                    <input type="number" 
                           id="formPermindokNomor" 
                           min="1"
                           placeholder="1"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokTahun" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Tahun: <span style="color: #ef4444;">*</span></label>
                    <input type="number" 
                           id="formPermindokTahun" 
                           min="2020" 
                           max="2030"
                           placeholder="2026"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="formPermindokJudul" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Judul Permindok: <span style="color: #ef4444;">*</span></label>
                    <textarea id="formPermindokJudul" 
                              rows="3"
                              placeholder="Contoh: Permintaan Data Statistik Kependudukan Triwulan I 2026"
                              style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="formPermindokLink" style="display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.9rem;">Link Permindok:</label>
                    <input type="url" 
                           id="formPermindokLink" 
                           placeholder="https://drive.google.com/... (opsional)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                    <small style="color: #64748b;">Bisa dikosongkan, dapat diisi kemudian</small>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button onclick="closeModal('modalFormPermindok')" 
                            style="padding: 0.75rem 1.5rem; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        Batal
                    </button>
                    <button onclick="saveFormPermindok()" 
                            style="padding: 0.75rem 1.5rem; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        💾 Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript -->
    <script src="assets/js/ikss_functions.js"></script>
    <script src="assets/js/permindok_functions.js"></script>

    <!-- Helper function for closing modals -->
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>

    <!-- CSS Animation untuk loading spinner -->
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>


</body>
</html>

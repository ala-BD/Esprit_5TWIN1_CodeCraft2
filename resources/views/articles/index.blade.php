@extends('layouts.app')

@section('title', 'Marketplace Textile — RETISS')

@section('styles')
<style>
    /* ===================================================
       PREMIUM FILTER SIDEBAR — ENHANCED v2
    =================================================== */

    /* ---- Sidebar container ---- */
    .filter-sidebar {
        background: #fff;
        border-radius: 1.5rem;
        border: 1px solid rgba(203,213,225,0.6);
        box-shadow:
            0 0 0 1px rgba(13,148,136,0.04),
            0 8px 40px rgba(15,23,42,0.08),
            0 2px 8px rgba(13,148,136,0.04);
        position: sticky; top: 88px;
        overflow: hidden;
        max-height: calc(100vh - 104px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #e2e8f0 transparent;
        transition: box-shadow 0.3s ease;
    }
    .filter-sidebar:hover {
        box-shadow:
            0 0 0 1px rgba(13,148,136,0.08),
            0 12px 48px rgba(15,23,42,0.10),
            0 4px 12px rgba(13,148,136,0.08);
    }
    .filter-sidebar::-webkit-scrollbar { width: 3px; }
    .filter-sidebar::-webkit-scrollbar-thumb { background: linear-gradient(#0d9488,#059669); border-radius: 4px; }

    /* ---- Sidebar clean header ---- */
    .sidebar-header {
        background: #0f172a;
        padding: 0.95rem 1.15rem;
        border-bottom: 1px solid #1e293b;
        position: relative;
    }
    .header-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 19px; height: 19px; padding: 0 5px;
        border-radius: 9999px;
        background: #0d9488;
        color: #fff; font-size: 10px; font-weight: 700;
    }

    /* ---- Accordion sections ---- */
    .fs { border-bottom: 1px solid #f0f4f8; position: relative; }
    .fs:last-child { border-bottom: none; }
    .fs-trigger {
        width: 100%; display: flex; align-items: center; justify-content: space-between;
        padding: 0.78rem 1.1rem; background: none; border: none; cursor: pointer;
        transition: background 0.18s, padding-left 0.18s;
        position: relative;
    }
    .fs-trigger::before {
        content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
        width: 3px; height: 0; border-radius: 0 3px 3px 0;
        background: linear-gradient(#0d9488,#059669);
        transition: height 0.2s ease;
    }
    .fs-trigger[aria-expanded="true"]::before { height: 60%; }
    .fs-trigger[aria-expanded="true"] { background: linear-gradient(90deg,#f0fdf4,#fff); padding-left: 1.3rem; }
    .fs-trigger:hover:not([aria-expanded="true"]) { background: #fafbfc; }
    .fs-title {
        display: flex; align-items: center; gap: 0.55rem;
        font-size: 0.71rem; font-weight: 700; letter-spacing: 0.05em;
        text-transform: uppercase; color: #475569;
        transition: color 0.18s;
    }
    .fs-trigger[aria-expanded="true"] .fs-title { color: #0d9488; }
    .fs-title-icon {
        width: 23px; height: 23px; border-radius: 7px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.6rem; flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .fs-trigger[aria-expanded="true"] .fs-title-icon { transform: scale(1.1); }
    .fs-chevron { color: #cbd5e1; font-size: 0.6rem; transition: transform 0.28s cubic-bezier(0.4,0,0.2,1); }
    .fs-trigger[aria-expanded="true"] .fs-chevron { transform: rotate(180deg); color: #0d9488; }
    .fs-badge {
        font-size: 0.6rem; font-weight: 800; padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        background: linear-gradient(135deg,#0d9488,#059669);
        color: #fff; line-height: 1.4;
        box-shadow: 0 1px 4px rgba(13,148,136,0.3);
    }
    .fs-body {
        padding: 0 1.1rem 0.9rem;
        display: none;
        animation: fadeSlideIn 0.2s ease;
    }
    .fs-trigger[aria-expanded="true"] + .fs-body { display: block; }
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ---- Custom toggle switches ---- */
    .toggle-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.5rem 0.5rem; border-radius: 0.75rem; cursor: pointer;
        transition: background 0.15s; margin-bottom: 2px;
    }
    .toggle-row:hover { background: #f8fafc; }
    .toggle-label { font-size: 0.78rem; color: #374151; font-weight: 500; line-height: 1.2; }
    .toggle-label small { display: block; font-size: 0.64rem; color: #9ca3af; font-weight: 400; margin-top: 1px; }
    .toggle-icon { font-size: 0.7rem; margin-right: 0.4rem; }
    .toggle-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
    .toggle-track {
        position: absolute; inset: 0; border-radius: 9999px;
        background: #e2e8f0; transition: background 0.22s;
        cursor: pointer; border: 1.5px solid transparent;
    }
    .toggle-track::after {
        content: ''; position: absolute; width: 13px; height: 13px;
        border-radius: 50%; background: #fff; top: 2px; left: 2px;
        transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1);
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    .toggle-switch input:checked + .toggle-track {
        background: linear-gradient(135deg,#0d9488,#059669);
        border-color: transparent;
    }
    .toggle-switch input:checked + .toggle-track::after { transform: translateX(17px); }

    /* ---- Size grid ---- */
    .size-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.32rem; }
    .size-box {
        display: flex; align-items: center; justify-content: center;
        padding: 0.42rem 0.2rem; border-radius: 0.55rem;
        font-size: 0.71rem; font-weight: 800;
        border: 1.5px solid #e8edf3; background: #f8fafc; color: #64748b;
        text-decoration: none; transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
        position: relative; overflow: hidden;
    }
    .size-box::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg,#0d9488,#059669);
        opacity: 0; transition: opacity 0.18s;
    }
    .size-box:hover  { border-color: #0d9488; color: #0d9488; transform: scale(1.05); box-shadow: 0 2px 8px rgba(13,148,136,0.15); }
    .size-box.active {
        border-color: transparent; color: #fff;
        box-shadow: 0 3px 10px rgba(13,148,136,0.4);
        transform: scale(1.05);
    }
    .size-box.active::before { opacity: 1; }
    .size-box span { position: relative; z-index: 1; }

    /* ---- Color swatches ---- */
    .color-grid { display: flex; flex-wrap: wrap; gap: 0.38rem; }
    .color-swatch {
        display: flex; align-items: center; gap: 0.38rem;
        padding: 0.26rem 0.6rem 0.26rem 0.38rem;
        border-radius: 9999px; border: 1.5px solid #e2e8f0;
        background: #f8fafc; text-decoration: none;
        font-size: 0.67rem; font-weight: 600; color: #475569;
        transition: all 0.18s; cursor: pointer; position: relative;
    }
    .color-swatch:hover {
        border-color: #94a3b8; background: #f1f5f9;
        color: #1e293b; transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(15,23,42,0.08);
    }
    .color-swatch.active {
        border-color: transparent; color: #fff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.22);
        transform: translateY(-1px);
    }
    .color-dot {
        width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
        border: 1.5px solid rgba(0,0,0,0.12);
        box-shadow: inset 0 1px 2px rgba(255,255,255,0.3);
    }

    /* ---- Category rows ---- */
    .cat-row {
        display: flex; align-items: center; gap: 0.55rem;
        padding: 0.42rem 0.55rem; border-radius: 0.7rem;
        font-size: 0.75rem; font-weight: 600; color: #475569;
        text-decoration: none; transition: all 0.18s; margin-bottom: 1px;
        position: relative;
    }
    .cat-row::after {
        content: '\f054'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        font-size: 0.5rem; color: #cbd5e1; margin-left: auto;
        transition: transform 0.18s, color 0.18s;
    }
    .cat-row:hover  { background: #f0fdf9; color: #0d9488; padding-left: 0.75rem; }
    .cat-row:hover::after { transform: translateX(2px); color: #0d9488; }
    .cat-row.active {
        background: linear-gradient(90deg,#ecfdf5,#f0fdf9);
        color: #059669; font-weight: 700;
        border-left: 3px solid #0d9488;
        padding-left: 0.45rem;
    }
    .cat-row.active::after { color: #0d9488; }
    .cat-row.active .cat-dot { background: #0d9488; }
    .cat-dot { width: 5px; height: 5px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; transition: background 0.15s; }
    .cat-icon {
        width: 22px; height: 22px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.6rem; flex-shrink: 0;
        transition: transform 0.18s;
    }
    .cat-row:hover .cat-icon { transform: scale(1.1); }

    /* ---- État rows ---- */
    .etat-row {
        display: flex; align-items: center; gap: 0.55rem; padding: 0.42rem 0.55rem;
        border-radius: 0.7rem; text-decoration: none; transition: all 0.18s;
        font-size: 0.74rem; font-weight: 600; color: #475569; margin-bottom: 2px;
    }
    .etat-row:hover  { background: #fffbeb; color: #92400e; padding-left: 0.75rem; }
    .etat-row.active {
        background: linear-gradient(90deg,#fef9c3,#fffbeb);
        color: #854d0e; border-left: 3px solid #f59e0b;
        padding-left: 0.45rem;
    }
    .etat-star { font-size: 0.52rem; }

    /* ---- Vendeur pills ---- */
    .vendeur-pill {
        display: inline-flex; align-items: center; gap: 0.38rem;
        padding: 0.32rem 0.72rem; border-radius: 9999px;
        font-size: 0.69rem; font-weight: 700; border: 1.5px solid #e2e8f0;
        background: #f8fafc; color: #475569; text-decoration: none;
        transition: all 0.18s; margin: 0.15rem 0.1rem 0 0;
    }
    .vendeur-pill:hover {
        border-color: #6366f1; background: #eef2ff; color: #4338ca;
        transform: translateY(-2px); box-shadow: 0 4px 10px rgba(99,102,241,0.2);
    }
    .vendeur-pill.active {
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.4);
        transform: translateY(-1px);
    }

    /* ---- Price range ---- */
    .price-range-inputs { display: flex; gap: 0.45rem; align-items: center; }
    .price-input {
        flex: 1; padding: 0.45rem 0.6rem; border: 1.5px solid #e2e8f0;
        border-radius: 0.7rem; font-size: 0.75rem; outline: none;
        transition: all 0.2s; background: #f8fafc; color: #1e293b; font-weight: 600;
    }
    .price-input:focus {
        border-color: #0d9488; background: #fff;
        box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
    }
    .price-apply-btn {
        width: 100%; margin-top: 0.55rem; padding: 0.55rem;
        border-radius: 0.85rem; border: none; cursor: pointer; font-size: 0.72rem;
        font-weight: 800; letter-spacing: 0.05em;
        background: linear-gradient(135deg, #0d9488, #059669);
        color: #fff; transition: all 0.22s;
        box-shadow: 0 3px 10px rgba(13,148,136,0.3);
        position: relative; overflow: hidden;
    }
    .price-apply-btn::before {
        content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
        background: linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);
        transition: left 0.4s;
    }
    .price-apply-btn:hover::before { left: 100%; }
    .price-apply-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(13,148,136,0.4); }

    /* ===== SORT BAR ===== */
    .sort-bar {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        background: linear-gradient(135deg,#fff 0%,#fafcff 100%);
        border-radius: 1.15rem; border: 1px solid #e5ebf2;
        padding: 0.65rem 1.1rem; margin-bottom: 1.1rem;
        box-shadow: 0 2px 12px rgba(15,23,42,0.05), 0 1px 2px rgba(15,23,42,0.04);
    }
    .sort-btn {
        padding: 0.35rem 0.9rem; border-radius: 9999px; font-size: 0.69rem; font-weight: 700;
        border: 1.5px solid transparent; background: transparent; color: #64748b;
        cursor: pointer; transition: all 0.2s cubic-bezier(0.4,0,0.2,1); text-decoration: none;
        display: inline-flex; align-items: center; gap: 0.35rem; letter-spacing: 0.02em;
    }
    .sort-btn:hover:not(.active) { background: #f1f5f9; color: #0f172a; border-color: #e2e8f0; }
    .sort-btn.active {
        background: linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);
        border-color: transparent; color: #fff;
        box-shadow: 0 3px 10px rgba(15,23,42,0.25);
    }

    /* Active filter chips */
    .active-chip {
        display: inline-flex; align-items: center; gap: 0.35rem;
        background: linear-gradient(135deg,#dbeafe,#e0f2fe);
        border: 1.5px solid #93c5fd; color: #1d4ed8;
        border-radius: 9999px; padding: 0.28rem 0.75rem;
        font-size: 0.68rem; font-weight: 700;
        animation: chipIn 0.25s cubic-bezier(0.34,1.56,0.64,1);
        box-shadow: 0 1px 4px rgba(29,78,216,0.12);
    }
    @keyframes chipIn { from { opacity:0; transform: scale(0.7) translateY(4px); } to { opacity:1; transform: scale(1) translateY(0); } }
    .active-chip a { text-decoration: none; color: inherit; margin-left: 1px; opacity: 0.7; transition: opacity 0.15s, color 0.15s; }
    .active-chip a:hover { opacity: 1; color: #dc2626; }

    /* Search bar */
    .search-bar-wrap {
        background: #fff; border-radius: 1.3rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(15,23,42,0.06), 0 1px 3px rgba(15,23,42,0.04);
        padding: 0.9rem 1.05rem; margin-bottom: 1rem;
    }
    #suggest-list {
        position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 200;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 1rem; box-shadow: 0 12px 32px rgba(15,23,42,0.14);
        overflow: hidden;
    }
    .suggest-item {
        padding: 0.6rem 1rem; font-size: 0.8rem; color: #334155;
        cursor: pointer; transition: all 0.12s;
        display: flex; align-items: center; gap: 0.6rem;
        border-left: 3px solid transparent;
    }
    .suggest-item::before {
        content: '\f002'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        font-size: 0.6rem; color: #94a3b8; flex-shrink: 0;
    }
    .suggest-item:hover {
        background: linear-gradient(90deg,#f0fdf9,#fff);
        color: #0d9488; border-left-color: #0d9488;
    }

    /* ---- Responsive Drawer Wrapper ---- */
    @media (max-width: 1023px) {
        .filter-sidebar-wrapper {
            position: fixed; inset: 0; z-index: 500;
            background: rgba(15,23,42,0.65);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            display: none; align-items: flex-end; justify-content: center;
            animation: overlayIn 0.22s ease;
        }
        .filter-sidebar-wrapper.mobile-open { display: flex; }
        .filter-sidebar-wrapper .filter-sidebar {
            width: 100%; max-width: 520px;
            max-height: 88vh;
            border-radius: 1.75rem 1.75rem 0 0;
            position: relative; top: 0;
            animation: drawerSlideUp 0.3s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 -12px 48px rgba(15,23,42,0.35);
            border-bottom: none;
        }
        .mobile-drawer-handle {
            width: 44px; height: 4px; border-radius: 9999px;
            background: rgba(255,255,255,0.35);
            margin: 0.6rem auto 0.4rem;
        }
    }
    @media (min-width: 1024px) {
        .filter-sidebar-wrapper {
            display: block !important;
            width: 16rem; /* 256px */
            flex-shrink: 0;
            position: static;
            background: transparent;
            backdrop-filter: none;
        }
        .mobile-drawer-handle { display: none; }
    }
    @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes drawerSlideUp {
        from { transform: translateY(100%); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
    }

    /* Mobile FAB */
    .mobile-filter-fab {
        display: none;
        position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
        z-index: 450;
        background: linear-gradient(135deg, #0c1526 0%, #0d9488 100%);
        color: #fff; border: 1.5px solid rgba(255,255,255,0.25);
        cursor: pointer;
        padding: 0.75rem 1.4rem; border-radius: 9999px;
        font-size: 0.82rem; font-weight: 700; letter-spacing: 0.03em;
        box-shadow: 0 12px 32px rgba(13,148,136,0.45), 0 4px 12px rgba(0,0,0,0.25);
        gap: 0.55rem; align-items: center;
        animation: fabBounce 0.45s cubic-bezier(0.34,1.56,0.64,1);
        transition: all 0.22s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes fabBounce {
        from { transform: translateX(-50%) scale(0.6) translateY(30px); opacity:0; }
        to   { transform: translateX(-50%) scale(1) translateY(0); opacity:1; }
    }
    .mobile-filter-fab:hover {
        transform: translateX(-50%) translateY(-3px) scale(1.03);
        box-shadow: 0 16px 40px rgba(13,148,136,0.55);
    }
    .mobile-filter-fab:active { transform: translateX(-50%) scale(0.97); }
    @media (max-width: 1023px) {
        .mobile-filter-fab { display: inline-flex; }
    }

    /* ---- Price presets ---- */
    .price-preset-btn {
        padding: 0.32rem 0.45rem;
        font-size: 0.68rem; font-weight: 700;
        border-radius: 0.55rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc; color: #475569;
        cursor: pointer; transition: all 0.16s;
        text-align: center;
    }
    .price-preset-btn:hover {
        border-color: #0d9488; color: #0d9488; background: #f0fdf9;
    }
    .price-preset-btn.active {
        border-color: #0d9488; background: #0d9488; color: #fff;
        box-shadow: 0 2px 6px rgba(13,148,136,0.25);
    }

    /* ---- View Switcher ---- */
    .view-btn {
        padding: 0.35rem 0.55rem; border-radius: 0.5rem;
        font-size: 0.72rem; border: none; background: transparent;
        color: #64748b; cursor: pointer; transition: all 0.18s;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .view-btn:hover { color: #0f172a; background: #e2e8f0; }
    .view-btn.active {
        background: #fff; color: #0d9488;
        box-shadow: 0 1px 4px rgba(0,0,0,0.12);
    }

    /* Compact Grid layout */
    #articles-grid.view-compact {
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.85rem;
    }
    @media (min-width: 640px) {
        #articles-grid.view-compact { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        #articles-grid.view-compact { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1280px) {
        #articles-grid.view-compact { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        #articles-grid.view-compact .card-banner { height: 140px; }
    }

    /* List view layout */
    #articles-grid.view-list {
        grid-template-columns: 1fr !important;
        gap: 0.9rem;
    }
    #articles-grid.view-list .article-card {
        flex-direction: row;
        align-items: stretch;
    }
    #articles-grid.view-list .card-banner {
        width: 230px; height: auto; min-height: 180px; flex-shrink: 0;
    }
    #articles-grid.view-list .card-banner img { height: 100%; }
    @media (max-width: 639px) {
        #articles-grid.view-list .article-card { flex-direction: column; }
        #articles-grid.view-list .card-banner { width: 100%; height: 170px; }
    }

    /* ---- Marketplace page bg ---- */
    .marketplace-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }
    .marketplace-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative; overflow: hidden; padding: 3rem 0 5.5rem;
    }
    .marketplace-hero::before {
        content: ''; position: absolute; inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px; pointer-events: none;
    }
    .marketplace-content { margin-top: -3.5rem; position: relative; z-index: 10; }

    /* ---- Article cards ---- */
    .article-card {
        background: #fff; border-radius: 1.5rem;
        border: 1px solid #e2e8f0;
        transition: all 0.32s cubic-bezier(0.4,0,0.2,1);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .article-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px -10px rgba(13,148,136,0.18), 0 6px 16px rgba(15,23,42,0.08);
        border-color: #99f6e4;
    }
    .card-banner {
        height: 180px;
        background: linear-gradient(135deg, #0f172a, #1e293b 40%, #0d9488);
        position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .card-banner img {
        width: 100%; height: 100%; object-fit: cover; transition: transform 0.42s ease;
    }
    .article-card:hover .card-banner img { transform: scale(1.08); }
    .card-banner-pattern {
        position: absolute; inset: 0; opacity: 0.12;
        background-image: radial-gradient(#fff 1.5px, transparent 1.5px);
        background-size: 14px 14px;
    }
    /* Card shimmer on hover */
    .article-card::after {
        content: ''; position: absolute; inset: 0; border-radius: 1.5rem;
        background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.03) 50%, transparent 60%);
        background-size: 200% 200%;
        opacity: 0; transition: opacity 0.3s;
        pointer-events: none;
    }
    .article-card:hover::after { opacity: 1; }

    .badge-statut-disponible { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-statut-reserve    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-statut-vendu      { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-statut-archive    { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .tag-filter-btn {
        padding: 0.45rem 1rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;
        transition: all 0.15s; text-decoration: none; display: inline-flex; align-items: center;
        gap: 0.4rem; border: 1px solid #e2e8f0; background: #fff; color: #475569;
    }
    .tag-filter-btn:hover { background: #f0fdf9; border-color: #0d9488; color: #0d9488; }
    .tag-filter-btn.active { background: #0d9488; border-color: #0d9488; color: #fff; box-shadow: 0 4px 12px rgba(13,148,136,0.25); }
</style>
@endsection

@section('content')
<div class="marketplace-page">

    {{-- ===== HERO ===== --}}
    <div class="marketplace-hero">
        <div class="hero-grid"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">Marketplace Textile</span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-xs font-semibold mb-3">
                        <i class="fas fa-recycle text-[10px]"></i> Économie Circulaire & Seconde Main
                    </div>
                    <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
                        Marketplace Textile
                    </h1>
                    <p class="text-white/70 text-sm max-w-xl">
                        Découvrez, achetez et publiez des articles textiles upcyclés, réemployés ou revalorisés. Donnez une seconde vie à vos vêtements.
                    </p>
                </div>

                {{-- Action button + Counters --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <a href="{{ route('commandes.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl font-bold text-sm text-white bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition-all duration-200"
                       style="text-decoration:none">
                        <i class="fas fa-shopping-bag text-teal-300"></i>
                        <span>Mes Commandes</span>
                    </a>

                    @can('create', \App\Models\Article::class)
                        <a href="{{ route('articles.create') }}"
                           class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl font-bold text-sm text-slate-900 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/20 transition-all duration-200 transform hover:-translate-y-0.5"
                           style="text-decoration:none">
                            <i class="fas fa-plus-circle text-base"></i>
                            <span>Publier un article</span>
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Stats Pills --}}
            <div class="mt-6 flex flex-wrap items-center gap-3 pt-4 border-t border-white/10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-white">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-bold">{{ $stats['total'] }}</span> article(s) au total
                </div>
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-white">
                    <span class="w-2 h-2 rounded-full bg-teal-300"></span>
                    <span class="font-bold">{{ $stats['disponibles'] }}</span> disponible(s)
                </div>
                @auth
                <a href="{{ route('articles.index', ['filter' => 'mine']) }}"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-teal-200 transition-colors"
                   style="text-decoration:none">
                    <i class="fas fa-user-tag text-xs"></i>
                    <span class="font-bold">{{ $stats['mesArticles'] }}</span> publié(s) par vous
                </a>
                @endauth
            </div>

        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 marketplace-content pb-16">

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="flex gap-7 items-start">

        {{-- ===== SIDEBAR FILTERS (PREMIUM & RESPONSIVE) ===== --}}
        <aside id="filter-sidebar-wrapper" class="filter-sidebar-wrapper" onclick="if(event.target === this) closeMobileFilters()">
            <form method="GET" action="{{ route('articles.index') }}" id="filter-form" class="filter-sidebar">
                <div class="mobile-drawer-handle"></div>

                {{-- Clean & Structured Header --}}
                <div class="sidebar-header">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-teal-500/20 text-teal-300 flex items-center justify-center text-xs">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-sm leading-none">Filtres</h3>
                                <p class="text-slate-400 text-[11px] mt-1">
                                    @if($activeFilters > 0)
                                        <span class="text-teal-400 font-semibold">{{ $activeFilters }} actif{{ $activeFilters > 1 ? 's' : '' }}</span>
                                    @else
                                        Tous les articles
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($activeFilters > 0)
                                <a href="{{ route('articles.index') }}"
                                   class="text-[11px] font-semibold text-red-400 hover:text-red-300 transition-colors"
                                   style="text-decoration:none">
                                    Effacer
                                </a>
                            @endif
                            <button type="button" onclick="closeMobileFilters()" class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg" title="Fermer">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Quick Toggles --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="true" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-violet-50 text-violet-600"><i class="fas fa-bolt"></i></span>
                            Accès rapide
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        <label class="toggle-row" for="toggle-mine">
                            <div class="toggle-label flex items-center gap-2">
                                <i class="fas fa-user-circle text-teal-600 text-sm"></i>
                                <div>
                                    Mes articles
                                    <small>Publiés par moi uniquement</small>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" id="toggle-mine" name="mine" value="1"
                                       {{ request()->boolean('mine') || request()->get('filter') === 'mine' ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <span class="toggle-track"></span>
                            </div>
                        </label>
                        <label class="toggle-row" for="toggle-stock">
                            <div class="toggle-label flex items-center gap-2">
                                <i class="fas fa-boxes-stacked text-emerald-600 text-sm"></i>
                                <div>
                                    En stock
                                    <small>Articles disponibles uniquement</small>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" id="toggle-stock" name="en_stock" value="1"
                                       {{ request()->boolean('en_stock') ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <span class="toggle-track"></span>
                            </div>
                        </label>
                        <label class="toggle-row" for="toggle-gratuits">
                            <div class="toggle-label flex items-center gap-2">
                                <i class="fas fa-hand-holding-heart text-amber-500 text-sm"></i>
                                <div>
                                    Dons gratuits
                                    <small>Prix = 0 DT</small>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" id="toggle-gratuits" name="gratuits" value="1"
                                       {{ request()->boolean('gratuits') ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <span class="toggle-track"></span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Prix --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('prix_min') || request()->filled('prix_max') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-amber-50 text-amber-600"><i class="fas fa-coins"></i></span>
                            Prix
                            @if(request()->filled('prix_min') || request()->filled('prix_max'))
                                <span class="fs-badge">actif</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        <div class="price-range-inputs mb-2">
                            <div class="relative flex-1">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9px] font-extrabold text-slate-400">MIN</span>
                                <input type="number" id="prix_min_input" name="prix_min" value="{{ request('prix_min') }}" placeholder="0" min="0" class="price-input pl-9">
                            </div>
                            <span class="text-slate-300 font-bold text-sm">→</span>
                            <div class="relative flex-1">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9px] font-extrabold text-slate-400">MAX</span>
                                <input type="number" id="prix_max_input" name="prix_max" value="{{ request('prix_max') }}" placeholder="∞" min="0" class="price-input pl-9">
                            </div>
                        </div>

                        {{-- Quick Presets --}}
                        <div class="grid grid-cols-2 gap-1 mb-2">
                            <button type="button" onclick="setPricePreset(0, 20)" class="price-preset-btn {{ request('prix_min') === '0' && request('prix_max') === '20' ? 'active' : '' }}">&lt; 20 DT</button>
                            <button type="button" onclick="setPricePreset(20, 50)" class="price-preset-btn {{ request('prix_min') === '20' && request('prix_max') === '50' ? 'active' : '' }}">20 - 50 DT</button>
                            <button type="button" onclick="setPricePreset(50, 100)" class="price-preset-btn {{ request('prix_min') === '50' && request('prix_max') === '100' ? 'active' : '' }}">50 - 100 DT</button>
                            <button type="button" onclick="setPricePreset(100, '')" class="price-preset-btn {{ request('prix_min') === '100' && empty(request('prix_max')) ? 'active' : '' }}">&gt; 100 DT</button>
                        </div>

                        <div class="text-[10px] text-slate-400 mb-1.5 flex items-center justify-between">
                            <span>Montant en Dinars (DT)</span>
                            @if(request()->filled('prix_min') || request()->filled('prix_max'))
                                <a href="{{ route('articles.index', request()->except(['prix_min','prix_max','page'])) }}" class="text-red-500 hover:underline text-[10px]">Effacer</a>
                            @endif
                        </div>
                        <button type="submit" class="price-apply-btn"><i class="fas fa-check mr-1"></i> Appliquer le prix</button>
                    </div>
                </div>

                {{-- Catégorie --}}
                @php
                $catIcons = ['Vetements Homme'=>['bg'=>'bg-blue-50','text'=>'text-blue-500','icon'=>'fa-male'],'Vetements Femme'=>['bg'=>'bg-pink-50','text'=>'text-pink-500','icon'=>'fa-female'],'Vetements Enfant'=>['bg'=>'bg-yellow-50','text'=>'text-yellow-500','icon'=>'fa-baby'],'Accessoires & Sacs'=>['bg'=>'bg-purple-50','text'=>'text-purple-500','icon'=>'fa-shopping-bag'],'Linge de maison'=>['bg'=>'bg-indigo-50','text'=>'text-indigo-500','icon'=>'fa-bed'],'Textile Upcycle'=>['bg'=>'bg-emerald-50','text'=>'text-emerald-500','icon'=>'fa-recycle'],'Chaussures'=>['bg'=>'bg-orange-50','text'=>'text-orange-500','icon'=>'fa-shoe-prints'],'Autre'=>['bg'=>'bg-slate-50','text'=>'text-slate-500','icon'=>'fa-ellipsis-h']];
                @endphp
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="true" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-teal-50 text-teal-600"><i class="fas fa-tag"></i></span>
                            Catégorie
                            @if(request()->filled('categorie'))
                                <span class="fs-badge">1</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        @foreach($categories as $cat)
                            @php $ci = $catIcons[$cat] ?? ['bg'=>'bg-slate-50','text'=>'text-slate-400','icon'=>'fa-circle']; @endphp
                            <a href="{{ route('articles.index', array_merge(request()->except(['categorie','page']), ['categorie' => request('categorie') === $cat ? '' : $cat])) }}"
                               class="cat-row {{ request('categorie') === $cat ? 'active' : '' }}">
                                <span class="cat-dot"></span>
                                <span class="cat-icon {{ $ci['bg'] }} {{ $ci['text'] }}"><i class="fas {{ $ci['icon'] }}"></i></span>
                                <span>{{ $cat }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Taille --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('taille') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-sky-50 text-sky-600"><i class="fas fa-ruler"></i></span>
                            Taille
                            @if(request()->filled('taille'))
                                <span class="fs-badge">{{ request('taille') }}</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        <div class="size-grid">
                            @foreach($tailles as $t)
                                <a href="{{ route('articles.index', array_merge(request()->except(['taille','page']), ['taille' => request('taille') === $t ? '' : $t])) }}"
                                   class="size-box {{ request('taille') === $t ? 'active' : '' }}">{{ $t }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Matière --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('matiere') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-lime-50 text-lime-700"><i class="fas fa-leaf"></i></span>
                            Matière
                            @if(request()->filled('matiere'))
                                <span class="fs-badge">1</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        <div class="flex flex-wrap gap-1">
                        @foreach($matieres as $m)
                            <a href="{{ route('articles.index', array_merge(request()->except(['matiere','page']), ['matiere' => request('matiere') === $m ? '' : $m])) }}"
                               class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-600 border transition-all
                                      {{ request('matiere') === $m ? 'bg-lime-600 border-lime-600 text-white shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-lime-400 hover:bg-lime-50 hover:text-lime-700' }}"
                               style="font-weight:600; text-decoration:none">
                                {{ $m }}
                            </a>
                        @endforeach
                        </div>
                    </div>
                </div>

                {{-- État --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('etat') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-yellow-50 text-yellow-600"><i class="fas fa-star"></i></span>
                            État
                            @if(request()->filled('etat'))
                                <span class="fs-badge">1</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        @php
                        $etatConfig = [
                            'Neuf'          => ['stars'=>5, 'color'=>'text-emerald-600', 'bg'=>'bg-emerald-50'],
                            'Tres bon etat' => ['stars'=>4, 'color'=>'text-teal-600',    'bg'=>'bg-teal-50'],
                            'Bon etat'      => ['stars'=>3, 'color'=>'text-blue-600',    'bg'=>'bg-blue-50'],
                            'Usage'         => ['stars'=>2, 'color'=>'text-slate-500',   'bg'=>'bg-slate-50'],
                        ];
                        @endphp
                        @foreach($etats as $e)
                            @php $ec = $etatConfig[$e] ?? ['stars'=>1,'color'=>'text-slate-400','bg'=>'bg-slate-50']; @endphp
                            <a href="{{ route('articles.index', array_merge(request()->except(['etat','page']), ['etat' => request('etat') === $e ? '' : $e])) }}"
                               class="etat-row {{ request('etat') === $e ? 'active' : '' }}">
                                <span class="{{ $ec['color'] }}">
                                    @for($s=0; $s < $ec['stars']; $s++) <i class="fas fa-star etat-star"></i> @endfor
                                    @for($s=$ec['stars']; $s < 5; $s++) <i class="far fa-star etat-star text-slate-200"></i> @endfor
                                </span>
                                <span>{{ $e }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Couleur (swatches) --}}
                @php
                $couleurMap = [
                    'Noir'=>'#1e293b','Blanc'=>'#f8fafc','Gris'=>'#94a3b8','Bleu'=>'#3b82f6',
                    'Rouge'=>'#ef4444','Vert'=>'#22c55e','Jaune'=>'#eab308','Orange'=>'#f97316',
                    'Rose'=>'#ec4899','Violet'=>'#a855f7','Marron'=>'#92400e','Beige'=>'#d4b896',
                    'Multicolore'=>'linear-gradient(135deg,#ef4444,#f97316,#eab308,#22c55e,#3b82f6,#a855f7)'
                ];
                @endphp
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('couleur') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-pink-50 text-pink-500"><i class="fas fa-palette"></i></span>
                            Couleur
                            @if(request()->filled('couleur'))
                                <span class="fs-badge">{{ request('couleur') }}</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        <div class="color-grid">
                            @foreach($couleurs as $c)
                                @php $bg = $couleurMap[$c] ?? '#94a3b8'; $isActive = request('couleur') === $c; @endphp
                                <a href="{{ route('articles.index', array_merge(request()->except(['couleur','page']), ['couleur' => $isActive ? '' : $c])) }}"
                                   class="color-swatch {{ $isActive ? 'active' : '' }}"
                                   style="{{ $isActive ? 'background:' . $bg . ';' : '' }}">
                                    <span class="color-dot" style="background: {{ $bg }}"></span>
                                    <span>{{ $c }}</span>
                                    @if($isActive) <i class="fas fa-check text-[8px] ml-auto opacity-80"></i> @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Statut --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('statut') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-slate-100 text-slate-500"><i class="fas fa-circle"></i></span>
                            Statut
                            @if(request()->filled('statut'))
                                <span class="fs-badge">1</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        @php
                        $statutConf = ['DISPONIBLE'=>['dot'=>'#10b981','bg'=>'bg-emerald-50','text'=>'text-emerald-700'],'RESERVE'=>['dot'=>'#f59e0b','bg'=>'bg-amber-50','text'=>'text-amber-700'],'VENDU'=>['dot'=>'#ef4444','bg'=>'bg-red-50','text'=>'text-red-700'],'ARCHIVE'=>['dot'=>'#94a3b8','bg'=>'bg-slate-100','text'=>'text-slate-500']];
                        @endphp
                        @foreach($statuts as $st)
                            @php $sc = $statutConf[$st] ?? ['dot'=>'#94a3b8','bg'=>'bg-slate-100','text'=>'text-slate-500']; @endphp
                            <a href="{{ route('articles.index', array_merge(request()->except(['statut','page']), ['statut' => request('statut') === $st ? '' : $st])) }}"
                               class="cat-row {{ request('statut') === $st ? 'active' : '' }}">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $sc['dot'] }}"></span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full {{ $sc['bg'] }} {{ $sc['text'] }} font-bold">{{ $st }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Vendeur --}}
                <div class="fs">
                    <button type="button" class="fs-trigger" aria-expanded="{{ request()->filled('vendeur_role') ? 'true' : 'false' }}" onclick="toggleSection(this)">
                        <span class="fs-title">
                            <span class="fs-title-icon bg-indigo-50 text-indigo-600"><i class="fas fa-store"></i></span>
                            Type vendeur
                            @if(request()->filled('vendeur_role'))
                                <span class="fs-badge">1</span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down fs-chevron"></i>
                    </button>
                    <div class="fs-body">
                        @php
                        $vendeurConf = ['Atelier'=>'fa-tools','Donateur'=>'fa-hand-holding-heart','Recycleur'=>'fa-recycle','Collecteur'=>'fa-truck-pickup'];
                        @endphp
                        <div class="flex flex-wrap">
                        @foreach(['Atelier','Donateur','Recycleur','Collecteur'] as $role)
                            <a href="{{ route('articles.index', array_merge(request()->except(['vendeur_role','page']), ['vendeur_role' => request('vendeur_role') === $role ? '' : $role])) }}"
                               class="vendeur-pill {{ request('vendeur_role') === $role ? 'active' : '' }}">
                                <i class="fas {{ $vendeurConf[$role] ?? 'fa-user' }} text-[9px]"></i>
                                {{ $role }}
                            </a>
                        @endforeach
                        </div>
                    </div>
                </div>

                {{-- Sticky footer on Mobile Drawer --}}
                <div class="p-3 bg-white/95 backdrop-blur-md border-t border-slate-100 lg:hidden sticky bottom-0 z-20 shadow-lg">
                    <button type="submit" class="w-full py-3 rounded-2xl font-bold text-sm text-white flex items-center justify-center gap-2 shadow-md shadow-teal-700/20"
                            style="background: linear-gradient(135deg,#0d9488,#059669);">
                        <i class="fas fa-check"></i>
                        <span>Voir les résultats ({{ $articles->total() }})</span>
                    </button>
                </div>

            </form>
        </aside>

        {{-- ===== RIGHT COLUMN ===== --}}
        <div class="flex-1 min-w-0">

            {{-- Premium Search bar --}}
            <div class="search-bar-wrap">
                <form method="GET" action="{{ route('articles.index') }}" class="flex gap-3 items-center">
                    @foreach(request()->except(['q','page']) as $k => $v)
                        @if(!empty($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <div class="relative flex-1" id="search-wrapper">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-teal-500 text-sm"></i>
                        <input type="text" id="search-input" name="q" value="{{ $currentQ }}"
                               placeholder="Rechercher un article, une matière, un titre…" autocomplete="off"
                               class="w-full pl-11 pr-10 py-3 rounded-xl border-2 border-slate-200 text-sm focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all bg-slate-50 focus:bg-white font-medium placeholder-slate-400">
                        @if(request()->filled('q'))
                            <a href="{{ route('articles.index', request()->except(['q','page'])) }}"
                               class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" style="text-decoration:none">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        @endif
                        <div id="suggest-list" class="hidden"></div>
                    </div>
                    <button type="submit"
                            class="flex-shrink-0 px-5 py-3 rounded-xl font-bold text-sm text-white flex items-center gap-2 transition-all"
                            style="background: linear-gradient(135deg,#0d9488,#059669); box-shadow: 0 4px 12px rgba(13,148,136,0.3);">
                        <i class="fas fa-search"></i>
                        <span>Rechercher</span>
                    </button>
                </form>
            </div>

            {{-- Premium Sort bar --}}
            <div class="sort-bar">
                <div class="flex items-center flex-wrap gap-1.5">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Trier :</span>
                    @php
                    $sortOptions = [
                        'recent'    => ['label'=>'Récents', 'icon'=>'fa-clock'],
                        'prix_asc'  => ['label'=>'Prix croissant', 'icon'=>'fa-sort-amount-up'],
                        'prix_desc' => ['label'=>'Prix décroissant', 'icon'=>'fa-sort-amount-down'],
                    ];
                    @endphp
                    @foreach($sortOptions as $key => $opt)
                        <a href="{{ route('articles.index', array_merge(request()->except(['tri','page']), ['tri' => $key])) }}"
                           class="sort-btn {{ $currentTri === $key ? 'active' : '' }}">
                            <i class="fas {{ $opt['icon'] }} text-[9px]"></i>
                            {{ $opt['label'] }}
                        </a>
                    @endforeach
                </div>
                <div class="flex items-center gap-3">
                    {{-- View mode switcher --}}
                    <div class="hidden sm:flex items-center gap-0.5 bg-slate-100 p-0.5 rounded-xl border border-slate-200">
                        <button type="button" onclick="setViewMode('grid')" class="view-btn active" data-view="grid" title="Grille standard">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" onclick="setViewMode('compact')" class="view-btn" data-view="compact" title="Grille compacte (4 colonnes)">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" onclick="setViewMode('list')" class="view-btn" data-view="list" title="Vue liste">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>
                    <div class="text-xs text-slate-400 whitespace-nowrap">
                        <span class="font-extrabold text-slate-700 text-sm">{{ $articles->total() }}</span>
                        <span class="ml-1">résultat{{ $articles->total() > 1 ? 's' : '' }}</span>
                    </div>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if($activeFilters > 0)
                <div class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-white/70 backdrop-blur-sm rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 mr-1">
                        <i class="fas fa-filter text-teal-600 text-[10px]"></i> Filtres :
                    </span>
                    @foreach(['q' => 'Recherche', 'categorie' => 'Catégorie', 'taille' => 'Taille', 'matiere' => 'Matière', 'etat' => 'État', 'couleur' => 'Couleur', 'statut' => 'Statut', 'prix_min' => 'Prix min', 'prix_max' => 'Prix max', 'vendeur_role' => 'Vendeur'] as $param => $label)
                        @if(request()->filled($param))
                            <span class="active-chip">
                                <span>{{ $label }}:</span>
                                <strong>{{ request($param) }}</strong>
                                <a href="{{ route('articles.index', array_merge(request()->except([$param,'page']))) }}" title="Supprimer ce filtre"><i class="fas fa-times-circle"></i></a>
                            </span>
                        @endif
                    @endforeach
                    @if(request()->boolean('mine') || request()->get('filter') === 'mine')
                        <span class="active-chip"><span>Mes articles</span> <a href="{{ route('articles.index', array_merge(request()->except(['mine','filter','page']))) }}"><i class="fas fa-times-circle"></i></a></span>
                    @endif
                    @if(request()->boolean('en_stock'))
                        <span class="active-chip"><span>En stock</span> <a href="{{ route('articles.index', array_merge(request()->except(['en_stock','page']))) }}"><i class="fas fa-times-circle"></i></a></span>
                    @endif
                    @if(request()->boolean('gratuits'))
                        <span class="active-chip"><span>Gratuits (0 DT)</span> <a href="{{ route('articles.index', array_merge(request()->except(['gratuits','page']))) }}"><i class="fas fa-times-circle"></i></a></span>
                    @endif
                    @if($activeFilters >= 2)
                        <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 transition-colors ml-auto" style="text-decoration:none">
                            <i class="fas fa-trash-alt text-[9px]"></i> Tout effacer
                        </a>
                    @endif
                </div>
            @endif

        {{-- ===== ARTICLES GRID ===== --}}
        @if($articles->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5" id="articles-grid">
                @foreach($articles as $article)
                    @php
                        $isOwner = Auth::id() === $article->user_id;
                        $isAdmin = Auth::user()->isAdmin();
                        $statutClass = match($article->statut) {
                            'DISPONIBLE' => 'badge-statut-disponible',
                            'RESERVE'    => 'badge-statut-reserve',
                            'VENDU'      => 'badge-statut-vendu',
                            default      => 'badge-statut-archive',
                        };
                        $coverImg = $article->getFirstImageUrl();
                    @endphp

                    <div class="article-card group">

                        {{-- Card Header / Banner (Photo or Pattern) --}}
                        <div class="card-banner">
                            @if($coverImg)
                                <img src="{{ $coverImg }}" alt="{{ $article->titre }}">
                            @else
                                <div class="card-banner-pattern"></div>
                                <div class="text-white/30 group-hover:scale-110 transition-transform duration-300">
                                    @if(str_contains(strtolower($article->categorie), 'homme'))
                                        <i class="fas fa-tshirt text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'femme'))
                                        <i class="fas fa-vest text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'enfant'))
                                        <i class="fas fa-baby text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'sac') || str_contains(strtolower($article->categorie), 'accessoire'))
                                        <i class="fas fa-shopping-bag text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'upcycl'))
                                        <i class="fas fa-recycle text-5xl text-emerald-400/40"></i>
                                    @else
                                        <i class="fas fa-cut text-5xl"></i>
                                    @endif
                                </div>
                            @endif

                            {{-- Category Badge --}}
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 backdrop-blur-sm text-slate-800 shadow-sm">
                                    {{ $article->categorie }}
                                </span>
                            </div>

                            {{-- Status Badge --}}
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statutClass }} shadow-sm">
                                    {{ $article->statut }}
                                </span>
                            </div>

                            {{-- Photo count badge if multiple --}}
                            @if($article->images && count($article->images) > 1)
                                <div class="absolute bottom-2 right-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-black/60 text-white backdrop-blur-sm">
                                        <i class="fas fa-camera"></i> {{ count($article->images) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Donation badge if linked --}}
                            @if($article->don_vetement_id)
                                <div class="absolute bottom-2 left-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-500/80 text-white backdrop-blur-sm" title="Issu d'un don textile">
                                        <i class="fas fa-hand-holding-heart"></i> Don
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-display font-bold text-slate-900 text-sm mb-1 line-clamp-1 group-hover:text-teal-600 transition-colors">
                                    <a href="{{ route('articles.show', $article) }}" style="text-decoration:none; color:inherit;">
                                        {{ $article->titre }}
                                    </a>
                                </h3>
                                {{-- Meta badges --}}
                                <div class="flex flex-wrap gap-1 mb-1.5">
                                    @if($article->taille)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold"><i class="fas fa-ruler text-[8px]"></i>{{ $article->taille }}</span>
                                    @endif
                                    @if($article->etat)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-semibold"><i class="fas fa-star text-[8px]"></i>{{ $article->etat }}</span>
                                    @endif
                                    @if($article->couleur)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-semibold"><i class="fas fa-palette text-[8px]"></i>{{ $article->couleur }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 line-clamp-2 mb-2 leading-relaxed">
                                    {{ $article->description ?: 'Aucune description fournie.' }}
                                </p>
                            </div>

                            <div>
                                {{-- Price & Stock --}}
                                <div class="flex items-baseline justify-between pt-2 border-t border-slate-100 mb-2">
                                    <span class="font-display font-extrabold text-lg text-teal-700">{{ number_format($article->prix, 2) }} DT</span>
                                    @if($article->stock > 0)
                                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">{{ $article->stock }} en stock</span>
                                    @else
                                        <span class="text-[11px] font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-md">Rupture</span>
                                    @endif
                                </div>
                                @if($article->co2_economise_kg)
                                    <div class="flex items-center gap-1 mb-1.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 rounded-lg px-2 py-1">
                                        <i class="fas fa-leaf"></i>{{ number_format($article->co2_economise_kg, 2) }} kg CO₂ économisé
                                    </div>
                                @endif

                                {{-- Seller info --}}
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                                    <div class="flex items-center gap-2 truncate">
                                        <div class="w-6 h-6 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                            {{ $article->user->initials }}
                                        </div>
                                        <span class="truncate font-medium text-slate-700">
                                            {{ $article->user->full_name ?: $article->user->name }}
                                        </span>
                                    </div>

                                    @if($isOwner)
                                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded">Vous</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card Actions Footer --}}
                        <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a href="{{ route('articles.show', $article) }}"
                               class="flex-1 text-center py-2 px-3 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 transition-colors"
                               style="text-decoration:none">
                                <i class="fas fa-eye mr-1"></i> Détails
                            </a>

                            @if($article->isDisponible() && !$isOwner)
                                <form method="POST" action="{{ route('panier.ajouter', $article) }}" class="inline add-to-cart-form">
                                    @csrf
                                    <input type="hidden" name="quantite" value="1">
                                    <button type="submit"
                                            class="py-2 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cart-btn"
                                            title="Ajouter au panier"
                                            data-article-id="{{ $article->id }}"
                                            data-article-titre="{{ $article->titre }}">
                                        <i class="fas fa-cart-plus"></i>
                                        <span>Panier</span>
                                    </button>
                                </form>
                            @endif

                            {{-- Edit & Delete buttons: ONLY for owner or admin --}}
                            @can('update', $article)
                                <a href="{{ route('articles.edit', $article) }}"
                                   class="p-2 rounded-xl bg-white hover:bg-teal-50 border border-slate-200 text-teal-600 text-xs font-semibold transition-colors"
                                   title="Modifier cet article"
                                   style="text-decoration:none">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form method="POST"
                                      action="{{ route('articles.destroy', $article) }}"
                                      data-confirm="Êtes-vous sûr de vouloir supprimer l'article « {{ addslashes($article->titre) }} » ? Cette action est irréversible."
                                      data-confirm-title="Supprimer cet article"
                                      data-confirm-button="Oui, supprimer"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-xl bg-white hover:bg-red-50 border border-slate-200 text-red-500 text-xs font-semibold transition-colors"
                                            title="Supprimer cet article">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">{{ $articles->links() }}</div>

        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-10 text-center max-w-md mx-auto shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-box-open"></i></div>
                <h3 class="font-display font-bold text-slate-800 text-lg mb-1">Aucun article trouvé</h3>
                <p class="text-xs text-slate-500 mb-5">
                    @if($activeFilters > 0) Aucun article ne correspond à vos filtres. @else La marketplace est encore vide. @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    @if($activeFilters > 0)
                        <a href="{{ route('articles.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors" style="text-decoration:none">Réinitialiser</a>
                    @endif
                    @can('create', \App\Models\Article::class)
                        <a href="{{ route('articles.create') }}" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-colors" style="text-decoration:none"><i class="fas fa-plus mr-1"></i> Publier</a>
                    @endcan
                </div>
            </div>
        @endif

        </div>{{-- end right column --}}
        </div>{{-- end flex row --}}

    </div>{{-- end max-w-7xl --}}

    {{-- Floating Action Button on Mobile --}}
    <button type="button" onclick="openMobileFilters()" class="mobile-filter-fab" title="Ouvrir les filtres">
        <i class="fas fa-sliders-h text-sm"></i>
        <span>Filtres</span>
        @if($activeFilters > 0)
            <span class="header-badge">{{ $activeFilters }}</span>
        @endif
    </button>

</div>{{-- end marketplace-page --}}
@endsection

@section('scripts')
<script>
// ===== Mobile Drawer Toggle =====
function openMobileFilters() {
    const wrapper = document.getElementById('filter-sidebar-wrapper');
    if (wrapper) {
        wrapper.classList.add('mobile-open');
        document.body.style.overflow = 'hidden';
    }
}
function closeMobileFilters() {
    const wrapper = document.getElementById('filter-sidebar-wrapper');
    if (wrapper) {
        wrapper.classList.remove('mobile-open');
        document.body.style.overflow = '';
    }
}

// ===== View Mode Switcher (Grid / Compact / List) =====
function setViewMode(mode) {
    const grid = document.getElementById('articles-grid');
    if (!grid) return;
    grid.classList.remove('view-compact', 'view-list');
    if (mode === 'compact') grid.classList.add('view-compact');
    if (mode === 'list') grid.classList.add('view-list');
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === mode);
    });
    try { localStorage.setItem('retiss_article_view', mode); } catch(e){}
}

// ===== Quick Price Presets =====
function setPricePreset(min, max) {
    const minInput = document.getElementById('prix_min_input');
    const maxInput = document.getElementById('prix_max_input');
    if (minInput) minInput.value = (min !== null && min !== '') ? min : '';
    if (maxInput) maxInput.value = (max !== null && max !== '') ? max : '';
    const form = document.getElementById('filter-form');
    if (form) form.submit();
}

// ===== Accordion Sections =====
function toggleSection(btn) {
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', !expanded);
}

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Restore saved view mode
    try {
        const savedView = localStorage.getItem('retiss_article_view');
        if (savedView) setViewMode(savedView);
    } catch(e){}

    // Keyboard Shortcuts (Ctrl+K or '/' for search, Escape for drawer)
    document.addEventListener('keydown', function(e) {
        if ((e.key === '/' || (e.ctrlKey && e.key === 'k')) && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            const searchInput = document.getElementById('search-input');
            if (searchInput) { searchInput.focus(); searchInput.select(); }
        }
        if (e.key === 'Escape') {
            closeMobileFilters();
        }
    });

    // ===== Autocomplete =====
    const searchInput = document.getElementById('search-input');
    const suggestList = document.getElementById('suggest-list');
    let debounceTimer;
    if (searchInput && suggestList) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { suggestList.classList.add('hidden'); return; }
            debounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`/articles/suggest?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                    const items = await res.json();
                    if (!items.length) { suggestList.classList.add('hidden'); return; }
                    suggestList.innerHTML = items.map(t => `<div class="suggest-item" data-value="${t}">${t}</div>`).join('');
                    suggestList.classList.remove('hidden');
                } catch(e) { suggestList.classList.add('hidden'); }
            }, 250);
        });
        suggestList.addEventListener('click', function(e) {
            const item = e.target.closest('.suggest-item');
            if (item) { searchInput.value = item.dataset.value; suggestList.classList.add('hidden'); searchInput.closest('form').submit(); }
        });
        document.addEventListener('click', e => { if (!e.target.closest('#search-wrapper')) suggestList.classList.add('hidden'); });
    }

    // ===== Add to cart =====
    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = form.querySelector('.cart-btn');
            if (!btn || btn.disabled) return;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quantite: 1 }),
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i><span>Ajouté !</span>';
                    btn.classList.replace('bg-teal-600','bg-emerald-600');
                    btn.classList.replace('hover:bg-teal-700','hover:bg-emerald-700');
                    if (data.resume?.nb_articles !== undefined) {
                        document.querySelectorAll('.cart-badge-count').forEach(b => {
                            b.textContent = data.resume.nb_articles > 99 ? '99+' : data.resume.nb_articles;
                            b.style.display = data.resume.nb_articles > 0 ? 'flex' : 'none';
                        });
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('bg-emerald-600','bg-teal-600');
                        btn.classList.replace('hover:bg-emerald-700','hover:bg-teal-700');
                        btn.disabled = false;
                    }, 1800);
                } else {
                    retissAlert({ title: 'Attention', text: data.message || data.error || "Erreur lors de l'ajout au panier.", icon: 'warning' });
                    btn.innerHTML = originalHtml; btn.disabled = false;
                }
            } catch (err) {
                retissAlert({ title: 'Erreur', text: "Erreur réseau lors de l'ajout au panier.", icon: 'error' });
                btn.innerHTML = originalHtml; btn.disabled = false;
            }
        });
    });

    // Smooth scroll to active filter chip if present
    const chips = document.querySelector('.active-chip');
    if (chips) chips.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
});
</script>
@endsection

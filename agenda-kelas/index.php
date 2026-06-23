<?php
/**
 * Landing Page - Agenda Kelas (Redesign Premium)
 * File: index.php
 */

require_once 'config/session.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'siswa':
            header("Location: siswa/dashboard.php");
            break;
        case 'sekretaris':
            header("Location: sekre/dashboard.php");
            break;
        case 'guru':
            header("Location: guru/dashboard.php");
            break;
        case 'walikelas':
            header("Location: walikelas/dashboard.php");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaKelas — Sistem Agenda & Kehadiran Digital</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: DM Sans + Inter -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* =========================================
           TOKENS
        ========================================= */
        :root {
            --amber:       #D4A017;   /* kuning gelap utama */
            --amber-hover: #B8880E;   /* hover lebih gelap */
            --amber-soft:  #FBF0CC;   /* background badge / soft fill */
            --amber-mid:   #E8C247;   /* untuk gradient/accent ringan */
            --dark:        #111827;
            --ink:         #1F2937;
            --muted:       #6B7280;
            --border:      #E5E7EB;
            --surface:     #FFFFFF;
            --canvas:      #F9FAFB;
            --shadow-xs:   0 1px 2px rgba(0,0,0,.06);
            --shadow-sm:   0 2px 8px rgba(0,0,0,.08);
            --shadow-md:   0 6px 20px rgba(0,0,0,.10);
            --shadow-lg:   0 16px 40px rgba(0,0,0,.12);
            --shadow-xl:   0 28px 64px rgba(0,0,0,.15);
            --r-sm:        10px;
            --r-md:        16px;
            --r-lg:        24px;
            --r-xl:        32px;
        }

        /* =========================================
           BASE
        ========================================= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--canvas);
            color: var(--ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, .display-font {
            font-family: 'DM Sans', sans-serif;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--amber); border-radius: 6px; }

        /* =========================================
           NAVBAR
        ========================================= */
        .site-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid transparent;
            transition: border-color .3s, box-shadow .3s;
            padding: .75rem 0;
        }

        .site-nav.scrolled {
            border-bottom-color: var(--border);
            box-shadow: var(--shadow-sm);
        }

        .nav-logo {
            font-family: 'DM Sans', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--dark) !important;
            text-decoration: none;
            letter-spacing: -.4px;
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .nav-logo .logo-dot {
            width: 10px; height: 10px;
            background: var(--amber);
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .site-nav .nav-link {
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            font-size: .875rem;
            color: var(--muted) !important;
            padding: .4rem .75rem !important;
            border-radius: var(--r-sm);
            transition: color .2s, background .2s;
        }

        .site-nav .nav-link:hover {
            color: var(--dark) !important;
            background: var(--amber-soft);
        }

        .btn-nav-login {
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .875rem;
            background: var(--dark);
            color: #fff !important;
            padding: .5rem 1.25rem;
            border-radius: var(--r-sm);
            text-decoration: none;
            transition: background .25s, transform .2s, box-shadow .2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .btn-nav-login:hover {
            background: var(--amber);
            color: var(--dark) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(212,160,23,.35);
        }

        /* =========================================
           HERO
        ========================================= */
        .hero {
            min-height: 92vh;
            display: grid;
            align-items: center;
            padding: 5rem 0 4rem;
            position: relative;
            overflow: hidden;
        }

        /* subtle grid pattern */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(212,160,23,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,160,23,.06) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* amber blob top-right */
        .hero-blob {
            position: absolute;
            top: -120px;
            right: -160px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(212,160,23,.18) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--amber-soft);
            border: 1px solid rgba(212,160,23,.4);
            color: var(--amber-hover);
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: .35rem .85rem;
            border-radius: 50px;
            margin-bottom: 1.5rem;
        }

        .hero-eyebrow span {
            width: 6px; height: 6px;
            background: var(--amber);
            border-radius: 50%;
            display: inline-block;
            animation: blink 1.6s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }

        .hero-title {
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(2.4rem, 5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.03em;
            color: var(--dark);
            margin-bottom: 1.25rem;
        }

        .hero-title .line-accent {
            position: relative;
            display: inline-block;
            color: var(--amber-hover);
        }

        .hero-title .line-accent::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--amber), var(--amber-mid));
            border-radius: 4px;
            transform-origin: left;
            animation: underline-grow 1s .6s ease both;
        }

        @keyframes underline-grow {
            from { transform: scaleX(0); }
            to   { transform: scaleX(1); }
        }

        .hero-body {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.7;
            max-width: 460px;
            margin-bottom: 2rem;
        }

        .hero-cta-group {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-primary-custom {
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .95rem;
            background: var(--amber);
            color: var(--dark);
            padding: .75rem 1.75rem;
            border-radius: var(--r-md);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            transition: background .25s, transform .2s, box-shadow .25s;
            box-shadow: 0 4px 18px rgba(212,160,23,.3);
        }

        .btn-primary-custom:hover {
            background: var(--amber-hover);
            color: var(--dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(212,160,23,.4);
        }

        .btn-ghost {
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: .95rem;
            background: transparent;
            color: var(--ink);
            padding: .75rem 1.5rem;
            border-radius: var(--r-md);
            border: 1.5px solid var(--border);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            transition: border-color .2s, background .2s, transform .2s;
        }

        .btn-ghost:hover {
            border-color: var(--amber);
            background: var(--amber-soft);
            color: var(--dark);
            transform: translateY(-2px);
        }

        .hero-trust {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .hero-trust-item {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            color: var(--muted);
            font-weight: 500;
        }

        .hero-trust-item i {
            color: var(--amber);
            font-size: .85rem;
        }

        /* Hero Visual Card */
        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .hero-card-main {
            background: var(--surface);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow-xl);
            padding: 1.75rem;
            width: 100%;
            max-width: 380px;
            border: 1px solid var(--border);
            position: relative;
            z-index: 2;
            animation: floatCard 5s ease-in-out infinite;
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .hero-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .card-avatar-group {
            display: flex;
        }

        .card-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid var(--surface);
            margin-left: -8px;
            background: var(--amber-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            font-weight: 700;
            color: var(--amber-hover);
        }

        .card-avatar:first-child { margin-left: 0; }

        .card-badge-live {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #DCFCE7;
            color: #15803D;
            font-size: .7rem;
            font-weight: 700;
            padding: .25rem .65rem;
            border-radius: 50px;
        }

        .card-badge-live::before {
            content: '';
            width: 6px; height: 6px;
            background: #22C55E;
            border-radius: 50%;
            animation: blink 1.4s infinite;
        }

        .attendance-list { display: flex; flex-direction: column; gap: .6rem; }

        .att-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .65rem .85rem;
            background: var(--canvas);
            border-radius: var(--r-sm);
            font-size: .82rem;
        }

        .att-row .att-name {
            font-weight: 600;
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
        }

        .att-status {
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .6rem;
            border-radius: 50px;
        }

        .att-status.hadir    { background: #DCFCE7; color: #15803D; }
        .att-status.izin     { background: #FEF9C3; color: #A16207; }
        .att-status.alpha    { background: #FEE2E2; color: #B91C1C; }

        .hero-card-footer {
            margin-top: 1.2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .progress-label { font-size: .75rem; color: var(--muted); font-weight: 500; }

        .progress-bar-custom {
            flex: 1;
            height: 6px;
            background: var(--border);
            border-radius: 6px;
            margin: 0 .75rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--amber), var(--amber-mid));
            border-radius: 6px;
            width: 82%;
            animation: progress-load 1.8s 1s ease both;
            transform-origin: left;
        }

        @keyframes progress-load {
            from { width: 0; }
            to { width: 82%; }
        }

        .progress-pct {
            font-size: .75rem;
            font-weight: 700;
            color: var(--amber-hover);
            font-family: 'DM Sans', sans-serif;
        }

        /* Floating badge cards */
        .floating-badge {
            position: absolute;
            background: var(--surface);
            border-radius: var(--r-md);
            padding: .65rem 1rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 700;
            color: var(--dark);
            z-index: 3;
            white-space: nowrap;
        }

        .fb-qr {
            top: -24px;
            right: -20px;
            animation: floatCard 4s 1s ease-in-out infinite;
        }

        .fb-export {
            bottom: -18px;
            left: -24px;
            animation: floatCard 4.5s .5s ease-in-out infinite;
        }

        /* =========================================
           SECTION LABELS
        ========================================= */
        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .7rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--amber-hover);
            margin-bottom: .75rem;
        }

        .section-eyebrow::before {
            content: '';
            display: inline-block;
            width: 20px; height: 2px;
            background: var(--amber);
            border-radius: 2px;
        }

        .section-title {
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--dark);
            line-height: 1.15;
            margin-bottom: .75rem;
        }

        .section-sub {
            font-size: .95rem;
            color: var(--muted);
            line-height: 1.6;
            max-width: 520px;
            margin: 0 auto;
        }

        /* =========================================
           FEATURES
        ========================================= */
        .features-section {
            padding: 6rem 0;
            background: var(--canvas);
        }

        .feat-card {
            background: var(--surface);
            border-radius: var(--r-xl);
            padding: 2rem 1.75rem;
            border: 1px solid var(--border);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--amber), var(--amber-mid));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s ease;
        }

        .feat-card:hover::after { transform: scaleX(1); }

        .feat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(212,160,23,.3);
        }

        .feat-icon-wrap {
            width: 54px; height: 54px;
            background: var(--amber-soft);
            border-radius: var(--r-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            transition: transform .3s;
        }

        .feat-card:hover .feat-icon-wrap { transform: rotate(-4deg) scale(1.08); }

        .feat-icon-wrap i {
            font-size: 1.5rem;
            color: var(--amber-hover);
        }

        .feat-role-tag {
            display: inline-block;
            background: var(--amber-soft);
            color: var(--amber-hover);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .2rem .65rem;
            border-radius: 50px;
            margin-bottom: .6rem;
        }

        .feat-title {
            font-family: 'DM Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: .5rem;
        }

        .feat-desc {
            font-size: .85rem;
            color: var(--muted);
            line-height: 1.65;
        }

        .feat-list {
            list-style: none;
            margin-top: .85rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .feat-list li {
            font-size: .8rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .feat-list li::before {
            content: '';
            width: 5px; height: 5px;
            background: var(--amber);
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* =========================================
           HOW IT WORKS — TIMELINE
        ========================================= */
        .how-section {
            padding: 6rem 0;
            background: var(--surface);
        }

        .steps-track {
            position: relative;
            display: flex;
            gap: 0;
        }

        .steps-track::before {
            content: '';
            position: absolute;
            top: 28px;
            left: 28px;
            right: 28px;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }

        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 0 .5rem;
        }

        .step-num {
            width: 56px; height: 56px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--muted);
            margin-bottom: 1.25rem;
            transition: all .35s ease;
            position: relative;
            background: var(--canvas);
        }

        .step-item:hover .step-num,
        .step-item.active .step-num {
            background: var(--amber);
            border-color: var(--amber);
            color: var(--dark);
            box-shadow: 0 0 0 6px rgba(212,160,23,.2);
        }

        .step-icon-area {
            width: 52px; height: 52px;
            background: var(--amber-soft);
            border-radius: var(--r-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto .9rem;
        }

        .step-icon-area i {
            font-size: 1.4rem;
            color: var(--amber-hover);
        }

        .step-item h5 {
            font-family: 'DM Sans', sans-serif;
            font-size: .95rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: .4rem;
        }

        .step-item p {
            font-size: .78rem;
            color: var(--muted);
            line-height: 1.5;
        }

        /* =========================================
           STATS
        ========================================= */
        .stats-section {
            padding: 5rem 0;
            background: var(--dark);
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(212,160,23,.12), transparent 60%);
            border-radius: 50%;
            pointer-events: none;
        }

        .stats-section::after {
            content: '';
            position: absolute;
            bottom: -40%;
            right: -5%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(212,160,23,.08), transparent 60%);
            border-radius: 50%;
            pointer-events: none;
        }

        .stat-item {
            text-align: center;
            padding: 2rem 1rem;
            position: relative;
        }

        .stat-num {
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            font-weight: 800;
            color: var(--amber);
            line-height: 1;
            margin-bottom: .35rem;
            letter-spacing: -.02em;
        }

        .stat-lbl {
            font-size: .85rem;
            color: rgba(255,255,255,.55);
            font-weight: 500;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,.1);
            align-self: stretch;
        }

        /* =========================================
           CTA
        ========================================= */
        .cta-section {
            padding: 5rem 0 6rem;
            background: var(--canvas);
        }

        .cta-box {
            background: var(--dark);
            border-radius: 40px;
            padding: 4rem 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(212,160,23,.2);
        }

        .cta-box::before {
            content: '';
            position: absolute;
            top: -60%;
            right: -10%;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(212,160,23,.15), transparent 60%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-box::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -5%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(212,160,23,.1), transparent 60%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-box h2 {
            font-family: 'DM Sans', sans-serif;
            font-size: clamp(1.75rem, 3vw, 2.6rem);
            font-weight: 800;
            color: #fff;
            letter-spacing: -.025em;
            line-height: 1.2;
            margin-bottom: .75rem;
        }

        .cta-box p {
            color: rgba(255,255,255,.55);
            font-size: .95rem;
        }

        .btn-cta-main {
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            background: var(--amber);
            color: var(--dark);
            padding: .85rem 2rem;
            border-radius: var(--r-md);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: background .25s, transform .2s, box-shadow .25s;
            box-shadow: 0 6px 20px rgba(212,160,23,.3);
        }

        .btn-cta-main:hover {
            background: #EFBE30;
            color: var(--dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(212,160,23,.45);
        }

        /* =========================================
           FOOTER
        ========================================= */
        .site-footer {
            background: #0A0F1C;
            padding: 4.5rem 0 2rem;
        }

        .footer-logo {
            font-family: 'DM Sans', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .9rem;
        }

        .footer-logo .logo-amber { color: var(--amber); }

        .footer-tagline {
            font-size: .83rem;
            color: rgba(255,255,255,.4);
            line-height: 1.65;
            max-width: 260px;
        }

        .footer-social {
            display: flex;
            gap: .6rem;
            margin-top: 1.25rem;
        }

        .social-btn {
            width: 34px; height: 34px;
            border-radius: var(--r-sm);
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,.5);
            font-size: .85rem;
            text-decoration: none;
            transition: background .2s, color .2s, border-color .2s;
        }

        .social-btn:hover {
            background: var(--amber);
            color: var(--dark);
            border-color: var(--amber);
        }

        .footer-col-title {
            font-family: 'DM Sans', sans-serif;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            margin-bottom: 1rem;
        }

        .footer-links { list-style: none; display: flex; flex-direction: column; gap: .5rem; }

        .footer-links li a, .footer-links li span {
            font-size: .84rem;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            transition: color .2s;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .footer-links li a:hover { color: var(--amber); }

        .footer-links li i {
            font-size: .75rem;
            color: var(--amber);
            width: 14px;
            flex-shrink: 0;
        }

        .footer-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.07);
            margin: 2.5rem 0 1.5rem;
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .footer-copy {
            font-size: .75rem;
            color: rgba(255,255,255,.25);
        }

        .footer-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(212,160,23,.12);
            border: 1px solid rgba(212,160,23,.2);
            color: var(--amber);
            font-size: .7rem;
            font-weight: 700;
            padding: .25rem .7rem;
            border-radius: 50px;
        }

        /* =========================================
           UTILITIES & RESPONSIVE
        ========================================= */
        .py-section { padding: 5.5rem 0; }

        @media (max-width: 992px) {
            .hero { padding: 3.5rem 0 3rem; min-height: unset; }
            .hero-visual { margin-top: 3rem; }
            .steps-track { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
            .steps-track::before { display: none; }
            .step-item { display: flex; align-items: flex-start; gap: 1.25rem; text-align: left; }
            .step-num { flex-shrink: 0; margin-bottom: 0; }
        }

        @media (max-width: 768px) {
            .cta-box { padding: 2.5rem 1.75rem; border-radius: 28px; }
            .stat-divider { display: none; }
            .floating-badge { display: none; }
            .footer-bottom { justify-content: center; text-align: center; }
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="site-nav" id="mainNav">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <a href="index.php" class="nav-logo">
                <span class="logo-dot"></span>
                AgendaKelas
            </a>

            <ul class="nav d-none d-lg-flex align-items-center gap-1 mb-0">
                <li><a href="#home" class="nav-link">Beranda</a></li>
                <li><a href="#features" class="nav-link">Fitur</a></li>
                <li><a href="#how-it-works" class="nav-link">Cara Kerja</a></li>
                <li><a href="#contact" class="nav-link">Kontak</a></li>
            </ul>

            <a href="login.php" class="btn-nav-login">
                <i class="fas fa-arrow-right-to-bracket"></i>
                Masuk
            </a>
        </div>
    </div>
</nav>


<!-- ========== HERO ========== -->
<section id="home" class="hero">
    <div class="hero-blob"></div>
    <div class="container position-relative">
        <div class="row align-items-center">

            <!-- Left -->
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="700">
                <div class="hero-eyebrow">
                    <span></span>
                    Solusi Digital Sekolah
                </div>
                <h1 class="hero-title">
                    Kelola Agenda &<br>
                    <span class="line-accent">Kehadiran Kelas</span><br>
                    Lebih Mudah
                </h1>
                <p class="hero-body">
                    Sistem manajemen absensi dan agenda berbasis QR Code — dirancang untuk siswa, sekretaris, guru, dan wali kelas.
                </p>
                <div class="hero-cta-group">
                    <a href="login.php" class="btn-primary-custom">
                        Mulai Sekarang
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#how-it-works" class="btn-ghost">
                        <i class="fas fa-play-circle"></i>
                        Lihat Cara Kerja
                    </a>
                </div>
                <div class="hero-trust">
                    <div class="hero-trust-item"><i class="fas fa-check-circle"></i> 100% Gratis</div>
                    <div class="hero-trust-item"><i class="fas fa-shield-halved"></i> Data Terenkripsi</div>
                    <div class="hero-trust-item"><i class="fas fa-bolt"></i> Akses Real-Time</div>
                </div>
            </div>

            <!-- Right: mock card -->
            <div class="col-lg-6" data-aos="fade-left" data-aos-duration="700" data-aos-delay="100">
                <div class="hero-visual">
                    <!-- Floating badges -->
                    <div class="floating-badge fb-qr">
                        <i class="fas fa-qrcode" style="color:var(--amber);font-size:1rem;"></i>
                        QR Terscan
                    </div>
                    <div class="floating-badge fb-export">
                        <i class="fas fa-file-export" style="color:var(--amber);font-size:1rem;"></i>
                        Ekspor PDF / Excel
                    </div>

                    <!-- Main card -->
                    <div class="hero-card-main">
                        <div class="hero-card-header">
                            <div>
                                <div style="font-family:'DM Sans',sans-serif;font-weight:700;font-size:.9rem;color:var(--dark);">Absensi Hari Ini</div>
                                <div style="font-size:.72rem;color:var(--muted);margin-top:.15rem;">Senin, 10 Juni 2025</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.6rem;">
                                <div class="card-badge-live">Live</div>
                                <div class="card-avatar-group">
                                    <div class="card-avatar">A</div>
                                    <div class="card-avatar">B</div>
                                    <div class="card-avatar">+30</div>
                                </div>
                            </div>
                        </div>

                        <div class="attendance-list">
                            <div class="att-row">
                                <div class="att-name">Aldi Firmansyah</div>
                                <span class="att-status hadir">Hadir</span>
                            </div>
                            <div class="att-row">
                                <div class="att-name">Bunga Lestari</div>
                                <span class="att-status izin">Izin</span>
                            </div>
                            <div class="att-row">
                                <div class="att-name">Candra Wijaya</div>
                                <span class="att-status hadir">Hadir</span>
                            </div>
                            <div class="att-row">
                                <div class="att-name">Dewi Rahayu</div>
                                <span class="att-status alpha">Alpha</span>
                            </div>
                        </div>

                        <div class="hero-card-footer">
                            <span class="progress-label">Kehadiran</span>
                            <div class="progress-bar-custom">
                                <div class="progress-fill"></div>
                            </div>
                            <span class="progress-pct">82%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ========== FEATURES ========== -->
<section id="features" class="features-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Fitur Unggulan</div>
            <h2 class="section-title">Satu Sistem, Empat Role</h2>
            <p class="section-sub">Dirancang khusus untuk setiap peran dalam ekosistem kelas — dari siswa hingga wali kelas.</p>
        </div>

        <div class="row g-4">
            <!-- Siswa -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="80">
                <div class="feat-card">
                    <div class="feat-role-tag">Siswa</div>
                    <div class="feat-icon-wrap"><i class="fas fa-user-graduate"></i></div>
                    <h3 class="feat-title">Dashboard Siswa</h3>
                    <p class="feat-desc">Pantau kehadiran dan agenda kelas secara langsung dari satu halaman.</p>
                    <ul class="feat-list">
                        <li>Riwayat kehadiran</li>
                        <li>Lihat agenda harian</li>
                        <li>Ubah profil & password</li>
                    </ul>
                </div>
            </div>

            <!-- Sekretaris -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="160">
                <div class="feat-card">
                    <div class="feat-role-tag">Sekretaris</div>
                    <div class="feat-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
                    <h3 class="feat-title">Kelola Agenda</h3>
                    <p class="feat-desc">Buat dan atur agenda kelas, serta kelola kehadiran siswa dengan mudah.</p>
                    <ul class="feat-list">
                        <li>Input & edit agenda</li>
                        <li>Kelola status absensi</li>
                        <li>Generate QR Code</li>
                    </ul>
                </div>
            </div>

            <!-- Guru -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="240">
                <div class="feat-card">
                    <div class="feat-role-tag">Guru</div>
                    <div class="feat-icon-wrap"><i class="fas fa-chalkboard-user"></i></div>
                    <h3 class="feat-title">Validasi QR</h3>
                    <p class="feat-desc">Scan QR Code untuk memvalidasi absensi secara instan dan akurat.</p>
                    <ul class="feat-list">
                        <li>Scan QR real-time</li>
                        <li>Validasi kehadiran</li>
                        <li>Lihat riwayat absensi</li>
                    </ul>
                </div>
            </div>

            <!-- Wali Kelas -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="320">
                <div class="feat-card">
                    <div class="feat-role-tag">Wali Kelas</div>
                    <div class="feat-icon-wrap"><i class="fas fa-chart-line"></i></div>
                    <h3 class="feat-title">Monitoring & Laporan</h3>
                    <p class="feat-desc">Pantau statistik kehadiran dan ekspor laporan kapan saja.</p>
                    <ul class="feat-list">
                        <li>Dashboard statistik</li>
                        <li>Ekspor PDF & Excel</li>
                        <li>Monitoring real-time</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ========== HOW IT WORKS ========== -->
<section id="how-it-works" class="how-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Alur Kerja</div>
            <h2 class="section-title">Bagaimana Sistem Bekerja?</h2>
            <p class="section-sub">Proses yang sederhana dan terstruktur dari pencatatan hingga validasi.</p>
        </div>

        <div class="steps-track" data-aos="fade-up" data-aos-delay="100">
            <div class="step-item">
                <div class="step-num">1</div>
                <div>
                    <div class="step-icon-area"><i class="fas fa-user-check"></i></div>
                    <h5>Sekretaris Input</h5>
                    <p>Membuat agenda & menginput status kehadiran siswa.</p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div>
                    <div class="step-icon-area"><i class="fas fa-qrcode"></i></div>
                    <h5>Generate QR Code</h5>
                    <p>Sistem otomatis menghasilkan QR Code unik untuk sesi tersebut.</p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div>
                    <div class="step-icon-area"><i class="fas fa-camera"></i></div>
                    <h5>Guru Scan & Validasi</h5>
                    <p>Guru memindai QR untuk mengkonfirmasi dan memvalidasi data.</p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-num">4</div>
                <div>
                    <div class="step-icon-area"><i class="fas fa-chart-simple"></i></div>
                    <h5>Wali Kelas Monitor</h5>
                    <p>Laporan dan statistik langsung tersedia untuk dimonitor.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ========== STATS ========== -->
<section class="stats-section">
    <div class="container position-relative" style="z-index:1;">
        <div class="row g-0 align-items-center justify-content-center">

            <div class="col-6 col-md-3">
                <div class="stat-item" data-aos="fade-up" data-aos-delay="60">
                    <div class="stat-num" data-target="500">0+</div>
                    <div class="stat-lbl">Siswa Terdaftar</div>
                </div>
            </div>
            <div class="stat-divider d-none d-md-block"></div>
            <div class="col-6 col-md-3">
                <div class="stat-item" data-aos="fade-up" data-aos-delay="140">
                    <div class="stat-num" data-target="50">0+</div>
                    <div class="stat-lbl">Kelas Aktif</div>
                </div>
            </div>
            <div class="stat-divider d-none d-md-block"></div>
            <div class="col-6 col-md-3">
                <div class="stat-item" data-aos="fade-up" data-aos-delay="220">
                    <div class="stat-num" data-target="1250">0+</div>
                    <div class="stat-lbl">Absensi Tercatat</div>
                </div>
            </div>
            <div class="stat-divider d-none d-md-block"></div>
            

        </div>
    </div>
</section>


<!-- ========== CTA ========== -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box" data-aos="fade-up">
            <div class="row align-items-center position-relative" style="z-index:1;">
                <div class="col-lg-7">
                    <h2>Siap Digitalkan<br>Kelas Kamu?</h2>
                    <p class="mt-2">Bergabung sekarang dan kelola agenda serta kehadiran kelas dengan lebih efisien.</p>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0 text-lg-end">
                    <a href="login.php" class="btn-cta-main">
                        Masuk ke Dashboard
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ========== FOOTER ========== -->
<footer class="site-footer" id="contact">
    <div class="container">
        <div class="row g-5">

            <!-- Brand -->
            <div class="col-lg-4">
                <a href="index.php" class="footer-logo">
                    <i class="fas fa-calendar-alt" style="color:var(--amber);"></i>
                    Agenda<span class="footer-logo" style="color:var(--amber);display:contents;">Kelas</span>
                </a>
                <p class="footer-tagline">
                    Solusi digital untuk pengelolaan agenda dan kehadiran kelas yang efisien, akurat, dan mudah digunakan.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Navigasi -->
            <div class="col-6 col-md-2 col-lg-2">
                <div class="footer-col-title">Navigasi</div>
                <ul class="footer-links">
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#how-it-works">Cara Kerja</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
            </div>

            <!-- Fitur -->
            <div class="col-6 col-md-3 col-lg-3">
                <div class="footer-col-title">Fitur</div>
                <ul class="footer-links">
                    <li><a href="#">Manajemen Agenda</a></li>
                    <li><a href="#">Absensi Digital</a></li>
                    <li><a href="#">Validasi QR Code</a></li>
                    <li><a href="#">Ekspor Laporan</a></li>
                </ul>
            </div>

            <!-- Kontak -->
            <div class="col-md-3 col-lg-3">
                <div class="footer-col-title">Kontak</div>
                <ul class="footer-links">
                    <li>
                        <span>
                            <i class="fas fa-envelope"></i>
                            info@agendakelas.com
                        </span>
                    </li>
                    <li>
                        <span>
                            <i class="fas fa-phone"></i>
                            +62 812 3456 7890
                        </span>
                    </li>
                    <li>
                        <span>
                            <i class="fas fa-location-dot"></i>
                            Jakarta, Indonesia
                        </span>
                    </li>
                </ul>
            </div>

        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">
            <span class="footer-copy">&copy; 2025 AgendaKelas. Seluruh hak dilindungi.</span>
            <span class="footer-badge">
                <i class="fas fa-shield-halved"></i>
                Sistem Terenkripsi & Aman
            </span>
        </div>
    </div>
</footer>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 700, once: true, offset: 60 });

    // Navbar scroll shadow
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // Counter animation
    function runCounter(el) {
        const target  = parseInt(el.dataset.target);
        const suffix  = el.dataset.suffix || '+';
        const dur     = 1400;
        const step    = 16;
        const inc     = target / (dur / step);
        let cur       = 0;
        const timer   = setInterval(() => {
            cur += inc;
            if (cur >= target) {
                el.textContent = target.toLocaleString() + suffix;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(cur).toLocaleString() + suffix;
            }
        }, step);
    }

    // IntersectionObserver trigger
    const statNums = document.querySelectorAll('.stat-num[data-target]');
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { runCounter(e.target); io.unobserve(e.target); } });
    }, { threshold: .4 });

    statNums.forEach(el => io.observe(el));

    // Step hover highlight
    document.querySelectorAll('.step-item').forEach(item => {
        item.addEventListener('mouseenter', () => item.querySelector('.step-num').classList.add('active'));
        item.addEventListener('mouseleave', () => item.querySelector('.step-num').classList.remove('active'));
    });
</script>
</body>
</html>
<?php
require_once 'db.php';

// Stats globales
$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(statut = 'En attente') as en_attente,
        SUM(statut = 'En soin') as en_soin,
        SUM(niveau_priorite LIKE '1%') as priorite_critique
    FROM patients_urgence WHERE statut != 'Sorti'
")->fetch();

// Filtres
$filtre_statut   = $_GET['statut']    ?? 'tous';
$filtre_priorite = $_GET['priorite']  ?? 'tous';
$recherche       = trim($_GET['recherche'] ?? '');

$where  = ["statut != 'Sorti'"];
$params = [];

if ($filtre_statut !== 'tous') {
    $where[] = "statut = :statut";
    $params[':statut'] = $filtre_statut;
}
if ($filtre_priorite !== 'tous') {
    $where[] = "niveau_priorite LIKE :priorite";
    $params[':priorite'] = $filtre_priorite . '%';
}
if ($recherche !== '') {
    $where[] = "(nom LIKE :rech OR prenom LIKE :rech2 OR motif_consultation LIKE :rech3)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
    $params[':rech3'] = '%' . $recherche . '%';
}

$sql  = "SELECT * FROM patients_urgence WHERE " . implode(' AND ', $where) . " ORDER BY niveau_priorite ASC, heure_arrivee ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$now = new DateTime();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="60">
<title>UrgenceSN — Tableau de bord</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
    --rouge:    #E63946;
    --rouge-d:  #B02030;
    --orange:   #F4821A;
    --vert:     #16C784;
    --bleu:     #3B82F6;
    --fond:     #ffffff;
    --surface:  #0c2344f7;
    --surface2: #1c2030ef;
    --surface3: #051a30ea;
    --border:   rgba(255,255,255,0.07);
    --text:     #E2E8F0;
    --muted:    #64748B;
    --sidebar-w:250px;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
    font-family:'Bricolage Grotesque',sans-serif;
    
    background-image: url('Gemini_Generated_Image_2wegij2wegij2weg.png');
    background-repeat: no-repeat;
    background-position: center center;
    background-attachment: fixed;
    background-size: cover;

    color:var(--text);
    display:flex;
    min-height:100vh;
    overflow-x:hidden;
}

/* ══════════ SIDEBAR ══════════ */
.sidebar{
    width:var(--sidebar-w);
    background:var(--surface);
    display:flex;flex-direction:column;
    padding:0;
    position:fixed;height:100vh;z-index:200;
    border-right:1px solid var(--border);
}
.sidebar-logo{
    padding:28px 24px 24px;
    border-bottom:1px solid var(--border);
    position:relative;overflow:hidden;
}
.sidebar-logo::after{
    content:'';position:absolute;
    bottom:-40px;right:-40px;
    width:100px;height:100px;
    background:var(--rouge);opacity:.05;
    border-radius:50%;
}
.logo-badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(230,57,70,.12);
    border:1px solid rgba(230,57,70,.25);
    border-radius:8px;padding:4px 10px;
    margin-bottom:10px;
}
.logo-badge span{font-size:.7rem;font-weight:600;color:var(--rouge);letter-spacing:.08em;text-transform:uppercase;}
.sidebar-logo h2{
    font-family:'Syne',sans-serif;
    font-size:1.4rem;font-weight:800;
    color:#fff;letter-spacing:-.5px;
    line-height:1;
}
.sidebar-logo h2 em{color:var(--rouge);font-style:normal;}
.sidebar-logo p{font-size:.72rem;color:var(--muted);margin-top:4px;}

.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto;}
.nav-section-title{
    font-size:.65rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.1em;
    color:var(--muted);padding:12px 24px 6px;
}
.sidebar-nav a{
    display:flex;align-items:center;gap:12px;
    padding:10px 24px;color:#94A3B8;
    text-decoration:none;font-size:.855rem;font-weight:500;
    transition:all .2s;border-left:3px solid transparent;
    margin:1px 0;
}
.sidebar-nav a:hover{background:rgba(255,255,255,.04);color:var(--text);border-left-color:rgba(255,255,255,.2);}
.sidebar-nav a.active{background:rgba(230,57,70,.1);color:#fff;border-left-color:var(--rouge);}
.sidebar-nav a i{font-size:1rem;width:18px;flex-shrink:0;}
.nav-badge{
    margin-left:auto;background:var(--rouge);
    color:#fff;font-size:.65rem;font-weight:700;
    padding:2px 7px;border-radius:20px;
}

.sidebar-bottom{
    padding:16px 24px;
    border-top:1px solid var(--border);
}
.status-dot{
    display:flex;align-items:center;gap:8px;
    font-size:.75rem;color:var(--muted);margin-bottom:6px;
}
.pulse-dot{
    width:8px;height:8px;border-radius:50%;
    background:var(--vert);flex-shrink:0;
    animation:pulse 2s ease-in-out infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(22,199,132,.4)}50%{box-shadow:0 0 0 6px rgba(22,199,132,0)}}
.auto-refresh{
    font-size:.72rem;color:var(--muted);
    display:flex;align-items:center;gap:6px;
}
.refresh-bar{
    flex:1;height:3px;
    background:var(--surface2);
    border-radius:2px;overflow:hidden;
}
.refresh-fill{
    height:100%;background:var(--vert);
    width:0%;animation:fillbar 60s linear infinite;
    border-radius:2px;
}
@keyframes fillbar{0%{width:0%}100%{width:100%}}

/* ══════════ MAIN ══════════ */
.main{
    margin-left:var(--sidebar-w);
    flex:1;padding:0;
    max-width:calc(100% - var(--sidebar-w));
    display:flex;flex-direction:column;
}

.topbar{
    position:sticky;top:0;z-index:100;
    background:rgb(5, 8, 15);
    backdrop-filter:blur(12px);
    border-bottom:1px solid var(--border);
    padding:14px 32px;
    display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.topbar-left{display:flex;align-items:center;gap:16px;}
.topbar-left h1{
    font-family:'Syne',sans-serif;
    font-size:1.15rem;font-weight:700;color:#fff;
}
.topbar-left h1 span{color:var(--muted);font-size:.9rem;font-weight:400;}
.time-chip{
    display:flex;align-items:center;gap:6px;
    background:var(--surface2);border:1px solid var(--border);
    border-radius:8px;padding:5px 12px;
    font-size:.78rem;color:var(--muted);
}
.btn-primary{
    background:var(--rouge);color:#fff;
    padding:9px 18px;border:none;border-radius:10px;
    font-size:.855rem;font-weight:600;
    cursor:pointer;text-decoration:none;
    display:inline-flex;align-items:center;gap:7px;
    transition:all .2s;font-family:inherit;
    box-shadow:0 0 0 0 rgba(230,57,70,.4);
}
.btn-primary:hover{background:var(--rouge-d);box-shadow:0 0 20px rgba(230,57,70,.3);transform:translateY(-1px);}

.content{padding:28px 32px;}

/* ══════════ KPI GRID ══════════ */
.kpi-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;margin-bottom:24px;
}
.kpi-card{
    background:var(--surface3);
    border:1px solid var(--border);
    border-radius:16px;padding:20px;
    position:relative;overflow:hidden;
    transition:transform .2s,border-color .2s;
    cursor:default;
}
.kpi-card:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.14);}
.kpi-card::before{
    content:'';position:absolute;
    inset:0;opacity:0;
    background:radial-gradient(circle at top right, var(--accent-color) 0%, transparent 60%);
    transition:opacity .3s;
}
.kpi-card:hover::before{opacity:.05;}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;}
.kpi-icon{
    width:42px;height:42px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;
}
.kpi-trend{font-size:.72rem;font-weight:600;padding:3px 8px;border-radius:6px;}
.kpi-value{
    font-family:'Syne',sans-serif;
    font-size:2.4rem;font-weight:800;
    line-height:1;margin-bottom:4px;
}
.kpi-label{font-size:.78rem;color:var(--muted);font-weight:500;}

.kpi-card.total   { --accent-color:#3B82F6; }
.kpi-card.critique{ --accent-color:#E63946; }
.kpi-card.attente { --accent-color:#F4821A; }
.kpi-card.soin    { --accent-color:#16C784; }
.kpi-card.total    .kpi-icon{background:rgba(59,130,246,.12);color:#3B82F6;}
.kpi-card.critique .kpi-icon{background:rgba(230,57,70,.12);color:var(--rouge);}
.kpi-card.attente  .kpi-icon{background:rgba(244,130,26,.12);color:var(--orange);}
.kpi-card.soin     .kpi-icon{background:rgba(22,199,132,.12);color:var(--vert);}
.kpi-card.total    .kpi-value{color:#3B82F6;}
.kpi-card.critique .kpi-value{color:var(--rouge);}
.kpi-card.attente  .kpi-value{color:var(--orange);}
.kpi-card.soin     .kpi-value{color:var(--vert);}

/* Alert critique */
<?php if (($stats['priorite_critique'] ?? 0) > 0): ?>
.kpi-card.critique{border-color:rgba(230,57,70,.35);animation:criticalPulse 2s ease-in-out infinite;}
@keyframes criticalPulse{0%,100%{box-shadow:0 0 0 0 rgba(230,57,70,.15)}50%{box-shadow:0 0 20px rgba(230,57,70,.15)}}
<?php endif; ?>

/* ══════════ FILTERS ══════════ */
.filters-bar{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:14px;padding:14px 18px;
    display:flex;gap:10px;align-items:center;
    flex-wrap:wrap;margin-bottom:20px;
}
.search-box{
    position:relative;flex:1;min-width:220px;
}
.search-box i{
    position:absolute;left:12px;top:50%;
    transform:translateY(-50%);color:var(--muted);font-size:.95rem;
    pointer-events:none;
}
.search-box input{
    width:100%;padding:9px 12px 9px 36px;
    background:var(--surface2);border:1px solid var(--border);
    border-radius:10px;color:var(--text);font-size:.855rem;
    font-family:inherit;outline:none;
    transition:border-color .2s,box-shadow .2s;
}
.search-box input::placeholder{color:var(--muted);}
.search-box input:focus{border-color:rgba(59,130,246,.5);box-shadow:0 0 0 3px rgba(59,130,246,.1);}
.filter-select{
    padding:9px 14px;background:var(--surface2);
    border:1px solid var(--border);border-radius:10px;
    color:var(--text);font-size:.855rem;font-family:inherit;
    outline:none;cursor:pointer;
    transition:border-color .2s;
}
.filter-select:focus{border-color:rgba(59,130,246,.5);}
.btn-filter{
    padding:9px 16px;background:var(--surface2);
    border:1px solid var(--border);border-radius:10px;
    color:var(--text);font-size:.855rem;font-family:inherit;
    cursor:pointer;font-weight:500;transition:all .2s;
    display:inline-flex;align-items:center;gap:6px;
}
.btn-filter:hover{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);}
.btn-reset{
    font-size:.8rem;color:var(--rouge);text-decoration:none;
    font-weight:600;padding:4px;transition:opacity .2s;
}
.btn-reset:hover{opacity:.7;}

/* ══════════ TABLE ══════════ */
.table-card{
    background:var(--surface3);
    border:1px solid var(--border);
    border-radius:16px;overflow:hidden;
}
.table-head-bar{
    padding:16px 20px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
}
.table-head-bar h2{
    font-family:'Syne',sans-serif;
    font-size:.95rem;font-weight:700;color:#fff;
}
.count-pill{
    background:var(--surface2);border:1px solid var(--border);
    border-radius:20px;padding:3px 12px;
    font-size:.75rem;font-weight:600;color:var(--muted);
}
table{width:100%;border-collapse:collapse;}
thead th{
    background:rgba(255,255,255,.02);
    padding:11px 16px;font-size:.72rem;font-weight:700;
    color:var(--muted);text-transform:uppercase;letter-spacing:.07em;
    text-align:left;white-space:nowrap;border-bottom:1px solid var(--border);
}
tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(255,255,255,.025);}
td{padding:13px 16px;font-size:.855rem;vertical-align:middle;}

/* Priority stripe */
.row-p1 td:first-child{border-left:3px solid var(--rouge);}
.row-p2 td:first-child{border-left:3px solid var(--orange);}
.row-p3 td:first-child{border-left:3px solid var(--vert);}

/* Avatar */
.patient-cell{display:flex;align-items:center;gap:10px;}
.avatar{
    width:36px;height:36px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:.75rem;font-weight:800;flex-shrink:0;
    font-family:'Syne',sans-serif;
}
.av-p1{background:rgba(230,57,70,.15);color:var(--rouge);}
.av-p2{background:rgba(244,130,26,.15);color:var(--orange);}
.av-p3{background:rgba(22,199,132,.15);color:var(--vert);}
.patient-name{font-weight:600;color:#fff;font-size:.875rem;}
.patient-age{font-size:.73rem;color:var(--muted);margin-top:1px;}

/* Priority badge */
.badge-p{
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 10px;border-radius:8px;
    font-size:.75rem;font-weight:700;white-space:nowrap;
}
.badge-p1{background:rgba(230,57,70,.12);color:var(--rouge);border:1px solid rgba(230,57,70,.25);}
.badge-p2{background:rgba(244,130,26,.12);color:var(--orange);border:1px solid rgba(244,130,26,.25);}
.badge-p3{background:rgba(22,199,132,.12);color:var(--vert);border:1px solid rgba(22,199,132,.25);}
.badge-dot{width:6px;height:6px;border-radius:50%;}
.bd-p1{background:var(--rouge);animation:pulse 1.5s infinite;}
.bd-p2{background:var(--orange);}
.bd-p3{background:var(--vert);}

/* Status badge */
.badge-s{
    display:inline-block;padding:4px 12px;
    border-radius:20px;font-size:.75rem;font-weight:600;
}
.s-attente{background:rgba(59,130,246,.1);color:#60A5FA;border:1px solid rgba(59,130,246,.2);}
.s-soin   {background:rgba(244,130,26,.1);color:#FDBA74;border:1px solid rgba(244,130,26,.2);}

/* Wait time */
.wait-normal{color:var(--muted);font-size:.82rem;}
.wait-alert{color:var(--rouge);font-weight:700;font-size:.82rem;}
.wait-warn{color:var(--orange);font-weight:600;font-size:.82rem;}

/* Select statut */
.select-statut{
    padding:6px 10px;background:var(--surface2);
    border:1px solid var(--border);border-radius:8px;
    color:var(--text);font-size:.8rem;font-family:inherit;
    cursor:pointer;outline:none;transition:border-color .2s;
    appearance:auto;
}
.select-statut:focus{border-color:rgba(59,130,246,.5);}

/* Motif */
.motif-txt{
    max-width:200px;overflow:hidden;
    text-overflow:ellipsis;white-space:nowrap;
    font-size:.82rem;color:var(--muted);
}

/* Tel */
.tel-txt{font-size:.8rem;color:var(--muted);}

/* Empty */
.empty-state{text-align:center;padding:70px 20px;color:var(--muted);}
.empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px;}
.empty-state p{font-size:.9rem;}

/* ══════════ RESPONSIVE ══════════ */
@media(max-width:1100px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:900px){
    .sidebar{width:64px;}
    .sidebar-logo h2,.sidebar-logo p,.logo-badge,.nav-section-title,
    .sidebar-nav a span,.nav-badge,.sidebar-bottom .status-dot span,
    .auto-refresh span,.refresh-bar{display:none;}
    .sidebar-logo{padding:20px 16px;}
    .sidebar-nav a{padding:12px 20px;justify-content:center;}
    .sidebar-nav a i{width:auto;}
    .main{margin-left:64px;max-width:calc(100% - 64px);}
    .kpi-grid{grid-template-columns:repeat(2,1fr);}
    .content{padding:20px;}
    .topbar{padding:12px 20px;}
}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge"><span>● Live</span></div>
        <h2><em>Urgence</em>SN</h2>
        <p>Centre Hospitalier de Dakar</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Navigation</div>
        <a href="index.php" class="active">
            <i class="bi bi-grid-fill"></i>
            <span>Tableau de bord</span>
            <?php if (($stats['total'] ?? 0) > 0): ?>
            <span class="nav-badge"><?= $stats['total'] ?></span>
            <?php endif; ?>
        </a>
        <a href="admission_patient.php">
            <i class="bi bi-person-plus-fill"></i>
            <span>Nouvelle admission</span>
        </a>
        <a href="patients_sortis.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Patients sortis</span>
        </a>
        <a href="statistiques.php">
            <i class="bi bi-bar-chart-fill"></i>
            <span>Statistiques</span>
        </a>
    </nav>
    <div class="sidebar-bottom">
        <div class="status-dot">
            <span class="pulse-dot"></span>
            <span>Système actif</span>
        </div>
        <div class="auto-refresh">
            <span>Actu. dans 60s</span>
            <div class="refresh-bar"><div class="refresh-fill"></div></div>
        </div>
    </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="main">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>Suivi des urgences <span>— <?= date('d/m/Y') ?></span></h1>
            <div class="time-chip"><i class="bi bi-clock"></i><?= date('H:i') ?></div>
        </div>
        <a href="admission_patient.php" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Nouvelle admission
        </a>
    </div>

    <div class="content">

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card total">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="kpi-value"><?= $stats['total'] ?? 0 ?></div>
                <div class="kpi-label">Patients présents</div>
            </div>
            <div class="kpi-card critique">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                </div>
                <div class="kpi-value"><?= $stats['priorite_critique'] ?? 0 ?></div>
                <div class="kpi-label">Cas critiques (P1)</div>
            </div>
            <div class="kpi-card attente">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="kpi-value"><?= $stats['en_attente'] ?? 0 ?></div>
                <div class="kpi-label">En attente de soins</div>
            </div>
            <div class="kpi-card soin">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                </div>
                <div class="kpi-value"><?= $stats['en_soin'] ?? 0 ?></div>
                <div class="kpi-label">En cours de soin</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filters-bar" method="GET">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="recherche"
                       placeholder="Rechercher un patient, motif…"
                       value="<?= htmlspecialchars($recherche) ?>">
            </div>
            <select class="filter-select" name="statut">
                <option value="tous"       <?= $filtre_statut === 'tous'       ? 'selected' : '' ?>>Tous les statuts</option>
                <option value="En attente" <?= $filtre_statut === 'En attente' ? 'selected' : '' ?>>En attente</option>
                <option value="En soin"    <?= $filtre_statut === 'En soin'    ? 'selected' : '' ?>>En soin</option>
            </select>
            <select class="filter-select" name="priorite">
                <option value="tous" <?= $filtre_priorite === 'tous' ? 'selected' : '' ?>>Toutes priorités</option>
                <option value="1"    <?= $filtre_priorite === '1'    ? 'selected' : '' ?>>🔴 P1 — Vital</option>
                <option value="2"    <?= $filtre_priorite === '2'    ? 'selected' : '' ?>>🟠 P2 — Grave</option>
                <option value="3"    <?= $filtre_priorite === '3'    ? 'selected' : '' ?>>🟢 P3 — Stable</option>
            </select>
            <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
            <?php if ($filtre_statut !== 'tous' || $filtre_priorite !== 'tous' || $recherche): ?>
            <a href="index.php" class="btn-reset"><i class="bi bi-x-circle"></i> Réinitialiser</a>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="table-card">
            <div class="table-head-bar">
                <h2>Liste des patients actifs</h2>
                <span class="count-pill"><?= count($patients) ?> patient<?= count($patients) > 1 ? 's' : '' ?></span>
            </div>

            <?php if (empty($patients)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Aucun patient trouvé selon ces critères.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Priorité</th>
                        <th>Motif</th>
                        <th>Téléphone</th>
                        <th>Arrivée</th>
                        <th>Attente</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p):
                    $num = (int) ($p['niveau_priorite'][0] ?? 3);
                    $row_cls = 'row-p' . $num;
                    $av_cls  = 'av-p'  . $num;
                    $bp_cls  = 'badge-p' . $num;
                    $bd_cls  = 'bd-p'  . $num;

                    $arrivee = new DateTime($p['heure_arrivee']);
                    $diff    = $now->diff($arrivee);
                    $minutes = $diff->days * 1440 + $diff->h * 60 + $diff->i;
                    $att_txt = $diff->h > 0 ? $diff->h . 'h ' . $diff->i . 'min' : $diff->i . ' min';

                    $wait_cls = 'wait-normal';
                    if ($num === 1 && $minutes > 10)  $wait_cls = 'wait-alert';
                    elseif ($num === 2 && $minutes > 30) $wait_cls = 'wait-warn';
                    elseif ($minutes > 120)              $wait_cls = 'wait-warn';

                    $badge_s_cls = $p['statut'] === 'En attente' ? 's-attente' : 's-soin';
                    $initiales   = strtoupper(substr($p['prenom'],0,1) . substr($p['nom'],0,1));
                ?>
                <tr class="<?= $row_cls ?>">
                    <td>
                        <div class="patient-cell">
                            <div class="avatar <?= $av_cls ?>"><?= $initiales ?></div>
                            <div>
                                <div class="patient-name"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></div>
                                <div class="patient-age"><?= (int)$p['age'] ?> ans</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge-p <?= $bp_cls ?>">
                            <span class="badge-dot <?= $bd_cls ?>"></span>
                            <?= htmlspecialchars($p['niveau_priorite']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="motif-txt" title="<?= htmlspecialchars($p['motif_consultation']) ?>">
                            <?= htmlspecialchars($p['motif_consultation']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="tel-txt">
                            <?= $p['telephone'] ? htmlspecialchars($p['telephone']) : '—' ?>
                        </span>
                    </td>
                    <td style="color:var(--muted);font-size:.82rem;"><?= date('H:i', strtotime($p['heure_arrivee'])) ?></td>
                    <td><span class="<?= $wait_cls ?>"><?= $att_txt ?></span></td>
                    <td><span class="badge-s <?= $badge_s_cls ?>"><?= htmlspecialchars($p['statut']) ?></span></td>
                    <td>
                        <form method="POST" action="mise_a_jour_statut.php">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <select name="nouveau_statut" class="select-statut" onchange="this.form.submit()">
                                <option value="En attente" <?= $p['statut'] === 'En attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="En soin"    <?= $p['statut'] === 'En soin'    ? 'selected' : '' ?>>En soin</option>
                                <option value="Sorti"      <?= $p['statut'] === 'Sorti'      ? 'selected' : '' ?>>Sorti ✓</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /content -->
</main>
</body>
</html>

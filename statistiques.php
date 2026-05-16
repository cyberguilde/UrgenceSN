<?php
require_once 'db.php';

$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days'));
$date_fin   = $_GET['date_fin']   ?? date('Y-m-d');

// KPI 1 : Total admissions sur la période
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$total_admissions = (int) $stmt->fetch()['total'];

// KPI 2 : Patients encore présents
$patients_presents = (int) $pdo->query("SELECT COUNT(*) FROM patients_urgence WHERE statut != 'Sorti'")->fetchColumn();

// KPI 3 : Taux d'occupation
$capacite_max    = 50;
$taux_occupation = $capacite_max > 0 ? round(($patients_presents / $capacite_max) * 100, 1) : 0;

// KPI 4 : Durée moyenne de séjour (heure_sortie est un DATETIME)
$stmt = $pdo->prepare("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, heure_arrivee, heure_sortie)) / 60 as duree_moy
    FROM patients_urgence
    WHERE heure_sortie IS NOT NULL
      AND DATE(heure_arrivee) BETWEEN :d1 AND :d2
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$duree_moyenne = round($stmt->fetch()['duree_moy'] ?? 0, 1);

// KPI 5 : Flux sortis/présents sur la période
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN heure_sortie IS NOT NULL THEN 1 ELSE 0 END) as sortis,
        SUM(CASE WHEN heure_sortie IS NULL THEN 1 ELSE 0 END) as presents
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$flux = $stmt->fetch();

// Admissions par jour
$stmt = $pdo->prepare("
    SELECT DATE(heure_arrivee) as jour, COUNT(*) as nb
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
    GROUP BY DATE(heure_arrivee) ORDER BY jour ASC
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$admissions_par_jour = $stmt->fetchAll();

// Répartition par priorité
$stmt = $pdo->prepare("
    SELECT niveau_priorite, COUNT(*) as nb
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
    GROUP BY niveau_priorite ORDER BY niveau_priorite ASC
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$repartition_priorite = $stmt->fetchAll();

// Admissions par tranche horaire
$stmt = $pdo->prepare("
    SELECT HOUR(heure_arrivee) as heure, COUNT(*) as nb
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
    GROUP BY HOUR(heure_arrivee) ORDER BY heure ASC
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$admissions_par_heure = $stmt->fetchAll();

// Répartition par statut
$stmt = $pdo->prepare("
    SELECT statut, COUNT(*) as nb
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
    GROUP BY statut ORDER BY nb DESC
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$repartition_statut = $stmt->fetchAll();

// Top 10 motifs
$stmt = $pdo->prepare("
    SELECT motif_consultation, COUNT(*) as nb
    FROM patients_urgence WHERE DATE(heure_arrivee) BETWEEN :d1 AND :d2
    GROUP BY motif_consultation ORDER BY nb DESC LIMIT 10
");
$stmt->execute(['d1' => $date_debut, 'd2' => $date_fin]);
$top_motifs = $stmt->fetchAll();

// JSON pour Chart.js
$chart_jours           = json_encode(array_column($admissions_par_jour, 'jour'));
$chart_nb_jour         = json_encode(array_map('intval', array_column($admissions_par_jour, 'nb')));
$chart_priorite_labels = json_encode(array_column($repartition_priorite, 'niveau_priorite'));
$chart_priorite_data   = json_encode(array_map('intval', array_column($repartition_priorite, 'nb')));
$chart_heures          = json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . 'h', array_column($admissions_par_heure, 'heure')));
$chart_nb_heure        = json_encode(array_map('intval', array_column($admissions_par_heure, 'nb')));
$chart_statut_labels   = json_encode(array_column($repartition_statut, 'statut'));
$chart_statut_data     = json_encode(array_map('intval', array_column($repartition_statut, 'nb')));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UrgenceSN — Statistiques</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
    --rouge:#E63946;--rouge-d:#B02030;
    --orange:#F4821A;--vert:#16C784;--bleu:#3B82F6;
    --violet:#818CF8;
    --fond:#0D0F14;--surface:#0c2344;--surface2:#0c2344;
    --border:rgba(255,255,255,0.07);--text:#E2E8F0;--muted:#64748B;
    --sidebar-w:250px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Bricolage Grotesque',sans-serif;
background-image: url('Gemini_Generated_Image_2wegij2wegij2weg.png');
    background-repeat: no-repeat;
    background-position: center center;
    background-attachment: fixed;
    background-size: cover;


;color:var(--text);display:flex;min-height:100vh;}

/* Sidebar */
.sidebar{width:var(--sidebar-w);background:var(--surface);display:flex;flex-direction:column;position:fixed;height:100vh;z-index:200;border-right:1px solid var(--border);}
.sidebar-logo{padding:28px 24px 24px;border-bottom:1px solid var(--border);position:relative;overflow:hidden;}
.sidebar-logo::after{content:'';position:absolute;bottom:-40px;right:-40px;width:100px;height:100px;background:var(--rouge);opacity:.05;border-radius:50%;}
.logo-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(230,57,70,.12);border:1px solid rgba(230,57,70,.25);border-radius:8px;padding:4px 10px;margin-bottom:10px;}
.logo-badge span{font-size:.7rem;font-weight:600;color:var(--rouge);letter-spacing:.08em;text-transform:uppercase;}
.sidebar-logo h2{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-.5px;line-height:1;}
.sidebar-logo h2 em{color:var(--rouge);font-style:normal;}
.sidebar-logo p{font-size:.72rem;color:var(--muted);margin-top:4px;}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto;}
.nav-section-title{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);padding:12px 24px 6px;}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:10px 24px;color:#94A3B8;text-decoration:none;font-size:.855rem;font-weight:500;transition:all .2s;border-left:3px solid transparent;margin:1px 0;}
.sidebar-nav a:hover{background:rgba(255,255,255,.04);color:var(--text);border-left-color:rgba(255,255,255,.2);}
.sidebar-nav a.active{background:rgba(230,57,70,.1);color:#fff;border-left-color:var(--rouge);}
.sidebar-nav a i{font-size:1rem;width:18px;flex-shrink:0;}
.sidebar-bottom{padding:16px 24px;border-top:1px solid var(--border);}
.status-dot{display:flex;align-items:center;gap:8px;font-size:.75rem;color:var(--muted);}
.pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--vert);animation:pulse 2s ease-in-out infinite;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(22,199,132,.4)}50%{box-shadow:0 0 0 6px rgba(22,199,132,0)}}

/* Main */
.main{margin-left:var(--sidebar-w);flex:1;max-width:calc(100% - var(--sidebar-w));display:flex;flex-direction:column;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(13,15,20,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.topbar-left{display:flex;align-items:center;gap:16px;}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:.855rem;transition:color .2s;}
.btn-back:hover{color:var(--text);}
.topbar-sep{color:var(--border);}
.topbar h1{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;}

/* Date filter */
.date-filter{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.date-filter label{font-size:.78rem;color:var(--muted);}
.date-filter input[type="date"]{padding:7px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.82rem;font-family:inherit;outline:none;transition:border-color .2s;}
.date-filter input[type="date"]:focus{border-color:rgba(59,130,246,.5);}
.btn-apply{padding:7px 16px;background:var(--violet);color:#fff;border:none;border-radius:8px;font-size:.82rem;font-family:inherit;cursor:pointer;font-weight:600;transition:background .2s;}
.btn-apply:hover{background:#6366f1;}

.content{padding:28px 32px;}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;position:relative;overflow:hidden;}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.kpi-card:nth-child(1)::before{background:var(--violet);}
.kpi-card:nth-child(2)::before{background:var(--vert);}
.kpi-card:nth-child(3)::before{background:var(--orange);}
.kpi-card:nth-child(4)::before{background:var(--rouge);}
.kpi-card:nth-child(5)::before{background:var(--bleu);}
.kpi-label{font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.kpi-value{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;line-height:1;}
.kpi-card:nth-child(1) .kpi-value{color:var(--violet);}
.kpi-card:nth-child(2) .kpi-value{color:var(--vert);}
.kpi-card:nth-child(3) .kpi-value{color:var(--orange);}
.kpi-card:nth-child(4) .kpi-value{color:var(--rouge);}
.kpi-card:nth-child(5) .kpi-value{color:var(--bleu);}
.kpi-sub{font-size:.72rem;color:var(--muted);margin-top:6px;}

/* Charts */
.charts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px;}
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px;}
.chart-card.full{grid-column:1/-1;}
.chart-card h3{font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;color:#fff;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.chart-card h3 i{color:var(--muted);font-size:.9rem;}
.chart-wrapper{position:relative;width:100%;height:260px;}

/* Motif table */
.table-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px;}
.table-card h3{font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;color:#fff;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.table-card h3 i{color:var(--muted);}
.motif-table{width:100%;border-collapse:collapse;}
.motif-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700;padding:8px 12px;border-bottom:1px solid var(--border);}
.motif-table td{padding:10px 12px;font-size:.855rem;border-bottom:1px solid rgba(255,255,255,.03);}
.motif-table tr:last-child td{border-bottom:none;}
.motif-table tr:hover td{background:rgba(255,255,255,.02);}
.rank-badge{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);font-size:.72rem;font-weight:700;color:var(--violet);}
.bar-bg{background:var(--surface2);border-radius:4px;height:7px;flex:1;overflow:hidden;}
.bar-fill{height:100%;background:var(--violet);border-radius:4px;transition:width .5s ease;}

@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){
    .sidebar{width:64px;}
    .sidebar-logo h2,.sidebar-logo p,.logo-badge,.nav-section-title,.sidebar-nav a span,.sidebar-bottom .status-dot span{display:none;}
    .sidebar-logo{padding:20px 16px;}
    .sidebar-nav a{padding:12px 20px;justify-content:center;}
    .main{margin-left:64px;max-width:calc(100% - 64px);}
    .kpi-grid{grid-template-columns:repeat(2,1fr);}
    .charts-grid{grid-template-columns:1fr;}
    .content{padding:20px;}
    .topbar{padding:12px 16px;}
}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge"><span>● Live</span></div>
        <h2><em>Urgence</em>SN</h2>
        <p>Centre Hospitalier de Dakar</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Navigation</div>
        <a href="index.php"><i class="bi bi-grid-fill"></i><span>Tableau de bord</span></a>
        <a href="admission_patient.php"><i class="bi bi-person-plus-fill"></i><span>Nouvelle admission</span></a>
        <a href="patients_sortis.php"><i class="bi bi-box-arrow-right"></i><span>Patients sortis</span></a>
        <a href="statistiques.php" class="active"><i class="bi bi-bar-chart-fill"></i><span>Statistiques</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="status-dot"><span class="pulse-dot"></span><span>Système actif</span></div>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Retour</a>
            <span class="topbar-sep">/</span>
            <h1>Statistiques des urgences</h1>
        </div>
        <form class="date-filter" method="GET">
            <label>Du</label>
            <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
            <label>au</label>
            <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
            <button type="submit" class="btn-apply"><i class="bi bi-funnel-fill"></i> Appliquer</button>
        </form>
    </div>

    <div class="content">

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total admissions</div>
                <div class="kpi-value"><?= number_format($total_admissions) ?></div>
                <div class="kpi-sub"><?= $date_debut ?> → <?= $date_fin ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Patients présents</div>
                <div class="kpi-value"><?= $patients_presents ?></div>
                <div class="kpi-sub">En ce moment aux urgences</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Taux d'occupation</div>
                <div class="kpi-value"><?= $taux_occupation ?>%</div>
                <div class="kpi-sub">Capacité : <?= $capacite_max ?> lits</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Durée moy. séjour</div>
                <div class="kpi-value"><?= $duree_moyenne ?>h</div>
                <div class="kpi-sub">Patients sortis uniquement</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Sortis / Présents</div>
                <div class="kpi-value"><?= number_format($total_admissions - $patients_presents) ?> / <?= $patients_presents ?></div>
                <div class="kpi-sub">Sur la période sélectionnée</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card full">
                <h3><i class="bi bi-graph-up"></i> Admissions par jour</h3>
                <div class="chart-wrapper"><canvas id="chartJour"></canvas></div>
            </div>
            <div class="chart-card">
                <h3><i class="bi bi-pie-chart-fill"></i> Répartition par priorité</h3>
                <div class="chart-wrapper"><canvas id="chartPriorite"></canvas></div>
            </div>
            <div class="chart-card">
                <h3><i class="bi bi-pie-chart"></i> Répartition par statut</h3>
                <div class="chart-wrapper"><canvas id="chartStatut"></canvas></div>
            </div>
            <div class="chart-card full">
                <h3><i class="bi bi-bar-chart-fill"></i> Admissions par tranche horaire</h3>
                <div class="chart-wrapper"><canvas id="chartHeure"></canvas></div>
            </div>
        </div>

        <!-- Motifs -->
        <div class="table-card">
            <h3><i class="bi bi-list-ol"></i> Top 10 — Motifs de consultation</h3>
            <table class="motif-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Motif</th>
                        <th style="width:80px">Nombre</th>
                        <th style="width:220px">Proportion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $max_motif = !empty($top_motifs) ? (int)$top_motifs[0]['nb'] : 1;
                    foreach ($top_motifs as $i => $m):
                        $pct = round(($m['nb'] / max($total_admissions, 1)) * 100, 1);
                        $bar = round(($m['nb'] / $max_motif) * 100);
                    ?>
                    <tr>
                        <td><span class="rank-badge"><?= $i + 1 ?></span></td>
                        <td><?= htmlspecialchars($m['motif_consultation']) ?></td>
                        <td style="font-weight:600;color:var(--violet)"><?= (int)$m['nb'] ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="bar-bg">
                                    <div class="bar-fill" style="width:<?= $bar ?>%"></div>
                                </div>
                                <span style="font-size:.78rem;color:var(--muted);min-width:36px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<script>
const gridColor  = 'rgba(255,255,255,0.05)';
const tickStyle  = { color: '#64748B', font: { size: 11, family: 'Bricolage Grotesque' } };
const legendStyle = { labels: { color: '#94A3B8', padding: 14, font: { size: 12 } } };

// Admissions par jour — Line
new Chart(document.getElementById('chartJour'), {
    type: 'line',
    data: {
        labels: <?= $chart_jours ?>,
        datasets: [{
            label: 'Admissions',
            data: <?= $chart_nb_jour ?>,
            borderColor: '#818CF8',
            backgroundColor: 'rgba(129,140,248,0.08)',
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#818CF8',
            pointBorderColor: '#0D0F14',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: legendStyle },
        scales: {
            x: { grid: { color: gridColor }, ticks: tickStyle },
            y: { grid: { color: gridColor }, ticks: tickStyle, beginAtZero: true }
        }
    }
});

// Priorité — Doughnut
new Chart(document.getElementById('chartPriorite'), {
    type: 'doughnut',
    data: {
        labels: <?= $chart_priorite_labels ?>,
        datasets: [{
            data: <?= $chart_priorite_data ?>,
            backgroundColor: ['#E63946','#F4821A','#16C784','#3B82F6','#818CF8'],
            borderColor: '#13161E', borderWidth: 3
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { color: '#94A3B8', padding: 12, font: { size: 12 } } } },
        cutout: '58%'
    }
});

// Statut — Doughnut
new Chart(document.getElementById('chartStatut'), {
    type: 'doughnut',
    data: {
        labels: <?= $chart_statut_labels ?>,
        datasets: [{
            data: <?= $chart_statut_data ?>,
            backgroundColor: ['#3B82F6','#F4821A','#16C784','#818CF8'],
            borderColor: '#13161E', borderWidth: 3
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { color: '#94A3B8', padding: 12, font: { size: 12 } } } },
        cutout: '58%'
    }
});

// Horaire — Bar
new Chart(document.getElementById('chartHeure'), {
    type: 'bar',
    data: {
        labels: <?= $chart_heures ?>,
        datasets: [{
            label: 'Admissions',
            data: <?= $chart_nb_heure ?>,
            backgroundColor: 'rgba(129,140,248,0.5)',
            borderColor: '#818CF8',
            borderWidth: 1.5,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: legendStyle },
        scales: {
            x: { grid: { color: gridColor }, ticks: tickStyle },
            y: { grid: { color: gridColor }, ticks: tickStyle, beginAtZero: true }
        }
    }
});
</script>
</body>
</html>

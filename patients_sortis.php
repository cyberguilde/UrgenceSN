<?php
require_once 'db.php';

function duree_sejour(?string $arrivee, ?string $sortie): string {
    if (!$arrivee || !$sortie) return 'N/A';
    $a    = new DateTime($arrivee);
    $s    = new DateTime($sortie);
    $diff = $a->diff($s);
    if ($diff->days > 0) return $diff->days . 'j ' . $diff->h . 'h ' . $diff->i . 'min';
    return $diff->h . 'h ' . $diff->i . 'min';
}

// Filtres
$search      = trim($_GET['search']          ?? '');
$filtre_prio = $_GET['niveau_priorite']      ?? '';
$date_debut  = $_GET['date_debut']           ?? '';
$date_fin    = $_GET['date_fin']             ?? '';

$where  = ["statut = 'Sorti'"];
$params = [];

if ($search !== '') {
    $where[]       = "(nom LIKE :s OR prenom LIKE :s2 OR motif_consultation LIKE :s3)";
    $params['s']   = "%$search%";
    $params['s2']  = "%$search%";
    $params['s3']  = "%$search%";
}
if ($filtre_prio !== '') {
    $where[]        = "niveau_priorite = :prio";
    $params['prio'] = $filtre_prio;
}
if ($date_debut !== '') {
    $where[]       = "DATE(heure_sortie) >= :dd";
    $params['dd']  = $date_debut;
}
if ($date_fin !== '') {
    $where[]       = "DATE(heure_sortie) <= :df";
    $params['df']  = $date_fin;
}

$sql  = "SELECT * FROM patients_urgence WHERE " . implode(' AND ', $where) . " ORDER BY heure_sortie DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

// Stats rapides
$total  = count($patients);
$durees = [];
foreach ($patients as $p) {
    if ($p['heure_arrivee'] && $p['heure_sortie']) {
        $a = new DateTime($p['heure_arrivee']);
        $s = new DateTime($p['heure_sortie']);
        $durees[] = ($s->getTimestamp() - $a->getTimestamp()) / 3600;
    }
}
$moy_heures  = $durees ? round(array_sum($durees) / count($durees), 1) : 0;
$max_heures  = $durees ? round(max($durees), 1) : 0;
$taux_p1 = $total > 0
    ? round(count(array_filter($patients, fn($p) => str_starts_with($p['niveau_priorite'], '1'))) / $total * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UrgenceSN — Patients sortis</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{
    --rouge:#E63946;--rouge-d:#B02030;
    --orange:#F4821A;--vert:#16C784;--bleu:#3B82F6;
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
.topbar{position:sticky;top:0;z-index:100;background:rgba(13,15,20,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;gap:16px;}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:.855rem;transition:color .2s;}
.btn-back:hover{color:var(--text);}
.topbar h1{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;}
.topbar-sep{color:var(--border);}
.content{padding:28px 32px;}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;transition:transform .2s;}
.kpi-card:hover{transform:translateY(-2px);}
.kpi-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:14px;}
.kpi-icon.bleu{background:rgba(59,130,246,.12);color:var(--bleu);}
.kpi-icon.vert{background:rgba(22,199,132,.12);color:var(--vert);}
.kpi-icon.rouge{background:rgba(230,57,70,.12);color:var(--rouge);}
.kpi-value{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;line-height:1;margin-bottom:4px;}
.kpi-label{font-size:.78rem;color:var(--muted);}
.kpi-card.bleu .kpi-value{color:var(--bleu);}
.kpi-card.vert .kpi-value{color:var(--vert);}
.kpi-card.rouge .kpi-value{color:var(--rouge);}

/* Filters */
.filters-bar{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:20px;}
.filter-group{display:flex;flex-direction:column;gap:6px;}
.filter-group label{font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;}
.filter-group input,.filter-group select{padding:9px 13px;background:var(--surface2);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:.855rem;font-family:inherit;outline:none;transition:border-color .2s;}
.filter-group input:focus,.filter-group select:focus{border-color:rgba(59,130,246,.5);}
.filter-group input[type="text"]{width:230px;}
.filter-group input[type="date"]{width:160px;}
.filter-group input::placeholder{color:var(--muted);}
.btn-filter{padding:9px 16px;background:var(--surface2);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:.855rem;font-family:inherit;cursor:pointer;font-weight:500;display:inline-flex;align-items:center;gap:6px;transition:all .2s;align-self:flex-end;}
.btn-filter:hover{background:rgba(255,255,255,.06);}
.btn-reset{padding:9px 14px;background:transparent;border:1px solid rgba(230,57,70,.3);border-radius:10px;color:var(--rouge);font-size:.855rem;font-family:inherit;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:5px;transition:all .2s;text-decoration:none;align-self:flex-end;}
.btn-reset:hover{background:rgba(230,57,70,.1);}

/* Cards grid */
.patients-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px;}
.patient-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;border-left:4px solid var(--border);transition:transform .2s,border-color .2s,box-shadow .2s;}
.patient-card:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.3);}
.patient-card.critique{border-left-color:var(--rouge);}
.patient-card.urgent{border-left-color:var(--orange);}
.patient-card.modere{border-left-color:var(--vert);}

.card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:12px;}
.patient-info-name{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700;color:#fff;}
.patient-info-age{font-size:.78rem;color:var(--muted);margin-top:3px;}

.badge-p{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:.75rem;font-weight:700;white-space:nowrap;flex-shrink:0;}
.badge-p.critique{background:rgba(230,57,70,.12);color:var(--rouge);border:1px solid rgba(230,57,70,.25);}
.badge-p.urgent{background:rgba(244,130,26,.12);color:var(--orange);border:1px solid rgba(244,130,26,.25);}
.badge-p.modere{background:rgba(22,199,132,.12);color:var(--vert);border:1px solid rgba(22,199,132,.25);}

.card-details{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.detail-item .dt{font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;}
.detail-item .dd{font-size:.855rem;color:var(--text);font-weight:500;}
.detail-item.full{grid-column:1/-1;}
.sejour-chip{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(22,199,132,.08);border:1px solid rgba(22,199,132,.2);
    border-radius:8px;padding:4px 10px;
    font-size:.82rem;color:var(--vert);font-weight:600;
}

.empty-state{text-align:center;padding:70px 20px;color:var(--muted);}
.empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px;}

@media(max-width:900px){
    .sidebar{width:64px;}
    .sidebar-logo h2,.sidebar-logo p,.logo-badge,.nav-section-title,.sidebar-nav a span,.sidebar-bottom .status-dot span{display:none;}
    .sidebar-logo{padding:20px 16px;}
    .sidebar-nav a{padding:12px 20px;justify-content:center;}
    .main{margin-left:64px;max-width:calc(100% - 64px);}
    .kpi-grid{grid-template-columns:1fr;}
    .patients-grid{grid-template-columns:1fr;}
    .content{padding:20px;}
}
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
        <a href="patients_sortis.php" class="active"><i class="bi bi-box-arrow-right"></i><span>Patients sortis</span></a>
        <a href="statistiques.php"><i class="bi bi-bar-chart-fill"></i><span>Statistiques</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="status-dot"><span class="pulse-dot"></span><span>Système actif</span></div>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Retour</a>
        <span class="topbar-sep">/</span>
        <h1>Patients sortis</h1>
    </div>

    <div class="content">

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card bleu">
                <div class="kpi-icon bleu"><i class="bi bi-people-fill"></i></div>
                <div class="kpi-value"><?= $total ?></div>
                <div class="kpi-label">Total patients sortis</div>
            </div>
            <div class="kpi-card vert">
                <div class="kpi-icon vert"><i class="bi bi-clock-history"></i></div>
                <div class="kpi-value"><?= $moy_heures ?>h</div>
                <div class="kpi-label">Durée moyenne de séjour</div>
            </div>
            <div class="kpi-card rouge">
                <div class="kpi-icon rouge"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="kpi-value"><?= $taux_p1 ?>%</div>
                <div class="kpi-label">Cas P1 — Vital</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filters-bar" method="GET">
            <div class="filter-group">
                <label>Rechercher</label>
                <input type="text" name="search"
                       placeholder="Nom, prénom, motif…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label>Priorité</label>
                <select name="niveau_priorite">
                    <option value="">Toutes</option>
                    <?php foreach (['1 - Vital','2 - Grave','3 - Stable'] as $niv): ?>
                    <option value="<?= htmlspecialchars($niv) ?>"
                            <?= $filtre_prio === $niv ? 'selected' : '' ?>>
                        <?= htmlspecialchars($niv) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Du</label>
                <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
            </div>
            <div class="filter-group">
                <label>Au</label>
                <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
            </div>
            <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
            <?php if ($search || $filtre_prio || $date_debut || $date_fin): ?>
            <a href="patients_sortis.php" class="btn-reset"><i class="bi bi-x-circle"></i> Réinitialiser</a>
            <?php endif; ?>
        </form>

        <!-- Cards -->
        <?php if (empty($patients)): ?>
        <div class="empty-state">
            <i class="bi bi-clipboard-x"></i>
            <p>Aucun patient sorti trouvé avec ces critères.</p>
        </div>
        <?php else: ?>
        <div class="patients-grid">
            <?php foreach ($patients as $row):
                $niveau = $row['niveau_priorite'] ?? '';
                $classe = str_contains($niveau,'Vital') ? 'critique' : (str_contains($niveau,'Grave') ? 'urgent' : 'modere');
                $duree  = duree_sejour($row['heure_arrivee'], $row['heure_sortie']);
            ?>
            <div class="patient-card <?= $classe ?>">
                <div class="card-top">
                    <div>
                        <div class="patient-info-name"><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></div>
                        <div class="patient-info-age"><?= (int)$row['age'] ?> ans
                            <?php if ($row['telephone']): ?>
                            · <i class="bi bi-phone" style="font-size:.75rem;"></i> <?= htmlspecialchars($row['telephone']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge-p <?= $classe ?>"><?= htmlspecialchars($niveau) ?></span>
                </div>
                <div class="card-details">
                    <div class="detail-item">
                        <div class="dt">Arrivée</div>
                        <div class="dd"><?= $row['heure_arrivee'] ? date('d/m/Y H:i', strtotime($row['heure_arrivee'])) : 'N/A' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="dt">Sortie</div>
                        <div class="dd"><?= $row['heure_sortie'] ? date('d/m/Y H:i', strtotime($row['heure_sortie'])) : 'N/A' ?></div>
                    </div>
                    <div class="detail-item full">
                        <div class="dt">Durée de séjour</div>
                        <div class="dd">
                            <?php if ($duree !== 'N/A'): ?>
                            <span class="sejour-chip"><i class="bi bi-clock"></i> <?= $duree ?></span>
                            <?php else: ?>N/A<?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-item full">
                        <div class="dt">Motif de consultation</div>
                        <div class="dd"><?= htmlspecialchars($row['motif_consultation'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
</body>
</html>

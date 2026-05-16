<?php
require_once 'db.php';

$message      = '';
$message_type = '';
$nom = $prenom = $motif = $tel = '';
$age = '';
$priorite = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom']     ?? '');
    $prenom   = trim($_POST['prenom']  ?? '');
    $age      = (int) ($_POST['age']   ?? 0);
    $motif    = trim($_POST['motif']   ?? '');
    $priorite = $_POST['priorite']     ?? '';
    $tel      = trim($_POST['tel']     ?? '');

    if ($nom && $prenom && $age > 0 && $motif && $priorite) {
        $sql = "INSERT INTO patients_urgence
                    (nom, prenom, age, motif_consultation, niveau_priorite, telephone, statut, heure_arrivee)
                VALUES
                    (:nom, :prenom, :age, :motif, :priorite, :tel, 'En attente', NOW())";
        try {
            $pdo->prepare($sql)->execute([
                ':nom'      => $nom,
                ':prenom'   => $prenom,
                ':age'      => $age,
                ':motif'    => $motif,
                ':priorite' => $priorite,
                ':tel'      => $tel,
            ]);
            $message      = "Patient <strong>" . htmlspecialchars($prenom . ' ' . $nom) . "</strong> enregistré avec succès !";
            $message_type = 'success';
            $nom = $prenom = $motif = $tel = '';
            $age = '';
            $priorite = '';
        } catch (PDOException $e) {
            $message      = "Erreur lors de l'enregistrement : " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message      = "Veuillez remplir tous les champs obligatoires.";
        $message_type = 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UrgenceSN — Nouvelle admission</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{
    --rouge:#E63946;--rouge-d:#B02030;
    --orange:#F4821A;--vert:#16C784;--bleu:#3B82F6;
    --fond:#ffffff;--surface: #0c2344f5 ;--surface2: #0c2344e9 ;
    --border:rgba(255,255,255,0.07);--text:#E2E8F0;--muted:#64748B;
    --sidebar-w:250px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{
    font-family:'Bricolage Grotesque',sans-serif;
    
    background-image: url('Gemini_Generated_Image_2wegij2wegij2weg.png');
    background-repeat: no-repeat;
    background-position: center center;
    background-attachment: fixed;
    background-size: cover;

    ;color:var(--text);
    display:flex;min-height:100vh;
}

/* Sidebar */
.sidebar{width:var(--sidebar-w);background:var(--surface);display:flex;flex-direction:column;padding:0;position:fixed;height:100vh;z-index:200;border-right:1px solid var(--border);}
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
.pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--vert);flex-shrink:0;animation:pulse 2s ease-in-out infinite;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(22,199,132,.4)}50%{box-shadow:0 0 0 6px rgba(22,199,132,0)}}

/* Main */
.main{margin-left:var(--sidebar-w);flex:1;padding:0;max-width:calc(100% - var(--sidebar-w));display:flex;flex-direction:column;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(13,15,20,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;gap:16px;}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:.855rem;transition:color .2s;}
.btn-back:hover{color:var(--text);}
.topbar-sep{color:var(--border);}
.topbar h1{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;}
.content{padding:36px 32px;display:flex;justify-content:center;}

/* Form card */
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;overflow:hidden;width:100%;max-width:680px;}
.form-card-header{
    background:linear-gradient(135deg, rgba(230,57,70,.15) 0%, rgba(176,32,48,.08) 100%);
    border-bottom:1px solid var(--border);
    padding:28px 32px;display:flex;align-items:center;gap:18px;
    position:relative;overflow:hidden;
}
.form-card-header::before{
    content:'';position:absolute;right:-30px;top:-30px;
    width:120px;height:120px;border-radius:50%;
    background:rgba(230,57,70,.08);
}
.header-icon{
    width:52px;height:52px;border-radius:14px;
    background:rgba(230,57,70,.15);border:1px solid rgba(230,57,70,.3);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;color:var(--rouge);flex-shrink:0;
}
.form-card-header h2{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;color:#fff;}
.form-card-header p{font-size:.78rem;color:var(--muted);margin-top:4px;}
.form-body{padding:32px;}

/* Alerts */
.alert{padding:14px 18px;border-radius:12px;margin-bottom:24px;font-size:.855rem;display:flex;align-items:flex-start;gap:10px;line-height:1.5;}
.alert i{font-size:1.05rem;margin-top:1px;flex-shrink:0;}
.alert-success{background:rgba(22,199,132,.1);color:#4ADE80;border:1px solid rgba(22,199,132,.25);}
.alert-error  {background:rgba(230,57,70,.1);color:#FC8181;border:1px solid rgba(230,57,70,.25);}
.alert-warning{background:rgba(244,130,26,.1);color:#FDBA74;border:1px solid rgba(244,130,26,.25);}

/* Form */
.form-section{margin-bottom:28px;}
.form-section-title{
    font-size:.72rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.1em;color:var(--muted);
    margin-bottom:16px;padding-bottom:10px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:8px;
}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{display:flex;flex-direction:column;gap:7px;}
.form-group.full{grid-column:1/-1;}
label{font-size:.82rem;font-weight:600;color:#CBD5E1;}
label .req{color:var(--rouge);margin-left:2px;}
input[type="text"],input[type="number"],input[type="tel"],textarea,select{
    padding:11px 14px;
    background:var(--surface2);border:1px solid var(--border);
    border-radius:10px;color:var(--text);font-size:.855rem;
    font-family:inherit;outline:none;
    transition:border-color .2s,box-shadow .2s;
}
input::placeholder,textarea::placeholder{color:var(--muted);}
input:focus,textarea:focus,select:focus{
    border-color:rgba(59,130,246,.5);
    box-shadow:0 0 0 3px rgba(59,130,246,.1);
}
textarea{resize:vertical;min-height:90px;}
select option{background:var(--surface2);}

/* Priorité */
.priorite-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.priorite-card{
    border:1.5px solid var(--border);border-radius:14px;
    padding:16px 12px;cursor:pointer;transition:all .2s;
    display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center;
    background:var(--surface2);
}
.priorite-card:hover{border-color:rgba(255,255,255,.2);transform:translateY(-2px);}
.priorite-card input[type="radio"]{display:none;}
.priorite-card.p1:has(input:checked){border-color:var(--rouge);background:rgba(230,57,70,.1);box-shadow:0 0 20px rgba(230,57,70,.15);}
.priorite-card.p2:has(input:checked){border-color:var(--orange);background:rgba(244,130,26,.1);box-shadow:0 0 20px rgba(244,130,26,.15);}
.priorite-card.p3:has(input:checked){border-color:var(--vert);background:rgba(22,199,132,.1);box-shadow:0 0 20px rgba(22,199,132,.15);}
.p-icon{font-size:1.6rem;}
.p-label{font-size:.8rem;font-weight:700;}
.p-desc{font-size:.71rem;color:var(--muted);line-height:1.3;}
.p1 .p-label{color:var(--rouge);}
.p2 .p-label{color:var(--orange);}
.p3 .p-label{color:var(--vert);}

/* Submit */
.btn-submit{
    width:100%;padding:13px;
    background:var(--rouge);color:#fff;
    border:none;border-radius:12px;
    font-size:1rem;font-family:inherit;font-weight:700;
    cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
    transition:all .2s;margin-top:8px;
    box-shadow:0 4px 20px rgba(230,57,70,.3);
}
.btn-submit:hover{background:var(--rouge-d);transform:translateY(-1px);box-shadow:0 8px 30px rgba(230,57,70,.4);}

@media(max-width:900px){
    .sidebar{width:64px;}
    .sidebar-logo h2,.sidebar-logo p,.logo-badge,.nav-section-title,.sidebar-nav a span,.sidebar-bottom .status-dot span{display:none;}
    .sidebar-logo{padding:20px 16px;}
    .sidebar-nav a{padding:12px 20px;justify-content:center;}
    .main{margin-left:64px;max-width:calc(100% - 64px);}
    .form-grid{grid-template-columns:1fr;}
    .priorite-grid{grid-template-columns:1fr;}
    .content{padding:24px 16px;}
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
        <a href="admission_patient.php" class="active"><i class="bi bi-person-plus-fill"></i><span>Nouvelle admission</span></a>
        <a href="patients_sortis.php"><i class="bi bi-box-arrow-right"></i><span>Patients sortis</span></a>
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
        <h1>Nouvelle admission</h1>
    </div>

    <div class="content">
        <div class="form-card">
            <div class="form-card-header">
                <div class="header-icon"><i class="bi bi-person-plus-fill"></i></div>
                <div>
                    <h2>Enregistrement d'un patient</h2>
                    <p>Les champs marqués d'une étoile (*) sont obligatoires</p>
                </div>
            </div>
            <div class="form-body">

                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>">
                    <i class="bi bi-<?= $message_type === 'success' ? 'check-circle-fill' : ($message_type === 'error' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
                    <span><?= $message ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person"></i> Identité du patient</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom <span class="req">*</span></label>
                                <input type="text" name="nom" required
                                       value="<?= htmlspecialchars($nom) ?>"
                                       placeholder="Nom de famille">
                            </div>
                            <div class="form-group">
                                <label>Prénom <span class="req">*</span></label>
                                <input type="text" name="prenom" required
                                       value="<?= htmlspecialchars($prenom) ?>"
                                       placeholder="Prénom">
                            </div>
                            <div class="form-group">
                                <label>Âge <span class="req">*</span></label>
                                <input type="number" name="age" required min="0" max="130"
                                       value="<?= htmlspecialchars((string)$age) ?>"
                                       placeholder="Ex: 34">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="tel"
                                       value="<?= htmlspecialchars($tel) ?>"
                                       placeholder="+221 77 000 00 00">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-clipboard2-pulse"></i> Motif de consultation</div>
                        <div class="form-group full">
                            <label>Description <span class="req">*</span></label>
                            <textarea name="motif" required
                                      placeholder="Décrivez brièvement le motif de la consultation…"><?= htmlspecialchars($motif) ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-exclamation-diamond"></i> Niveau de priorité <span class="req">*</span></div>
                        <div class="priorite-grid">
                            <label class="priorite-card p1">
                                <input type="radio" name="priorite" value="1 - Vital" required
                                       <?= $priorite === '1 - Vital' ? 'checked' : '' ?>>
                                <span class="p-icon">🔴</span>
                                <span class="p-label">P1 — Vital</span>
                                <span class="p-desc">Urgence absolue, risque vital immédiat</span>
                            </label>
                            <label class="priorite-card p2">
                                <input type="radio" name="priorite" value="2 - Grave"
                                       <?= $priorite === '2 - Grave' ? 'checked' : '' ?>>
                                <span class="p-icon">🟠</span>
                                <span class="p-label">P2 — Grave</span>
                                <span class="p-desc">Urgence relative, surveillance rapprochée</span>
                            </label>
                            <label class="priorite-card p3">
                                <input type="radio" name="priorite" value="3 - Stable"
                                       <?= $priorite === '3 - Stable' ? 'checked' : '' ?>>
                                <span class="p-icon">🟢</span>
                                <span class="p-label">P3 — Stable</span>
                                <span class="p-desc">État stable, attente possible</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-lg"></i> Enregistrer le patient
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
</html>

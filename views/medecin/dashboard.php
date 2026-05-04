<?php
// views/medecin/dashboard.php

// ── Timeout inactivité 15 min ──
const SESSION_TIMEOUT = 900; // 15 minutes en secondes
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: medecin.php?action=connexion&timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

$initiale  = mb_strtoupper(mb_substr($medecinNom, 0, 1));
$dureeMin  = isset($affectation['duree_estimee']) ? round($affectation['duree_estimee'] / 60) : 30;
$estChef   = $affectation ? (bool)$affectation['est_chef'] : false;
$aSousService = $affectation && !empty($affectation['ss_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Médecin — QueueCare</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/medecin.css">
  <script src="../../public/js/auto-dismiss.js" defer></script>
</head>
<body class="dash-body">

<!-- TOPBAR -->
<header class="med-topbar">
  <a href="medecin.php?action=dashboard" class="med-topbar-logo">
    <span class="med-topbar-logo-icon"><i class="fa-solid fa-user-doctor"></i></span>
    QueueCare
  </a>
  <div class="med-topbar-sep"></div>

  <?php if ($aSousService): ?>
  <div class="med-topbar-badge">
    <i class="fa-solid fa-hospital"></i>
    <?= htmlspecialchars($affectation['service_nom']) ?> —
    <strong><?= htmlspecialchars($affectation['ss_nom']) ?></strong>
  </div>
  <?php elseif ($estChef && $affectation): ?>
  <div class="med-topbar-badge" style="background:#fef3c7;border-color:#fde68a;color:#92400e;">
    <i class="fa-solid fa-hospital"></i>
    <?= htmlspecialchars($affectation['service_nom']) ?>
    — <em>Aucun sous-service créé</em>
  </div>
  <?php endif; ?>

  <div class="med-topbar-user">
    <?php if ($estChef): ?>
    <span class="badge badge-blue" style="font-size:.7rem;">
      <i class="fa-solid fa-crown fa-xs"></i> Chef de service
    </span>
    <?php else: ?>
    <span class="badge" style="background:var(--blue-pale);color:var(--blue-dark);font-size:.7rem;">
      <i class="fa-solid fa-user-doctor fa-xs"></i> Médecin
    </span>
    <?php endif; ?>
    <div class="med-topbar-avatar"><?= $initiale ?></div>
    <span>Dr. <?= htmlspecialchars($medecinNom) ?></span>
  </div>

  <a href="accueil.php" class="med-logout" style="margin-right:4px;" title="Retour à l'accueil">
    <i class="fa-solid fa-house"></i> Accueil
  </a>
  <a href="medecin.php?action=deconnexion" class="med-logout">
    <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
  </a>
</header>

<!-- Bannière avertissement timeout -->
<div id="timeoutBanner" style="display:none;position:fixed;top:0;left:0;right:0;z-index:9999;background:#c2410c;color:#fff;padding:12px 24px;align-items:center;justify-content:space-between;gap:16px;font-size:.9rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.3);">
  <span><i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;"></i>Votre session expire dans moins d'1 minute en raison d'inactivité.</span>
  <button onclick="resetIdleTimer();document.getElementById('timeoutBanner').style.display='none';" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:#fff;padding:6px 16px;border-radius:6px;cursor:pointer;font-weight:600;">
    Rester connecté
  </button>
</div>

<!-- CONTENU -->
<main class="med-main">

  <!-- En-tête -->
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;gap:16px;flex-wrap:wrap;" class="anim-fade-up">
    <div>
      <h1 class="med-welcome">
        Bonjour, Dr. <?= htmlspecialchars(explode(' ', $medecinNom)[0]) ?>
        <i class="fa-solid fa-hand-wave" style="color:var(--blue);font-size:1.4rem;"></i>
      </h1>
      <p class="med-date">
        <i class="fa-regular fa-calendar" style="color:var(--blue);"></i>
        <?= date('d/m/Y') ?>
        &nbsp;|&nbsp;
        <i class="fa-regular fa-clock" style="color:var(--blue);"></i>
        <span id="clock"></span>
      </p>
    </div>
    <button class="btn btn-secondary" onclick="window.location.reload()">
      <i class="fa-solid fa-rotate-right"></i> Actualiser
    </button>
  </div>

  <!-- Messages -->
  <?php if (!empty($messageAction)): ?>
  <div class="alert alert-success anim-fade-up" style="margin-bottom:20px;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($messageAction) ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($erreurAction)): ?>
  <div class="alert alert-error anim-fade-up" style="margin-bottom:20px;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erreurAction) ?>
  </div>
  <?php endif; ?>

  <?php if ($estChef && !$aSousService): ?>
  <!-- ═══════════ CHEF SANS SOUS-SERVICE AFFECTÉ ═══════════ -->
  <div class="med-section anim-fade-up-1" style="max-width:700px;margin:0 auto;">
    <div class="med-section-header" style="background:var(--blue);">
      <span class="med-section-title" style="color:white;">
        <i class="fa-solid fa-circle-info" style="color:rgba(255,255,255,.8);"></i>
        En attente d'affectation
      </span>
    </div>
    <div style="padding:28px 24px;text-align:center;">
      <div style="width:64px;height:64px;border-radius:50%;background:var(--blue-pale);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.6rem;color:var(--blue);">
        <i class="fa-solid fa-layer-group"></i>
      </div>
      <h3 style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--blue-dark);margin-bottom:10px;">
        Aucun sous-service affecté
      </h3>
      <p style="font-size:.88rem;color:var(--text-muted);line-height:1.7;max-width:420px;margin:0 auto 20px;">
        Vous êtes médecin chef de <strong><?= htmlspecialchars($affectation['service_nom'] ?? '') ?></strong>.
        Les sous-services sont créés et gérés par l'administrateur dans la section
        <strong>Services Hospitaliers</strong>. Veuillez contacter votre administrateur
        pour être affecté à un sous-service.
      </p>
      <a href="accueil.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--blue);color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:600;">
        <i class="fa-solid fa-house"></i> Retour à l'accueil
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- ═══════════ DASHBOARD NORMAL ═══════════ -->

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:18px;margin-bottom:28px;" class="anim-fade-up-1">
    <div class="med-stat-card">
      <div class="med-stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="med-stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
      <div class="med-stat-label">Total</div>
    </div>
    <div class="med-stat-card">
      <div class="med-stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
      <div class="med-stat-value"><?= (int)($stats['en_attente'] ?? 0) ?></div>
      <div class="med-stat-label">En attente</div>
    </div>
    <div class="med-stat-card s-green">
      <div class="med-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="med-stat-value"><?= (int)($stats['traitees'] ?? 0) ?></div>
      <div class="med-stat-label">Traitées</div>
    </div>
    <div class="med-stat-card s-orange">
      <div class="med-stat-icon"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
      <div class="med-stat-value"><?= (int)($stats['absentes'] ?? 0) ?></div>
      <div class="med-stat-label">Absents</div>
    </div>
    <div class="med-stat-card s-red">
      <div class="med-stat-icon"><i class="fa-solid fa-xmark"></i></div>
      <div class="med-stat-value"><?= (int)($stats['annulees'] ?? 0) ?></div>
      <div class="med-stat-label">Annulées</div>
    </div>
    <div class="med-stat-card">
      <div class="med-stat-icon"><i class="fa-solid fa-stopwatch"></i></div>
      <?php
        $dureeMoyRaw = isset($stats['duree_moy_sec']) ? $stats['duree_moy_sec'] : null;
        $dureeMoyAff = (!is_null($dureeMoyRaw) && $dureeMoyRaw !== '' && (float)$dureeMoyRaw > 0)
                       ? round((float)$dureeMoyRaw / 60) . ' min'
                       : '—';
      ?>
      <div class="med-stat-value"><?= htmlspecialchars($dureeMoyAff) ?></div>
      <div class="med-stat-label">Durée moy.</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;" class="anim-fade-up-2">

    <!-- Colonne gauche -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Consultations du jour -->
      <div class="med-section">
        <div class="med-section-header">
          <span class="med-section-title">
            <i class="fa-solid fa-calendar-day"></i> Consultations du jour
          </span>
          <span class="badge badge-blue"><?= count($consultations) ?></span>
        </div>
        <?php if (empty($consultations)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-light);">
          <i class="fa-solid fa-inbox" style="font-size:2rem;display:block;margin-bottom:12px;color:var(--border);"></i>
          Aucune consultation pour aujourd'hui.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="med-file-table">
            <thead>
              <tr>
                <th>#</th><th>Patient</th><th>Téléphone</th>
                <th>Heure prévue</th><th>Début</th><th>Fin</th>
                <th>Mode</th><th>Statut</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($consultations as $c): ?>
              <tr>
                <td><div class="med-rang"><?= (int)$c['rang'] ?></div></td>
                <td><strong><?= htmlspecialchars($c['patient_nom'] . ' ' . $c['patient_prenom']) ?></strong></td>
                <td><?= htmlspecialchars($c['telephone']) ?></td>
                <td><?= $c['heure_passage_estimee'] ? date('H:i', strtotime($c['heure_passage_estimee'])) : '—' ?></td>
                <td><?= $c['heure_debut_reelle']    ? date('H:i', strtotime($c['heure_debut_reelle']))    : '—' ?></td>
                <td><?= $c['heure_fin_reelle']      ? date('H:i', strtotime($c['heure_fin_reelle']))      : '—' ?></td>
                <td>
                  <?php if ($c['mode_prise'] === 'LIGNE'): ?>
                    <span class="badge badge-blue"><i class="fa-solid fa-wifi fa-xs"></i> Ligne</span>
                  <?php else: ?>
                    <span class="badge badge-green"><i class="fa-solid fa-qrcode fa-xs"></i> QR</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $bc = match($c['statut']) {
                    'traite'  => 'badge-green', 'en_cours' => 'badge-blue',
                    'annule','absent' => 'badge-red', default => 'badge-grey'
                  };
                  $lb = match($c['statut']) {
                    'traite' => 'Traitée','en_cours' => 'En cours',
                    'annule' => 'Annulée','absent'   => 'Absent',
                    'en_attente' => 'En attente','confirme' => 'Confirmée', default => $c['statut']
                  };
                  ?>
                  <span class="badge <?= $bc ?>"><?= $lb ?></span>
                </td>
                <td>
                  <?php if (in_array($c['statut'], ['en_attente','confirme','en_cours'])): ?>
                  <form method="POST" action="medecin.php?action=dashboard" style="display:flex;gap:5px;">
                    <input type="hidden" name="action" value="maj_statut">
                    <input type="hidden" name="consultation_id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" name="statut" value="traite"
                      class="btn btn-blue" style="padding:4px 8px;font-size:.72rem;">
                      <i class="fa-solid fa-check"></i>
                    </button>
                    <button type="submit" name="statut" value="absent"
                      class="btn btn-secondary" style="padding:4px 8px;font-size:.72rem;">
                      <i class="fa-solid fa-user-slash"></i>
                    </button>
                  </form>
                  <?php else: ?>
                  <span style="color:var(--text-light);font-size:.8rem;">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Planning de la semaine -->
      <div class="med-section">
        <div class="med-section-header">
          <span class="med-section-title">
            <i class="fa-solid fa-calendar-week"></i> Planning — semaine en cours
          </span>
          <span class="badge badge-blue"><?= count($planning) ?> créneaux</span>
        </div>
        <?php if (empty($planning)): ?>
        <div style="text-align:center;padding:32px;color:var(--text-light);font-size:.875rem;">
          <i class="fa-regular fa-calendar" style="font-size:1.8rem;display:block;margin-bottom:10px;color:var(--border);"></i>
          Aucun créneau planifié cette semaine.
        </div>
        <?php else: ?>
        <div class="planning-grid">
          <?php foreach ($planning as $p): ?>
          <div class="planning-item">
            <div class="planning-jour">
              <?= date('l d/m', strtotime($p['jour'])) ?>
            </div>
            <div class="planning-heure">
              <?= date('H:i', strtotime($p['heure_debut'])) ?>
              — <?= date('H:i', strtotime($p['heure_fin'])) ?>
            </div>
            <div class="planning-ss">
              <i class="fa-solid fa-sitemap fa-xs"></i>
              <?= htmlspecialchars($p['ss_nom']) ?>
            </div>
            <div style="margin-top:6px;">
              <span class="badge badge-blue" style="font-size:.65rem;">
                <?= (int)$p['nb_creneaux'] ?> RDV
              </span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($estChef && !empty($sousServicesChef)): ?>
      <!-- Sous-services du service (chef uniquement) -->
      <div class="med-section">
        <div class="med-section-header">
          <span class="med-section-title">
            <i class="fa-solid fa-sitemap"></i> Sous-services de mon service
          </span>
          <span class="badge badge-blue"><?= count($sousServicesChef) ?></span>
        </div>
        <div style="overflow-x:auto;">
          <table class="med-file-table">
            <thead>
              <tr>
                <th>Sous-service</th><th>Durée estimée</th>
                <th>Capacité/h</th><th>Médecins</th><th>Gestionnaires</th><th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sousServicesChef as $ss): ?>
              <tr>
                <td><strong><?= htmlspecialchars($ss['nom']) ?></strong>
                  <?php if ($ss['description']): ?>
                  <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($ss['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= round($ss['duree_estimee']/60) ?> min</td>
                <td><?= (int)$ss['capacite_horaire'] ?></td>
                <td><span class="badge badge-blue"><?= (int)$ss['nb_medecins'] ?></span></td>
                <td><span class="badge badge-green"><?= (int)$ss['nb_gestionnaires'] ?></span></td>
                <td>
                  <span class="badge <?= $ss['statut'] === 'actif' ? 'badge-green' : 'badge-grey' ?>">
                    <?= $ss['statut'] === 'actif' ? 'Actif' : 'Inactif' ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Fin section sous-services chef -->
      </div>
      <?php endif; ?>

    </div><!-- /colonne gauche -->

    <!-- Colonne droite -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Durée estimée -->
      <div class="med-section">
        <div class="med-section-header" style="background:var(--blue);">
          <span class="med-section-title" style="color:white;">
            <i class="fa-solid fa-stopwatch" style="color:rgba(255,255,255,.8);"></i>
            Durée estimée
          </span>
        </div>
        <div style="padding:18px;display:flex;flex-direction:column;gap:12px;">
          <div style="background:var(--blue-pale);border:1px solid var(--blue-light);border-radius:var(--radius-sm);padding:16px;text-align:center;">
            <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:var(--blue-dark);">
              <?= $dureeMin ?> min
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;">
              Par consultation
            </div>
          </div>
          <p style="font-size:.77rem;color:var(--text-muted);text-align:center;line-height:1.5;">
            <i class="fa-solid fa-moon" style="color:var(--blue);"></i>
            Recalculée chaque soir à 23h00
          </p>
        </div>
      </div>

      <!-- Infos session -->
      <div class="med-section">
        <div class="med-section-header" style="background:var(--blue-dark);">
          <span class="med-section-title" style="color:white;">
            <i class="fa-solid fa-circle-info" style="color:rgba(255,255,255,.8);"></i>
            Ma session
          </span>
        </div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--blue-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-user-doctor" style="color:var(--blue-dark);"></i>
            <div>
              <div style="font-size:.8rem;font-weight:700;color:var(--blue-dark);">Dr. <?= htmlspecialchars($medecinNom) ?></div>
              <div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($medecin['specialite'] ?? '') ?></div>
            </div>
          </div>
          <?php if ($aSousService): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--blue-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-hospital" style="color:var(--blue-dark);"></i>
            <span style="font-size:.82rem;color:var(--text-main);"><?= htmlspecialchars($affectation['service_nom']) ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--blue-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-sitemap" style="color:var(--blue-dark);"></i>
            <span style="font-size:.82rem;color:var(--text-main);"><?= htmlspecialchars($affectation['ss_nom']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($estChef): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:#fef3c7;border-radius:var(--radius-sm);">
            <i class="fa-solid fa-crown" style="color:#92400e;"></i>
            <span style="font-size:.82rem;color:#92400e;font-weight:600;">Chef de service</span>
          </div>
          <?php endif; ?>
          <a href="medecin.php?action=deconnexion" class="btn btn-danger btn-full" style="margin-top:4px;">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
          </a>
        </div>
      </div>

    </div><!-- /colonne droite -->
  </div>

  <?php endif; // fin else (dashboard normal) ?>

</main>

<script>
// ── Horloge ──
function updateClock() {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock, 1000);

// ── Timeout inactivité 15 minutes ──
const TIMEOUT_MS  = 15 * 60 * 1000; // 15 min
const WARN_MS     = 14 * 60 * 1000; // Avertissement à 14 min
let   idleTimer, warnTimer;

function resetIdleTimer() {
  clearTimeout(idleTimer);
  clearTimeout(warnTimer);
  // Avertissement à 14 min
  warnTimer = setTimeout(() => {
    showTimeoutWarning();
  }, WARN_MS);
  // Déconnexion à 15 min
  idleTimer = setTimeout(() => {
    window.location.href = 'medecin.php?action=deconnexion&timeout=1';
  }, TIMEOUT_MS);
}

function showTimeoutWarning() {
  const banner = document.getElementById('timeoutBanner');
  if (banner) banner.style.display = 'flex';
}

// Réinitialiser le timer sur toute activité utilisateur
['mousemove','mousedown','keydown','scroll','touchstart','click'].forEach(evt =>
  document.addEventListener(evt, resetIdleTimer, { passive: true })
);
resetIdleTimer();

// Rechargement automatique des données toutes les 60s
setInterval(() => window.location.reload(), 60000);
</script>
</body>
</html>

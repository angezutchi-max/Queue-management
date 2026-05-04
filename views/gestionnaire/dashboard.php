<?php
// views/gestionnaire/dashboard.php

// ── Timeout inactivité 15 min ──
const SESSION_TIMEOUT_G = 900; // 15 minutes
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_G) {
        session_unset();
        session_destroy();
        header('Location: gestionnaire.php?action=connexion&timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

$initiale = mb_strtoupper(mb_substr($gestionnaireNom, 0, 1));
$dureeMin = isset($sousService['duree_estimee']) ? round($sousService['duree_estimee'] / 60) : 30;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — QueueCare</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/dashboard.css">
  <script src="../../public/js/auto-dismiss.js" defer></script>
</head>
<body class="dash-body">

<header class="topbar">
  <a href="gestionnaire.php?action=dashboard" class="topbar-logo">
    <span class="topbar-logo-icon"><i class="fa-solid fa-list-check"></i></span>
    QueueCare
  </a>
  <div class="topbar-sep"></div>
  <div class="topbar-service">
    <i class="fa-solid fa-hospital"></i>
    <?= htmlspecialchars($sousService['service_nom']) ?> —
    <strong><?= htmlspecialchars($sousService['nom']) ?></strong>
  </div>
  <div class="topbar-user">
    <div class="topbar-avatar"><?= $initiale ?></div>
    <span><?= htmlspecialchars($gestionnaireNom) ?></span>
  </div>
  <a href="accueil.php" class="topbar-logout" style="background:rgba(255,255,255,.08);margin-right:4px;" title="Retour à l'accueil">
    <i class="fa-solid fa-house"></i> Accueil
  </a>
  <a href="gestionnaire.php?action=deconnexion" class="topbar-logout">
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

<main class="dash-main">

  <!-- En-tête -->
  <div class="dash-header anim-fade-up">
    <div>
      <h1 class="dash-welcome">
        Bonjour, <?= htmlspecialchars(explode(' ', $gestionnaireNom)[0]) ?>
        <i class="fa-solid fa-hand-wave" style="color:var(--green);font-size:1.5rem;"></i>
      </h1>
      <p class="dash-date">
        <i class="fa-regular fa-calendar" style="color:var(--green);"></i>
        <?= date('d/m/Y') ?>
        &nbsp;|&nbsp;
        <i class="fa-regular fa-clock" style="color:var(--blue);"></i>
        <span id="clock"></span>
      </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <form method="POST" action="gestionnaire.php?action=dashboard" style="margin:0;">
        <input type="hidden" name="action" value="appeler_suivant">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-bullhorn"></i> Appeler le suivant
        </button>
      </form>
      <button class="btn btn-secondary" onclick="window.location.reload()">
        <i class="fa-solid fa-rotate-right"></i> Actualiser
      </button>
      <button type="button" class="btn btn-blue" onclick="document.getElementById('modalConsultation').style.display='flex'">
        <i class="fa-solid fa-user-plus"></i> Enregistrer une consultation
      </button>
    </div>
  </div>

  <!-- Message action -->
  <?php if (!empty($messageAction)): ?>
  <div class="action-msg anim-fade-up <?= ($typeMessage ?? 'success') === 'error' ? 'action-msg-error' : '' ?>">
    <i class="fa-solid <?= ($typeMessage ?? 'success') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
    <?= htmlspecialchars($messageAction) ?>
  </div>
  <?php endif; ?>

  <!-- Statistiques du jour -->
  <div class="stats-grid anim-fade-up-1">
    <div class="stat-card s-green">
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
      <div class="stat-label">Total consultations</div>
    </div>
    <div class="stat-card s-blue">
      <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
      <div class="stat-value"><?= (int)($stats['en_attente'] ?? 0) ?></div>
      <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card s-green">
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="stat-value"><?= (int)($stats['traitees'] ?? 0) ?></div>
      <div class="stat-label">Traitées</div>
    </div>
    <div class="stat-card s-orange">
      <div class="stat-icon"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
      <div class="stat-value"><?= (int)($stats['absentes'] ?? 0) ?></div>
      <div class="stat-label">Absents</div>
    </div>
    <div class="stat-card s-red">
      <div class="stat-icon"><i class="fa-solid fa-xmark"></i></div>
      <div class="stat-value"><?= (int)($stats['annulees'] ?? 0) ?></div>
      <div class="stat-label">Annulées</div>
    </div>
    <div class="stat-card s-blue">
      <div class="stat-icon"><i class="fa-solid fa-wifi"></i></div>
      <div class="stat-value"><?= (int)($stats['en_ligne'] ?? 0) ?></div>
      <div class="stat-label">En ligne</div>
    </div>
    <div class="stat-card s-green">
      <div class="stat-icon"><i class="fa-solid fa-qrcode"></i></div>
      <div class="stat-value"><?= (int)($stats['sur_place'] ?? 0) ?></div>
      <div class="stat-label">Sur place (QR)</div>
    </div>
  </div>

  <!-- Grille principale -->
  <div class="dash-grid anim-fade-up-2">

    <!-- Colonne gauche : listes -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- File d'attente active -->
      <div class="section">
        <div class="section-header">
          <span class="section-title">
            <i class="fa-solid fa-list-ol"></i> File d'attente en cours
          </span>
          <span class="badge badge-green"><?= count($file) ?> patient<?= count($file) > 1 ? 's' : '' ?></span>
        </div>
        <?php if (empty($file)): ?>
        <div class="file-empty">
          <i class="fa-solid fa-inbox"></i>
          Aucun patient en attente pour le moment.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="file-table">
            <thead>
              <tr>
                <th>Rang</th><th>Patient</th><th>Téléphone</th>
                <th>Heure estimée</th><th>Mode</th><th>Statut</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($file as $c): ?>
              <tr>
                <td>
                  <div class="rang-badge <?= $c['statut'] === 'en_cours' ? 'actif' : '' ?>">
                    <?= (int)$c['rang'] ?>
                  </div>
                </td>
                <td><strong><?= htmlspecialchars($c['patient_nom'] . ' ' . $c['patient_prenom']) ?></strong></td>
                <td><i class="fa-solid fa-phone fa-xs" style="color:var(--green);"></i> <?= htmlspecialchars($c['telephone']) ?></td>
                <td>
                  <?= $c['heure_passage_estimee']
                      ? '<i class="fa-regular fa-clock fa-xs" style="color:var(--blue);"></i> ' . date('H:i', strtotime($c['heure_passage_estimee']))
                      : '<span style="color:var(--text-light);">—</span>' ?>
                </td>
                <td>
                  <?php if ($c['mode_prise'] === 'LIGNE'): ?>
                    <span class="badge badge-blue"><i class="fa-solid fa-wifi fa-xs"></i> En ligne</span>
                  <?php else: ?>
                    <span class="badge badge-green"><i class="fa-solid fa-qrcode fa-xs"></i> QR Code</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $bc = match($c['statut']) {
                    'en_cours'   => 'badge-blue',
                    'en_attente','confirme' => 'badge-grey',
                    default      => 'badge-grey'
                  };
                  $lb = match($c['statut']) {
                    'en_cours'   => 'En cours',
                    'en_attente' => 'En attente',
                    'confirme'   => 'Confirmé',
                    default      => $c['statut']
                  };
                  ?>
                  <span class="badge <?= $bc ?>"><?= $lb ?></span>
                </td>
                <td>
                  <form method="POST" action="gestionnaire.php?action=dashboard"
                        style="display:flex;gap:5px;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="maj_statut">
                    <input type="hidden" name="consultation_id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" name="statut" value="traite"
                      class="btn btn-primary" style="padding:5px 10px;font-size:.73rem;">
                      <i class="fa-solid fa-check"></i> Traité
                    </button>
                    <button type="submit" name="statut" value="absent"
                      class="btn btn-secondary" style="padding:5px 10px;font-size:.73rem;">
                      <i class="fa-solid fa-user-slash"></i> Absent
                    </button>
                    <button type="submit" name="statut" value="annule"
                      class="btn btn-danger" style="padding:5px 10px;font-size:.73rem;">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Toutes les consultations du jour -->
      <div class="section">
        <div class="section-header">
          <span class="section-title">
            <i class="fa-solid fa-calendar-day"></i> Toutes les consultations du jour
          </span>
          <span class="badge badge-blue"><?= count($consultations) ?></span>
        </div>
        <?php if (empty($consultations)): ?>
        <div class="file-empty">
          <i class="fa-regular fa-calendar-xmark"></i> Aucune consultation enregistrée.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="file-table">
            <thead>
              <tr>
                <th>#</th><th>Patient</th><th>Médecin</th>
                <th>Heure prévue</th><th>Début réel</th><th>Fin réelle</th>
                <th>Mode</th><th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($consultations as $c): ?>
              <tr>
                <td><?= (int)$c['rang'] ?></td>
                <td><strong><?= htmlspecialchars($c['patient_nom'] . ' ' . $c['patient_prenom']) ?></strong></td>
                <td><?= $c['medecin_nom'] ? htmlspecialchars($c['medecin_nom']) : '<span style="color:var(--text-light);">—</span>' ?></td>
                <td><?= $c['heure_passage_estimee'] ? date('H:i', strtotime($c['heure_passage_estimee'])) : '—' ?></td>
                <td><?= $c['heure_debut_reelle']    ? date('H:i', strtotime($c['heure_debut_reelle']))    : '—' ?></td>
                <td><?= $c['heure_fin_reelle']      ? date('H:i', strtotime($c['heure_fin_reelle']))      : '—' ?></td>
                <td>
                  <?php if ($c['mode_prise'] === 'LIGNE'): ?>
                    <span class="badge badge-blue"><i class="fa-solid fa-wifi fa-xs"></i> En ligne</span>
                  <?php else: ?>
                    <span class="badge badge-green"><i class="fa-solid fa-qrcode fa-xs"></i> QR Code</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $bc = match($c['statut']) {
                    'traite'     => 'badge-green', 'en_cours'   => 'badge-blue',
                    'annule','absent' => 'badge-red',
                    default      => 'badge-grey'
                  };
                  $lb = match($c['statut']) {
                    'traite' => 'Traitée', 'en_cours' => 'En cours',
                    'annule' => 'Annulée', 'absent'   => 'Absent',
                    'en_attente' => 'En attente', 'confirme' => 'Confirmée',
                    default  => $c['statut']
                  };
                  ?>
                  <span class="badge <?= $bc ?>"><?= $lb ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Colonne droite : actions -->
    <div class="actions-panel">

      <!-- Durée estimée -->
      <div class="action-card">
        <div class="section-header blue-hdr" style="background:var(--blue);">
          <span class="section-title" style="color:white;">
            <i class="fa-solid fa-stopwatch"></i> Durée estimée
          </span>
        </div>
        <div class="action-body">
          <div class="estim-box">
            <div class="estim-value"><?= $dureeMin ?> min</div>
            <div class="estim-label">Par consultation</div>
          </div>
          <p style="font-size:.77rem;color:var(--text-muted);text-align:center;line-height:1.5;">
            <i class="fa-solid fa-moon" style="color:var(--blue);"></i>
            Recalculée chaque soir à 23h00 par moyenne pondérée
          </p>
        </div>
      </div>

      <!-- Urgences -->
      <div class="action-card">
        <div class="section-header red-hdr" style="background:#dc2626;">
          <span class="section-title" style="color:white;">
            <i class="fa-solid fa-triangle-exclamation"></i> Urgences actives
          </span>
          <?php if (!empty($urgences)): ?>
          <span class="badge" style="background:rgba(255,255,255,.25);color:white;"><?= count($urgences) ?></span>
          <?php endif; ?>
        </div>
        <div class="action-body">
          <?php if (empty($urgences)): ?>
          <div style="text-align:center;padding:16px 0;color:var(--text-light);font-size:.875rem;">
            <i class="fa-solid fa-circle-check" style="font-size:1.4rem;color:var(--green);display:block;margin-bottom:8px;"></i>
            Aucune urgence active
          </div>
          <?php else: ?>
          <?php foreach ($urgences as $u): ?>
          <div class="urgence-item prio-<?= (int)$u['priorite'] ?>">
            <i class="fa-solid fa-triangle-exclamation urgence-icon"></i>
            <div>
              <div class="urgence-desc"><?= htmlspecialchars($u['description']) ?></div>
              <div class="urgence-time">
                <i class="fa-regular fa-clock fa-xs"></i>
                <?= date('H:i', strtotime($u['created_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Gérer une consultation -->
      <div class="action-card">
        <div class="section-header">
          <span class="section-title">
            <i class="fa-solid fa-sliders"></i> Gérer une consultation
          </span>
        </div>
        <div class="action-body">
          <form method="POST" action="gestionnaire.php?action=dashboard"
                style="display:flex;flex-direction:column;gap:10px;">
            <input type="hidden" name="action" value="maj_statut">
            <label class="field-label">
              <i class="fa-solid fa-hashtag fa-xs"></i> Consultation
            </label>
            <select name="consultation_id" class="inline-select">
              <option value="">— Sélectionner —</option>
              <?php foreach ($consultations as $c): ?>
              <option value="<?= (int)$c['id'] ?>">
                #<?= (int)$c['rang'] ?> — <?= htmlspecialchars($c['patient_nom'] . ' ' . $c['patient_prenom']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <label class="field-label">
              <i class="fa-solid fa-tag fa-xs"></i> Nouveau statut
            </label>
            <select name="statut" class="inline-select">
              <option value="">— Statut —</option>
              <option value="traite">Traitée</option>
              <option value="absent">Absent</option>
              <option value="annule">Annulée</option>
            </select>
            <button type="submit" class="btn btn-primary btn-full">
              <i class="fa-solid fa-floppy-disk"></i> Appliquer
            </button>
          </form>
        </div>
      </div>

      <!-- Infos session -->
      <div class="action-card">
        <div class="section-header" style="background:var(--green-dark);">
          <span class="section-title" style="color:white;">
            <i class="fa-solid fa-circle-info"></i> Ma session
          </span>
        </div>
        <div class="action-body">
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--green-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-user-tie" style="color:var(--green-dark);"></i>
            <span style="font-size:.875rem;font-weight:600;color:var(--text-main);"><?= htmlspecialchars($gestionnaireNom) ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--blue-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-hospital" style="color:var(--blue-dark);"></i>
            <span style="font-size:.875rem;color:var(--text-main);"><?= htmlspecialchars($sousService['service_nom']) ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--green-pale);border-radius:var(--radius-sm);">
            <i class="fa-solid fa-sitemap" style="color:var(--green-dark);"></i>
            <span style="font-size:.875rem;color:var(--text-main);"><?= htmlspecialchars($sousService['nom']) ?></span>
          </div>
          <a href="gestionnaire.php?action=deconnexion" class="btn btn-danger btn-full" style="margin-top:4px;">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
          </a>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- ══════════════ MODAL CONSULTATION MANUELLE ══════════════ -->
<div id="modalConsultation"
     style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(13,29,67,.55);backdrop-filter:blur(3px);
            align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:20px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;
              box-shadow:0 30px 80px rgba(0,0,0,.3);animation:slideUp .3s cubic-bezier(.22,1,.36,1);">

    <!-- Header modal -->
    <div style="background:linear-gradient(135deg,#1a4db5,#2563eb);border-radius:20px 20px 0 0;padding:22px 28px;
                display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:#fff;margin-bottom:3px;">
          <i class="fa-solid fa-user-plus" style="margin-right:8px;opacity:.85;"></i>Enregistrer une consultation
        </div>
        <div style="font-size:.8rem;color:rgba(255,255,255,.7);">Saisie manuelle — <?= htmlspecialchars($sousService['nom']) ?></div>
      </div>
      <button onclick="fermerModal()" style="background:rgba(255,255,255,.15);border:none;color:#fff;
              width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:1rem;
              display:flex;align-items:center;justify-content:center;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Corps formulaire -->
    <form method="POST" action="gestionnaire.php?action=dashboard" id="formConsultManuelle"
          style="padding:24px 28px;display:flex;flex-direction:column;gap:18px;">
      <input type="hidden" name="action" value="consultation_manuelle">

      <!-- Séparateur : Informations patient -->
      <div style="font-size:.72rem;font-weight:700;color:#1a4db5;text-transform:uppercase;letter-spacing:.08em;
                  padding-bottom:6px;border-bottom:1.5px solid #e2ecf8;display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-user"></i> Informations du patient
      </div>

      <!-- Téléphone (cherche en auto) -->
      <div class="field">
        <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
          <i class="fa-solid fa-phone fa-xs"></i> Téléphone *
        </label>
        <div style="position:relative;">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b83a8;font-size:.85rem;">
            <i class="fa-solid fa-phone"></i>
          </span>
          <input type="tel" id="m_telephone" name="patient_telephone"
                 style="width:100%;padding:10px 38px 10px 36px;border:1.5px solid #c8dff2;border-radius:8px;
                        font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;"
                 placeholder="+237699123456" inputmode="tel"
                 oninput="this.value=this.value.replace(/[^0-9+]/g,'');rechercherPatient(this.value)">
          <span id="searchSpinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#1a4db5;">
            <i class="fa-solid fa-spinner fa-spin"></i>
          </span>
        </div>
        <small style="color:#6b83a8;font-size:.71rem;">Entrez le numéro pour rechercher un patient existant.</small>
      </div>

      <!-- Nom et Prénom -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="field">
          <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
            <i class="fa-solid fa-user fa-xs"></i> Nom *
          </label>
          <input type="text" id="m_nom" name="patient_nom"
                 style="width:100%;padding:10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                        font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;"
                 placeholder="Nom de famille" required>
        </div>
        <div class="field">
          <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
            <i class="fa-solid fa-user fa-xs"></i> Prénom *
          </label>
          <input type="text" id="m_prenom" name="patient_prenom"
                 style="width:100%;padding:10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                        font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;"
                 placeholder="Prénom" required>
        </div>
      </div>

      <!-- Email -->
      <div class="field">
        <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
          <i class="fa-solid fa-envelope fa-xs"></i> Email (optionnel)
        </label>
        <input type="email" id="m_email" name="patient_email"
               style="width:100%;padding:10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                      font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;"
               placeholder="patient@email.cm">
      </div>

      <!-- Séparateur : Détails consultation -->
      <div style="font-size:.72rem;font-weight:700;color:#1a4db5;text-transform:uppercase;letter-spacing:.08em;
                  padding-bottom:6px;border-bottom:1.5px solid #e2ecf8;display:flex;align-items:center;gap:8px;margin-top:4px;">
        <i class="fa-solid fa-notes-medical"></i> Détails de la consultation
      </div>

      <!-- Médecin affecté -->
      <div class="field">
        <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
          <i class="fa-solid fa-user-doctor fa-xs"></i> Médecin affecté (optionnel)
        </label>
        <div style="position:relative;">
          <select name="medecin_id"
                  style="width:100%;padding:10px 32px 10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                         font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;appearance:none;">
            <option value="">— Aucun médecin spécifique —</option>
            <?php foreach ($medecins as $med): ?>
            <option value="<?= (int)$med['id'] ?>">
              Dr <?= htmlspecialchars($med['prenom']) ?> <?= htmlspecialchars($med['nom']) ?>
              — <?= htmlspecialchars($med['specialite']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b83a8;pointer-events:none;font-size:.75rem;">
            <i class="fa-solid fa-chevron-down"></i>
          </span>
        </div>
        <?php if (empty($medecins)): ?>
        <small style="color:#f59e0b;font-size:.71rem;"><i class="fa-solid fa-circle-exclamation fa-xs"></i> Aucun médecin disponible pour ce sous-service.</small>
        <?php endif; ?>
      </div>

      <!-- Mode de prise & Statut initial -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="field">
          <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
            <i class="fa-solid fa-arrow-right-to-bracket fa-xs"></i> Mode de prise
          </label>
          <div style="position:relative;">
            <select name="mode_prise"
                    style="width:100%;padding:10px 32px 10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                           font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;appearance:none;">
              <option value="PLACE" selected>Sur place</option>
              <option value="LIGNE">En ligne</option>
            </select>
            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b83a8;pointer-events:none;font-size:.75rem;"><i class="fa-solid fa-chevron-down"></i></span>
          </div>
        </div>
        <div class="field">
          <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
            <i class="fa-solid fa-tag fa-xs"></i> Statut initial
          </label>
          <div style="position:relative;">
            <select name="statut"
                    style="width:100%;padding:10px 32px 10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                           font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;appearance:none;">
              <option value="en_attente" selected>En attente</option>
              <option value="confirme">Confirmé</option>
            </select>
            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b83a8;pointer-events:none;font-size:.75rem;"><i class="fa-solid fa-chevron-down"></i></span>
          </div>
        </div>
      </div>

      <!-- Motif -->
      <div class="field">
        <label style="font-size:.75rem;font-weight:700;color:#0d2d6b;display:block;margin-bottom:5px;">
          <i class="fa-solid fa-comment-medical fa-xs"></i> Motif de consultation (optionnel)
        </label>
        <textarea name="motif" rows="3"
                  style="width:100%;padding:10px 12px;border:1.5px solid #c8dff2;border-radius:8px;
                         font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8faff;
                         resize:vertical;line-height:1.5;"
                  placeholder="Décrivez brièvement le motif de la consultation…"></textarea>
      </div>

      <!-- Actions formulaire -->
      <div style="display:flex;gap:10px;margin-top:4px;">
        <button type="button" onclick="fermerModal()"
                style="flex:1;padding:12px;border:1.5px solid #c8dff2;border-radius:9px;background:#f0f4fa;
                       color:#6b83a8;font-family:inherit;font-size:.875rem;font-weight:600;cursor:pointer;">
          <i class="fa-solid fa-xmark"></i> Annuler
        </button>
        <button type="submit" id="btnSubmitConsult"
                style="flex:2;padding:12px;border:none;border-radius:9px;
                       background:linear-gradient(135deg,#1a4db5,#2563eb);
                       color:#fff;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;
                       box-shadow:0 4px 14px rgba(26,77,181,.35);transition:all .2s;">
          <i class="fa-solid fa-check"></i> Enregistrer la consultation
        </button>
      </div>

    </form>
  </div>
</div>

<style>
@keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
.action-msg-error { background:linear-gradient(135deg,#fee2e2,#fecaca) !important; border-color:#fca5a5 !important; color:#7f1d1d !important; }
</style>

<script>
// ── Horloge ──
function updateClock() {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);

// ── Timeout inactivité 15 minutes ──
const TIMEOUT_MS = 15 * 60 * 1000;
const WARN_MS    = 14 * 60 * 1000;
let idleTimer, warnTimer;

function resetIdleTimer() {
  clearTimeout(idleTimer);
  clearTimeout(warnTimer);
  warnTimer = setTimeout(() => {
    const b = document.getElementById('timeoutBanner');
    if (b) b.style.display = 'flex';
  }, WARN_MS);
  idleTimer = setTimeout(() => {
    window.location.href = 'gestionnaire.php?action=deconnexion&timeout=1';
  }, TIMEOUT_MS);
}

['mousemove','mousedown','keydown','scroll','touchstart','click'].forEach(evt =>
  document.addEventListener(evt, resetIdleTimer, { passive: true })
);
resetIdleTimer();

// ── Modal consultation ──
function fermerModal() {
  document.getElementById('modalConsultation').style.display = 'none';
  document.getElementById('formConsultManuelle').reset();
}
// Fermer en cliquant sur le fond
document.getElementById('modalConsultation').addEventListener('click', function(e) {
  if (e.target === this) fermerModal();
});
// Soumettre avec feedback
document.getElementById('formConsultManuelle').addEventListener('submit', function() {
  const btn = document.getElementById('btnSubmitConsult');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement…';
  btn.disabled  = true;
});

// ── Recherche patient par téléphone (AJAX) ──
let searchTimer;
function rechercherPatient(tel) {
  const clean = tel.replace(/[^0-9+]/g, '');
  if (clean.length < 8) return;
  clearTimeout(searchTimer);
  const spinner = document.getElementById('searchSpinner');
  searchTimer = setTimeout(async () => {
    spinner.style.display = '';
    try {
      const r = await fetch('gestionnaire.php?action=dashboard&api=patient&tel=' + encodeURIComponent(clean));
      const p = await r.json();
      if (p) {
        document.getElementById('m_nom').value    = p.nom    || '';
        document.getElementById('m_prenom').value = p.prenom || '';
        document.getElementById('m_email').value  = p.email  || '';
        // Feedback visuel
        document.getElementById('m_nom').style.background    = '#d1fae5';
        document.getElementById('m_prenom').style.background = '#d1fae5';
        setTimeout(() => {
          document.getElementById('m_nom').style.background    = '#f8faff';
          document.getElementById('m_prenom').style.background = '#f8faff';
        }, 1500);
      }
    } catch(e) {}
    finally { spinner.style.display = 'none'; }
  }, 500);
}

// Rechargement des données toutes les 60s
setInterval(() => window.location.reload(), 60000);
</script>
</body>
</html>

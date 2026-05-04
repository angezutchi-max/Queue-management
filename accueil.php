<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueueCare — Bienvenue</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #0a5c36 0%, #1a4db5 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-body);
      position: relative;
      overflow: hidden;
    }

    /* Décorations background */
    .bg-circle {
      position: absolute; border-radius: 50%;
      border: 1.5px solid rgba(255,255,255,.08);
      pointer-events: none;
    }
    .bg-circle-1 { width:600px; height:600px; top:-200px; left:-150px; }
    .bg-circle-2 { width:400px; height:400px; bottom:-100px; right:-80px; }
    .bg-circle-3 { width:200px; height:200px; top:50%;  left:60%; }
    .bg-dot { position:absolute; border-radius:50%; background:rgba(255,255,255,.12); pointer-events:none; }
    .bg-dot-1 { width:12px; height:12px; top:20%; left:15%; }
    .bg-dot-2 { width: 8px; height: 8px; top:70%; left:80%; }
    .bg-dot-3 { width:16px; height:16px; top:40%; left:5%;  }

    /* Carte principale */
    .welcome-card {
      background: rgba(255,255,255,.97);
      border-radius: 24px;
      box-shadow: 0 24px 80px rgba(0,0,0,.25);
      padding: 56px 52px;
      width: 100%;
      max-width: 580px;
      position: relative;
      z-index: 10;
      animation: fadeUp .5s ease both;
    }

    @keyframes fadeUp {
      from { opacity:0; transform:translateY(24px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* Logo */
    .wc-logo {
      display: flex; align-items: center; justify-content: center;
      gap: 12px; margin-bottom: 36px;
    }
    .wc-logo-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #1a8a52, #1a4db5);
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 1.3rem;
      box-shadow: 0 6px 20px rgba(26,77,181,.35);
    }
    .wc-logo-name {
      font-family: var(--font-display);
      font-size: 1.9rem; font-weight: 700;
      color: var(--blue-dark);
    }

    /* Titre */
    .wc-title {
      font-family: var(--font-display);
      font-size: 1.55rem; font-weight: 700;
      color: var(--blue-dark);
      text-align: center;
      margin-bottom: 10px;
    }
    .wc-sub {
      font-size: .9rem; color: var(--text-muted);
      text-align: center; margin-bottom: 44px; line-height: 1.6;
    }

    /* Cartes de choix */
    .choice-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    /* Service hospitalier — orange/slate */
    .choice-card.service {
      border-color: #fed7aa;
      grid-column: 1 / -1;
    }
    .choice-card.service::before {
      background: linear-gradient(135deg, rgba(234,88,12,.05), rgba(234,88,12,.02));
    }
    .choice-card.service:hover {
      border-color: #ea580c;
    }
    .choice-card.service:hover::before { opacity: 1; }
    .choice-card.service .choice-icon {
      background: linear-gradient(135deg, #fff7ed, #fed7aa);
      color: #9a3412;
    }
    .choice-card.service .choice-label { color: #7c2d12; }
    .choice-card.service .choice-arrow { color: #ea580c; }
    .choice-card.service {
      flex-direction: row; gap: 20px; padding: 22px 28px; align-items: center;
    }
    .choice-card.service .choice-icon { width: 56px; height: 56px; flex-shrink: 0; }
    .choice-card.service .choice-text { flex: 1; }
    .choice-card.service .choice-label { font-size: 1.05rem; text-align: left; }
    .choice-card.service .choice-desc  { text-align: left; }
    .choice-card.service .choice-arrow { white-space:nowrap; }

    .section-divider {
      display:flex; align-items:center; gap:12px;
      color: #8aabd4; font-size:.75rem; letter-spacing:.08em; text-transform:uppercase;
      margin-bottom:16px;
    }
    .section-divider::before, .section-divider::after {
      content:''; flex:1; height:1px; background:#e2ecf8;
    }

    .choice-card {
      display: flex; flex-direction: column; align-items: center;
      gap: 16px; padding: 32px 20px;
      border-radius: 16px;
      border: 2px solid var(--border);
      background: var(--white);
      cursor: pointer; text-decoration: none;
      transition: all .22s cubic-bezier(.4,0,.2,1);
      position: relative; overflow: hidden;
    }
    .choice-card::before {
      content: ''; position: absolute; inset: 0;
      opacity: 0; transition: opacity .22s;
    }
    .choice-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 36px rgba(0,0,0,.14);
    }

    /* Gestionnaire — vert */
    .choice-card.gestionnaire {
      border-color: #9dd8bc;
    }
    .choice-card.gestionnaire::before {
      background: linear-gradient(135deg, rgba(26,138,82,.06), rgba(26,138,82,.02));
    }
    .choice-card.gestionnaire:hover {
      border-color: #1a8a52;
    }
    .choice-card.gestionnaire:hover::before { opacity: 1; }
    .choice-card.gestionnaire .choice-icon {
      background: linear-gradient(135deg, #edfaf3, #d4f0e2);
      color: #0a5c36;
    }
    .choice-card.gestionnaire .choice-label { color: #0a5c36; }
    .choice-card.gestionnaire .choice-arrow { color: #1a8a52; }

    /* Médecin — bleu */
    .choice-card.medecin {
      border-color: #bfdbfe;
    }
    .choice-card.medecin::before {
      background: linear-gradient(135deg, rgba(26,77,181,.06), rgba(26,77,181,.02));
    }
    .choice-card.medecin:hover {
      border-color: #1a4db5;
    }
    .choice-card.medecin:hover::before { opacity: 1; }
    .choice-card.medecin .choice-icon {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      color: #0d2d6b;
    }
    .choice-card.medecin .choice-label { color: #0d2d6b; }
    .choice-card.medecin .choice-arrow { color: #1a4db5; }

    .choice-icon {
      width: 68px; height: 68px; border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.7rem; position: relative; z-index: 1;
      transition: transform .22s;
    }
    .choice-card:hover .choice-icon { transform: scale(1.08); }

    .choice-label {
      font-family: var(--font-display);
      font-size: 1.15rem; font-weight: 700;
      position: relative; z-index: 1; text-align: center;
    }
    .choice-desc {
      font-size: .8rem; color: var(--text-muted);
      text-align: center; line-height: 1.5;
      position: relative; z-index: 1;
    }
    .choice-arrow {
      font-size: .85rem; position: relative; z-index: 1;
      transition: transform .22s;
    }
    .choice-card:hover .choice-arrow { transform: translateX(4px); }

    /* Footer */
    .wc-footer {
      text-align: center; font-size: .78rem; color: var(--text-light);
      border-top: 1px solid var(--border); padding-top: 22px;
    }

    @media (max-width: 520px) {
      .welcome-card  { padding: 36px 24px; margin: 16px; }
      .choice-grid   { grid-template-columns: 1fr; }
      .wc-logo-name  { font-size: 1.5rem; }
    }
  </style>
</head>
<body>

  <div class="bg-circle bg-circle-1"></div>
  <div class="bg-circle bg-circle-2"></div>
  <div class="bg-circle bg-circle-3"></div>
  <div class="bg-dot bg-dot-1"></div>
  <div class="bg-dot bg-dot-2"></div>
  <div class="bg-dot bg-dot-3"></div>

  <div class="welcome-card">

    <!-- Logo -->
    <div class="wc-logo">
      <div class="wc-logo-icon"><i class="fa-solid fa-list-check"></i></div>
      <span class="wc-logo-name">QueueCare</span>
    </div>

    <h1 class="wc-title">Bienvenue sur la plateforme</h1>
    <p class="wc-sub">
      Système intelligent de gestion des files d'attente hospitalières.<br>
      Choisissez votre espace pour vous connecter.
    </p>

    <!-- Étape 1 : Service hospitalier -->
    <div class="section-divider">Étape 1 — Configurer l'hôpital</div>

    <div class="choice-grid" style="margin-bottom:16px;">
      <a href="service.php?action=liste" class="choice-card service">
        <div class="choice-icon">
          <i class="fa-solid fa-hospital"></i>
        </div>
        <div class="choice-text">
          <div class="choice-label">Services Hospitaliers</div>
          <div class="choice-desc">Créez et gérez les établissements avant toute inscription</div>
        </div>
        <span class="choice-arrow">
          <i class="fa-solid fa-arrow-right"></i> Gérer
        </span>
      </a>
    </div>

    <!-- Étape 2 : Connexion -->
    <div class="section-divider">Étape 2 — Se connecter</div>

    <!-- Choix de connexion -->
    <div class="choice-grid">

      <a href="gestionnaire.php?action=connexion" class="choice-card gestionnaire">
        <div class="choice-icon">
          <i class="fa-solid fa-user-tie"></i>
        </div>
        <div class="choice-label">Gestionnaire</div>
        <div class="choice-desc">Gérer la file d'attente et les consultations du sous-service</div>
        <span class="choice-arrow">
          <i class="fa-solid fa-arrow-right"></i> Accéder
        </span>
      </a>

      <a href="medecin.php?action=connexion" class="choice-card medecin">
        <div class="choice-icon">
          <i class="fa-solid fa-user-doctor"></i>
        </div>
        <div class="choice-label">Médecin</div>
        <div class="choice-desc">Gérer les consultations, les plannings et les sous-services</div>
        <span class="choice-arrow">
          <i class="fa-solid fa-arrow-right"></i> Accéder
        </span>
      </a>

    </div>

    <div class="wc-footer">
      &copy; <?= date('Y') ?> QueueCare &mdash; Plateforme hospitalière de gestion des files d'attente
    </div>

  </div>

</body>
</html>

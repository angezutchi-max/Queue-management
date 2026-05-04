<?php // views/medecin/connexion.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Médecin — QueueCare</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/medecin.css">
  <script src="../../public/js/auto-dismiss.js" defer></script>
</head>
<body class="med-auth-body">
<div class="med-auth-wrapper">

  <!-- Panneau gauche bleu -->
  <aside class="med-brand">
    <div class="med-brand-inner">
      <div class="med-logo anim-fade-up">
        <span class="med-logo-icon"><i class="fa-solid fa-user-doctor"></i></span>
        <span class="med-logo-name">QueueCare</span>
      </div>
      <div class="med-brand-body anim-fade-up-1">
        <h1 class="med-brand-title">Bon retour,<br><em>Docteur.</em></h1>
        <p class="med-brand-sub">Accédez à votre espace pour gérer vos consultations, votre planning et vos sous-services.</p>
      </div>
      <ul class="med-features anim-fade-up-2">
        <li><i class="fa-solid fa-circle-check"></i> Tableau de bord des consultations</li>
        <li><i class="fa-solid fa-circle-check"></i> Gestion des sous-services (chef)</li>
        <li><i class="fa-solid fa-circle-check"></i> Planning hebdomadaire</li>
        <li><i class="fa-solid fa-circle-check"></i> Suivi des patients en temps réel</li>
      </ul>
      <div class="med-brand-deco">
        <div class="med-deco-ring med-deco-ring-1"></div>
        <div class="med-deco-ring med-deco-ring-2"></div>
        <div class="med-deco-dot med-deco-dot-1"></div>
        <div class="med-deco-dot med-deco-dot-2"></div>
      </div>
    </div>
  </aside>

  <!-- Panneau droit -->
  <main class="med-form-panel">
    <div class="med-form-inner anim-fade-up">

      <div class="med-form-header">
        <a href="accueil.php" style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-muted);margin-bottom:14px;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='var(--blue)'" onmouseout="this.style.color='var(--text-muted)'">
          <i class="fa-solid fa-arrow-left fa-xs"></i> Retour à l'accueil
        </a><br>
        <span class="badge badge-blue"><i class="fa-solid fa-shield-halved"></i>&nbsp; Accès sécurisé</span>
        <h2 class="med-title">Se connecter</h2>
        <p class="med-subtitle">
          Pas de compte ?
          <a href="medecin.php?action=inscription" class="link-blue">
            S'inscrire <i class="fa-solid fa-arrow-right fa-xs"></i>
          </a>
        </p>
      </div>

      <?php if (!empty($erreurs['global'])): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($erreurs['global']) ?>
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'succes'): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        Compte créé avec succès ! Connectez-vous.
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['deconnecte'])): ?>
      <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        Vous avez été déconnecté.
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['timeout'])): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-clock"></i>
        Votre session a expiré après 15 minutes d'inactivité. Veuillez vous reconnecter.
      </div>
      <?php endif; ?>

      <form method="POST" action="medecin.php?action=connexion"
            class="med-form" id="connexionForm" novalidate>

        <div class="field">
          <label class="field-label" for="email">
            <i class="fa-solid fa-envelope fa-xs"></i> Adresse email
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="email" name="email" class="field-input"
              placeholder="medecin@hopital.cm"
              value="<?= htmlspecialchars($ancien_email ?? '') ?>"
              autocomplete="email" required autofocus>
          </div>
        </div>

        <div class="field">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="field-label" for="password" style="margin-bottom:0">
              <i class="fa-solid fa-lock fa-xs"></i> Mot de passe
            </label>
            <a href="#" class="link-blue" style="font-size:.8rem;">
              <i class="fa-solid fa-rotate-left fa-xs"></i> Oublié ?
            </a>
          </div>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" name="password" class="field-input"
              placeholder="Votre mot de passe"
              autocomplete="current-password" required>
            <button type="button" class="toggle-pw" id="togglePw">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
          <input type="checkbox" id="remember" name="remember"
                 style="accent-color:var(--blue);width:16px;height:16px;cursor:pointer;">
          <label for="remember" style="font-size:.875rem;color:var(--text-muted);cursor:pointer;">
            Se souvenir de moi
          </label>
        </div>

        <button type="submit" class="btn btn-blue btn-full btn-lg" id="submitBtn">
          <i class="fa-solid fa-right-to-bracket"></i> Se connecter
        </button>
      </form>

      <div class="med-divider">ou</div>

      <div style="text-align:center;padding:14px;background:var(--blue-pale);border-radius:var(--radius-sm);border:1px solid var(--blue-light);">
        <i class="fa-solid fa-circle-info" style="color:var(--blue);"></i>
        <span style="font-size:.82rem;color:var(--text-muted);margin-left:6px;">
          Accès réservé aux médecins enregistrés
        </span>
      </div>

      <p style="text-align:center;margin-top:18px;font-size:.82rem;color:var(--text-muted);">
        <a href="accueil.php" class="link-blue">
          <i class="fa-solid fa-arrow-left fa-xs"></i> Retour à l'accueil
        </a>
      </p>

    </div>
  </main>
</div>

<script>
const pwInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');
document.getElementById('togglePw').addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  eyeIcon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});
document.getElementById('connexionForm').addEventListener('submit', () => {
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connexion…';
  btn.disabled  = true;
});
</script>
</body>
</html>

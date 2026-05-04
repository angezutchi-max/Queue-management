<?php // views/gestionnaire/connexion.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — QueueCare</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/auth.css">
  <script src="../../public/js/auto-dismiss.js" defer></script>
</head>
<body class="auth-body">
<div class="auth-wrapper">
  <aside class="auth-brand">
    <div class="auth-brand-inner">
      <div class="auth-logo anim-fade-up">
        <span class="auth-logo-icon"><i class="fa-solid fa-list-check"></i></span>
        <span class="auth-logo-name">QueueCare</span>
      </div>
      <div class="auth-brand-body anim-fade-up-1">
        <h1 class="auth-brand-title">Bon retour<br><em>parmi nous.</em></h1>
        <p class="auth-brand-sub">Connectez-vous pour accéder à votre tableau de bord et gérer vos consultations.</p>
      </div>
      <ul class="auth-features anim-fade-up-2">
        <li><i class="fa-solid fa-circle-check"></i> Suivi en temps réel de la file</li>
        <li><i class="fa-solid fa-circle-check"></i> Appel et gestion des patients</li>
        <li><i class="fa-solid fa-circle-check"></i> Déclaration des urgences</li>
        <li><i class="fa-solid fa-circle-check"></i> Rapports &amp; statistiques du jour</li>
      </ul>
      <div class="auth-brand-decoration">
        <div class="deco-ring deco-ring-1"></div>
        <div class="deco-ring deco-ring-2"></div>
        <div class="deco-dot deco-dot-1"></div>
        <div class="deco-dot deco-dot-2"></div>
      </div>
    </div>
  </aside>

  <main class="auth-form-panel">
    <div class="auth-form-inner anim-fade-up">
      <div class="auth-form-header">
        <a href="accueil.php" style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-muted);margin-bottom:14px;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--text-muted)'">
          <i class="fa-solid fa-arrow-left fa-xs"></i> Retour à l'accueil
        </a><br>
        <span class="badge badge-green"><i class="fa-solid fa-shield-halved"></i>&nbsp; Accès sécurisé</span>
        <h2 class="auth-title">Se connecter</h2>
        <p class="auth-subtitle">Pas de compte ? <a href="gestionnaire.php?action=inscription" class="link-green">S'inscrire <i class="fa-solid fa-arrow-right fa-xs"></i></a></p>
      </div>

      <?php if (!empty($erreurs['global'])): ?>
      <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erreurs['global']) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'succes'): ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Compte créé avec succès ! Connectez-vous.</div>
      <?php endif; ?>
      <?php if (isset($_GET['deconnecte'])): ?>
      <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> Vous avez été déconnecté.</div>
      <?php endif; ?>
      <?php if (isset($_GET['timeout'])): ?>
      <div class="alert alert-error"><i class="fa-solid fa-clock"></i> Session expirée après 15 minutes d'inactivité. Veuillez vous reconnecter.</div>
      <?php endif; ?>

      <form method="POST" action="gestionnaire.php?action=connexion" class="auth-form" id="connexionForm" novalidate>
        <div class="field">
          <label class="field-label" for="email"><i class="fa-solid fa-envelope fa-xs"></i> Adresse email</label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="email" name="email" class="field-input"
              placeholder="exemple@hopital.cm"
              value="<?= htmlspecialchars($ancien_email ?? '') ?>"
              autocomplete="email" required autofocus>
          </div>
        </div>

        <div class="field">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="field-label" for="password" style="margin-bottom:0"><i class="fa-solid fa-lock fa-xs"></i> Mot de passe</label>
            <a href="#" class="link-green" style="font-size:.8rem;"><i class="fa-solid fa-rotate-left fa-xs"></i> Mot de passe oublié ?</a>
          </div>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" name="password" class="field-input"
              placeholder="Votre mot de passe" autocomplete="current-password" required>
            <button type="button" class="toggle-pw" id="togglePw" aria-label="Afficher">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
          <input type="checkbox" id="remember" name="remember" style="accent-color:var(--green);width:16px;height:16px;cursor:pointer;">
          <label for="remember" style="font-size:.875rem;color:var(--text-muted);cursor:pointer;">Se souvenir de moi</label>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          <i class="fa-solid fa-right-to-bracket"></i> Se connecter
        </button>
      </form>

      <div class="auth-divider">ou</div>
      <div style="text-align:center;padding:14px;background:var(--off-white);border-radius:var(--radius-sm);border:1px solid var(--border);">
        <i class="fa-solid fa-circle-info" style="color:var(--blue);"></i>
        <span style="font-size:.82rem;color:var(--text-muted);margin-left:6px;">Accès réservé aux gestionnaires autorisés</span>
      </div>

      <p style="text-align:center;margin-top:18px;font-size:.82rem;color:var(--text-muted);">
        <a href="accueil.php" class="link-green">
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
  btn.disabled = true;
});
</script>
</body>
</html>

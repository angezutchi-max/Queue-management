<?php // views/gestionnaire/inscription.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription Gestionnaire — QueueCare</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,400i,700i|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/auth.css">
  <script src="../../public/js/auto-dismiss.js" defer></script>
</head>
<body class="auth-body">
<div class="auth-wrapper">

  <!-- ── Panneau gauche vert ── -->
  <aside class="auth-brand">
    <div class="auth-brand-inner">
      <div class="auth-logo anim-fade-up">
        <span class="auth-logo-icon"><i class="fa-solid fa-list-check"></i></span>
        <span class="auth-logo-name">QueueCare</span>
      </div>
      <div class="auth-brand-body anim-fade-up-1">
        <h1 class="auth-brand-title">Gérez les files d'attente,<br><em>intelligemment!</em></h1>
        <p class="auth-brand-sub">Plateforme intelligente de gestion des consultations et des files d'attente hospitalières.</p>
      </div>
      <ul class="auth-features anim-fade-up-2">
        <li><i class="fa-solid fa-circle-check"></i> Prendre rendez-vous en ligne &amp; sur place (QR)</li>
        <li><i class="fa-solid fa-circle-check"></i> Estimation dynamique du temps d'attente</li>
        <li><i class="fa-solid fa-circle-check"></i> Notifications push en temps réel</li>
        <li><i class="fa-solid fa-circle-check"></i> Rapports &amp; statistiques d'activité</li>
      </ul>
      <div class="auth-brand-decoration">
        <div class="deco-ring deco-ring-1"></div>
        <div class="deco-ring deco-ring-2"></div>
        <div class="deco-dot deco-dot-1"></div>
        <div class="deco-dot deco-dot-2"></div>
      </div>
    </div>
  </aside>

  <!-- ── Panneau droit : formulaire ── -->
  <main class="auth-form-panel">
    <div class="auth-form-inner anim-fade-up">

      <div class="auth-form-header">
        <a href="accueil.php" style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-muted);margin-bottom:14px;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--text-muted)'">
          <i class="fa-solid fa-arrow-left fa-xs"></i> Retour à l'accueil
        </a><br>
        <span class="badge badge-green">
          <i class="fa-solid fa-user-tie"></i>&nbsp; Espace Gestionnaire
        </span>
        <h2 class="auth-title">Créer un compte</h2>
        <p class="auth-subtitle">
          Déjà inscrit ?
          <a href="gestionnaire.php?action=connexion" class="link-green">
            Se connecter <i class="fa-solid fa-arrow-right fa-xs"></i>
          </a>
        </p>
      </div>

      <?php if (!empty($erreurs['global'])): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($erreurs['global']) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="gestionnaire.php?action=inscription"
            class="auth-form" id="inscriptionForm" novalidate>

        <!-- Nom complet -->
        <div class="field <?= isset($erreurs['nom']) ? 'field--error' : '' ?>">
          <label class="field-label" for="nom">
            <i class="fa-solid fa-user fa-xs"></i> Nom complet
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-user"></i></span>
            <input type="text" id="nom" name="nom" class="field-input"
              placeholder="ex : Karim Ben Ali"
              value="<?= htmlspecialchars($anciens['nom'] ?? '') ?>"
              autocomplete="name" required>
          </div>
          <?php if (isset($erreurs['nom'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['nom']) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Téléphone -->
        <div class="field <?= isset($erreurs['telephone']) ? 'field--error' : '' ?>">
          <label class="field-label" for="telephone">
            <i class="fa-solid fa-phone fa-xs"></i> Numéro de téléphone
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-phone"></i></span>
            <input type="tel" id="telephone" name="telephone" class="field-input"
              placeholder="ex : 699 123 456"
              value="<?= htmlspecialchars($anciens['telephone'] ?? '') ?>"
              autocomplete="tel" required>
          </div>
          <?php if (isset($erreurs['telephone'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['telephone']) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="field <?= isset($erreurs['email']) ? 'field--error' : '' ?>">
          <label class="field-label" for="email">
            <i class="fa-solid fa-envelope fa-xs"></i> Adresse email
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="email" name="email" class="field-input"
              placeholder="exemple@hopital.cm"
              value="<?= htmlspecialchars($anciens['email'] ?? '') ?>"
              autocomplete="email" required>
          </div>
          <?php if (isset($erreurs['email'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['email']) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Sous-service (remplace rôle — créés par le médecin chef) -->
        <div class="field <?= isset($erreurs['sous_service_id']) ? 'field--error' : '' ?>">
          <label class="field-label" for="sous_service_id">
            <i class="fa-solid fa-sitemap fa-xs"></i> Sous-service affecté
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-sitemap"></i></span>
            <select id="sous_service_id" name="sous_service_id"
                    class="field-input field-select" required>
              <option value="" disabled
                <?= empty($anciens['sous_service_id']) ? 'selected' : '' ?>>
                Sélectionner un sous-service
              </option>
              <?php foreach ($sousServices as $ss): ?>
              <option value="<?= (int)$ss['id'] ?>"
                <?= (int)($anciens['sous_service_id'] ?? 0) === (int)$ss['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ss['service_nom']) ?> —
                <?= htmlspecialchars($ss['nom']) ?>
              </option>
              <?php endforeach; ?>
              <?php if (empty($sousServices)): ?>
              <option value="" disabled>Aucun sous-service disponible</option>
              <?php endif; ?>
            </select>
            <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
          </div>
          <?php if (isset($erreurs['sous_service_id'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['sous_service_id']) ?>
          </span>
          <?php endif; ?>
          <small style="color:var(--text-light);font-size:.73rem;margin-top:2px;">
            <i class="fa-solid fa-circle-info fa-xs"></i>
            Les sous-services sont créés par le médecin chef de service.
          </small>
        </div>

        <!-- Mot de passe -->
        <div class="field <?= isset($erreurs['password']) ? 'field--error' : '' ?>">
          <label class="field-label" for="password">
            <i class="fa-solid fa-lock fa-xs"></i> Mot de passe
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" name="password" class="field-input"
              placeholder="Min. 8 car., 1 maj., 1 chiffre"
              autocomplete="new-password" required>
            <button type="button" class="toggle-pw" id="togglePw"
                    aria-label="Afficher le mot de passe">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
          <div class="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
            <span class="pw-label" id="pwLabel"></span>
          </div>
          <?php if (isset($erreurs['password'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['password']) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Confirmation -->
        <div class="field <?= isset($erreurs['confirm']) ? 'field--error' : '' ?>">
          <label class="field-label" for="confirm">
            <i class="fa-solid fa-shield-halved fa-xs"></i> Confirmer le mot de passe
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <input type="password" id="confirm" name="confirm" class="field-input"
              placeholder="Répéter le mot de passe"
              autocomplete="new-password" required>
          </div>
          <?php if (isset($erreurs['confirm'])): ?>
          <span class="field-error">
            <i class="fa-solid fa-circle-exclamation fa-xs"></i>
            <?= htmlspecialchars($erreurs['confirm']) ?>
          </span>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          <i class="fa-solid fa-user-plus"></i> Créer mon compte
        </button>

      </form>

      <p class="auth-legal">
        En vous inscrivant, vous acceptez les
        <a href="#">conditions d'utilisation</a> et la
        <a href="#">politique de confidentialité</a>.
      </p>

    </div>
  </main>
</div>

<script>
// Toggle affichage mot de passe
const pwInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');
document.getElementById('togglePw').addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  eyeIcon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// Téléphone : chiffres et + uniquement
const telInput = document.getElementById('telephone');
if (telInput) {
  telInput.addEventListener('keypress', (e) => {
    if (!/[0-9+]/.test(e.key)) e.preventDefault();
  });
  telInput.addEventListener('input', () => {
    telInput.value = telInput.value.replace(/[^0-9+]/g, '');
  });
}

// Indicateur de force du mot de passe
pwInput.addEventListener('input', () => {
  const v = pwInput.value;
  let score = 0;
  if (v.length >= 8)           score++;
  if (/[A-Z]/.test(v))         score++;
  if (/[0-9]/.test(v))         score++;
  if (/[^A-Za-z0-9]/.test(v))  score++;
  const lvl = [
    { w:'0%',   c:'',         t:'' },
    { w:'25%',  c:'#ef4444', t:'Très faible' },
    { w:'50%',  c:'#f59e0b', t:'Faible' },
    { w:'75%',  c:'#2563eb', t:'Moyen' },
    { w:'100%', c:'#22a863', t:'Fort' },
  ];
  document.getElementById('pwFill').style.width           = lvl[score].w;
  document.getElementById('pwFill').style.backgroundColor = lvl[score].c;
  document.getElementById('pwLabel').textContent          = lvl[score].t;
  document.getElementById('pwLabel').style.color          = lvl[score].c;
});

// Vérification côté client que les mots de passe correspondent
document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
  const pw  = document.getElementById('password').value;
  const cfm = document.getElementById('confirm').value;
  if (pw !== cfm) {
    e.preventDefault();
    const el = document.getElementById('confirm');
    el.style.borderColor = '#ef4444';
    el.focus();
    // Afficher un message d'erreur inline si absent
    let msg = document.getElementById('confirmErrMsg');
    if (!msg) {
      msg = document.createElement('span');
      msg.id = 'confirmErrMsg';
      msg.style.cssText = 'color:#ef4444;font-size:.75rem;margin-top:3px;display:block;';
      el.parentElement.parentElement.appendChild(msg);
    }
    msg.textContent = 'Les mots de passe ne correspondent pas.';
    return;
  }
  // Soumission valide : indiquer le chargement après un léger délai
  const btn = document.getElementById('submitBtn');
  setTimeout(() => {
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Création en cours…';
    btn.disabled  = true;
  }, 50);
});
</script>
</body>
</html>

<?php // views/medecin/inscription.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription Médecin — QueueCare</title>
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
        <h1 class="med-brand-title">L'expertise<br><em>au service des patients.</em></h1>
        <p class="med-brand-sub">Espace dédié aux médecins pour gérer les consultations, les plannings et les sous-services.</p>
      </div>
      <ul class="med-features anim-fade-up-2">
        <li><i class="fa-solid fa-circle-check"></i> Créer et gérer les sous-services (chef)</li>
        <li><i class="fa-solid fa-circle-check"></i> Suivi des consultations du jour</li>
        <li><i class="fa-solid fa-circle-check"></i> Planning hebdomadaire personnalisé</li>
        <li><i class="fa-solid fa-circle-check"></i> Estimation dynamique du temps d'attente</li>
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
        <span class="badge badge-blue"><i class="fa-solid fa-user-doctor"></i>&nbsp; Espace Médecin</span>
        <h2 class="med-title">Créer un compte</h2>
        <p class="med-subtitle">
          Déjà inscrit ?
          <a href="medecin.php?action=connexion" class="link-blue">
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

      <form method="POST" action="medecin.php?action=inscription"
            class="med-form" id="medecinForm" novalidate>

        <!-- Nom / Prénom -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="field <?= isset($erreurs['nom']) ? 'field--error' : '' ?>">
            <label class="field-label" for="nom"><i class="fa-solid fa-user fa-xs"></i> Nom</label>
            <div class="field-wrap">
              <span class="field-icon"><i class="fa-solid fa-user"></i></span>
              <input type="text" id="nom" name="nom" class="field-input"
                placeholder="Dupont" value="<?= htmlspecialchars($anciens['nom'] ?? '') ?>" required>
            </div>
            <?php if (isset($erreurs['nom'])): ?>
            <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['nom']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field <?= isset($erreurs['prenom']) ? 'field--error' : '' ?>">
            <label class="field-label" for="prenom"><i class="fa-solid fa-user fa-xs"></i> Prénom</label>
            <div class="field-wrap">
              <span class="field-icon"><i class="fa-solid fa-user"></i></span>
              <input type="text" id="prenom" name="prenom" class="field-input"
                placeholder="Jean" value="<?= htmlspecialchars($anciens['prenom'] ?? '') ?>" required>
            </div>
            <?php if (isset($erreurs['prenom'])): ?>
            <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['prenom']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Téléphone -->
        <div class="field <?= isset($erreurs['telephone']) ? 'field--error' : '' ?>">
          <label class="field-label" for="telephone"><i class="fa-solid fa-phone fa-xs"></i> Téléphone</label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-phone"></i></span>
            <input type="tel" id="telephone" name="telephone" class="field-input"
              placeholder="+237699123456"
              value="<?= htmlspecialchars($anciens['telephone'] ?? '') ?>"
              pattern="[0-9+]+" inputmode="tel" required>
          </div>
          <small style="color:var(--text-light);font-size:.72rem;margin-top:2px;">
            <i class="fa-solid fa-circle-info fa-xs"></i> Chiffres et le signe + uniquement
          </small>
          <?php if (isset($erreurs['telephone'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['telephone']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="field <?= isset($erreurs['email']) ? 'field--error' : '' ?>">
          <label class="field-label" for="email"><i class="fa-solid fa-envelope fa-xs"></i> Email</label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="email" name="email" class="field-input"
              placeholder="medecin@hopital.cm"
              value="<?= htmlspecialchars($anciens['email'] ?? '') ?>" required>
          </div>
          <?php if (isset($erreurs['email'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['email']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Type de compte -->
        <div class="field">
          <label class="field-label"><i class="fa-solid fa-id-badge fa-xs"></i> Type de compte</label>
          <?php if (isset($erreurs['type_compte'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['type_compte']) ?></span>
          <?php endif; ?>
          <div class="type-toggle">
            <div>
              <input type="radio" id="type_chef" name="type_compte" value="chef" class="type-option"
                     <?= ($anciens['typeCompte'] ?? '') === 'chef' ? 'checked' : '' ?>>
              <label for="type_chef" class="type-label">
                <i class="fa-solid fa-crown"></i>
                <span>Chef de sous-service<br><small style="font-weight:400;font-size:.72rem;">Crée son propre sous-service</small></span>
              </label>
            </div>
            <div>
              <input type="radio" id="type_ordinaire" name="type_compte" value="ordinaire" class="type-option"
                     <?= ($anciens['typeCompte'] ?? '') === 'ordinaire' ? 'checked' : '' ?>>
              <label for="type_ordinaire" class="type-label">
                <i class="fa-solid fa-user-doctor"></i>
                <span>Médecin ordinaire<br><small style="font-weight:400;font-size:.72rem;">Rejoint un sous-service existant</small></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Hôpital / Service  — VISIBLE pour les DEUX types -->
        <div class="field <?= isset($erreurs['service_id']) ? 'field--error' : '' ?>" id="fieldService" style="display:none;">
          <label class="field-label" for="service_id">
            <i class="fa-solid fa-hospital fa-xs"></i> Hôpital / Service
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-hospital"></i></span>
            <select id="service_id" name="service_id" class="field-input field-select">
              <option value="" disabled selected>Sélectionner l'hôpital</option>
              <?php foreach ($services as $s): ?>
              <option value="<?= (int)$s['id'] ?>"
                <?= (int)($anciens['serviceId'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nom']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
          </div>
          <?php if (isset($erreurs['service_id'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['service_id']) ?></span>
          <?php endif; ?>
          <small style="color:var(--text-light);font-size:.72rem;margin-top:2px;" id="hintChef">
            <i class="fa-solid fa-circle-info fa-xs"></i>
            Un seul médecin chef par hôpital. Vous créerez votre sous-service après connexion.
          </small>
          <small style="color:var(--text-light);font-size:.72rem;margin-top:2px;display:none;" id="hintOrdinaire">
            <i class="fa-solid fa-circle-info fa-xs"></i>
            Choisissez d'abord l'hôpital pour voir les sous-services disponibles.
          </small>
        </div>

        <!-- Spécialité / Sous-service — UNIQUEMENT pour médecin ordinaire -->
        <div class="field <?= isset($erreurs['specialite']) ? 'field--error' : '' ?>" id="fieldSpecialite" style="display:none;">
          <label class="field-label" for="specialite">
            <i class="fa-solid fa-stethoscope fa-xs"></i> Spécialité / Sous-service
          </label>
          <div class="field-wrap" style="position:relative;">
            <span class="field-icon"><i class="fa-solid fa-stethoscope"></i></span>
            <select id="specialite" name="specialite" class="field-input field-select" disabled>
              <option value="" disabled selected>— Choisissez d'abord l'hôpital —</option>
            </select>
            <span class="select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
            <span id="ssLoading" style="display:none;position:absolute;right:36px;top:50%;transform:translateY(-50%);">
              <i class="fa-solid fa-spinner fa-spin" style="color:#1a4db5;font-size:.85rem;"></i>
            </span>
          </div>
          <small style="color:var(--text-light);font-size:.72rem;margin-top:2px;" id="specialiteHint">
            <i class="fa-solid fa-circle-info fa-xs"></i>
            Sélectionnez un sous-service de l'hôpital choisi comme spécialité.
          </small>
          <?php if (isset($erreurs['specialite'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['specialite']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Spécialité libre — UNIQUEMENT pour médecin chef -->
        <div class="field <?= isset($erreurs['specialite']) ? 'field--error' : '' ?>" id="fieldSpecialiteChef" style="display:none;">
          <label class="field-label" for="specialite_chef">
            <i class="fa-solid fa-stethoscope fa-xs"></i> Spécialité
          </label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-stethoscope"></i></span>
            <input type="text" id="specialite_chef" name="specialite_chef" class="field-input"
              placeholder="ex : Cardiologie, Oncologie, Pédiatrie…"
              value="<?= htmlspecialchars($anciens['specialiteChef'] ?? '') ?>">
          </div>
        </div>

        <!-- Mot de passe -->
        <div class="field <?= isset($erreurs['password']) ? 'field--error' : '' ?>">
          <label class="field-label" for="password"><i class="fa-solid fa-lock fa-xs"></i> Mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" name="password" class="field-input"
              placeholder="Min. 8 car., 1 maj., 1 chiffre"
              autocomplete="new-password" required>
            <button type="button" class="toggle-pw" id="togglePw">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
          <div class="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
            <span class="pw-label" id="pwLabel"></span>
          </div>
          <?php if (isset($erreurs['password'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['password']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Confirmation -->
        <div class="field <?= isset($erreurs['confirm']) ? 'field--error' : '' ?>">
          <label class="field-label" for="confirm"><i class="fa-solid fa-shield-halved fa-xs"></i> Confirmer le mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <input type="password" id="confirm" name="confirm" class="field-input"
              placeholder="Répéter le mot de passe" autocomplete="new-password" required>
          </div>
          <?php if (isset($erreurs['confirm'])): ?>
          <span class="field-error"><i class="fa-solid fa-circle-exclamation fa-xs"></i> <?= htmlspecialchars($erreurs['confirm']) ?></span>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-blue btn-full btn-lg" id="submitBtn">
          <i class="fa-solid fa-user-plus"></i> Créer mon compte
        </button>

      </form>

      <p class="med-legal">
        En vous inscrivant, vous acceptez les <a href="#">conditions d'utilisation</a>
        et la <a href="#">politique de confidentialité</a>.
      </p>

    </div>
  </main>
</div>

<script>
// ── Données sous-services (chargées depuis le serveur via AJAX) ──
const API_SS_URL = 'medecin.php?action=api_sous_services&service_id=';

const radios           = document.querySelectorAll('input[name="type_compte"]');
const fieldService     = document.getElementById('fieldService');
const fieldSpecOrdi    = document.getElementById('fieldSpecialite');
const fieldSpecChef    = document.getElementById('fieldSpecialiteChef');
const selService       = document.getElementById('service_id');
const selSousService   = document.getElementById('specialite');
const hintChef         = document.getElementById('hintChef');
const hintOrdinaire    = document.getElementById('hintOrdinaire');
const ssLoading        = document.getElementById('ssLoading');

function getType() {
  return document.querySelector('input[name="type_compte"]:checked')?.value || '';
}

function updateFields() {
  const type = getType();

  if (!type) {
    // Rien sélectionné : tout masqué
    fieldService.style.display  = 'none';
    fieldSpecOrdi.style.display = 'none';
    fieldSpecChef.style.display = 'none';
    return;
  }

  // Hôpital visible pour les deux types
  fieldService.style.display = 'block';

  if (type === 'chef') {
    hintChef.style.display      = '';
    hintOrdinaire.style.display = 'none';
    fieldSpecOrdi.style.display = 'none';
    fieldSpecChef.style.display = 'block';
    selService.required         = true;
    selSousService.required     = false;
    selSousService.disabled     = true;
  } else {
    // ordinaire
    hintChef.style.display      = 'none';
    hintOrdinaire.style.display = '';
    fieldSpecOrdi.style.display = 'block';
    fieldSpecChef.style.display = 'none';
    selService.required         = true;
    selSousService.required     = true;
    // Charger les sous-services si un service est déjà choisi
    if (selService.value) loadSousServices(selService.value);
  }
}

// ── Chargement AJAX des sous-services de l'hôpital choisi ──
async function loadSousServices(serviceId) {
  if (!serviceId) {
    resetSousServiceSelect('— Choisissez d\'abord l\'hôpital —');
    return;
  }

  ssLoading.style.display = '';
  selSousService.disabled = true;

  try {
    const resp = await fetch(API_SS_URL + encodeURIComponent(serviceId));
    const data = await resp.json();

    selSousService.innerHTML = '';
    if (!data.length) {
      selSousService.innerHTML = '<option value="" disabled selected>Aucun sous-service disponible</option>';
    } else {
      const def = document.createElement('option');
      def.value = ''; def.disabled = true; def.selected = true;
      def.textContent = 'Sélectionner un sous-service';
      selSousService.appendChild(def);
      data.forEach(ss => {
        const opt = document.createElement('option');
        opt.value       = ss.nom;   // on stocke le nom comme spécialité
        opt.dataset.id  = ss.id;
        opt.textContent = ss.nom + (ss.capacite_horaire ? ' (' + ss.capacite_horaire + '/h)' : '');
        // Rétablir la sélection après erreur de validation
        const ancienVal = <?= json_encode($anciens['specialite'] ?? '') ?>;
        if (ancienVal && opt.value === ancienVal) opt.selected = true;
        selSousService.appendChild(opt);
      });
    }
    selSousService.disabled = false;
  } catch (e) {
    resetSousServiceSelect('Erreur de chargement — réessayez');
  } finally {
    ssLoading.style.display = 'none';
  }
}

function resetSousServiceSelect(msg) {
  selSousService.innerHTML = `<option value="" disabled selected>${msg}</option>`;
  selSousService.disabled  = true;
}

// Écoutes
radios.forEach(r => r.addEventListener('change', updateFields));

selService.addEventListener('change', function() {
  if (getType() === 'ordinaire') {
    loadSousServices(this.value);
  }
});

// Init
updateFields();
// Si un hôpital était déjà sélectionné (retour après erreur)
<?php if (!empty($anciens['serviceId']) && ($anciens['typeCompte'] ?? '') === 'ordinaire'): ?>
loadSousServices(<?= (int)$anciens['serviceId'] ?>);
<?php endif; ?>

// ── Téléphone : chiffres et + uniquement ──
document.getElementById('telephone').addEventListener('keypress', function(e) {
  if (!/[0-9+]/.test(e.key)) e.preventDefault();
});
document.getElementById('telephone').addEventListener('input', function() {
  this.value = this.value.replace(/[^0-9+]/g, '');
});

// ── Toggle mot de passe ──
const pwInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');
document.getElementById('togglePw').addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  eyeIcon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// ── Force mot de passe ──
pwInput.addEventListener('input', () => {
  const v = pwInput.value; let s = 0;
  if (v.length >= 8)           s++;
  if (/[A-Z]/.test(v))         s++;
  if (/[0-9]/.test(v))         s++;
  if (/[^A-Za-z0-9]/.test(v))  s++;
  const lvl = [
    {w:'0%',c:'',t:''}, {w:'25%',c:'#ef4444',t:'Très faible'},
    {w:'50%',c:'#f59e0b',t:'Faible'}, {w:'75%',c:'#2563eb',t:'Moyen'},
    {w:'100%',c:'#1a8a52',t:'Fort'},
  ];
  document.getElementById('pwFill').style.width           = lvl[s].w;
  document.getElementById('pwFill').style.backgroundColor = lvl[s].c;
  document.getElementById('pwLabel').textContent          = lvl[s].t;
  document.getElementById('pwLabel').style.color          = lvl[s].c;
});

// ── Chargement ──
document.getElementById('medecinForm').addEventListener('submit', function(e) {
  // Pour le médecin chef, copier la spécialité libre dans le select caché
  if (getType() === 'chef') {
    const chefVal = document.getElementById('specialite_chef').value.trim();
    // On force la valeur dans le select spécialité avant envoi
    selSousService.disabled = false;
    const opt = document.createElement('option');
    opt.value = chefVal; opt.selected = true;
    selSousService.innerHTML = '';
    selSousService.appendChild(opt);
  }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Création…';
  btn.disabled  = true;
});
</script>
</body>
</html>

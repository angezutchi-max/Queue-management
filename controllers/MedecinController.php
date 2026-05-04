<?php
/**
 * controllers/MedecinController.php
 * Contrôleur MVC — Médecin (Inscription, Connexion, Dashboard)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../models/MedecinModel.php';

class MedecinController
{
    private MedecinModel $model;

    public function __construct()
    {
        $this->model = new MedecinModel();
    }

    /* ══════════════════════════════════════════════════
       INSCRIPTION
    ══════════════════════════════════════════════════ */

    public function afficherInscription(): void
    {
        $erreurs     = [];
        $anciens     = [];
        $services    = $this->model->getServices();
        $sousServices= $this->model->getSousServices();
        require __DIR__ . '/../views/medecin/inscription.php';
    }

    public function traiterInscription(): void
    {
        $erreurs      = [];
        $services     = $this->model->getServices();
        $sousServices = $this->model->getSousServices();

        $nom           = trim($_POST['nom']             ?? '');
        $prenom        = trim($_POST['prenom']          ?? '');
        $telephone     = trim($_POST['telephone']       ?? '');
        $email         = trim($_POST['email']           ?? '');
        $password      = trim($_POST['password']        ?? '');
        $confirm       = trim($_POST['confirm']         ?? '');
        $typeCompte    = trim($_POST['type_compte']     ?? '');   // 'chef' ou 'ordinaire'
        $serviceId     = (int)($_POST['service_id']    ?? 0);

        // Spécialité : libre pour chef, nom du sous-service pour ordinaire
        if ($typeCompte === 'chef') {
            $specialite    = trim($_POST['specialite_chef'] ?? '');
            $sousServiceId = 0;
        } else {
            $specialite    = trim($_POST['specialite']      ?? '');  // nom du sous-service choisi
            $sousServiceId = 0; // on cherche l'ID depuis le nom + service_id
        }

        $anciens = [
            'nom'          => $nom,
            'prenom'       => $prenom,
            'specialite'   => $specialite,
            'specialiteChef'=> ($typeCompte === 'chef') ? $specialite : '',
            'telephone'    => $telephone,
            'email'        => $email,
            'typeCompte'   => $typeCompte,
            'serviceId'    => $serviceId,
            'sousServiceId'=> $sousServiceId,
        ];

        /* ── Validation ── */
        if (mb_strlen($nom) < 2)
            $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères.';

        if (mb_strlen($prenom) < 2)
            $erreurs['prenom'] = 'Le prénom doit contenir au moins 2 caractères.';

        if (empty($specialite))
            $erreurs['specialite'] = 'La spécialité est obligatoire.';

        if (!preg_match('/^[0-9+]{8,20}$/', $telephone))
            $erreurs['telephone'] = 'Numéro invalide (chiffres et + uniquement, 8-20 caractères).';
        elseif ($this->model->telephoneExiste($telephone))
            $erreurs['telephone'] = 'Ce numéro est déjà utilisé.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $erreurs['email'] = 'Adresse email invalide.';
        elseif ($this->model->emailExiste($email))
            $erreurs['email'] = 'Cette adresse email est déjà utilisée.';

        if (!in_array($typeCompte, ['chef','ordinaire']))
            $erreurs['type_compte'] = 'Veuillez choisir un type de compte.';

        if ($typeCompte === 'chef') {
            if ($serviceId === 0)
                $erreurs['service_id'] = 'Veuillez sélectionner un service (hôpital).';
            elseif ($this->model->chefExistePourService($serviceId))
                $erreurs['service_id'] = 'Un médecin chef existe déjà pour ce service.';
        } elseif ($typeCompte === 'ordinaire') {
            if ($serviceId === 0)
                $erreurs['service_id'] = 'Veuillez sélectionner un hôpital.';
            if (empty($specialite))
                $erreurs['specialite'] = 'Veuillez sélectionner un sous-service.';
            if ($serviceId > 0 && !empty($specialite)) {
                $sousServiceId = $this->model->trouverSousServiceParNom($specialite, $serviceId);
                if (!$sousServiceId)
                    $erreurs['specialite'] = 'Sous-service introuvable. Veuillez resélectionner.';
            }
        }

        if (strlen($password) < 8)
            $erreurs['password'] = 'Minimum 8 caractères.';
        elseif (!preg_match('/[A-Z]/', $password))
            $erreurs['password'] = 'Au moins une majuscule requise.';
        elseif (!preg_match('/[0-9]/', $password))
            $erreurs['password'] = 'Au moins un chiffre requis.';

        if ($password !== $confirm)
            $erreurs['confirm'] = 'Les mots de passe ne correspondent pas.';

        if (!empty($erreurs)) {
            require __DIR__ . '/../views/medecin/inscription.php';
            return;
        }

        /* ── Création du médecin ── */
        $ok = $this->model->creer([
            'nom'       => $nom,
            'prenom'    => $prenom,
            'specialite'=> $specialite,
            'telephone' => $telephone,
            'email'     => $email,
            'password'  => $password,
        ]);

        if (!$ok) {
            $erreurs['global'] = 'Erreur lors de la création du compte. Veuillez réessayer.';
            require __DIR__ . '/../views/medecin/inscription.php';
            return;
        }

        $medecinId = $this->model->dernierID();

        /* ── Affectation sous-service ── */
        if ($typeCompte === 'chef') {
            // Le chef n'est pas encore affecté à un sous-service — il le créera depuis son dashboard
            // On stocke le service_id en session temporairement
            $_SESSION['chef_service_id_pending'] = $serviceId;
        } else {
            // Médecin ordinaire : affectation immédiate
            $this->model->affecterSousService($medecinId, $sousServiceId, false);
        }

        header('Location: medecin.php?action=connexion&inscription=succes');
        exit;
    }

    /* ══════════════════════════════════════════════════
       CONNEXION
    ══════════════════════════════════════════════════ */

    public function afficherConnexion(): void
    {
        if (isset($_SESSION['medecin_id'])) {
            header('Location: medecin.php?action=dashboard');
            exit;
        }
        $erreurs      = [];
        $ancien_email = '';
        require __DIR__ . '/../views/medecin/connexion.php';
    }

    public function traiterConnexion(): void
    {
        $erreurs      = [];
        $ancien_email = trim($_POST['email']    ?? '');
        $password     = trim($_POST['password'] ?? '');

        if (empty($ancien_email) || empty($password)) {
            $erreurs['global'] = 'Veuillez remplir tous les champs.';
            require __DIR__ . '/../views/medecin/connexion.php';
            return;
        }

        $medecin = $this->model->trouverParEmail($ancien_email);

        if (!$medecin || !password_verify($password, $medecin['password'])) {
            $erreurs['global'] = 'Email ou mot de passe incorrect.';
            require __DIR__ . '/../views/medecin/connexion.php';
            return;
        }

        session_regenerate_id(true);
        $_SESSION['medecin_id']     = $medecin['id'];
        $_SESSION['medecin_nom']    = $medecin['nom'] . ' ' . $medecin['prenom'];
        $_SESSION['medecin_prenom'] = $medecin['prenom'];
        $_SESSION['last_activity']  = time();

        header('Location: medecin.php?action=dashboard');
        exit;
    }

    /* ══════════════════════════════════════════════════
       DASHBOARD
    ══════════════════════════════════════════════════ */

    public function afficherDashboard(): void
    {
        if (!isset($_SESSION['medecin_id'])) {
            header('Location: medecin.php?action=connexion');
            exit;
        }

        $medecinId  = (int)$_SESSION['medecin_id'];
        $medecinNom = $_SESSION['medecin_nom'];
        $medecin    = $this->model->trouverParId($medecinId);
        $affectation = $this->model->getSousServiceMedecin($medecinId);

        $messageAction = '';
        $erreurAction  = '';

        // Chef sans sous-service encore créé
        $estChef            = false;
        $ssId               = 0;
        $stats              = [];
        $consultations      = [];
        $planning           = [];
        $sousServicesChef   = [];

        if ($affectation) {
            $estChef = (bool)$affectation['est_chef'];
            $ssId    = (int)$affectation['ss_id'];
        }

        // Récupération des données selon le rôle
        if ($ssId > 0) {
            $stats         = $this->model->statsJour($ssId);
            $consultations = $this->model->consultationsDuJour($ssId, $medecinId);
        }

        $planning = $this->model->planningMedecin($medecinId);

        if ($estChef && $affectation) {
            $sousServicesChef = $this->model->getSousServicesDeService((int)$affectation['service_id']);
        }

        /* ── Actions POST ── */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            // Chef : créer un sous-service
            if ($action === 'creer_sous_service' && $estChef) {
                $nomSS    = trim($_POST['ss_nom']         ?? '');
                $descSS   = trim($_POST['ss_description'] ?? '');
                $dureeSS  = (int)($_POST['ss_duree']      ?? 1800);
                $capaSS   = (int)($_POST['ss_capacite']   ?? 10);

                if (empty($nomSS)) {
                    $erreurAction = 'Le nom du sous-service est obligatoire.';
                } else {
                    $ok = $this->model->creerSousService([
                        'service_id'     => $affectation['service_id'],
                        'nom'            => $nomSS,
                        'description'    => $descSS,
                        'duree_rdv_defaut'=> $dureeSS,
                        'capacite_horaire'=> $capaSS,
                    ]);
                    if ($ok) {
                        $newSsId = $this->model->dernierID();
                        // Affecter le chef à ce sous-service
                        $this->model->affecterSousService($medecinId, $newSsId, true);
                        $messageAction = "Sous-service « {$nomSS} » créé avec succès.";
                        // Mettre à jour les données
                        $affectation      = $this->model->getSousServiceMedecin($medecinId);
                        $ssId             = (int)($affectation['ss_id'] ?? 0);
                        $sousServicesChef = $this->model->getSousServicesDeService((int)$affectation['service_id']);
                    } else {
                        $erreurAction = 'Erreur lors de la création du sous-service.';
                    }
                }
            }

            // Modifier statut consultation
            if ($action === 'maj_statut') {
                $cId    = (int)($_POST['consultation_id'] ?? 0);
                $statut = $_POST['statut'] ?? '';
                if ($cId > 0 && in_array($statut, ['traite','annule','absent'], true)) {
                    $this->model->majStatutConsultation($cId, $statut);
                    $messageAction = 'Statut de la consultation mis à jour.';
                }
            }

            // Rafraîchir
            if ($ssId > 0) {
                $stats         = $this->model->statsJour($ssId);
                $consultations = $this->model->consultationsDuJour($ssId, $medecinId);
            }
            $planning = $this->model->planningMedecin($medecinId);
        }

        require __DIR__ . '/../views/medecin/dashboard.php';
    }

    /* ══════════════════════════════════════════════════
       DÉCONNEXION
    ══════════════════════════════════════════════════ */

    public function deconnecter(): void
    {
        unset($_SESSION['medecin_id'], $_SESSION['medecin_nom'],
              $_SESSION['medecin_prenom'], $_SESSION['chef_service_id_pending']);
        session_destroy();
        header('Location: medecin.php?action=connexion&deconnecte=1');
        exit;
    }

    /* ══════════════════════════════════════════════════
       API JSON — Sous-services d'un service (pour inscription)
    ══════════════════════════════════════════════════ */

    public function apiSousServices(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $serviceId = (int)($_GET['service_id'] ?? 0);
        if ($serviceId <= 0) {
            echo json_encode([]);
            exit;
        }
        $liste = $this->model->getSousServicesActifsParService($serviceId);
        echo json_encode($liste);
        exit;
    }
}

/* ── Dispatch ─────────────────────────────────────────────── */
$action = $_GET['action'] ?? 'connexion';
$ctrl   = new MedecinController();

match ($action) {
    'inscription'        => $_SERVER['REQUEST_METHOD'] === 'POST'
                               ? $ctrl->traiterInscription()
                               : $ctrl->afficherInscription(),
    'connexion'          => $_SERVER['REQUEST_METHOD'] === 'POST'
                               ? $ctrl->traiterConnexion()
                               : $ctrl->afficherConnexion(),
    'dashboard'          => $ctrl->afficherDashboard(),
    'deconnexion'        => $ctrl->deconnecter(),
    'api_sous_services'  => $ctrl->apiSousServices(),
    default              => $ctrl->afficherConnexion(),
};

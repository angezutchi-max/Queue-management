<?php

require_once __DIR__ . '/../models/GestionnaireModel.php';

class GestionnaireController
{
    private GestionnaireModel $model;

    public function __construct()
    {
        $this->model = new GestionnaireModel();
    }

    /* ══════ INSCRIPTION ══════ */

    public function afficherInscription(): void
    {
        $erreurs      = [];
        $anciens      = [];
        $sousServices = $this->model->getSousServices();
        require __DIR__ . '/../views/gestionnaire/inscription.php';
    }

    public function traiterInscription(): void
    {
        $erreurs      = [];
        $sousServices = $this->model->getSousServices();

        $nom           = trim($_POST['nom']               ?? '');
        $telephone     = trim($_POST['telephone']         ?? '');
        $email         = trim($_POST['email']             ?? '');
        $password      = $_POST['password']               ?? '';
        $confirm       = $_POST['confirm']                ?? '';
        $sousServiceId = (int)($_POST['sous_service_id']  ?? 0);

        $anciens = [
            'nom'             => $nom,
            'telephone'       => $telephone,
            'email'           => $email,
            'sous_service_id' => $sousServiceId,
        ];

        if (mb_strlen($nom) < 2)
            $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères.';

        if (!preg_match('/^\+?[0-9]{8,19}$/', $telephone))
            $erreurs['telephone'] = 'Numéro invalide (chiffres et + uniquement).';
        elseif ($this->model->telephoneExiste($telephone))
            $erreurs['telephone'] = 'Ce numéro est déjà utilisé.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $erreurs['email'] = 'Adresse email invalide.';
        elseif ($this->model->emailExiste($email))
            $erreurs['email'] = 'Cette adresse email est déjà utilisée.';

        if ($sousServiceId === 0)
            $erreurs['sous_service_id'] = 'Veuillez sélectionner un sous-service.';

        if (strlen($password) < 8)
            $erreurs['password'] = 'Minimum 8 caractères.';
        elseif (!preg_match('/[A-Z]/', $password))
            $erreurs['password'] = 'Au moins une majuscule requise.';
        elseif (!preg_match('/[0-9]/', $password))
            $erreurs['password'] = 'Au moins un chiffre requis.';

        if ($password !== $confirm)
            $erreurs['confirm'] = 'Les mots de passe ne correspondent pas.';

        if (!empty($erreurs)) {
            require __DIR__ . '/../views/gestionnaire/inscription.php';
            return;
        }

        $ok = $this->model->creer([
            'nom'             => $nom,
            'telephone'       => $telephone,
            'email'           => $email,
            'password'        => $password,
            'sous_service_id' => $sousServiceId,
        ]);

        if ($ok) {
            header('Location: gestionnaire.php?action=connexion&inscription=succes');
            exit;
        }

        $erreurs['global'] = 'Une erreur est survenue. Veuillez réessayer.';
        require __DIR__ . '/../views/gestionnaire/inscription.php';
    }

    /* ══════ CONNEXION ══════ */

    public function afficherConnexion(): void
    {
        $erreurs      = [];
        $ancien_email = '';
        require __DIR__ . '/../views/gestionnaire/connexion.php';
    }

    public function traiterConnexion(): void
    {
        $erreurs      = [];
        $ancien_email = trim($_POST['email']    ?? '');
        $password     = $_POST['password']      ?? '';

        if (empty($ancien_email) || empty($password)) {
            $erreurs['global'] = 'Veuillez remplir tous les champs.';
            require __DIR__ . '/../views/gestionnaire/connexion.php';
            return;
        }

        $gestionnaire = $this->model->trouverParEmail($ancien_email);

        if (!$gestionnaire || !password_verify($password, $gestionnaire['password'])) {
            $erreurs['global'] = 'Email ou mot de passe incorrect.';
            require __DIR__ . '/../views/gestionnaire/connexion.php';
            return;
        }

        session_regenerate_id(true);
        $_SESSION['gestionnaire_id']  = $gestionnaire['id'];
        $_SESSION['gestionnaire_nom'] = $gestionnaire['nom'];
        $_SESSION['last_activity']    = time();

        header('Location: gestionnaire.php?action=dashboard');
        exit;
    }

    /* ══════ DASHBOARD ══════ */

    public function afficherDashboard(): void
    {
        if (!isset($_SESSION['gestionnaire_id'])) {
            header('Location: gestionnaire.php?action=connexion');
            exit;
        }

        $gestionnaireId  = (int)$_SESSION['gestionnaire_id'];
        $gestionnaireNom = $_SESSION['gestionnaire_nom'];

        $sousService = $this->model->getSousServiceGestionnaire($gestionnaireId);
        if (!$sousService) {
            die('Aucun sous-service affecté à ce compte. Contactez l\'administrateur.');
        }

        $ssId          = (int)$sousService['id'];
        $stats         = $this->model->statsJour($ssId);
        $file          = $this->model->fileAttente($ssId);
        $consultations = $this->model->consultationsJour($ssId);
        $urgences      = $this->model->urgencesOuvertes($ssId);
        $messageAction = '';
        $typeMessage   = 'success';
        $medecins      = $this->model->getMedecinsDisponibles($ssId);

        // ── Endpoint AJAX : chercher un patient par téléphone ──
        if (isset($_GET['api']) && $_GET['api'] === 'patient') {
            header('Content-Type: application/json; charset=utf-8');
            $tel    = trim($_GET['tel'] ?? '');
            $result = $tel ? $this->model->chercherPatientParTel($tel) : false;
            echo json_encode($result ?: null);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'appeler_suivant') {
                $suivant = $this->model->appelerSuivant($ssId);
                $messageAction = $suivant
                    ? "Patient appelé : {$suivant['prenom']} {$suivant['nom']} — Rang #{$suivant['rang']}"
                    : "Aucun patient en attente.";
            }

            if ($action === 'maj_statut') {
                $cId    = (int)($_POST['consultation_id'] ?? 0);
                $statut = $_POST['statut'] ?? '';
                $autorises = ['traite', 'annule', 'absent'];
                if ($cId > 0 && in_array($statut, $autorises, true)) {
                    $this->model->majStatutConsultation($cId, $statut);
                    $messageAction = 'Statut de la consultation mis à jour.';
                }
            }

            if ($action === 'consultation_manuelle') {
                $patNom       = trim($_POST['patient_nom']       ?? '');
                $patPrenom    = trim($_POST['patient_prenom']    ?? '');
                $patTel       = trim($_POST['patient_telephone'] ?? '');
                $patEmail     = trim($_POST['patient_email']     ?? '');
                $medecinId    = (int)($_POST['medecin_id']       ?? 0);
                $modePrise    = trim($_POST['mode_prise']        ?? 'PLACE');
                $statut       = trim($_POST['statut']            ?? 'en_attente');
                $motif        = trim($_POST['motif']             ?? '');

                $errC = [];
                if (mb_strlen($patNom)    < 2) $errC[] = 'Nom du patient requis.';
                if (mb_strlen($patPrenom) < 2) $errC[] = 'Prénom du patient requis.';
                if (!preg_match('/^\+?[0-9]{8,20}$/', $patTel))
                    $errC[] = 'Numéro de téléphone invalide.';

                if (empty($errC)) {
                    try {
                        $patientId = $this->model->rechercherOuCreerPatient([
                            'nom'       => $patNom,
                            'prenom'    => $patPrenom,
                            'telephone' => $patTel,
                            'email'     => $patEmail,
                        ]);

                        $consultId = $this->model->enregistrerConsultationManuelle([
                            'patient_id'      => $patientId,
                            'sous_service_id' => $ssId,
                            'medecin_id'      => $medecinId ?: null,
                            'mode_prise'      => $modePrise,
                            'statut'          => $statut,
                            'motif'           => $motif,
                        ]);

                        if ($consultId > 0) {
                            $rangAffiche   = $this->model->prochainRang($ssId) - 1;
                            $messageAction = "Consultation enregistrée — {$patPrenom} {$patNom} (rang #{$rangAffiche}).";
                            $typeMessage   = 'success';
                        } else {
                            $messageAction = 'Erreur lors de l\'enregistrement. Vérifiez les données.';
                            $typeMessage   = 'error';
                        }
                    } catch (\Exception $e) {
                        $messageAction = 'Erreur : ' . $e->getMessage();
                        $typeMessage   = 'error';
                    }
                } else {
                    $messageAction = implode(' | ', $errC);
                    $typeMessage   = 'error';
                }
            }

            $stats         = $this->model->statsJour($ssId);
            $file          = $this->model->fileAttente($ssId);
            $consultations = $this->model->consultationsJour($ssId);
            $urgences      = $this->model->urgencesOuvertes($ssId);
            $medecins      = $this->model->getMedecinsDisponibles($ssId);
        }

        require __DIR__ . '/../views/gestionnaire/dashboard.php';
    }

    /* ══════ DÉCONNEXION ══════ */

    public function deconnecter(): void
    {
        session_unset();
        session_destroy();
        header('Location: gestionnaire.php?action=connexion&deconnecte=1');
        exit;
    }
}

/* ── Dispatch ─────────────────────────────────────────────── */
$action = $_GET['action'] ?? 'connexion';
$ctrl   = new GestionnaireController();

match ($action) {
    'inscription' => $_SERVER['REQUEST_METHOD'] === 'POST'
                       ? $ctrl->traiterInscription()
                       : $ctrl->afficherInscription(),
    'connexion'   => $_SERVER['REQUEST_METHOD'] === 'POST'
                       ? $ctrl->traiterConnexion()
                       : $ctrl->afficherConnexion(),
    'dashboard'   => $ctrl->afficherDashboard(),
    'deconnexion' => $ctrl->deconnecter(),
    default       => $ctrl->afficherConnexion(),
};

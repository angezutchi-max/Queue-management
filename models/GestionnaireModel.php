<?php
/**
 * models/GestionnaireModel.php
 * Modèle MVC — Gestionnaire
 * Adapté à la base files_attente v3 :
 *   - Table : consultations  (plus rendez_vous)
 *   - sous_service_id directement dans gestionnaires (plus de table pivot)
 *   - gestionnaires contient : nom, telephone, email, password, sous_service_id
 */
require_once __DIR__ . '/../config/database.php';

class GestionnaireModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* ════════════════════════════════════════════════════════
       AUTHENTIFICATION & COMPTE
    ════════════════════════════════════════════════════════ */

    /** Vérifie si l'email existe déjà */
    public function emailExiste(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM gestionnaires WHERE email = :e'
        );
        $stmt->execute([':e' => strtolower(trim($email))]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Vérifie si le téléphone existe déjà */
    public function telephoneExiste(string $telephone): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM gestionnaires WHERE telephone = :t'
        );
        $stmt->execute([':t' => trim($telephone)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crée un gestionnaire
     * Colonnes : nom, telephone, email, password, sous_service_id
     */
    public function creer(array $d): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO gestionnaires (nom, telephone, email, password, sous_service_id)
             VALUES (:nom, :telephone, :email, :password, :sous_service_id)'
        );
        return $stmt->execute([
            ':nom'             => htmlspecialchars(trim($d['nom'])),
            ':telephone'       => trim($d['telephone']),
            ':email'           => strtolower(trim($d['email'])),
            ':password'        => password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ':sous_service_id' => (int)$d['sous_service_id'],
        ]);
    }

    /** Trouve un gestionnaire par email (pour la connexion) */
    public function trouverParEmail(string $email)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM gestionnaires WHERE email = :e LIMIT 1'
        );
        $stmt->execute([':e' => strtolower(trim($email))]);
        return $stmt->fetch();
    }

    /** Trouve un gestionnaire par ID */
    public function trouverParId(int $id)
    {
        $stmt = $this->db->prepare(
            'SELECT id, nom, telephone, email, sous_service_id
             FROM gestionnaires WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Retourne le dernier ID inséré */
    public function dernierID(): int
    {
        return (int)$this->db->lastInsertId();
    }

    /* ════════════════════════════════════════════════════════
       SOUS-SERVICES (pour le select d'inscription)
    ════════════════════════════════════════════════════════ */

    /**
     * Liste tous les sous-services actifs avec leur hôpital
     * Créés par le médecin chef de service
     */
    public function getSousServices(): array
    {
        $stmt = $this->db->query(
            'SELECT ss.id, ss.nom, s.nom AS service_nom
             FROM sous_services ss
             JOIN services s ON s.id = ss.service_id
             WHERE ss.statut = "actif"
               AND s.statut  = "actif"
             ORDER BY s.nom, ss.nom'
        );
        return $stmt->fetchAll();
    }

    /**
     * Sous-service affecté à ce gestionnaire
     * sous_service_id est directement dans la table gestionnaires
     */
    public function getSousServiceGestionnaire(int $gestionnaireId)
    {
        $stmt = $this->db->prepare(
            'SELECT ss.id, ss.nom, ss.duree_estimee,
                    ss.capacite_horaire, ss.qr_code,
                    s.nom AS service_nom, s.adresse AS service_adresse
             FROM gestionnaires g
             JOIN sous_services ss ON ss.id = g.sous_service_id
             JOIN services      s  ON s.id  = ss.service_id
             WHERE g.id = :gid
             LIMIT 1'
        );
        $stmt->execute([':gid' => $gestionnaireId]);
        return $stmt->fetch();
    }

    /* ════════════════════════════════════════════════════════
       DASHBOARD — STATISTIQUES DU JOUR
    ════════════════════════════════════════════════════════ */

    /** Statistiques du jour pour un sous-service */
    public function statsJour(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
               COUNT(*)                                          AS total,
               SUM(statut = "traite")                           AS traitees,
               SUM(statut IN ("en_attente", "confirme"))        AS en_attente,
               SUM(statut = "absent")                           AS absentes,
               SUM(statut = "annule")                           AS annulees,
               SUM(mode_prise = "LIGNE")                        AS en_ligne,
               SUM(mode_prise = "PLACE")                        AS sur_place
             FROM consultations
             WHERE sous_service_id = :ss
               AND DATE(heure_emission) = CURDATE()'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetch() ?: [];
    }

    /* ════════════════════════════════════════════════════════
       DASHBOARD — FILE D'ATTENTE
    ════════════════════════════════════════════════════════ */

    /** File d'attente en cours (statuts actifs uniquement) */
    public function fileAttente(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
               c.id, c.rang, c.statut, c.mode_prise,
               c.heure_passage_estimee, c.motif,
               p.nom AS patient_nom, p.prenom AS patient_prenom,
               p.telephone,
               CONCAT(m.prenom, " ", m.nom) AS medecin_nom
             FROM consultations c
             JOIN patients p ON p.id = c.patient_id
             LEFT JOIN medecins m ON m.id = c.medecin_id
             WHERE c.sous_service_id = :ss
               AND c.statut IN ("en_attente", "confirme", "en_cours")
               AND DATE(c.heure_emission) = CURDATE()
             ORDER BY c.rang'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetchAll();
    }

    /** Toutes les consultations du jour */
    public function consultationsJour(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
               c.id, c.rang, c.statut, c.mode_prise,
               c.heure_passage_estimee,
               c.heure_debut_reelle, c.heure_fin_reelle,
               c.motif,
               p.nom AS patient_nom, p.prenom AS patient_prenom,
               p.telephone,
               CONCAT(m.prenom, " ", m.nom) AS medecin_nom
             FROM consultations c
             JOIN patients p ON p.id = c.patient_id
             LEFT JOIN medecins m ON m.id = c.medecin_id
             WHERE c.sous_service_id = :ss
               AND DATE(c.heure_emission) = CURDATE()
             ORDER BY c.rang'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetchAll();
    }

    /* ════════════════════════════════════════════════════════
       DASHBOARD — ACTIONS GESTIONNAIRE
    ════════════════════════════════════════════════════════ */

    /** Mettre à jour le statut d'une consultation */
    public function majStatutConsultation(int $consultationId, string $statut): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE consultations SET statut = :s WHERE id = :id'
        );
        return $stmt->execute([':s' => $statut, ':id' => $consultationId]);
    }

    /**
     * Appeler le patient suivant en attente
     * Passe son statut à "en_cours"
     */
    public function appelerSuivant(int $ssId)
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.rang, p.nom, p.prenom, p.telephone
             FROM consultations c
             JOIN patients p ON p.id = c.patient_id
             WHERE c.sous_service_id = :ss
               AND c.statut = "en_attente"
               AND DATE(c.heure_emission) = CURDATE()
             ORDER BY c.rang
             LIMIT 1'
        );
        $stmt->execute([':ss' => $ssId]);
        $suivant = $stmt->fetch();
        if ($suivant) {
            $this->majStatutConsultation($suivant['id'], 'en_cours');
        }
        return $suivant;
    }

    /* ════════════════════════════════════════════════════════
       DASHBOARD — URGENCES
    ════════════════════════════════════════════════════════ */

    /** Urgences ouvertes du service lié au sous-service */
    public function urgencesOuvertes(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.description, u.priorite, u.statut, u.created_at
             FROM urgences u
             JOIN sous_services ss ON ss.service_id = u.service_id
             WHERE ss.id = :ss
               AND u.statut != "cloturee"
             ORDER BY u.priorite ASC, u.created_at DESC'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetchAll();
    }

    /* ════════════════════════════════════════════════════════
       CONSULTATION MANUELLE
    ════════════════════════════════════════════════════════ */

    /**
     * Cherche un patient par téléphone ou email.
     * Si absent, le crée avec les données fournies.
     * Retourne l'ID du patient.
     */
    public function rechercherOuCreerPatient(array $d): int
    {
        $telephone = trim($d['telephone']);
        $email     = trim($d['email']);
        $nom       = htmlspecialchars(trim($d['nom']));
        $prenom    = htmlspecialchars(trim($d['prenom']));

        // Chercher par téléphone d'abord
        $stmt = $this->db->prepare(
            'SELECT id FROM patients WHERE telephone = :tel LIMIT 1'
        );
        $stmt->execute([':tel' => $telephone]);
        $row = $stmt->fetch();
        if ($row) return (int)$row['id'];

        // Chercher par email si fourni
        if (!empty($email)) {
            $stmt = $this->db->prepare(
                'SELECT id FROM patients WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch();
            if ($row) return (int)$row['id'];
        }

        // Créer le patient (email unique : si vide on génère un placeholder)
        $emailFinal = !empty($email) ? $email : $telephone . '@noemail.local';
        $stmt = $this->db->prepare(
            'INSERT INTO patients (nom, prenom, telephone, email, statut)
             VALUES (:nom, :prenom, :tel, :email, "actif")'
        );
        $stmt->execute([
            ':nom'    => $nom,
            ':prenom' => $prenom,
            ':tel'    => $telephone,
            ':email'  => $emailFinal,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Retourne le prochain rang disponible dans la file du jour
     * pour ce sous-service.
     */
    public function prochainRang(int $ssId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(rang), 0) + 1
             FROM consultations
             WHERE sous_service_id = :ss
               AND DATE(heure_emission) = CURDATE()'
        );
        $stmt->execute([':ss' => $ssId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Enregistre une consultation manuelle avec tous les attributs de la table.
     * Retourne l'ID inséré ou 0 en cas d'échec.
     */
    public function enregistrerConsultationManuelle(array $d): int
    {
        $rang           = $this->prochainRang((int)$d['sous_service_id']);
        $dureeEstimee   = $this->getDureeEstimee((int)$d['sous_service_id']);
        $heureEstimee   = $this->calculerHeureEstimee((int)$d['sous_service_id'], $rang, $dureeEstimee);

        $stmt = $this->db->prepare(
            'INSERT INTO consultations
               (patient_id, sous_service_id, medecin_id,
                statut, rang, mode_prise,
                heure_emission, heure_passage_estimee,
                duree_estimee, motif)
             VALUES
               (:patient_id, :ss_id, :medecin_id,
                :statut, :rang, :mode_prise,
                NOW(), :heure_estimee,
                :duree_estimee, :motif)'
        );
        $ok = $stmt->execute([
            ':patient_id'     => (int)$d['patient_id'],
            ':ss_id'          => (int)$d['sous_service_id'],
            ':medecin_id'     => !empty($d['medecin_id']) ? (int)$d['medecin_id'] : null,
            ':statut'         => in_array($d['statut'] ?? '', ['en_attente','confirme'])
                                     ? $d['statut'] : 'en_attente',
            ':rang'           => $rang,
            ':mode_prise'     => in_array($d['mode_prise'] ?? '', ['LIGNE','PLACE'])
                                     ? $d['mode_prise'] : 'PLACE',
            ':heure_estimee'  => $heureEstimee,
            ':duree_estimee'  => $dureeEstimee,
            ':motif'          => !empty($d['motif']) ? htmlspecialchars(trim($d['motif'])) : null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    /** Durée estimée (secondes) d'un sous-service */
    private function getDureeEstimee(int $ssId): int
    {
        $stmt = $this->db->prepare(
            'SELECT duree_estimee FROM sous_services WHERE id = :id'
        );
        $stmt->execute([':id' => $ssId]);
        return (int)($stmt->fetchColumn() ?: 1800);
    }

    /**
     * Calcule l'heure de passage estimée :
     * dernière heure de passage + duree_estimee × (rang - dernierRang)
     */
    private function calculerHeureEstimee(int $ssId, int $rang, int $dureeEstimee): ?string
    {
        // Heure de passage de la dernière consultation en attente
        $stmt = $this->db->prepare(
            'SELECT heure_passage_estimee
             FROM consultations
             WHERE sous_service_id = :ss
               AND DATE(heure_emission) = CURDATE()
               AND heure_passage_estimee IS NOT NULL
             ORDER BY rang DESC
             LIMIT 1'
        );
        $stmt->execute([':ss' => $ssId]);
        $derniere = $stmt->fetchColumn();

        if ($derniere) {
            $base = strtotime($derniere);
        } else {
            $base = time();
        }
        $estimee = date('Y-m-d H:i:s', $base + $dureeEstimee);
        return $estimee;
    }

    /** Médecins disponibles affectés à un sous-service */
    public function getMedecinsDisponibles(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id, m.nom, m.prenom, m.specialite, m.statut
             FROM medecins m
             JOIN medecin_sous_service mss ON mss.medecin_id = m.id
             WHERE mss.sous_service_id = :ss
               AND m.statut = "disponible"
             ORDER BY m.nom, m.prenom'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetchAll();
    }

    /** Cherche un patient par téléphone (pour auto-remplissage AJAX) */
    public function chercherPatientParTel(string $telephone)
    {
        $stmt = $this->db->prepare(
            'SELECT id, nom, prenom, telephone, email
             FROM patients
             WHERE telephone = :tel LIMIT 1'
        );
        $stmt->execute([':tel' => $telephone]);
        return $stmt->fetch();
    }
}

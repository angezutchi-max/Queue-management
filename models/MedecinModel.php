<?php
/**
 * models/MedecinModel.php
 * Modèle MVC — Médecin
 * Table : medecins (id, nom, prenom, specialite, telephone, email, password, est_chef, sous_service_id, statut)
 * Règle : un seul médecin chef par sous_service (est_chef=1)
 */
require_once __DIR__ . '/../config/database.php';

class MedecinModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* ══════════════════════════════════════════════════
       AUTHENTIFICATION & COMPTE
    ══════════════════════════════════════════════════ */

    public function emailExiste(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM medecins WHERE email = :e');
        $stmt->execute([':e' => strtolower(trim($email))]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function telephoneExiste(string $tel): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM medecins WHERE telephone = :t');
        $stmt->execute([':t' => trim($tel)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si un médecin chef existe déjà pour un service donné
     * (via la table medecin_sous_service : est_chef=1)
     * Règle : 1 seul chef par service
     */
    public function chefExistePourService(int $serviceId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM medecin_sous_service mss
             JOIN sous_services ss ON ss.id = mss.sous_service_id
             WHERE ss.service_id = :sid AND mss.est_chef = 1'
        );
        $stmt->execute([':sid' => $serviceId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crée un médecin
     * Colonnes : nom, prenom, specialite, telephone, email, password, statut
     * est_chef et sous_service_id gérés séparément via medecin_sous_service
     */
    public function creer(array $d): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO medecins (nom, prenom, specialite, telephone, email, password, statut)
             VALUES (:nom, :prenom, :specialite, :telephone, :email, :password, "disponible")'
        );
        return $stmt->execute([
            ':nom'        => htmlspecialchars(trim($d['nom'])),
            ':prenom'     => htmlspecialchars(trim($d['prenom'])),
            ':specialite' => htmlspecialchars(trim($d['specialite'])),
            ':telephone'  => trim($d['telephone']),
            ':email'      => strtolower(trim($d['email'])),
            ':password'   => password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
    }

    public function dernierID(): int
    {
        return (int)$this->db->lastInsertId();
    }

    /**
     * Affecter le médecin à un sous-service
     * est_chef = 1 si chef de service, 0 sinon
     */
    public function affecterSousService(int $medecinId, int $ssId, bool $estChef): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO medecin_sous_service (medecin_id, sous_service_id, est_chef, date_affectation)
             VALUES (:mid, :ssid, :chef, CURDATE())
             ON DUPLICATE KEY UPDATE est_chef = :chef'
        );
        return $stmt->execute([
            ':mid'  => $medecinId,
            ':ssid' => $ssId,
            ':chef' => $estChef ? 1 : 0
        ]);
    }

    public function trouverParEmail(string $email)
    {
        $stmt = $this->db->prepare('SELECT * FROM medecins WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => strtolower(trim($email))]);
        return $stmt->fetch();
    }

    public function trouverParId(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM medecins WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Récupère le sous-service + service du médecin
     * avec son rôle (est_chef)
     */
    public function getSousServiceMedecin(int $medecinId)
    {
        $stmt = $this->db->prepare(
            'SELECT ss.id AS ss_id, ss.nom AS ss_nom, ss.duree_estimee,
                    ss.capacite_horaire, ss.qr_code,
                    s.id AS service_id, s.nom AS service_nom, s.adresse,
                    mss.est_chef
             FROM medecin_sous_service mss
             JOIN sous_services ss ON ss.id  = mss.sous_service_id
             JOIN services      s  ON s.id   = ss.service_id
             WHERE mss.medecin_id = :mid
             LIMIT 1'
        );
        $stmt->execute([':mid' => $medecinId]);
        return $stmt->fetch();
    }

    /* ══════════════════════════════════════════════════
       LISTES POUR LES SELECTS
    ══════════════════════════════════════════════════ */

    /** Liste des services actifs (pour le select chef de service) */
    public function getServices(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nom, adresse FROM services WHERE statut = "actif" ORDER BY nom'
        );
        return $stmt->fetchAll();
    }

    /** Liste des sous-services actifs avec hôpital (pour médecin ordinaire) */
    public function getSousServices(): array
    {
        $stmt = $this->db->query(
            'SELECT ss.id, ss.nom, s.nom AS service_nom, s.id AS service_id
             FROM sous_services ss
             JOIN services s ON s.id = ss.service_id
             WHERE ss.statut = "actif" AND s.statut = "actif"
             ORDER BY s.nom, ss.nom'
        );
        return $stmt->fetchAll();
    }

    /* ══════════════════════════════════════════════════
       DASHBOARD MÉDECIN
    ══════════════════════════════════════════════════ */

    /** Statistiques du jour pour un sous-service */
    public function statsJour(int $ssId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
               COUNT(*)                                    AS total,
               SUM(statut = "traite")                     AS traitees,
               SUM(statut IN ("en_attente","confirme"))   AS en_attente,
               SUM(statut = "absent")                     AS absentes,
               SUM(statut = "annule")                     AS annulees,
               ROUND(AVG(
                 CASE WHEN heure_debut_reelle IS NOT NULL
                       AND heure_fin_reelle   IS NOT NULL
                      THEN TIMESTAMPDIFF(SECOND, heure_debut_reelle, heure_fin_reelle)
                 END
               ))                                         AS duree_moy_sec
             FROM consultations
             WHERE sous_service_id = :ss
               AND DATE(heure_emission) = CURDATE()'
        );
        $stmt->execute([':ss' => $ssId]);
        return $stmt->fetch() ?: [];
    }

    /** Consultations du jour pour ce médecin */
    public function consultationsDuJour(int $ssId, int $medecinId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.rang, c.statut, c.mode_prise,
                    c.heure_passage_estimee, c.heure_debut_reelle, c.heure_fin_reelle, c.motif,
                    p.nom AS patient_nom, p.prenom AS patient_prenom, p.telephone
             FROM consultations c
             JOIN patients p ON p.id = c.patient_id
             WHERE c.sous_service_id = :ss
               AND (c.medecin_id = :mid OR c.medecin_id IS NULL)
               AND DATE(c.heure_emission) = CURDATE()
             ORDER BY c.rang'
        );
        $stmt->execute([':ss' => $ssId, ':mid' => $medecinId]);
        return $stmt->fetchAll();
    }

    /** Emplois du temps du médecin (semaine en cours) */
    public function planningMedecin(int $medecinId): array
    {
        $stmt = $this->db->prepare(
            'SELECT edt.id, edt.jour, edt.heure_debut, edt.heure_fin,
                    edt.nb_creneaux, ss.nom AS ss_nom, s.nom AS service_nom
             FROM emplois_du_temps edt
             JOIN sous_services ss ON ss.id = edt.sous_service_id
             JOIN services      s  ON s.id  = ss.service_id
             WHERE edt.medecin_id = :mid
               AND edt.jour BETWEEN
                   DATE(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY))
                   AND DATE(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY))
             ORDER BY edt.jour, edt.heure_debut'
        );
        $stmt->execute([':mid' => $medecinId]);
        return $stmt->fetchAll();
    }

    /** Mettre à jour le statut d'une consultation */
    public function majStatutConsultation(int $id, string $statut): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE consultations SET statut = :s WHERE id = :id'
        );
        return $stmt->execute([':s' => $statut, ':id' => $id]);
    }

    /* ══════════════════════════════════════════════════
       CHEF DE SERVICE — GESTION DES SOUS-SERVICES
    ══════════════════════════════════════════════════ */

    /** Crée un sous-service (réservé au chef de service) */
    public function creerSousService(array $d): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sous_services (service_id, nom, description, duree_rdv_defaut, duree_estimee, capacite_horaire)
             VALUES (:sid, :nom, :desc, :duree, :duree, :capa)'
        );
        return $stmt->execute([
            ':sid'   => (int)$d['service_id'],
            ':nom'   => htmlspecialchars(trim($d['nom'])),
            ':desc'  => htmlspecialchars(trim($d['description'] ?? '')),
            ':duree' => (int)($d['duree_rdv_defaut'] ?? 1800),
            ':capa'  => (int)($d['capacite_horaire'] ?? 10),
        ]);
    }

    /** Sous-services du service du chef */
    public function getSousServicesDeService(int $serviceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ss.id, ss.nom, ss.description, ss.duree_estimee,
                    ss.capacite_horaire, ss.statut,
                    (SELECT COUNT(*) FROM gestionnaires g WHERE g.sous_service_id = ss.id) AS nb_gestionnaires,
                    (SELECT COUNT(*) FROM medecin_sous_service mss2 WHERE mss2.sous_service_id = ss.id) AS nb_medecins
             FROM sous_services ss
             WHERE ss.service_id = :sid
             ORDER BY ss.nom'
        );
        $stmt->execute([':sid' => $serviceId]);
        return $stmt->fetchAll();
    }

    /** Retourne uniquement les sous-services actifs d'un service (pour API JSON inscription) */
    public function getSousServicesActifsParService(int $serviceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ss.id, ss.nom, ss.capacite_horaire, ss.duree_estimee
             FROM sous_services ss
             WHERE ss.service_id = :sid AND ss.statut = "actif"
             ORDER BY ss.nom'
        );
        $stmt->execute([':sid' => $serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retrouve l'ID d'un sous-service par son nom dans un service donné */
    public function trouverSousServiceParNom(string $nom, int $serviceId): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM sous_services
             WHERE TRIM(nom) = TRIM(:nom) AND service_id = :sid AND statut = "actif"
             LIMIT 1'
        );
        $stmt->execute([':nom' => $nom, ':sid' => $serviceId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }
}

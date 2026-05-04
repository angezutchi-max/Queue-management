-- ============================================================
--  BASE DE DONNÉES : files_attente
--  Version        : 3.0 — Finale
--  Compatible     : MySQL 8.0 / MariaDB (WampServer)
--  Encodage       : UTF-8
--
--  MODÈLE :
--    - Service      = Hôpital (ex: Hôpital Régional de Douala)
--    - SousService  = Département médical (ex: Hématologie, Oncologie)
--                     → QR Code scanné au niveau du sous-service
--    - Consultation = Prise en charge d'un patient (remplace "rendez_vous")
--    - Gestionnaire = Agent affecté à un sous-service (sans attribut rôle)
--    - Médecin      = Intervenant médical dans un sous-service
--    - Patient      = Usager du système
--  DONNÉES    : Aucune (base vierge)
-- ============================================================

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone  = "+00:00";
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Création / sélection de la base
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `files_attente`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `files_attente`;


-- ============================================================
-- 1. TABLE : services
--    Représente un hôpital ou établissement de santé.
--    Ex : Hôpital Régional de Douala, CHU de Yaoundé
-- ============================================================
CREATE TABLE `services` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(200)     NOT NULL                  COMMENT 'Nom de l\'hôpital / établissement',
    `description` TEXT                 NULL,
    `adresse`     VARCHAR(300)     NOT NULL,
    `horaires`    VARCHAR(200)         NULL                  COMMENT 'Ex : Lun-Ven 07h00-17h00',
    `statut`      ENUM('actif','inactif')
                                   NOT NULL DEFAULT 'actif',
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_services_statut` (`statut`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Hôpitaux et établissements de santé';


-- ============================================================
-- 2. TABLE : sous_services
--    Département médical appartenant à un service (hôpital).
--    Ex : Hématologie, Oncologie, Pédiatrie, Urgences…
--    Le QR Code dynamique est généré au niveau du sous-service.
-- ============================================================
CREATE TABLE `sous_services` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `service_id`      INT UNSIGNED     NOT NULL               COMMENT 'Hôpital auquel appartient ce département',
    `nom`             VARCHAR(200)     NOT NULL               COMMENT 'Ex : Hématologie, Oncologie',
    `description`     TEXT                 NULL,
    `duree_rdv_defaut` INT UNSIGNED   NOT NULL DEFAULT 1800   COMMENT 'Durée par défaut en secondes (30 min)',
    `duree_estimee`   INT UNSIGNED    NOT NULL DEFAULT 1800   COMMENT 'Durée estimée recalculée chaque nuit par moyenne pondérée (secondes)',
    `capacite_horaire` INT UNSIGNED   NOT NULL DEFAULT 10     COMMENT 'Nombre max de consultations par heure',
    `qr_code`         VARCHAR(500)         NULL               COMMENT 'Contenu / token du QR Code dynamique propre à ce sous-service',
    `qr_expire_at`    DATETIME             NULL               COMMENT 'Date d\'expiration du QR Code (régénéré périodiquement)',
    `statut`          ENUM('actif','inactif')
                                       NOT NULL DEFAULT 'actif',
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ss_service` (`service_id`),
    KEY `idx_ss_statut`  (`statut`),
    CONSTRAINT `fk_ss_service`
        FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Départements médicaux (Hématologie, Oncologie…) — niveau QR Code';


-- ============================================================
-- 3. TABLE : gestionnaires
--    Agent de gestion affecté à un sous-service précis.
--    Pas d'attribut rôle — 3 acteurs distincts dans le système :
--    Patient | Gestionnaire | Médecin
-- ============================================================
CREATE TABLE `gestionnaires` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `sous_service_id` INT UNSIGNED     NOT NULL               COMMENT 'Sous-service auquel le gestionnaire est affecté',
    `nom`             VARCHAR(150)     NOT NULL,
    `telephone`       VARCHAR(20)      NOT NULL,
    `email`           VARCHAR(150)     NOT NULL,
    `password`        VARCHAR(255)     NOT NULL               COMMENT 'Haché avec bcrypt (coût 12)',
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_gestionnaires_email`     (`email`),
    UNIQUE KEY `uq_gestionnaires_telephone` (`telephone`),
    KEY `idx_gestionnaires_ss`              (`sous_service_id`),
    CONSTRAINT `fk_gestionnaires_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Agents de gestion — chacun affecté à un sous-service';


-- ============================================================
-- 4. TABLE : medecins
--    Médecin intervenant dans un ou plusieurs sous-services.
--    Le médecin chef de service crée les sous-services.
-- ============================================================
CREATE TABLE `medecins` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(100)     NOT NULL,
    `prenom`      VARCHAR(100)     NOT NULL,
    `specialite`  VARCHAR(150)     NOT NULL,
    `telephone`   VARCHAR(20)          NULL,
    `email`       VARCHAR(150)         NULL,
    `statut`      ENUM('disponible','indisponible','conge')
                                   NOT NULL DEFAULT 'disponible',
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_medecins_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Médecins intervenant dans les sous-services';


-- ============================================================
-- 5. TABLE : medecin_sous_service
--    Association N-N : un médecin peut intervenir dans
--    plusieurs sous-services et vice-versa.
--    Le médecin chef de service crée les sous-services.
-- ============================================================
CREATE TABLE `medecin_sous_service` (
    `medecin_id`      INT UNSIGNED NOT NULL,
    `sous_service_id` INT UNSIGNED NOT NULL,
    `est_chef`        TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = médecin chef de service (crée les sous-services)',
    `date_affectation` DATE             NULL,
    PRIMARY KEY (`medecin_id`, `sous_service_id`),
    KEY `idx_mss_ss` (`sous_service_id`),
    CONSTRAINT `fk_mss_medecin`
        FOREIGN KEY (`medecin_id`)      REFERENCES `medecins`      (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mss_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Affectation médecin ↔ sous-service (est_chef = médecin chef)';


-- ============================================================
-- 6. TABLE : patients
--    Usager du système — peut prendre RDV en ligne ou sur place.
-- ============================================================
CREATE TABLE `patients` (
    `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nom`              VARCHAR(100)     NOT NULL,
    `prenom`           VARCHAR(100)     NOT NULL,
    `telephone`        VARCHAR(20)      NOT NULL,
    `email`            VARCHAR(150)     NOT NULL,
    `password`         VARCHAR(255)         NULL               COMMENT 'NULL si inscription via QR Code uniquement (anonyme)',
    `token_fcm`        VARCHAR(255)         NULL               COMMENT 'Token Firebase Cloud Messaging pour les push',
    `statut`           ENUM('actif','suspendu','inactif')
                                        NOT NULL DEFAULT 'actif',
    `date_inscription` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_patients_email`     (`email`),
    UNIQUE KEY `uq_patients_telephone` (`telephone`),
    KEY `idx_patients_statut`          (`statut`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Patients / usagers du système';


-- ============================================================
-- 7. TABLE : emplois_du_temps
--    Planning journalier d'un sous-service avec un médecin.
--    Relation : Médecin INTERVENIR dans EmploisDeTemps
--               EmploisDeTemps POSSEDE SousService
-- ============================================================
CREATE TABLE `emplois_du_temps` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `sous_service_id` INT UNSIGNED     NOT NULL,
    `medecin_id`      INT UNSIGNED         NULL               COMMENT 'Médecin responsable du créneau',
    `jour`            DATE             NOT NULL,
    `heure_debut`     TIME             NOT NULL,
    `heure_fin`       TIME             NOT NULL,
    `nb_creneaux`     INT UNSIGNED     NOT NULL DEFAULT 0     COMMENT 'Nombre de créneaux réservés sur ce planning',
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_edt_ss`      (`sous_service_id`),
    KEY `idx_edt_medecin` (`medecin_id`),
    KEY `idx_edt_jour`    (`jour`),
    CONSTRAINT `fk_edt_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_edt_medecin`
        FOREIGN KEY (`medecin_id`) REFERENCES `medecins` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Planning journalier des sous-services par médecin';


-- ============================================================
-- 8. TABLE : consultations
--    Remplace "rendez_vous" — représente la prise en charge
--    (consultation) d'un patient dans un sous-service.
--    Mode LIGNE  = RDV en ligne depuis le domicile (sans QR)
--    Mode PLACE  = QR Code scanné sur place
-- ============================================================
CREATE TABLE `consultations` (
    `id`                   INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `patient_id`           INT UNSIGNED   NOT NULL,
    `sous_service_id`      INT UNSIGNED   NOT NULL,
    `medecin_id`           INT UNSIGNED       NULL               COMMENT 'Médecin qui prend en charge',
    `emploi_temps_id`      INT UNSIGNED       NULL               COMMENT 'Créneau de l\'emploi du temps réservé',
    `statut`               ENUM(
                               'en_attente',
                               'confirme',
                               'en_cours',
                               'traite',
                               'annule',
                               'absent'
                           )              NOT NULL DEFAULT 'en_attente',
    `rang`                 INT UNSIGNED       NULL               COMMENT 'Position dans la file d\'attente du jour',
    `mode_prise`           ENUM('LIGNE','PLACE')
                                          NOT NULL DEFAULT 'PLACE' COMMENT 'LIGNE=domicile, PLACE=QR Code scanné',
    `heure_emission`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Heure de création du ticket',
    `heure_passage_estimee` DATETIME          NULL               COMMENT 'Heure de passage calculée (duree_estimee × rang)',
    `heure_debut_reelle`   DATETIME           NULL               COMMENT 'Heure réelle de début de consultation',
    `heure_fin_reelle`     DATETIME           NULL               COMMENT 'Heure réelle de fin de consultation',
    `duree_estimee`        INT UNSIGNED       NULL               COMMENT 'Durée estimée en secondes au moment de la réservation',
    `motif`                TEXT               NULL               COMMENT 'Motif de la consultation (optionnel)',
    `created_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_consult_patient`   (`patient_id`),
    KEY `idx_consult_ss`        (`sous_service_id`),
    KEY `idx_consult_medecin`   (`medecin_id`),
    KEY `idx_consult_edt`       (`emploi_temps_id`),
    KEY `idx_consult_statut`    (`statut`),
    KEY `idx_consult_emission`  (`heure_emission`),
    KEY `idx_consult_rang`      (`sous_service_id`, `rang`),
    CONSTRAINT `fk_consult_patient`
        FOREIGN KEY (`patient_id`)      REFERENCES `patients`       (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_consult_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services`  (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_consult_medecin`
        FOREIGN KEY (`medecin_id`)      REFERENCES `medecins`       (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_consult_edt`
        FOREIGN KEY (`emploi_temps_id`) REFERENCES `emplois_du_temps` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Consultations des patients (remplace rendez_vous)';


-- ============================================================
-- 9. TABLE : urgences
--    Urgence déclarée par un service (hôpital).
--    Insérée en tête de file — priorité absolue.
-- ============================================================
CREATE TABLE `urgences` (
    `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `service_id`   INT UNSIGNED     NOT NULL               COMMENT 'Hôpital qui déclare l\'urgence',
    `description`  TEXT             NOT NULL,
    `priorite`     TINYINT UNSIGNED NOT NULL DEFAULT 1     COMMENT '1=haute 2=moyenne 3=basse',
    `statut`       ENUM('ouverte','en_cours','cloturee')
                                    NOT NULL DEFAULT 'ouverte',
    `created_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_urgences_service` (`service_id`),
    KEY `idx_urgences_statut`  (`statut`),
    CONSTRAINT `fk_urgences_service`
        FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Urgences déclarées par les hôpitaux';


-- ============================================================
-- 10. TABLE : notifications
--     Notifications envoyées aux patients (FCM ou SMS).
--     Relation : Patient RECEVOIR Notification (0..*)
--                Notification GENEREE par Consultation
-- ============================================================
CREATE TABLE `notifications` (
    `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `patient_id`      INT UNSIGNED   NOT NULL,
    `consultation_id` INT UNSIGNED       NULL,
    `type`            ENUM(
                          'CONFIRMATION',
                          'RAPPEL_J1',
                          'RAPPEL_15MIN',
                          'APPEL_IMMEDIAT',
                          'AVANCEMENT',
                          'DECALAGE',
                          'ANNULATION',
                          'CLOTURE_ABSENT',
                          'MAJ_HEURE',
                          'URGENCE'
                      )              NOT NULL,
    `contenu`         TEXT           NOT NULL,
    `canal`           ENUM('FCM','SMS')
                                     NOT NULL DEFAULT 'FCM',
    `statut`          ENUM('en_attente','envoye','echec','lu')
                                     NOT NULL DEFAULT 'en_attente',
    `sent_at`         DATETIME           NULL,
    `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_patient`       (`patient_id`),
    KEY `idx_notif_consultation`  (`consultation_id`),
    KEY `idx_notif_statut`        (`statut`),
    KEY `idx_notif_type`          (`type`),
    CONSTRAINT `fk_notif_patient`
        FOREIGN KEY (`patient_id`)      REFERENCES `patients`       (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_consultation`
        FOREIGN KEY (`consultation_id`) REFERENCES `consultations`  (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Notifications envoyées aux patients (FCM ou SMS)';


-- ============================================================
-- 11. TABLE : session_service
--     Session de travail d'un gestionnaire sur un sous-service.
--     Relation : SessionService ASSURER par Gestionnaire (1)
--                SessionService AVOIR SousService (1)
-- ============================================================
CREATE TABLE `session_service` (
    `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `gestionnaire_id`  INT UNSIGNED   NOT NULL,
    `sous_service_id`  INT UNSIGNED   NOT NULL,
    `heure_debut`      DATETIME       NOT NULL,
    `heure_fin`        DATETIME           NULL,
    `nb_traites`       INT UNSIGNED   NOT NULL DEFAULT 0    COMMENT 'Nombre de consultations traitées durant cette session',
    `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sess_gestionnaire` (`gestionnaire_id`),
    KEY `idx_sess_ss`           (`sous_service_id`),
    KEY `idx_sess_date`         (`heure_debut`),
    CONSTRAINT `fk_sess_gestionnaire`
        FOREIGN KEY (`gestionnaire_id`) REFERENCES `gestionnaires`  (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sess_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services`  (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Sessions de travail des gestionnaires';


-- ============================================================
-- 12. TABLE : historique_durees
--     Durées réelles de chaque consultation.
--     Utilisée par le job cron nocturne (23h00) pour
--     recalculer la moyenne pondérée → duree_estimee.
-- ============================================================
CREATE TABLE `historique_durees` (
    `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `sous_service_id` INT UNSIGNED   NOT NULL,
    `consultation_id` INT UNSIGNED       NULL,
    `medecin_id`      INT UNSIGNED       NULL,
    `duree_reelle`    INT UNSIGNED   NOT NULL               COMMENT 'Durée réelle en secondes',
    `heure_debut`     DATETIME       NOT NULL,
    `jour_semaine`    TINYINT UNSIGNED NOT NULL             COMMENT '1=Lundi … 7=Dimanche',
    `tranche_horaire` TINYINT UNSIGNED NOT NULL             COMMENT 'Heure de début 0-23',
    `est_aberrant`    TINYINT(1)     NOT NULL DEFAULT 0     COMMENT '1 = exclue du calcul (< 60s ou > 10800s)',
    `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_hist_ss`           (`sous_service_id`),
    KEY `idx_hist_consultation` (`consultation_id`),
    KEY `idx_hist_medecin`      (`medecin_id`),
    KEY `idx_hist_created`      (`created_at`),
    CONSTRAINT `fk_hist_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services`  (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_consultation`
        FOREIGN KEY (`consultation_id`) REFERENCES `consultations`  (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_medecin`
        FOREIGN KEY (`medecin_id`)      REFERENCES `medecins`       (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique des durées réelles — base du recalcul nocturne';


-- ============================================================
-- 13. TABLE : logs_estimation
--     Traçabilité de chaque recalcul nocturne de la
--     moyenne pondérée par sous-service.
-- ============================================================
CREATE TABLE `logs_estimation` (
    `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `sous_service_id`  INT UNSIGNED   NOT NULL,
    `date_calcul`      DATE           NOT NULL,
    `nb_observations`  INT UNSIGNED   NOT NULL DEFAULT 0,
    `ancienne_duree`   INT UNSIGNED   NOT NULL               COMMENT 'Durée avant recalcul (secondes)',
    `nouvelle_duree`   INT UNSIGNED   NOT NULL               COMMENT 'Durée après recalcul (secondes)',
    `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_logs_ss`   (`sous_service_id`),
    KEY `idx_logs_date` (`date_calcul`),
    CONSTRAINT `fk_logs_ss`
        FOREIGN KEY (`sous_service_id`) REFERENCES `sous_services` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Traçabilité des recalculs nocturnes de la moyenne pondérée';


-- ============================================================
-- VUES UTILES
-- ============================================================

-- Vue : File d'attente en cours par sous-service
CREATE OR REPLACE VIEW `v_file_attente` AS
SELECT
    c.id                          AS consultation_id,
    c.rang,
    c.statut,
    c.mode_prise,
    c.heure_passage_estimee,
    c.motif,
    p.nom                         AS patient_nom,
    p.prenom                      AS patient_prenom,
    p.telephone                   AS patient_telephone,
    ss.nom                        AS sous_service_nom,
    ss.duree_estimee              AS duree_estimee_sec,
    s.nom                         AS service_nom,
    CONCAT(m.prenom, ' ', m.nom)  AS medecin_nom
FROM `consultations` c
JOIN `patients`      p  ON p.id  = c.patient_id
JOIN `sous_services` ss ON ss.id = c.sous_service_id
JOIN `services`      s  ON s.id  = ss.service_id
LEFT JOIN `medecins` m  ON m.id  = c.medecin_id
WHERE c.statut IN ('en_attente', 'confirme', 'en_cours')
  AND DATE(c.heure_emission) = CURDATE()
ORDER BY ss.id, c.rang;


-- Vue : Statistiques journalières par sous-service
CREATE OR REPLACE VIEW `v_stats_jour` AS
SELECT
    ss.id                                        AS sous_service_id,
    ss.nom                                       AS sous_service_nom,
    s.id                                         AS service_id,
    s.nom                                        AS service_nom,
    DATE(c.heure_emission)                       AS jour,
    COUNT(c.id)                                  AS total_consultations,
    SUM(c.statut = 'traite')                     AS traitees,
    SUM(c.statut IN ('en_attente','confirme'))   AS en_attente,
    SUM(c.statut = 'absent')                     AS absentes,
    SUM(c.statut = 'annule')                     AS annulees,
    SUM(c.mode_prise = 'LIGNE')                  AS prises_en_ligne,
    SUM(c.mode_prise = 'PLACE')                  AS prises_sur_place,
    ROUND(AVG(
        CASE
            WHEN c.heure_debut_reelle IS NOT NULL
             AND c.heure_fin_reelle   IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, c.heure_debut_reelle, c.heure_fin_reelle)
        END
    ))                                           AS duree_reelle_moy_sec
FROM `consultations` c
JOIN `sous_services` ss ON ss.id = c.sous_service_id
JOIN `services`      s  ON s.id  = ss.service_id
GROUP BY ss.id, ss.nom, s.id, s.nom, DATE(c.heure_emission);


-- Vue : Sous-services avec nom du service et gestionnaire affecté
CREATE OR REPLACE VIEW `v_sous_services_complet` AS
SELECT
    ss.id                          AS ss_id,
    ss.nom                         AS ss_nom,
    ss.duree_estimee,
    ss.capacite_horaire,
    ss.qr_code,
    ss.statut                      AS ss_statut,
    s.id                           AS service_id,
    s.nom                          AS service_nom,
    s.adresse                      AS service_adresse,
    g.id                           AS gestionnaire_id,
    g.nom                          AS gestionnaire_nom,
    g.telephone                    AS gestionnaire_telephone
FROM `sous_services` ss
JOIN `services`      s  ON s.id = ss.service_id
LEFT JOIN `gestionnaires` g ON g.sous_service_id = ss.id;


-- ============================================================
-- PROCÉDURE STOCKÉE : Recalcul nocturne de la moyenne pondérée
-- Appelée par le job CRON chaque soir à 23h00
-- Formule : Σ(duree_i / rang_i) / Σ(1 / rang_i)
--           rang 1 = la plus récente (poids fort)
-- ============================================================
DELIMITER //
CREATE PROCEDURE `sp_recalcul_estimation_nuit`()
BEGIN
    DECLARE v_ss_id        INT UNSIGNED;
    DECLARE v_nouvelle     INT UNSIGNED;
    DECLARE v_ancienne     INT UNSIGNED;
    DECLARE v_nb_obs       INT UNSIGNED;
    DECLARE done           INT DEFAULT FALSE;

    DECLARE cur CURSOR FOR
        SELECT id FROM sous_services WHERE statut = 'actif';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    boucle: LOOP
        FETCH cur INTO v_ss_id;
        IF done THEN LEAVE boucle; END IF;

        -- Moyenne pondérée sur les 100 dernières durées non aberrantes
        SELECT
            COUNT(*),
            ROUND(
                SUM(duree_reelle / rang_obs) /
                NULLIF(SUM(1.0   / rang_obs), 0)
            )
        INTO v_nb_obs, v_nouvelle
        FROM (
            SELECT
                duree_reelle,
                ROW_NUMBER() OVER (
                    PARTITION BY sous_service_id
                    ORDER BY created_at DESC
                ) AS rang_obs
            FROM historique_durees
            WHERE sous_service_id = v_ss_id
              AND est_aberrant     = 0
            LIMIT 100
        ) ranked;

        -- Minimum 5 observations requises pour mettre à jour
        IF v_nb_obs >= 5 AND v_nouvelle IS NOT NULL THEN
            SELECT duree_estimee INTO v_ancienne
            FROM sous_services WHERE id = v_ss_id;

            UPDATE sous_services
               SET duree_estimee = v_nouvelle
             WHERE id = v_ss_id;

            INSERT INTO logs_estimation
                (sous_service_id, date_calcul, nb_observations, ancienne_duree, nouvelle_duree)
            VALUES
                (v_ss_id, CURDATE(), v_nb_obs, v_ancienne, v_nouvelle);
        END IF;

    END LOOP;
    CLOSE cur;
END //
DELIMITER ;


-- ============================================================
-- TRIGGERS
-- ============================================================

-- Trigger 1 : Marquer automatiquement les durées aberrantes
--             (< 60 secondes ou > 10 800 secondes / 3 heures)
DELIMITER //
CREATE TRIGGER `trg_historique_aberrant`
BEFORE INSERT ON `historique_durees`
FOR EACH ROW
BEGIN
    IF NEW.duree_reelle < 60 OR NEW.duree_reelle > 10800 THEN
        SET NEW.est_aberrant = 1;
    END IF;
END //
DELIMITER ;


-- Trigger 2 : Incrémenter nb_creneaux à la création d'une consultation
DELIMITER //
CREATE TRIGGER `trg_consult_increment_creneaux`
AFTER INSERT ON `consultations`
FOR EACH ROW
BEGIN
    IF NEW.emploi_temps_id IS NOT NULL
       AND NEW.statut IN ('en_attente', 'confirme') THEN
        UPDATE emplois_du_temps
           SET nb_creneaux = nb_creneaux + 1
         WHERE id = NEW.emploi_temps_id;
    END IF;
END //
DELIMITER ;


-- Trigger 3 : Libérer le créneau si consultation annulée ou absent
DELIMITER //
CREATE TRIGGER `trg_consult_liberer_creneau`
AFTER UPDATE ON `consultations`
FOR EACH ROW
BEGIN
    IF NEW.statut IN ('annule', 'absent')
       AND OLD.statut NOT IN ('annule', 'absent')
       AND NEW.emploi_temps_id IS NOT NULL THEN
        UPDATE emplois_du_temps
           SET nb_creneaux = GREATEST(nb_creneaux - 1, 0)
         WHERE id = NEW.emploi_temps_id;
    END IF;
END //
DELIMITER ;


-- Trigger 4 : Enregistrer la durée réelle à la fin d'une consultation
DELIMITER //
CREATE TRIGGER `trg_consult_enregistrer_duree`
AFTER UPDATE ON `consultations`
FOR EACH ROW
BEGIN
    IF NEW.statut = 'traite'
       AND OLD.statut != 'traite'
       AND NEW.heure_debut_reelle IS NOT NULL
       AND NEW.heure_fin_reelle   IS NOT NULL THEN

        INSERT INTO historique_durees
            (sous_service_id, consultation_id, medecin_id,
             duree_reelle, heure_debut,
             jour_semaine, tranche_horaire)
        VALUES (
            NEW.sous_service_id,
            NEW.id,
            NEW.medecin_id,
            TIMESTAMPDIFF(SECOND, NEW.heure_debut_reelle, NEW.heure_fin_reelle),
            NEW.heure_debut_reelle,
            DAYOFWEEK(NEW.heure_debut_reelle),   -- 1=Dim … 7=Sam (MySQL)
            HOUR(NEW.heure_debut_reelle)
        );

        -- Incrémenter le compteur de la session active
        UPDATE session_service
           SET nb_traites = nb_traites + 1
         WHERE gestionnaire_id IN (
             SELECT id FROM gestionnaires WHERE sous_service_id = NEW.sous_service_id
         )
           AND heure_fin IS NULL
        LIMIT 1;

    END IF;
END //
DELIMITER ;


COMMIT;

-- ============================================================
-- RÉCAPITULATIF DES TABLES
-- ============================================================
-- services                → Hôpitaux / établissements
-- sous_services           → Départements médicaux (QR Code ici)
-- gestionnaires           → Agents affectés à un sous-service
-- medecins                → Médecins intervenants
-- medecin_sous_service    → Affectation N-N médecin ↔ sous-service
-- patients                → Usagers
-- emplois_du_temps        → Plannings journaliers
-- consultations           → Prise en charge des patients (≡ rendez_vous)
-- urgences                → Urgences déclarées par l'hôpital
-- notifications           → Notifications FCM / SMS
-- session_service         → Sessions de travail des gestionnaires
-- historique_durees       → Base du calcul de la moyenne pondérée
-- logs_estimation         → Traçabilité des recalculs nocturnes
-- ============================================================
-- FIN DU SCRIPT — files_attente v3.0
-- ============================================================

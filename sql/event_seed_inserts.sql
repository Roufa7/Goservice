USE goservice;

/*
  Seed file for the events module only.
  This file adds demo data into:
  - evenement
  - participation

  It does not alter the schema and does not add columns.
  Recommended: run it once on a clean or mostly empty database.
*/

INSERT INTO evenement (
    titre,
    description,
    date_debut,
    date_fin,
    lieu,
    type_evenement,
    nb_places,
    image,
    statut,
    date_creation
) VALUES
('GS - Atelier plomberie express', 'Atelier pratique sur la detection rapide des fuites, le remplacement des joints et les gestes essentiels pour une intervention propre et securisee.', '2026-05-06 09:00:00', '2026-05-06 12:30:00', 'Centre technique Tunis', 'atelier', 24, '', 'prevu', '2026-04-20 10:00:00'),
('GS - Formation electricite domestique', 'Formation accompagnee sur les bases de l electricite domestique, les protections, les outils et les bons reflexes avant intervention.', '2026-05-12 14:00:00', '2026-05-12 18:00:00', 'Hub GoService Lac 1', 'formation', 30, '', 'prevu', '2026-04-18 09:30:00'),
('GS - Promotion renovation cuisine', 'Evenement promotionnel autour des packs de renovation, du conseil budget et des services a forte valeur ajoutes pour les foyers.', '2026-05-18 10:00:00', '2026-05-18 16:00:00', 'Showroom Ariana', 'promotion', 40, '', 'prevu', '2026-04-22 11:15:00'),
('GS - Service jardin connecte', 'Presentation de solutions de jardinage intelligent, arrosage programme, entretien saisonnier et optimisation des espaces verts.', '2026-05-24 11:00:00', '2026-05-24 14:00:00', 'Parc urbain Ennasr', 'service', 20, '', 'prevu', '2026-04-21 15:00:00'),
('GS - Masterclass peinture moderne', 'Session immersive de preparation de supports, choix des finitions et astuces de rendu premium pour les travaux de peinture interieure.', '2026-06-02 09:30:00', '2026-06-02 13:30:00', 'Studio creation Bardo', 'formation', 18, '', 'prevu', '2026-04-28 13:10:00'),
('GS - Clinique fuite urgence', 'Atelier court et tres pratique axe sur les cas d urgence les plus frequents et les gestes avant l arrivee du professionnel.', '2026-05-02 08:00:00', '2026-05-02 19:00:00', 'Plateau de demonstration GoService', 'atelier', 16, '', 'en cours', '2026-04-15 08:20:00'),
('GS - Forum solaire maison', 'Decouverte des solutions d integration solaire, dimensionnement de base et retours d experience pour l habitat individuel.', '2026-06-12 10:00:00', '2026-06-12 17:00:00', 'Maison des innovations Sousse', 'service', 45, '', 'prevu', '2026-04-24 16:00:00'),
('GS - Rencontre deco terrasse', 'Rencontre autour de l amenagement exterieur, des materiaux, de l eclairage et des usages saisonniers des terrasses.', '2026-06-21 16:00:00', '2026-06-21 19:30:00', 'Roof garden Nabeul', 'promotion', 28, '', 'prevu', '2026-04-27 12:00:00'),
('GS - Session bricolage debutant', 'Parcours d initiation pour les particuliers souhaitant apprendre les bases du bricolage utile a la maison.', '2026-04-14 09:00:00', '2026-04-14 12:00:00', 'Maison de quartier Manouba', 'atelier', 22, '', 'termine', '2026-03-30 09:40:00'),
('GS - Atelier climatisation ete', 'Preparation de la saison chaude avec nettoyage, entretien, verification et bonnes pratiques autour des systemes de climatisation.', '2026-06-28 09:00:00', '2026-06-28 12:30:00', 'Centre services Sfax', 'service', 26, '', 'prevu', '2026-04-29 09:00:00'),
('GS - Webinaire optimisation eau', 'Session en ligne sur les gestes, equipements et routines d economie d eau adaptes aux besoins domestiques.', '2026-05-30 18:30:00', '2026-05-30 20:00:00', 'En ligne', 'formation', 80, '', 'prevu', '2026-04-25 18:15:00'),
('GS - Expo services multispecialistes', 'Expo centrale avec stands, mini demonstrations et rencontres rapides entre clients et fournisseurs de services.', '2026-07-08 09:00:00', '2026-07-08 18:00:00', 'Palais des congres Tunis', 'promotion', 120, '', 'prevu', '2026-04-28 14:45:00'),
('GS - Tournee menuiserie mobile', 'Parcours pratique sur les services de menuiserie sur site et la personnalisation rapide de petites installations.', '2026-04-20 10:00:00', '2026-04-20 13:00:00', 'Avenue Habib Bourguiba', 'service', 15, '', 'termine', '2026-04-01 10:00:00'),
('GS - Soiree travaux d ete', 'Soiree conseil et orientation pour planifier les travaux de l ete avec estimation, delais et priorites de chantier.', '2026-05-16 19:00:00', '2026-05-16 22:00:00', 'Hotel centre ville', 'promotion', 32, '', 'annule', '2026-04-23 17:20:00');

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Amina Ben Salah', 'amina.bensalah.demo1@goservice.test', '20100111', '2026-04-24 09:05:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier plomberie express' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Youssef Trabelsi', 'youssef.trabelsi.demo1@goservice.test', '20100112', '2026-04-24 10:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier plomberie express' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Meriem Kooli', 'meriem.kooli.demo1@goservice.test', '20100113', '2026-04-24 11:45:00', 'en attente' FROM evenement WHERE titre = 'GS - Atelier plomberie express' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Skander Jaziri', 'skander.jaziri.demo1@goservice.test', '20100114', '2026-04-25 09:12:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier plomberie express' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Nour Gharbi', 'nour.gharbi.demo1@goservice.test', '20100115', '2026-04-25 13:00:00', 'annule' FROM evenement WHERE titre = 'GS - Atelier plomberie express' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sami Ben Ali', 'sami.benali.demo2@goservice.test', '20100211', '2026-04-21 14:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Lina Ghedira', 'lina.ghedira.demo2@goservice.test', '20100212', '2026-04-21 16:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Walid Dhaouadi', 'walid.dhaouadi.demo2@goservice.test', '20100213', '2026-04-22 08:40:00', 'en attente' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Aya Maalej', 'aya.maalej.demo2@goservice.test', '20100214', '2026-04-22 10:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Hichem Ben Romdhane', 'hichem.benromdhane.demo2@goservice.test', '20100215', '2026-04-22 18:25:00', 'en attente' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Moez Karray', 'moez.karray.demo2@goservice.test', '20100216', '2026-04-23 12:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Formation electricite domestique' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Rim Touati', 'rim.touati.demo3@goservice.test', '20100311', '2026-04-23 09:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Hamza Mhiri', 'hamza.mhiri.demo3@goservice.test', '20100312', '2026-04-23 11:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ines Saidi', 'ines.saidi.demo3@goservice.test', '20100313', '2026-04-23 12:30:00', 'confirme' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Mahdi Chatti', 'mahdi.chatti.demo3@goservice.test', '20100314', '2026-04-24 08:50:00', 'en attente' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Rahma Ben Hmida', 'rahma.benhmida.demo3@goservice.test', '20100315', '2026-04-24 10:05:00', 'en attente' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Nidhal Gassab', 'nidhal.gassab.demo3@goservice.test', '20100316', '2026-04-24 14:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Marwa Frikha', 'marwa.frikha.demo3@goservice.test', '20100317', '2026-04-25 09:45:00', 'annule' FROM evenement WHERE titre = 'GS - Promotion renovation cuisine' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Maya Hentati', 'maya.hentati.demo4@goservice.test', '20100411', '2026-04-25 08:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Service jardin connecte' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Fedi Oueslati', 'fedi.oueslati.demo4@goservice.test', '20100412', '2026-04-25 09:40:00', 'confirme' FROM evenement WHERE titre = 'GS - Service jardin connecte' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sarra Kefi', 'sarra.kefi.demo4@goservice.test', '20100413', '2026-04-26 10:10:00', 'en attente' FROM evenement WHERE titre = 'GS - Service jardin connecte' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Amine Rekik', 'amine.rekik.demo4@goservice.test', '20100414', '2026-04-26 12:05:00', 'confirme' FROM evenement WHERE titre = 'GS - Service jardin connecte' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Wiem Ayari', 'wiem.ayari.demo4@goservice.test', '20100415', '2026-04-26 14:40:00', 'confirme' FROM evenement WHERE titre = 'GS - Service jardin connecte' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Salma Hmida', 'salma.hmida.demo5@goservice.test', '20100511', '2026-04-29 10:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Masterclass peinture moderne' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Nizar Bouzid', 'nizar.bouzid.demo5@goservice.test', '20100512', '2026-04-29 11:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Masterclass peinture moderne' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Hanen Jlassi', 'hanen.jlassi.demo5@goservice.test', '20100513', '2026-04-29 15:45:00', 'en attente' FROM evenement WHERE titre = 'GS - Masterclass peinture moderne' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ahmed Fathallah', 'ahmed.fathallah.demo5@goservice.test', '20100514', '2026-04-30 09:05:00', 'confirme' FROM evenement WHERE titre = 'GS - Masterclass peinture moderne' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ons Sassi', 'ons.sassi.demo5@goservice.test', '20100515', '2026-04-30 13:20:00', 'en attente' FROM evenement WHERE titre = 'GS - Masterclass peinture moderne' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Adem Tlili', 'adem.tlili.demo6@goservice.test', '20100611', '2026-04-16 08:50:00', 'confirme' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Dorra Mnasri', 'dorra.mnasri.demo6@goservice.test', '20100612', '2026-04-16 09:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Khalil Boussetta', 'khalil.boussetta.demo6@goservice.test', '20100613', '2026-04-16 10:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Rania Ghariani', 'rania.ghariani.demo6@goservice.test', '20100614', '2026-04-17 10:20:00', 'en attente' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Feriel Kammoun', 'feriel.kammoun.demo6@goservice.test', '20100615', '2026-04-17 11:40:00', 'confirme' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Bilel Chemli', 'bilel.chemli.demo6@goservice.test', '20100616', '2026-04-18 08:35:00', 'en attente' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Manel Kharrat', 'manel.kharrat.demo6@goservice.test', '20100617', '2026-04-18 12:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Tarek Ghannouchi', 'tarek.ghannouchi.demo6@goservice.test', '20100618', '2026-04-19 14:10:00', 'annule' FROM evenement WHERE titre = 'GS - Clinique fuite urgence' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sahar Mnif', 'sahar.mnif.demo7@goservice.test', '20100711', '2026-04-25 15:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Forum solaire maison' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Lotfi Hachicha', 'lotfi.hachicha.demo7@goservice.test', '20100712', '2026-04-25 15:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Forum solaire maison' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Nesrine Chouchane', 'nesrine.chouchane.demo7@goservice.test', '20100713', '2026-04-26 09:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Forum solaire maison' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Kenza Bellakhal', 'kenza.bellakhal.demo7@goservice.test', '20100714', '2026-04-26 12:40:00', 'en attente' FROM evenement WHERE titre = 'GS - Forum solaire maison' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Yassine Chelbi', 'yassine.chelbi.demo7@goservice.test', '20100715', '2026-04-27 08:05:00', 'en attente' FROM evenement WHERE titre = 'GS - Forum solaire maison' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Houda Beyaoui', 'houda.beyaoui.demo8@goservice.test', '20100811', '2026-04-28 17:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Rencontre deco terrasse' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Malek Mzoughi', 'malek.mzoughi.demo8@goservice.test', '20100812', '2026-04-29 09:30:00', 'confirme' FROM evenement WHERE titre = 'GS - Rencontre deco terrasse' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Jihen Baccar', 'jihen.baccar.demo8@goservice.test', '20100813', '2026-04-29 14:10:00', 'en attente' FROM evenement WHERE titre = 'GS - Rencontre deco terrasse' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sofiene Jerbi', 'sofiene.jerbi.demo8@goservice.test', '20100814', '2026-04-30 11:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Rencontre deco terrasse' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Mouna Tounsi', 'mouna.tounsi.demo8@goservice.test', '20100815', '2026-04-30 15:35:00', 'annule' FROM evenement WHERE titre = 'GS - Rencontre deco terrasse' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ridha Baccouche', 'ridha.baccouche.demo9@goservice.test', '20100911', '2026-04-03 09:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Session bricolage debutant' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ghofrane Rekik', 'ghofrane.rekik.demo9@goservice.test', '20100912', '2026-04-03 09:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Session bricolage debutant' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Zied Khelifi', 'zied.khelifi.demo9@goservice.test', '20100913', '2026-04-03 10:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Session bricolage debutant' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Dhia Abidi', 'dhia.abidi.demo9@goservice.test', '20100914', '2026-04-03 11:40:00', 'annule' FROM evenement WHERE titre = 'GS - Session bricolage debutant' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Meriem Feki', 'meriem.feki.demo10@goservice.test', '20101011', '2026-04-30 08:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier climatisation ete' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Anis Jarboui', 'anis.jarboui.demo10@goservice.test', '20101012', '2026-04-30 09:30:00', 'en attente' FROM evenement WHERE titre = 'GS - Atelier climatisation ete' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Asma Ben Hamouda', 'asma.benhamouda.demo10@goservice.test', '20101013', '2026-04-30 11:50:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier climatisation ete' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Bassem Miled', 'bassem.miled.demo10@goservice.test', '20101014', '2026-05-01 08:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Atelier climatisation ete' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Imen Krid', 'imen.krid.demo10@goservice.test', '20101015', '2026-05-01 08:30:00', 'en attente' FROM evenement WHERE titre = 'GS - Atelier climatisation ete' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Maha Darragi', 'maha.darragi.demo11@goservice.test', '20101111', '2026-04-25 19:05:00', 'confirme' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Karim Fradi', 'karim.fradi.demo11@goservice.test', '20101112', '2026-04-25 19:25:00', 'confirme' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Aicha Jomaa', 'aicha.jomaa.demo11@goservice.test', '20101113', '2026-04-26 09:15:00', 'en attente' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Yosra Zribi', 'yosra.zribi.demo11@goservice.test', '20101114', '2026-04-26 10:25:00', 'confirme' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Bassem Naija', 'bassem.naija.demo11@goservice.test', '20101115', '2026-04-26 11:10:00', 'confirme' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Eya Toumi', 'eya.toumi.demo11@goservice.test', '20101116', '2026-04-27 13:40:00', 'en attente' FROM evenement WHERE titre = 'GS - Webinaire optimisation eau' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Olfa Khlifi', 'olfa.khlifi.demo12@goservice.test', '20101211', '2026-04-29 09:00:00', 'confirme' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Samiha Belhaj', 'samiha.belhaj.demo12@goservice.test', '20101212', '2026-04-29 09:30:00', 'confirme' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Nawel Kooli', 'nawel.kooli.demo12@goservice.test', '20101213', '2026-04-29 10:20:00', 'confirme' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Atef Ben Amor', 'atef.benamor.demo12@goservice.test', '20101214', '2026-04-29 11:40:00', 'en attente' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Leila Mhamdi', 'leila.mhamdi.demo12@goservice.test', '20101215', '2026-04-29 14:05:00', 'confirme' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Khaled Jerad', 'khaled.jerad.demo12@goservice.test', '20101216', '2026-04-29 15:25:00', 'confirme' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sonia Ktari', 'sonia.ktari.demo12@goservice.test', '20101217', '2026-04-29 16:10:00', 'en attente' FROM evenement WHERE titre = 'GS - Expo services multispecialistes' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Ikram Meherzi', 'ikram.meherzi.demo13@goservice.test', '20101311', '2026-04-02 08:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Tournee menuiserie mobile' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Noureddine Ben Chaaben', 'noureddine.benchaaben.demo13@goservice.test', '20101312', '2026-04-02 09:15:00', 'confirme' FROM evenement WHERE titre = 'GS - Tournee menuiserie mobile' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Lobna Ben Salem', 'lobna.bensalem.demo13@goservice.test', '20101313', '2026-04-02 11:05:00', 'annule' FROM evenement WHERE titre = 'GS - Tournee menuiserie mobile' LIMIT 1;

INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Sirine Mhamdi', 'sirine.mhamdi.demo14@goservice.test', '20101411', '2026-04-24 18:15:00', 'annule' FROM evenement WHERE titre = 'GS - Soiree travaux d ete' LIMIT 1;
INSERT INTO participation (id_evenement, nom_participant, email_participant, telephone, date_inscription, statut_participation)
SELECT id_evenement, 'Marouen Belkadhi', 'marouen.belkadhi.demo14@goservice.test', '20101412', '2026-04-24 18:40:00', 'annule' FROM evenement WHERE titre = 'GS - Soiree travaux d ete' LIMIT 1;

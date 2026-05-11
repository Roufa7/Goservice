-- Sample Data for GoService Platform

-- Insert Sample Users
INSERT INTO users (nom, prenom, email, password, role, telephone, adresse, bio, statut) VALUES
('Lassaoui', 'Emna', 'emna@goservice.com', '123456', 'admin', '21601234567', 'Tunis, Esprit', 'Admin de la plateforme GoService', 'actif'),
('Bouafif', 'Amina', 'amina@goservice.com', '123456', 'provider', '21698765432', 'Ariana, Tunis', 'Avocate spécialisée en droit civil', 'actif'),
('Saleh', 'Mohamed', 'msaleh@goservice.com', '123456', 'provider', '21612345678', 'Ben Arous, Tunis', 'Plombier expérimenté - 15 ans', 'actif'),
('Ben Khaled', 'Tayssir', 'tayssir@goservice.com', '123456', 'provider', '21605555555', 'Manouba, Tunis', 'Chef cuisinier professionnel', 'actif'),
('Roufa', 'Raef', 'raef@goservice.com', '123456', 'utilisateur', '21699999999', 'Sfax, Tunisie', 'Cherche services de qualité', 'actif'),
('Zouari', 'Baha', 'baha@goservice.com', '123456', 'utilisateur', '21611111111', 'Kasserine, Tunisie', 'Passionné par les événements', 'actif'),
('Bouameur', 'Ali', 'ali@goservice.com', '123456', 'provider', '21613333333', 'Tunis', 'Designer graphique et web', 'actif'),
('Hassan', 'Sara', 'sara@goservice.com', '123456', 'utilisateur', '21614444444', 'Tunis', 'Besoin de services divers', 'actif');

-- Insert Sample Services
INSERT INTO services (titre, description, categorie, prix, provider_id, rating, statut) VALUES
('Consultation juridique', 'Consultation en droit civil et commercial', 'Juridique', 80.00, 2, 4.5, 'disponible'),
('Plomberie générale', 'Réparation et installation de tuyauterie', 'Plomberie', 50.00, 3, 4.8, 'disponible'),
('Cuisine à domicile', 'Préparation de repas personnalisés pour événements', 'Cuisine', 120.00, 4, 5.0, 'disponible'),
('Design graphique', 'Création de logos et supports visuels', 'Design', 150.00, 7, 4.7, 'disponible'),
('Nettoyage résidentiel', 'Service de nettoyage complet des maisons', 'Nettoyage', 60.00, 3, 4.3, 'disponible');

-- Insert Sample Events
INSERT INTO events (titre, description, date_event, lieu, organisateur_id, participant_count, statut) VALUES
('Webinaire sur l''entrepreneuriat', 'Conférence en ligne sur comment créer son entreprise', '2026-04-20 18:00:00', 'En ligne', 2, 45, 'a_venir'),
('Meetup Développeurs TN', 'Rencontre de développeurs à Tunis', '2026-04-25 17:00:00', 'Esprit School, Tunis', 1, 120, 'a_venir'),
('Atelier de cuisine', 'Apprendre les bases de la cuisine professionnelle', '2026-05-01 15:00:00', 'Cuisine centrale, Ariana', 4, 20, 'a_venir');

-- Insert Sample Forum Posts
INSERT INTO forum_posts (titre, contenu, auteur_id, categorie, views, replies) VALUES
('Comment trouver un bon plombier?', 'Besoin d''aide pour identifier un bon prestataire de services de plomberie', 6, 'Services', 45, 8),
('Meilleurs services de design graphique', 'Discussion sur les meilleures agences de design en Tunisie', 8, 'Design', 78, 12),
('Organisation d''événements en ligne', 'Comment organiser un événement virtuel réussi?', 5, 'Événements', 32, 5);

-- Insert Sample Forum Comments
INSERT INTO forum_comments (post_id, auteur_id, contenu) VALUES
(1, 4, 'Je recommande vraiment les services de plomberie locale, très professionnel!'),
(1, 3, 'Consulter les avis sur Google Maps aide beaucoup'),
(2, 7, 'Notre agence propose des services de design de qualité premium'),
(3, 4, 'Les plateformes Zoom et Teams marchent très bien pour cela');

-- Insert Sample Offers
INSERT INTO offers (titre, description, prix, service_id, creator_id, statut, date_debut, date_fin) VALUES
('Réduction consultation juridique', '20% de réduction sur première consultation', 64.00, 1, 2, 'active', '2026-04-13', '2026-04-30'),
('Pack plomberie spécial', 'Reparation + révision = 90€ au lieu de 120€', 90.00, 2, 3, 'active', '2026-04-13', '2026-05-13'),
('Catering pour événements', '10% de réduction pour contrats supérieurs à 5 jours', 108.00, 3, 4, 'active', '2026-04-13', '2026-05-13');

-- Insert Sample Reclamations
INSERT INTO reclamations (titre, description, user_id, service_id, type, priorite, statut) VALUES
('Service non complété', 'Le service a été payé mais non exécuté correctement', 6, 1, 'Qualité', 'haute', 'ouverte'),
('Retard important', 'Le prestataire est arrivé 2 heures en retard', 8, 2, 'Horaire', 'normale', 'en_cours'),
('Problème de facturation', 'Facture ne correspond pas au devis initial', 5, 3, 'Facturation', 'normale', 'ouverte');

-- Insert Sample Bookings
INSERT INTO bookings (user_id, service_id, date_reservation, statut, notes) VALUES
(5, 1, '2026-04-20 10:00:00', 'confirmee', 'Consultation en droit des contrats'),
(6, 2, '2026-04-18 14:00:00', 'en_attente', 'Fuite d''eau urgent'),
(8, 3, '2026-05-05 19:00:00', 'confirmee', 'Repas pour 10 personnes'),
(5, 4, '2026-04-25 09:00:00', 'confirmee', 'Création d''un logo pour entreprise');

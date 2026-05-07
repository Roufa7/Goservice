# Analyse de la tâche "Utilisateur" (GoService)

J'ai analysé en profondeur tout le code concernant la gestion des utilisateurs (Inscription, Connexion, Profil, CRUD Admin, et Face ID). Voici un bilan complet de ce qui a été bien fait et des problèmes potentiels à résoudre.

## ✅ Ce qui est bien implémenté (Points forts)

1. **Sécurité contre les Injections SQL** : Toutes les requêtes dans `model/User.php` utilisent des requêtes préparées avec PDO (`$this->pdo->prepare(...)`). C'est l'état de l'art pour éviter le piratage des bases de données.
2. **Sécurité des Mots de Passe** : Les mots de passe sont correctement hachés avec `password_hash()` et vérifiés avec `password_verify()`. Aucun mot de passe n'est stocké en texte clair.
3. **Sécurité de Récupération (Forgot Password)** : L'utilisation de `random_bytes(32)` pour générer des tokens, et la fonction `hash_equals()` pour éviter les attaques temporelles (timing attacks) lors de la vérification est digne d'un système en production. L'envoi direct via Brevo sécurise le processus.
4. **Architecture MVC** : La séparation entre le Modèle (`User.php`), les Vues (`login.php`, `register.php`), et l'interface de contrôle (`AuthController.php`) est respectée.

## ⚠️ Problèmes et Améliorations Nécessaires (À Corriger)

### 1. Faiblesse de la Validation des Données (Input Validation)
Actuellement, lors de l'inscription ou de la modification du profil, le code récupère les données avec `$_POST['email'] ?? ''` et les insère directement dans la base.
* **Problème** : Un utilisateur pourrait s'inscrire avec un email faux ("ceci_nest_pas_un_email") ou un numéro de téléphone invalide.
* **Solution** : Ajouter des validations strictes dans `AuthController.php` et `ProfileController.php` (ex: `filter_var($email, FILTER_VALIDATE_EMAIL)`).

### 2. Le CAPTCHA Cassé
* **Problème** : L'extension PHP `GD` n'étant pas activée sur votre XAMPP, nous avons dû désactiver le système anti-robot (CAPTCHA) maison.
* **Solution** : Soit l'activer via `php.ini`, soit intégrer un système externe robuste comme **Google reCAPTCHA** qui ne nécessite pas d'extensions spéciales côté serveur.

### 3. Gestion Limitée du Rôle "Admin"
Dans `AuthController.php`, lorsqu'un utilisateur régulier s'inscrit, on accepte le rôle envoyé par le formulaire : `$role = $_POST['role'] ?? 'user';`.
* **Problème (Vulnérabilité critique possible)** : Si un pirate modifie le code HTML de votre formulaire d'inscription et ajoute `<input type="hidden" name="role" value="admin">`, il se créera un compte avec les pleins pouvoirs (Droits administrateurs).
* **Solution** : Forcer statiquement le rôle `"user"` ou `"provider"` lors de l'action de registre dans l'Auth Controller public. N'autorisez la création d'Admins que depuis le back-office.

### 4. Face Service (Python) Exposé
* **Problème** : Votre API Python (Flask) pour le Face ID écoute sur le port 5000. Elle ne vérifie pas l'origine de la requête. N'importe qui sur le réseau pourrait théoriquement envoyer des photos à `/verify`.
* **Solution** : Ajouter une clé secrète d'API (API Key) entre le serveur PHP et le service Flask Python pour que seul PHP puisse lui parler.

## 🛠️ Recommandation de Priorié
Si vous souhaitez que je corrige quelque chose immédiatement, je vous conseille vivement de me laisser corriger la **vulnérabilité du rôle Admin (Point 3)**, car elle compromet l'ensemble de votre base de données.

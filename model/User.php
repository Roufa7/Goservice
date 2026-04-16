<?php
require_once __DIR__ . '/../config.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    public function register($nom, $prenom, $email, $password, $telephone, $adresse, $role, $photo = '') {
        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id_user FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'L\'email est déjà utilisé.'];
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $stmt = $this->pdo->prepare("
                INSERT INTO users (nom, prenom, email, password, telephone, adresse, role, photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([$nom, $prenom, $email, $hashedPassword, $telephone, $adresse, $role, $photo]);

            if ($success) {
                $userId = $this->pdo->lastInsertId();

                // If user is a provider, create a provider record and an initial empty portfolio
                if ($role === 'provider') {
                    $stmtProvider = $this->pdo->prepare("INSERT INTO provider (id_user, disponibilite) VALUES (?, 1)");
                    if ($stmtProvider->execute([$userId])) {
                        $providerId = $this->pdo->lastInsertId();
                        // Initialize an empty portfolio for the provider
                        $stmtPortfolio = $this->pdo->prepare("INSERT INTO portfolio (id_provider) VALUES (?)");
                        $stmtPortfolio->execute([$providerId]);
                    }
                }

                return ['success' => true, 'message' => 'Inscription réussie.'];
            }

            return ['success' => false, 'message' => 'Une erreur s\'est produite lors de l\'inscription.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                return ['success' => true, 'user' => $user];
            }

            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        }
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY id_user DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteUser($id) {
        try {
            // Because of foreign keys, we should delete from portfolio/provider first 
            // if we want cascading logic, but usually DB constraints restrict or cascade automatically.
            // Let's manually clean up provider tables if user is a provider.
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id_user = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] === 'provider') {
                $pStmt = $this->pdo->prepare("SELECT id_provider FROM provider WHERE id_user = ?");
                $pStmt->execute([$id]);
                $provider = $pStmt->fetch();
                if ($provider) {
                    $this->pdo->prepare("DELETE FROM portfolio WHERE id_provider = ?")->execute([$provider['id_provider']]);
                    $this->pdo->prepare("DELETE FROM provider WHERE id_provider = ?")->execute([$provider['id_provider']]);
                }
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id_user = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateUser($id, $nom, $prenom, $email, $role) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, role = ? WHERE id_user = ?");
            return $stmt->execute([$nom, $prenom, $email, $role, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateProfile($id, $nom, $prenom, $email, $telephone, $adresse) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ? WHERE id_user = ?");
            return $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>

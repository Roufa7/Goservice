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
            $stmt = $this->pdo->prepare("SELECT id_user FROM user WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'L\'email est déjà utilisé.'];
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Start transaction
            $this->pdo->beginTransaction();

            // Insert into user table
            $stmt = $this->pdo->prepare("INSERT INTO user (email, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashedPassword, $role]);
            $userId = $this->pdo->lastInsertId();

            // Insert into profile table
            // Default disponibilite to 1 if provider, else NULL
            $dispo = ($role === 'provider') ? 1 : NULL;
            $stmtProfile = $this->pdo->prepare("
                INSERT INTO profile (id_user, nom, prenom, telephone, adresse, photo, disponibilite) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtProfile->execute([$userId, $nom, $prenom, $telephone, $adresse, $photo, $dispo]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Inscription réussie.'];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.email = ?
            ");
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
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
            FROM user u 
            JOIN profile p ON u.id_user = p.id_user 
            WHERE u.id_user = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
            FROM user u 
            JOIN profile p ON u.id_user = p.id_user 
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("
            SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
            FROM user u 
            JOIN profile p ON u.id_user = p.id_user 
            ORDER BY u.id_user DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteUser($id) {
        try {
            // Thanks to ON DELETE CASCADE on profile.id_user, 
            // deleting from user will automatically delete from profile.
            $stmt = $this->pdo->prepare("DELETE FROM user WHERE id_user = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateUser($id, $nom, $prenom, $email, $role) {
        try {
            $this->pdo->beginTransaction();
            
            $stmtUser = $this->pdo->prepare("UPDATE user SET email = ?, role = ? WHERE id_user = ?");
            $stmtUser->execute([$email, $role, $id]);
            
            $stmtProfile = $this->pdo->prepare("UPDATE profile SET nom = ?, prenom = ? WHERE id_user = ?");
            $stmtProfile->execute([$nom, $prenom, $id]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function updateProfile($id, $nom, $prenom, $email, $telephone, $adresse, $photo = null) {
        try {
            $this->pdo->beginTransaction();
            
            $stmtUser = $this->pdo->prepare("UPDATE user SET email = ? WHERE id_user = ?");
            $stmtUser->execute([$email, $id]);
            
            if ($photo !== null && $photo !== '') {
                $stmtProfile = $this->pdo->prepare("UPDATE profile SET nom = ?, prenom = ?, telephone = ?, adresse = ?, photo = ? WHERE id_user = ?");
                $stmtProfile->execute([$nom, $prenom, $telephone, $adresse, $photo, $id]);
            } else {
                $stmtProfile = $this->pdo->prepare("UPDATE profile SET nom = ?, prenom = ?, telephone = ?, adresse = ? WHERE id_user = ?");
                $stmtProfile->execute([$nom, $prenom, $telephone, $adresse, $id]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function getUserStats() {
        try {
            $stats = [
                'total' => 0,
                'admin' => 0,
                'provider' => 0,
                'user' => 0
            ];
            
            $stmt = $this->pdo->query("SELECT role, COUNT(*) as count FROM user GROUP BY role");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['role']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            return $stats;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function searchUsers($query = '', $role = '', $sortBy = 'id_user', $order = 'DESC') {
        try {
            $sql = "
                SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE 1=1
            ";
            $params = [];

            if (!empty($query)) {
                $sql .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR u.email LIKE ? OR p.telephone LIKE ?)";
                $searchQuery = "%$query%";
                $params = array_merge($params, [$searchQuery, $searchQuery, $searchQuery, $searchQuery]);
            }

            if (!empty($role) && $role !== 'all') {
                $sql .= " AND u.role = ?";
                $params[] = $role;
            }

            // Map sort selection to real columns
            $allowedSort = [
                'id_user' => 'u.id_user',
                'nom' => 'p.nom',
                'email' => 'u.email',
                'role' => 'u.role'
            ];
            $sortColumn = $allowedSort[$sortBy] ?? 'u.id_user';
            $order = ($order === 'ASC') ? 'ASC' : 'DESC';

            $sql .= " ORDER BY $sortColumn $order";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getProfileStats($userId) {
        try {
            $stats = [
                'services_count' => 0,
                'offers_count' => 0,
                'events_count' => 0
            ];

            // Count services with either the newer id_user link or the provider relation.
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM service WHERE id_user = ?");
                $stmt->execute([$userId]);
                $stats['services_count'] = (int)$stmt->fetchColumn();
            } catch (PDOException $e) {
                $stats['services_count'] = 0;
            }

            if ($stats['services_count'] === 0) {
                try {
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*)
                        FROM service s
                        INNER JOIN provider p ON p.id_provider = s.id_provider
                        WHERE p.id_user = ?
                    ");
                    $stmt->execute([$userId]);
                    $stats['services_count'] = (int)$stmt->fetchColumn();
                } catch (PDOException $e) {
                    $stats['services_count'] = 0;
                }
            }

            // Count offers/applications (candidature)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_user = ?");
            $stmt->execute([$userId]);
            $stats['offers_count'] = (int)$stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getRegistrationTrends() {
        try {
            // Get registrations for the last 30 days
            $stmt = $this->pdo->query("
                SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM user 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllProviders() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, p.nom, p.prenom, p.telephone, p.adresse, p.photo, p.disponibilite 
                FROM user u 
                JOIN profile p ON u.id_user = p.id_user 
                WHERE u.role = 'provider'
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getGlobalCounts() {
        try {
            $counts = [
                'services' => 0,
                'events' => 0,
                'users' => 0
            ];
            
            $counts['users'] = (int)$this->pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
            $counts['services'] = (int)$this->pdo->query("SELECT COUNT(*) FROM service")->fetchColumn();
            $counts['events'] = (int)$this->pdo->query("SELECT COUNT(*) FROM evenement")->fetchColumn(); // Note: adjust table name if needed
            
            return $counts;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * ADVANCED BUSINESS LOGIC: Password Reset System
     */
    
    public function createPasswordResetToken($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) return null;

            // Generate a secure random token
            $rawToken = bin2hex(random_bytes(32));
            
            // Hash the token for DB storage (Security best practice)
            $hashedToken = hash('sha256', $rawToken);
            
            // Set expiration to 1 hour from now
            $expiresAt = date("Y-m-d H:i:s", strtotime('+1 hour'));

            $stmt = $this->pdo->prepare("UPDATE user SET reset_token = ?, reset_expires_at = ? WHERE id_user = ?");
            $stmt->execute([$hashedToken, $expiresAt, $user['id_user']]);

            return $rawToken;
        } catch (Exception $e) {
            return null;
        }
    }

    public function validateResetToken($rawToken) {
        try {
            $hashedToken = hash('sha256', $rawToken);
            
            $stmt = $this->pdo->prepare("SELECT id_user, reset_expires_at FROM user WHERE reset_token = ?");
            $stmt->execute([$hashedToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Check if expired
                if (strtotime($user['reset_expires_at']) > time()) {
                    return $user['id_user'];
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function resetPasswordWithToken($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id_user = ?");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>

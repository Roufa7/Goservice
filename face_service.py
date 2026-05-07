from flask import Flask, request, jsonify
from flask_cors import CORS
import mysql.connector
import numpy as np
import json
from datetime import datetime

app = Flask(__name__)
CORS(app) # Allow CORS for the PHP/JS frontend

# Database configuration - pointing to your MySQL
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'goservice' # Replace with your real DB name if different
}

def get_db_connection():
    return mysql.connector.connect(**db_config)

@app.route('/enroll', methods=['POST'])
def enroll():
    data = request.json
    user_id = data.get('user_id')
    descriptor = data.get('descriptor')
    
    if not user_id or not descriptor:
        return jsonify({"success": False, "message": "Données manquantes"}), 400

    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Insert or update face data
        descriptor_json = json.dumps(descriptor)
        query = """
            INSERT INTO user_face_data (id_user, face_descriptor, enrollment_ip)
            VALUES (%s, %s, %s)
            ON DUPLICATE KEY UPDATE face_descriptor = %s, enrollment_date = NOW()
        """
        cursor.execute(query, (user_id, descriptor_json, request.remote_addr, descriptor_json))
        
        conn.commit()
        cursor.close()
        conn.close()
        
        return jsonify({"success": True, "message": "Visage enrôlé avec succès"})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500

@app.route('/verify', methods=['POST'])
def verify():
    data = request.json
    user_id = data.get('user_id')
    input_descriptor = np.array(data.get('descriptor'))
    
    if not user_id or input_descriptor is None:
        return jsonify({"match": False, "message": "Données de vérification incomplètes"}), 400

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("SELECT * FROM user_face_data WHERE id_user = %s AND is_active = 1", (user_id,))
        record = cursor.fetchone()
        
        if not record:
            return jsonify({"match": False, "message": "Face ID non activé pour ce compte"}), 404

        # Euclidean distance check
        stored_descriptor = np.array(json.loads(record['face_descriptor']))
        distance = np.linalg.norm(input_descriptor - stored_descriptor)
        
        # Apply threshold (0.6 is common for face-recognition-net, lower is stricter)
        threshold = record['confidence_threshold']
        is_match = distance < threshold
        
        # Update attempts and dates
        new_failed = 0 if is_match else record['failed_attempts'] + 1
        update_query = """
            UPDATE user_face_data 
            SET last_verified_at = %s, 
                verification_attempts = verification_attempts + 1,
                failed_attempts = %s,
                last_verified_ip = %s
            WHERE id_user = %s
        """
        cursor.execute(update_query, (datetime.now(), new_failed, request.remote_addr, user_id))
        
        conn.commit()
        cursor.close()
        conn.close()
        
        return jsonify({
            "match": is_match,
            "confidence": round(float(1 - (distance / 2)), 4),
            "attempts": new_failed,
            "message": "Match réussi" if is_match else "Visage non reconnu"
        })
        
    except Exception as e:
        return jsonify({"match": False, "message": str(e)}), 500

if __name__ == '__main__':
    # Initial table check/creation
    try:
        c = get_db_connection()
        curr = c.cursor()
        curr.execute("""
            CREATE TABLE IF NOT EXISTS `user_face_data` (
              `id_user` INT PRIMARY KEY,
              `face_descriptor` JSON NOT NULL,
              `confidence_threshold` FLOAT DEFAULT 0.45,
              `enrollment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `last_verified_at` DATETIME NULL,
              `verification_attempts` INT DEFAULT 0,
              `failed_attempts` INT DEFAULT 0,
              `is_active` TINYINT(1) DEFAULT 1,
              `device_info` VARCHAR(255),
              `enrollment_ip` VARCHAR(45),
              `last_verified_ip` VARCHAR(45)
            ) ENGINE=InnoDB;
        """)
        c.commit()
        c.close()
    except:
        print("Note: Table check failed, make sure database exists.")
        
    app.run(port=5001, debug=True)

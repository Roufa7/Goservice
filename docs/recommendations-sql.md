Recommendation SQL Samples
These sample queries match the implemented recommendation logic.
1) Fetch user preference/localisation
```sql
SELECT
  u.id_user AS user_id,
  u.preferred_type_service,
  u.localisation
FROM users u
WHERE u.id_user = :user_id
LIMIT 1;
```
2) Fetch offers already applied by user (exclude from recommendations)
```sql
SELECT c.id_offre
FROM candidature c
WHERE c.id_user = :user_id;
```
3) Build history signals from applications
```sql
SELECT
  o.type_service,
  o.localisation,
  COUNT(*) AS weight
FROM candidature c
JOIN offre o ON o.id_offre = c.id_offre
WHERE c.id_user = :user_id
GROUP BY o.type_service, o.localisation;
```
4) Candidate active offers
```sql
SELECT
  o.id_offre AS id,
  o.type_service,
  o.localisation,
  o.date_publication AS date_post
FROM offre o
WHERE o.statut = 'ouverte';
```
5) Scored recommendation query (single SQL variant)
```sql
SELECT
  o.id_offre AS id,
  o.type_service,
  o.localisation,
  (
    CASE WHEN LOWER(o.type_service) = LOWER(:preferred_type) THEN 50 ELSE 0 END
    + CASE WHEN LOWER(o.localisation) = LOWER(:user_city) THEN 30 ELSE 0 END
    + CASE
        WHEN EXISTS (
          SELECT 1
          FROM candidature c2
          JOIN offre o2 ON o2.id_offre = c2.id_offre
          WHERE c2.id_user = :user_id
            AND (
              LOWER(o2.type_service) = LOWER(o.type_service)
              OR LOWER(o2.localisation) = LOWER(o.localisation)
            )
        ) THEN 20 ELSE 0
      END
  ) AS score
FROM offre o
WHERE o.statut = 'ouverte'
  AND o.id_offre NOT IN (
    SELECT c.id_offre
    FROM candidature c
    WHERE c.id_user = :user_id
  )
ORDER BY
  CASE
    WHEN LOWER(o.type_service) = LOWER(:preferred_type)
         AND LOWER(o.localisation) = LOWER(:user_city) THEN 1
    WHEN LOWER(o.type_service) = LOWER(:preferred_type) THEN 2
    ELSE 3
  END ASC,
  score DESC,
  o.date_publication DESC
LIMIT 10;
```
CREATE OR REPLACE VIEW personas_en_el_barrio AS

-- PROPIETARIOS
SELECT
    o.id AS id,
    o.first_name,
    o.last_name,
    'Propietario' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = o.id
    ) AS lote,
    'Owner' AS model,
    o.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = o.id AND ap.model = 'Owner' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM owners o
WHERE o.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'Owner'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- FAMILIARES
SELECT
    of.id AS id,
    of.first_name,
    of.last_name,
    'Familiar' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = of.owner_id
    ) AS lote,
    'OwnerFamily' AS model,
    of.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = of.id AND ap.model = 'OwnerFamily' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM owner_families of
WHERE of.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'OwnerFamily'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- VISITANTES ESPONTÁNEOS
SELECT
    osv.id AS id,
    osv.first_name,
    osv.last_name,
    'Visita espontánea' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = osv.owner_id
    ) AS lote,
    'OwnerSpontaneousVisit' AS model,
    osv.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = osv.id AND ap.model = 'OwnerSpontaneousVisit' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM owner_spontaneous_visits osv
WHERE osv.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'OwnerSpontaneousVisit'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- EMPLEADOS
SELECT
    e.id AS id,
    e.first_name,
    e.last_name,
    'Empleado' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = e.owner_id
    ) AS lote,
    'Employee' AS model,
    e.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = e.id AND ap.model = 'Employee' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM employees e
WHERE e.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'Employee'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- VISITANTES (general, playa, house)
SELECT
    fcp.id AS id,
    fcp.first_name,
    fcp.last_name,
    'Visitante' AS tipo,
    TRIM(BOTH '[]"' FROM REPLACE(REPLACE(fc.lote_ids, '\"', ''), '],[', ', ')) AS lote,
    'FormControl' AS model,
    fcp.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = fcp.id AND ap.model = 'FormControl' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM form_control_people fcp
JOIN form_controls fc ON fcp.form_control_id = fc.id
WHERE (
    fc.access_type LIKE '%general%'
    OR fc.access_type LIKE '%playa%'
    OR fc.access_type LIKE '%house%'
)
AND fcp.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'FormControl'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- INQUILINOS (lote)
SELECT
    fcp.id AS id,
    fcp.first_name,
    fcp.last_name,
    'Inquilino' AS tipo,
    TRIM(BOTH '[]"' FROM REPLACE(REPLACE(fc.lote_ids, '\"', ''), '],[', ', ')) AS lote,
    'FormControl' AS model,
    fcp.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = fcp.id AND ap.model = 'FormControl' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM form_control_people fcp
JOIN form_controls fc ON fcp.form_control_id = fc.id
WHERE fc.access_type LIKE '%lote%'
  AND fc.income_type LIKE '%Inquilino%'
  AND fcp.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'FormControl'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- TRABAJADORES (lote)
SELECT
    fcp.id AS id,
    fcp.first_name,
    fcp.last_name,
    'Trabajador' AS tipo,
    TRIM(BOTH '[]"' FROM REPLACE(REPLACE(fc.lote_ids, '\"', ''), '],[', ', ')) AS lote,
    'FormControl' AS model,
    fcp.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = fcp.id AND ap.model = 'FormControl' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM form_control_people fcp
JOIN form_controls fc ON fcp.form_control_id = fc.id
WHERE fc.access_type LIKE '%lote%'
  AND fc.income_type LIKE '%Trabajador%'
  AND fcp.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'FormControl'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)

UNION ALL

-- VISITAS (lote)
SELECT
    fcp.id AS id,
    fcp.first_name,
    fcp.last_name,
    'Visita' AS tipo,
    TRIM(BOTH '[]"' FROM REPLACE(REPLACE(fc.lote_ids, '\"', ''), '],[', ', ')) AS lote,
    'FormControl' AS model,
    fcp.id AS model_id,
    (
        SELECT MAX(a.created_at)
        FROM activities_people ap
        JOIN activities a ON ap.activities_id = a.id
        WHERE ap.model_id = fcp.id AND ap.model = 'FormControl' AND a.type = 'Entry'
    ) AS ultima_entrada
FROM form_control_people fcp
JOIN form_controls fc ON fcp.form_control_id = fc.id
WHERE fc.access_type LIKE '%lote%'
  AND fc.income_type LIKE '%Visita%'
  AND fcp.id IN (
    SELECT ap.model_id
    FROM activities_people ap
    JOIN activities a ON ap.activities_id = a.id
    WHERE ap.model = 'FormControl'
    GROUP BY ap.model_id
    HAVING SUM(CASE WHEN a.type = 'Entry' THEN 1 ELSE 0 END) > SUM(CASE WHEN a.type = 'Exit' THEN 1 ELSE 0 END)
)
;

CREATE OR REPLACE VIEW personas_en_el_barrio AS

-- La presencia se determina por el ultimo movimiento de cada identidad
-- (fecha y, en caso de empate, id), igual que en el monitor de accesos.

-- PROPIETARIOS
SELECT
    CONCAT('Owner-', o.id) AS id,
    o.first_name,
    o.last_name,
    o.dni,
    'Propietario' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = o.id
    ) AS lote,
    'Owner' AS model,
    o.id AS model_id,
    a.created_at AS ultima_entrada
FROM activities_people ap
JOIN activities a ON a.id = ap.activities_id
JOIN owners o ON o.id = ap.model_id
WHERE ap.model = 'Owner'
  AND ap.deleted_at IS NULL
  AND a.type = 'Entry'
  AND NOT EXISTS (
      SELECT 1
      FROM activities_people later_ap
      JOIN activities later_a ON later_a.id = later_ap.activities_id
      WHERE later_ap.model = ap.model
        AND later_ap.model_id = ap.model_id
        AND later_ap.deleted_at IS NULL
        AND (
            later_a.created_at > a.created_at
            OR (later_a.created_at = a.created_at AND later_ap.id > ap.id)
        )
  )

UNION ALL

-- FAMILIARES
SELECT
    CONCAT('OwnerFamily-', ofa.id) AS id,
    ofa.first_name,
    ofa.last_name,
    ofa.dni,
    'Familiar' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = ofa.owner_id
    ) AS lote,
    'OwnerFamily' AS model,
    ofa.id AS model_id,
    a.created_at AS ultima_entrada
FROM activities_people ap
JOIN activities a ON a.id = ap.activities_id
JOIN owner_families ofa ON ofa.id = ap.model_id
WHERE ap.model = 'OwnerFamily'
  AND ap.deleted_at IS NULL
  AND a.type = 'Entry'
  AND NOT EXISTS (
      SELECT 1
      FROM activities_people later_ap
      JOIN activities later_a ON later_a.id = later_ap.activities_id
      WHERE later_ap.model = ap.model
        AND later_ap.model_id = ap.model_id
        AND later_ap.deleted_at IS NULL
        AND (
            later_a.created_at > a.created_at
            OR (later_a.created_at = a.created_at AND later_ap.id > ap.id)
        )
  )

UNION ALL

-- VISITANTES ESPONTÁNEOS
SELECT
    CONCAT('OwnerSpontaneousVisit-', osv.id) AS id,
    osv.first_name,
    osv.last_name,
    osv.dni,
    'Visita espontánea' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = osv.owner_id
    ) AS lote,
    'OwnerSpontaneousVisit' AS model,
    osv.id AS model_id,
    a.created_at AS ultima_entrada
FROM activities_people ap
JOIN activities a ON a.id = ap.activities_id
JOIN owner_spontaneous_visits osv ON osv.id = ap.model_id
WHERE ap.model = 'OwnerSpontaneousVisit'
  AND ap.deleted_at IS NULL
  AND a.type = 'Entry'
  AND NOT EXISTS (
      SELECT 1
      FROM activities_people later_ap
      JOIN activities later_a ON later_a.id = later_ap.activities_id
      WHERE later_ap.model = ap.model
        AND later_ap.model_id = ap.model_id
        AND later_ap.deleted_at IS NULL
        AND (
            later_a.created_at > a.created_at
            OR (later_a.created_at = a.created_at AND later_ap.id > ap.id)
        )
  )

UNION ALL

-- EMPLEADOS
SELECT
    CONCAT('Employee-', e.id) AS id,
    e.first_name,
    e.last_name,
    e.dni,
    'Empleado' AS tipo,
    (
        SELECT GROUP_CONCAT(CONCAT(s.name, l.lote_id) SEPARATOR ', ')
        FROM lotes l
        JOIN sectors s ON l.sector_id = s.id
        WHERE l.owner_id = e.owner_id
    ) AS lote,
    'Employee' AS model,
    e.id AS model_id,
    a.created_at AS ultima_entrada
FROM activities_people ap
JOIN activities a ON a.id = ap.activities_id
JOIN employees e ON e.id = ap.model_id
WHERE ap.model = 'Employee'
  AND ap.deleted_at IS NULL
  AND a.type = 'Entry'
  AND NOT EXISTS (
      SELECT 1
      FROM activities_people later_ap
      JOIN activities later_a ON later_a.id = later_ap.activities_id
      WHERE later_ap.model = ap.model
        AND later_ap.model_id = ap.model_id
        AND later_ap.deleted_at IS NULL
        AND (
            later_a.created_at > a.created_at
            OR (later_a.created_at = a.created_at AND later_ap.id > ap.id)
        )
  )

UNION ALL

-- PERSONAS DE FORMULARIOS. Una sola rama evita que una misma persona sea
-- Visitante e Inquilino/Trabajador/Visita cuando access_type tiene varios valores.
SELECT
    CONCAT('FormControl-', fcp.id) AS id,
    fcp.first_name,
    fcp.last_name,
    fcp.dni,
    CASE
        WHEN fc.access_type LIKE '%lote%' AND fc.income_type LIKE '%Inquilino%' THEN 'Inquilino'
        WHEN fc.access_type LIKE '%lote%' AND fc.income_type LIKE '%Trabajador%' THEN 'Trabajador'
        WHEN fc.access_type LIKE '%lote%' AND fc.income_type LIKE '%Visita%' THEN 'Visita'
        ELSE 'Visitante'
    END AS tipo,
    TRIM(BOTH '[]"' FROM REPLACE(REPLACE(fc.lote_ids, '\"', ''), '],[', ', ')) AS lote,
    'FormControl' AS model,
    fcp.id AS model_id,
    a.created_at AS ultima_entrada
FROM activities_people ap
JOIN activities a ON a.id = ap.activities_id
JOIN form_control_people fcp ON fcp.id = ap.model_id
JOIN form_controls fc ON fc.id = fcp.form_control_id
WHERE ap.model = 'FormControl'
  AND ap.deleted_at IS NULL
  AND a.type = 'Entry'
  AND (
      fc.access_type LIKE '%general%'
      OR fc.access_type LIKE '%playa%'
      OR fc.access_type LIKE '%house%'
      OR fc.access_type LIKE '%lote%'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM activities_people later_ap
      JOIN activities later_a ON later_a.id = later_ap.activities_id
      WHERE later_ap.model = ap.model
        AND later_ap.model_id = ap.model_id
        AND later_ap.deleted_at IS NULL
        AND (
            later_a.created_at > a.created_at
            OR (later_a.created_at = a.created_at AND later_ap.id > ap.id)
        )
  )
;

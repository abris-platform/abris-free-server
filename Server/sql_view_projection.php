<?php

//$view_projection_entity = "SELECT entity.table_name AS projection_name,  entity.title,  entity.table_name AS jump,  entity.primarykey,  NULL AS additional,  CASE WHEN entity.table_name = 'entity_type'      OR entity.table_name = 'function_type'      OR entity.table_name = 'column_type'      OR entity.table_name = 'menu'    THEN TRUE     ELSE (NOT has_table_privilege(entity.entity_id, 'INSERT, UPDATE, DELETE'))  END AS readonly,  NULL AS hint,  entity.schema_name AS table_schema,  entity.table_name AS table_name FROM  (  SELECT v.oid AS entity_id,      n.nspname::text AS schema_name,      v.relname::text AS table_name,      COALESCE(obj_description(v.oid), v.relname::text) AS title,      CASE WHEN b.base_entity_cnt = 1          THEN b.base_entity_key::text          ELSE NULL::text      END AS primarykey,      v.relkind::text AS table_type,      pg_get_viewdef(v.oid) AS view_definition  FROM pg_class v  LEFT JOIN pg_namespace n ON n.oid = v.relnamespace  LEFT JOIN   (      SELECT t.refobjid AS entity_id,          t.obj AS base_entity_id,          t.obj_cnt AS base_entity_cnt,          at.attname::text AS base_entity_key      FROM       (          SELECT dv.refobjid,              count(dt.refobjid) AS obj_cnt,              min(dt.refobjid) AS obj          FROM pg_depend dv          JOIN pg_depend dt ON dv.objid = dt.objid AND dv.refobjid <> dt.refobjid AND dt.classid = 'pg_rewrite'::regclass::oid AND dt.refclassid = 'pg_class'::regclass::oid          WHERE dv.refclassid = 'pg_class'::regclass::oid AND dv.classid = 'pg_rewrite'::regclass::oid AND dv.deptype = 'i'::\"char\"          GROUP BY dv.refobjid      ) t      JOIN pg_class n_1 ON n_1.oid = t.obj AND n_1.relkind = 'r'::\"char\"      LEFT JOIN pg_constraint c ON c.conrelid = n_1.oid AND c.contype = 'p'::\"char\"      LEFT JOIN pg_attribute at ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid      LEFT JOIN pg_namespace ns ON ns.oid = n_1.relnamespace  ) b ON v.oid = b.entity_id  WHERE (v.relkind = ANY (ARRAY['v'::\"char\"])) AND (pg_has_role(v.relowner, 'USAGE'::text) OR has_table_privilege(v.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)    OR has_any_column_privilege(v.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))  UNION ALL  SELECT r.oid AS entity_id,      n.nspname AS schema_name,      r.relname AS table_name,      COALESCE(obj_description(r.oid), r.relname::text) AS title,      CASE WHEN a.conrelid IS NOT NULL          THEN CASE WHEN array_length(a.pkeys, 1) = 1              THEN a.pkeys[1]::text              ELSE array_to_json(a.pkeys)::text              END          ELSE NULL::text      END AS primarykey,      r.relkind::text AS table_type,      NULL::text AS view_definition  FROM pg_class r  LEFT JOIN pg_namespace n ON n.oid = r.relnamespace  LEFT JOIN   (      SELECT pc.conrelid, array_agg(at.attname ORDER BY at.attname)::text[] as pkeys      FROM pg_constraint pc      LEFT JOIN pg_attribute at ON (at.attnum = ANY (pc.conkey)) AND at.attrelid = pc.conrelid      WHERE pc.contype = 'p'::\"char\"      GROUP BY pc.conrelid  ) a ON a.conrelid = r.oid  WHERE (r.relkind = ANY (ARRAY['r'::\"char\", 'f'::\"char\"])) AND (pg_has_role(r.relowner, 'USAGE'::text) OR has_table_privilege(r.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)    OR has_any_column_privilege(r.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text])) ) as entity",

//$view_projection_property = "SELECT property.property_name AS projection_property_name,  property.title,  property.type,  CASE WHEN v.relname = 'menu_item' AND property.column_name = 'menu_id'      OR v.relname = 'entity' AND property.column_name = 'entity_id'      OR v.relname = 'entity' AND property.column_name = 'table_type'      OR v.relname = 'entity_edit' AND property.column_name = 'entity_id'      OR v.relname = 'entity_edit' AND property.column_name = 'schema_name'      OR v.relname = 'entity_edit' AND property.column_name = 'table_name'      OR v.relname = 'entity_edit' AND property.column_name = 'title'      OR v.relname = 'entity_edit' AND property.column_name = 'primarykey'      OR v.relname = 'entity_edit' AND property.column_name = 'table_type'      OR v.relname = 'property' AND property.column_name = 'property_name'      OR v.relname = 'property' AND property.column_name = 'visible'      OR v.relname = 'property' AND property.column_name = 'readonly'      OR v.relname = 'property' AND property.column_name = '_order'      OR v.relname = 'property' AND property.column_name = 'constraint_name'      OR v.relname = 'relation' AND property.column_name = 'relation_name'      OR v.relname = 'functions' AND property.column_name = 'function_id'      OR v.relname = 'schema' AND property.column_name = 'schema_id'      OR v.relname = 'triggers' AND property.column_name = 'trigger_id'    THEN TRUE    ELSE property.readonly  END as readonly,  property.visible,  v.relname AS projection_name,  property.column_name,  property.ref_key,  r.relname AS ref_projection,  NULL::text AS link_key,  property._order,  property.ref_entity,  NULL::text AS ref_filter,  false AS concat_prev,  false AS virtual,  NULL::text AS virtual_src_projection,  NULL::text AS original_column_name,  NULL::text AS hint,  NULL::text AS pattern,  property.is_nullable,  property.\"default\",  CASE WHEN  v.relname = 'entity_type' AND property.column_name = 'note'      OR v.relname = 'menu_item' AND property.column_name = 'name'      OR v.relname = 'menu_item' AND property.column_name = 'title'      OR v.relname = 'options' AND property.column_name = 'name'      OR v.relname = 'property_type' AND property.column_name = 'type'      OR v.relname = 'property_type' AND property.column_name = 'note'      OR v.relname = 'column_type' AND property.column_name = 'data_type'      OR v.relname = 'entity' AND property.column_name = 'schema_name'      OR v.relname = 'entity' AND property.column_name = 'table_name'      OR v.relname = 'entity_edit' AND property.column_name = 'schema_name'      OR v.relname = 'entity_edit' AND property.column_name = 'table_name'      OR v.relname = 'function_type' AND property.column_name = 'rval_type'      OR v.relname = 'functions' AND property.column_name = 'function_schema'      OR v.relname = 'functions' AND property.column_name = 'function_name'      OR v.relname = 'functions' AND property.column_name = 'function_attributes'      OR v.relname = 'property' AND property.column_name = 'column_name'      OR v.relname = 'relation' AND property.column_name = 'relation_entity'      OR v.relname = 'relation' AND property.column_name = 'entity_id'      OR v.relname = 'relation' AND property.column_name = 'title'      OR v.relname = 'schema' AND property.column_name = 'schema_name'      OR v.relname = 'triggers' AND property.column_name = 'trigger_name'    THEN TRUE    WHEN property.type IN ('string', 'caption') THEN TRUE     ELSE FALSE  END AS show_in_refs,  NULL::text AS additional   FROM    (    SELECT      c.oid ||'.'|| a.attname AS property_name,      a.attname::TEXT                                                 AS column_name,      c.oid                                                           AS entity_id,      COALESCE        (          CASE            WHEN co.conkey[1] IS NOT NULL THEN 'ref'            WHEN a.atttypid = 2950::oid THEN 'invisible'            ELSE NULL::TEXT          END, 'string'      )                                                               AS type,      CASE        WHEN t.typelem <> 0 AND t.typlen = '-1' THEN 'ARRAY'        ELSE format_type(a.atttypid, NULL)      END::information_schema.character_data                          AS data_type,      true                                                            AS visible,      FALSE                                                           AS readonly,      COALESCE(d.description, a.attname::TEXT)                        AS title,      r.oid          \t\t\t\t\t\t\t\t\t\t\t\t\tAS ref_entity,      at.attname::TEXT\t\t\t\t\t\t\t  \t\t\t\t\tAS ref_key,      a.attnum * 10                                                   AS _order,      co.conname::information_schema.sql_identifier                   AS constraint_name,      NOT (a.attnotnull OR t.typtype = 'd'::\"char\" AND t.typnotnull)  AS is_nullable,      pg_get_expr(ad.adbin, ad.adrelid)                               AS \"default\",      CASE        WHEN t.typtype = 'd' THEN NULL        ELSE          CASE              WHEN a.atttypmod = '-1'                   THEN NULL              WHEN a.atttypid = ANY (ARRAY[1042, 1043]) THEN a.atttypmod-4              WHEN a.atttypid = ANY (ARRAY[1560, 1562]) THEN a.atttypmod                                                        ELSE NULL          END                                                                   END::INTEGER                                                    AS data_type_len    FROM pg_attribute a      LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum      JOIN (pg_class c        JOIN pg_namespace nc ON c.relnamespace = nc.oid) ON a.attrelid = c.oid        JOIN (pg_type t          JOIN pg_namespace nt ON t.typnamespace = nt.oid) ON a.atttypid = t.oid      LEFT JOIN (pg_constraint co      JOIN (pg_class r        LEFT JOIN pg_namespace nr ON r.relnamespace = nr.oid      JOIN (pg_constraint cr      JOIN pg_attribute at ON cr.conkey[1] = at.attnum AND at.attrelid = cr.conrelid)        ON r.oid = cr.conrelid AND cr.contype = 'p'::\"char\")        ON r.oid = co.confrelid)        ON c.oid = co.conrelid AND co.contype = 'f'::\"char\" AND a.attnum = co.conkey[1]      LEFT JOIN pg_description d ON a.attnum = d.objsubid AND a.attrelid = d.objoid    WHERE a.attnum > 0      AND NOT a.attisdropped      AND (c.relkind = ANY (ARRAY['r'::\"char\", 'v'::\"char\", 'f'::\"char\", 'p'::\"char\"]))      AND (pg_has_role(c.relowner, 'USAGE'::TEXT)        OR has_column_privilege(c.oid, a.attnum, 'SELECT, INSERT, UPDATE, REFERENCES'::TEXT))      AND (nc.nspname <> ALL (ARRAY['information_schema'::name, 'pg_catalog'::name]))   ) as property   LEFT JOIN pg_class v ON v.oid = property.entity_id   LEFT JOIN pg_class r ON r.oid = property.ref_entity   ORDER BY property._order",

//$view_projection_relation = "SELECT relation.relation_name AS projection_relation_name,  relation.title,  v.relname AS projection_name,  relation.entity_id,  relation.ref_key,  r.relname AS related_projection_name,  relation.relation_entity,  relation.key,  false AS readonly,  TRUE AS visible,  false AS opened,  NULL AS _order,  NULL AS view_id,  NULL AS hint,  NULL AS additional FROM  (  SELECT      format('%s.%s.%s.%s',          r.oid,          atf.attname,          e.oid,          at.attname      )                                         AS relation_name,      COALESCE      (          obj_description(c.oid, 'pg_constraint'),          e.relname      )                            \t\t\t  AS title,      r.oid                                     AS entity_id,      atf.attname                               AS ref_key,      e.oid                                     AS relation_entity,      at.attname                                AS key,      false                                     AS virtual  FROM pg_class e  JOIN      pg_constraint c   ON e.oid = c.conrelid AND c.contype = 'f'::\"char\"  LEFT JOIN pg_class      r   ON r.oid = c.confrelid  LEFT JOIN pg_attribute  at  ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid  LEFT JOIN pg_attribute  atf ON c.confkey[1] = atf.attnum AND atf.attrelid = c.confrelid ) as relation LEFT JOIN pg_class v ON v.oid = relation.entity_id LEFT JOIN pg_class r ON r.oid = relation.relation_entity ORDER BY _order;";

//$view_entit = "SELECT  entity.entity_id,  entity.title,  entity.primarykey,  entity.table_type FROM  (  SELECT v.oid AS entity_id,      n.nspname::text AS schema_name,      v.relname::text AS table_name,      COALESCE(obj_description(v.oid), v.relname::text) AS title,      CASE WHEN b.base_entity_cnt = 1          THEN b.base_entity_key::text          ELSE NULL::text      END AS primarykey,      v.relkind::text AS table_type,      pg_get_viewdef(v.oid) AS view_definition  FROM pg_class v  LEFT JOIN pg_namespace n ON n.oid = v.relnamespace  LEFT JOIN   (      SELECT t.refobjid AS entity_id,          t.obj AS base_entity_id,          t.obj_cnt AS base_entity_cnt,          at.attname::text AS base_entity_key      FROM       (          SELECT dv.refobjid,              count(dt.refobjid) AS obj_cnt,              min(dt.refobjid) AS obj          FROM pg_depend dv          JOIN pg_depend dt ON dv.objid = dt.objid AND dv.refobjid <> dt.refobjid AND dt.classid = 'pg_rewrite'::regclass::oid AND dt.refclassid = 'pg_class'::regclass::oid          WHERE dv.refclassid = 'pg_class'::regclass::oid AND dv.classid = 'pg_rewrite'::regclass::oid AND dv.deptype = 'i'::\"char\"          GROUP BY dv.refobjid      ) t      JOIN pg_class n_1 ON n_1.oid = t.obj AND n_1.relkind = 'r'::\"char\"      LEFT JOIN pg_constraint c ON c.conrelid = n_1.oid AND c.contype = 'p'::\"char\"      LEFT JOIN pg_attribute at ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid      LEFT JOIN pg_namespace ns ON ns.oid = n_1.relnamespace  ) b ON v.oid = b.entity_id  WHERE (v.relkind = ANY (ARRAY['v'::\"char\"])) AND (pg_has_role(v.relowner, 'USAGE'::text) OR has_table_privilege(v.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)    OR has_any_column_privilege(v.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))  UNION ALL  SELECT r.oid AS entity_id,      n.nspname AS schema_name,      r.relname AS table_name,      COALESCE(obj_description(r.oid), r.relname::text) AS title,      CASE WHEN a.conrelid IS NOT NULL          THEN CASE WHEN array_length(a.pkeys, 1) = 1              THEN a.pkeys[1]::text              ELSE array_to_json(a.pkeys)::text              END          ELSE NULL::text      END AS primarykey,      r.relkind::text AS table_type,      NULL::text AS view_definition  FROM pg_class r  LEFT JOIN pg_namespace n ON n.oid = r.relnamespace  LEFT JOIN   (      SELECT pc.conrelid, array_agg(at.attname ORDER BY at.attname)::text[] as pkeys      FROM pg_constraint pc      LEFT JOIN pg_attribute at ON (at.attnum = ANY (pc.conkey)) AND at.attrelid = pc.conrelid      WHERE pc.contype = 'p'::\"char\"      GROUP BY pc.conrelid  ) a ON a.conrelid = r.oid  WHERE (r.relkind = ANY (ARRAY['r'::\"char\", 'f'::\"char\"])) AND (pg_has_role(r.relowner, 'USAGE'::text) OR has_table_privilege(r.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)    OR has_any_column_privilege(r.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text])) ) as entity WHERE entity.schema_name <> ALL (ARRAY['ems'::TEXT, 'meta'::TEXT]);";


$sql_view_projection = [
    "view_projection_entity" => "SELECT entity.table_name AS projection_name,
                entity.title, entity.table_name AS jump, entity.primarykey, NULL AS additional,
        CASE WHEN entity.table_name = 'entity_type'
        OR entity.table_name = 'function_type'
        OR entity.table_name = 'column_type'
        OR entity.table_name = 'menu'
      THEN TRUE 
      ELSE (NOT has_table_privilege(entity.entity_id, 'INSERT, UPDATE, DELETE'))
    END AS readonly,
    NULL AS hint,
    entity.schema_name AS table_schema,
    entity.table_name AS table_name
FROM 
(
    SELECT v.oid AS entity_id,
        n.nspname::text AS schema_name,
        v.relname::text AS table_name,
        COALESCE(obj_description(v.oid), v.relname::text) AS title,
        CASE WHEN b.base_entity_cnt = 1
            THEN b.base_entity_key::text
            ELSE NULL::text
        END AS primarykey,
        v.relkind::text AS table_type,
        pg_get_viewdef(v.oid) AS view_definition
    FROM pg_class v
    LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
    LEFT JOIN 
    (
        SELECT t.refobjid AS entity_id,
            t.obj AS base_entity_id,
            t.obj_cnt AS base_entity_cnt,
            at.attname::text AS base_entity_key
        FROM 
        (
            SELECT dv.refobjid,
                count(dt.refobjid) AS obj_cnt,
                min(dt.refobjid) AS obj
            FROM pg_depend dv
            JOIN pg_depend dt ON dv.objid = dt.objid AND dv.refobjid <> dt.refobjid AND dt.classid = 'pg_rewrite'::regclass::oid AND dt.refclassid = 'pg_class'::regclass::oid
            WHERE dv.refclassid = 'pg_class'::regclass::oid AND dv.classid = 'pg_rewrite'::regclass::oid AND dv.deptype = 'i'::\"char\"
            GROUP BY dv.refobjid
        ) t
        JOIN pg_class n_1 ON n_1.oid = t.obj AND n_1.relkind = 'r'::\"char\"
        LEFT JOIN pg_constraint c ON c.conrelid = n_1.oid AND c.contype = 'p'::\"char\"
        LEFT JOIN pg_attribute at ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid
        LEFT JOIN pg_namespace ns ON ns.oid = n_1.relnamespace
    ) b ON v.oid = b.entity_id
    WHERE (v.relkind = ANY (ARRAY['v'::\"char\"])) AND (pg_has_role(v.relowner, 'USAGE'::text) OR has_table_privilege(v.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
      OR has_any_column_privilege(v.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))
    UNION ALL
    SELECT r.oid AS entity_id,
        n.nspname AS schema_name,
        r.relname AS table_name,
        COALESCE(obj_description(r.oid), r.relname::text) AS title,
        CASE WHEN a.conrelid IS NOT NULL
            THEN CASE WHEN array_length(a.pkeys, 1) = 1
                THEN a.pkeys[1]::text
                ELSE array_to_json(a.pkeys)::text
                END
            ELSE NULL::text
        END AS primarykey,
        r.relkind::text AS table_type,
        NULL::text AS view_definition
    FROM pg_class r
    LEFT JOIN pg_namespace n ON n.oid = r.relnamespace
    LEFT JOIN 
    (
        SELECT pc.conrelid, array_agg(at.attname ORDER BY at.attname)::text[] as pkeys
        FROM pg_constraint pc
        LEFT JOIN pg_attribute at ON (at.attnum = ANY (pc.conkey)) AND at.attrelid = pc.conrelid
        WHERE pc.contype = 'p'::\"char\"
        GROUP BY pc.conrelid
    ) a ON a.conrelid = r.oid
    WHERE (r.relkind = ANY (ARRAY['r'::\"char\", 'f'::\"char\"])) AND (pg_has_role(r.relowner, 'USAGE'::text) OR has_table_privilege(r.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
      OR has_any_column_privilege(r.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))
) as entity",

    "view_projection_property" => "SELECT property.property_name AS projection_property_name,
    property.title,
    property.type,
    CASE WHEN v.relname = 'menu_item' AND property.column_name = 'menu_id'
        OR v.relname = 'entity' AND property.column_name = 'entity_id'
        OR v.relname = 'entity' AND property.column_name = 'table_type'
        OR v.relname = 'entity_edit' AND property.column_name = 'entity_id'
        OR v.relname = 'entity_edit' AND property.column_name = 'schema_name'
        OR v.relname = 'entity_edit' AND property.column_name = 'table_name'
        OR v.relname = 'entity_edit' AND property.column_name = 'title'
        OR v.relname = 'entity_edit' AND property.column_name = 'primarykey'
        OR v.relname = 'entity_edit' AND property.column_name = 'table_type'
        OR v.relname = 'property' AND property.column_name = 'property_name'
        OR v.relname = 'property' AND property.column_name = 'visible'
        OR v.relname = 'property' AND property.column_name = 'readonly'
        OR v.relname = 'property' AND property.column_name = '_order'
        OR v.relname = 'property' AND property.column_name = 'constraint_name'
        OR v.relname = 'relation' AND property.column_name = 'relation_name'
        OR v.relname = 'functions' AND property.column_name = 'function_id'
        OR v.relname = 'schema' AND property.column_name = 'schema_id'
        OR v.relname = 'triggers' AND property.column_name = 'trigger_id'
      THEN TRUE
      ELSE property.readonly
    END as readonly,
    property.visible,
    v.relname AS projection_name,
    property.column_name,
    property.ref_key,
    r.relname AS ref_projection,
    NULL::text AS link_key,
    property._order,
    property.ref_entity,
    NULL::text AS ref_filter,
    false AS concat_prev,
    false AS virtual,
    NULL::text AS virtual_src_projection,
    NULL::text AS original_column_name,
    NULL::text AS hint,
    NULL::text AS pattern,
    property.is_nullable,
    property.\"default\",
    CASE WHEN  v.relname = 'entity_type' AND property.column_name = 'note'
        OR v.relname = 'menu_item' AND property.column_name = 'name'
        OR v.relname = 'menu_item' AND property.column_name = 'title'
        OR v.relname = 'options' AND property.column_name = 'name'
        OR v.relname = 'property_type' AND property.column_name = 'type'
        OR v.relname = 'property_type' AND property.column_name = 'note'
        OR v.relname = 'column_type' AND property.column_name = 'data_type'
        OR v.relname = 'entity' AND property.column_name = 'schema_name'
        OR v.relname = 'entity' AND property.column_name = 'table_name'
        OR v.relname = 'entity_edit' AND property.column_name = 'schema_name'
        OR v.relname = 'entity_edit' AND property.column_name = 'table_name'
        OR v.relname = 'function_type' AND property.column_name = 'rval_type'
        OR v.relname = 'functions' AND property.column_name = 'function_schema'
        OR v.relname = 'functions' AND property.column_name = 'function_name'
        OR v.relname = 'functions' AND property.column_name = 'function_attributes'
        OR v.relname = 'property' AND property.column_name = 'column_name'
        OR v.relname = 'relation' AND property.column_name = 'relation_entity'
        OR v.relname = 'relation' AND property.column_name = 'entity_id'
        OR v.relname = 'relation' AND property.column_name = 'title'
        OR v.relname = 'schema' AND property.column_name = 'schema_name'
        OR v.relname = 'triggers' AND property.column_name = 'trigger_name'
      THEN TRUE
      WHEN property.type IN ('string', 'caption') THEN TRUE 
      ELSE FALSE
    END AS show_in_refs,
    NULL::text AS additional
  FROM 
  (
      SELECT
        c.oid ||'.'|| a.attname                                         AS property_name,
        a.attname::TEXT                                                 AS column_name,
        c.oid                                                           AS entity_id,
        COALESCE
          (
            CASE
              WHEN co.conkey[1] IS NOT NULL THEN 'ref'
              WHEN a.atttypid = 2950::oid THEN 'invisible'
              ELSE NULL::TEXT
            END, 'string'
        )                                                               AS type,
        CASE
          WHEN t.typelem <> 0 AND t.typlen = '-1' THEN 'ARRAY'
          ELSE format_type(a.atttypid, NULL)
        END::information_schema.character_data                          AS data_type,
        true                                                            AS visible,
        FALSE                                                           AS readonly,
        COALESCE(d.description, a.attname::TEXT)                        AS title,
        r.oid          													AS ref_entity,
        at.attname::TEXT							  					AS ref_key,
        a.attnum * 10                                                   AS _order,
        co.conname::information_schema.sql_identifier                   AS constraint_name,
        NOT (a.attnotnull OR t.typtype = 'd'::\"char\" AND t.typnotnull)  AS is_nullable,
        pg_get_expr(ad.adbin, ad.adrelid)                               AS \"default\",
        CASE
          WHEN t.typtype = 'd' THEN NULL
          ELSE
            CASE
                WHEN a.atttypmod = '-1'                   THEN NULL
                WHEN a.atttypid = ANY (ARRAY[1042, 1043]) THEN a.atttypmod-4
                WHEN a.atttypid = ANY (ARRAY[1560, 1562]) THEN a.atttypmod
                                                          ELSE NULL
            END                                                             
        END::INTEGER                                                    AS data_type_len
      FROM pg_attribute a
        LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum
        JOIN (pg_class c
          JOIN pg_namespace nc ON c.relnamespace = nc.oid) ON a.attrelid = c.oid
          JOIN (pg_type t
            JOIN pg_namespace nt ON t.typnamespace = nt.oid) ON a.atttypid = t.oid
        LEFT JOIN (pg_constraint co
        JOIN (pg_class r
          LEFT JOIN pg_namespace nr ON r.relnamespace = nr.oid
        JOIN (pg_constraint cr
        JOIN pg_attribute at ON cr.conkey[1] = at.attnum AND at.attrelid = cr.conrelid)
          ON r.oid = cr.conrelid AND cr.contype = 'p'::\"char\")
          ON r.oid = co.confrelid)
          ON c.oid = co.conrelid AND co.contype = 'f'::\"char\" AND a.attnum = co.conkey[1]
        LEFT JOIN pg_description d ON a.attnum = d.objsubid AND a.attrelid = d.objoid
      WHERE a.attnum > 0
        AND NOT a.attisdropped
        AND (c.relkind = ANY (ARRAY['r'::\"char\", 'v'::\"char\", 'f'::\"char\", 'p'::\"char\"]))
        AND (pg_has_role(c.relowner, 'USAGE'::TEXT)
          OR has_column_privilege(c.oid, a.attnum, 'SELECT, INSERT, UPDATE, REFERENCES'::TEXT))
        AND (nc.nspname <> ALL (ARRAY['information_schema'::name, 'pg_catalog'::name]))
  ) as property
  LEFT JOIN pg_class v ON v.oid = property.entity_id
  LEFT JOIN pg_class r ON r.oid = property.ref_entity
  ORDER BY property._order",

    "view_projection_relation" => "SELECT relation.relation_name AS projection_relation_name,
    relation.title,
    v.relname AS projection_name,
    relation.entity_id,
    relation.ref_key,
    r.relname AS related_projection_name,
    relation.relation_entity,
    relation.key,
    false AS readonly,
    TRUE AS visible,
    false AS opened,
    NULL AS _order,
    NULL AS view_id,
    NULL AS hint,
    NULL AS additional
FROM 
(
    SELECT
        format('%s.%s.%s.%s',
            r.oid,
            atf.attname,
            e.oid,
            at.attname
        )                                         AS relation_name,
        COALESCE
        (
            obj_description(c.oid, 'pg_constraint'),
            e.relname
        )                            			  AS title,
        r.oid                                     AS entity_id,
        atf.attname                               AS ref_key,
        e.oid                                     AS relation_entity,
        at.attname                                AS key,
        false                                     AS virtual
    FROM pg_class e
    JOIN      pg_constraint c   ON e.oid = c.conrelid AND c.contype = 'f'::\"char\"
    LEFT JOIN pg_class      r   ON r.oid = c.confrelid
    LEFT JOIN pg_attribute  at  ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid
    LEFT JOIN pg_attribute  atf ON c.confkey[1] = atf.attnum AND atf.attrelid = c.confrelid
) as relation
LEFT JOIN pg_class v ON v.oid = relation.entity_id
LEFT JOIN pg_class r ON r.oid = relation.relation_entity
ORDER BY _order;",

    "view_entit" => "SELECT
    entity.entity_id,
    entity.title,
    entity.primarykey,
    entity.table_type
FROM 
(
    SELECT v.oid AS entity_id,
        n.nspname::text AS schema_name,
        v.relname::text AS table_name,
        COALESCE(obj_description(v.oid), v.relname::text) AS title,
        CASE WHEN b.base_entity_cnt = 1
            THEN b.base_entity_key::text
            ELSE NULL::text
        END AS primarykey,
        v.relkind::text AS table_type,
        pg_get_viewdef(v.oid) AS view_definition
    FROM pg_class v
    LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
    LEFT JOIN 
    (
        SELECT t.refobjid AS entity_id,
            t.obj AS base_entity_id,
            t.obj_cnt AS base_entity_cnt,
            at.attname::text AS base_entity_key
        FROM 
        (
            SELECT dv.refobjid,
                count(dt.refobjid) AS obj_cnt,
                min(dt.refobjid) AS obj
            FROM pg_depend dv
            JOIN pg_depend dt ON dv.objid = dt.objid AND dv.refobjid <> dt.refobjid AND dt.classid = 'pg_rewrite'::regclass::oid AND dt.refclassid = 'pg_class'::regclass::oid
            WHERE dv.refclassid = 'pg_class'::regclass::oid AND dv.classid = 'pg_rewrite'::regclass::oid AND dv.deptype = 'i'::\"char\"
            GROUP BY dv.refobjid
        ) t
        JOIN pg_class n_1 ON n_1.oid = t.obj AND n_1.relkind = 'r'::\"char\"
        LEFT JOIN pg_constraint c ON c.conrelid = n_1.oid AND c.contype = 'p'::\"char\"
        LEFT JOIN pg_attribute at ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid
        LEFT JOIN pg_namespace ns ON ns.oid = n_1.relnamespace
    ) b ON v.oid = b.entity_id
    WHERE (v.relkind = ANY (ARRAY['v'::\"char\"])) AND (pg_has_role(v.relowner, 'USAGE'::text) OR has_table_privilege(v.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
      OR has_any_column_privilege(v.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))
    UNION ALL
    SELECT r.oid AS entity_id,
        n.nspname AS schema_name,
        r.relname AS table_name,
        COALESCE(obj_description(r.oid), r.relname::text) AS title,
        CASE WHEN a.conrelid IS NOT NULL
            THEN CASE WHEN array_length(a.pkeys, 1) = 1
                THEN a.pkeys[1]::text
                ELSE array_to_json(a.pkeys)::text
                END
            ELSE NULL::text
        END AS primarykey,
        r.relkind::text AS table_type,
        NULL::text AS view_definition
    FROM pg_class r
    LEFT JOIN pg_namespace n ON n.oid = r.relnamespace
    LEFT JOIN 
    (
        SELECT pc.conrelid, array_agg(at.attname ORDER BY at.attname)::text[] as pkeys
        FROM pg_constraint pc
        LEFT JOIN pg_attribute at ON (at.attnum = ANY (pc.conkey)) AND at.attrelid = pc.conrelid
        WHERE pc.contype = 'p'::\"char\"
        GROUP BY pc.conrelid
    ) a ON a.conrelid = r.oid
    WHERE (r.relkind = ANY (ARRAY['r'::\"char\", 'f'::\"char\"])) AND (pg_has_role(r.relowner, 'USAGE'::text) OR has_table_privilege(r.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
      OR has_any_column_privilege(r.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))
) as entity
WHERE entity.schema_name <> ALL (ARRAY['ems'::TEXT, 'meta'::TEXT])"

   ];
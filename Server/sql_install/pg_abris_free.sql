--
-- pg_abris v0.7.0
--
CREATE EXTENSION IF NOT EXISTS "uuid-ossp" CASCADE;
--
--
--  Создание схемы meta, таблиц, секвенций и типов данных
--
CREATE SCHEMA meta;
--
--
CREATE SEQUENCE meta.menu_seq START 1;
--
--
CREATE TYPE meta.file AS (
	mimetype text,
	filename text,
	preview bytea,
	content bytea
);
--
--
CREATE TABLE meta.options
(
    name text COLLATE pg_catalog."default" NOT NULL,
    value text COLLATE pg_catalog."default",
    CONSTRAINT options_pkey PRIMARY KEY (name)
);
--
--
CREATE TABLE meta.type_match
(
    data_type_id  OID
  , property_type TEXT
  , CONSTRAINT type_match_pkey PRIMARY KEY (data_type_id)
);
--
--
CREATE TABLE meta.entity_extra
(
    entity_id       OID   NOT NULL
  , primarykey      TEXT
  , e_schema        TEXT
  , e_table         TEXT
  , CONSTRAINT entity_extra_pkey PRIMARY KEY (entity_id)
);
--
--
CREATE TABLE meta.property_extra 
(
    property_name   TEXT NOT NULL
  , type            TEXT
  , ref_entity      OID
  , ref_key         TEXT
  , entity_id       OID
  , e_schema        TEXT
  , e_table         TEXT
  , p_name          TEXT
  , r_schema        TEXT
  , r_table         TEXT
  , is_pkey         BOOLEAN DEFAULT FALSE
  , CONSTRAINT property_extra_pkey PRIMARY KEY (property_name)
);
--
--
CREATE TABLE meta.relation_extra
(
    relation_name   TEXT NOT NULL  
  , entity_id       OID            
  , relation_entity OID            
  , key             TEXT           
  , title           TEXT           
  , e_schema        TEXT           
  , e_table         TEXT           
  , r_schema        TEXT           
  , r_table         TEXT           
  , ref_key         TEXT
  , CONSTRAINT relation_extra_pkey PRIMARY KEY (relation_name)
);
--
--
CREATE TABLE meta.menu_item (
  menu_id       INTEGER DEFAULT nextval('meta.menu_seq')
  , name        TEXT NOT NULL
  , title       TEXT
  , parent      INTEGER
  , projection  TEXT
  , view_id     TEXT DEFAULT 'list'
  , role        TEXT
  , _order      INTEGER DEFAULT 0
  , iconclass   TEXT
  , style       TEXT
  , key         TEXT
  , CONSTRAINT menu_item_pkey PRIMARY KEY (menu_id)
);
--
--
CREATE TABLE meta.entity_type
(
    type  CHARACTER
  , note  TEXT
  , CONSTRAINT entity_type_pkey PRIMARY KEY (type)
);
--
--
CREATE TABLE meta.property_type
(
    type    TEXT
  , note    TEXT
  , CONSTRAINT property_type_pkey PRIMARY KEY (type)
);
--
--
CREATE TABLE meta.exceptions
(
    errcode text primary key,
    message text,
    detail text,
    hint text
);
--
--
--  Создание представлений и функций
--
CREATE OR REPLACE VIEW meta.triggers AS
  SELECT pg_trigger.oid          AS trigger_id,
    pg_trigger.tgrelid           AS entity_id,
    pg_trigger.tgname            AS trigger_name,
    pg_trigger.tgfoid            AS function_id,
  CASE WHEN pg_trigger.tgenabled = 'D'
    THEN FALSE
    ELSE TRUE
  END                            AS trigger_state,
  (((pg_trigger.tgtype)::integer & (1 << 0)))::boolean AS type_row,
  (
  CASE WHEN (((pg_trigger.tgtype)::integer & (1 << 6)))::boolean THEN 3 -- instead of
    WHEN  (((pg_trigger.tgtype)::integer & (1 << 1)))::boolean THEN 1   -- before
    ELSE 2                                                              -- after
  END
  )::integer                     tg_type,
  array_to_json(
    array_remove(
      ARRAY[]::integer[]
      || (CASE WHEN ((pg_trigger.tgtype)::integer & (1 << 2))::boolean THEN 0 ELSE NULL END) -- insert
      || (CASE WHEN ((pg_trigger.tgtype)::integer & (1 << 4))::boolean THEN 1 ELSE NULL END) -- update
      || (CASE WHEN ((pg_trigger.tgtype)::integer & (1 << 3))::boolean THEN 2 ELSE NULL END) -- delete
      || (CASE WHEN ((pg_trigger.tgtype)::integer & (1 << 5))::boolean THEN 3 ELSE NULL END) -- truncate
      , NULL
    )
  )::json                        AS tg_event
  FROM pg_trigger
  WHERE tgconstraint = '0'::oid;
--
--
CREATE OR REPLACE VIEW meta.entity AS
SELECT v.oid AS entity_id,
	n.nspname::text AS schema_name,
	v.relname::text AS table_name,
	COALESCE(obj_description(v.oid), v.relname::text) AS title,
	CASE 
		WHEN array_length(p.column_name, 1) = 1 
		THEN p.column_name[1]::text
		ELSE array_to_json(p.column_name)::text
	END AS primarykey,
	v.relkind::text AS table_type,
	pg_get_viewdef(v.oid) AS view_definition,
	FALSE AS create_pkey
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
		WHERE dv.refclassid = 'pg_class'::regclass::oid AND dv.classid = 'pg_rewrite'::regclass::oid AND dv.deptype = 'i'::"char"
		GROUP BY dv.refobjid
	) t
	JOIN pg_class n_1 ON n_1.oid = t.obj AND n_1.relkind = 'r'::"char"
	LEFT JOIN pg_constraint c ON c.conrelid = n_1.oid AND c.contype = 'p'::"char"
	LEFT JOIN pg_attribute at ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid
	LEFT JOIN pg_namespace ns ON ns.oid = n_1.relnamespace
) b ON v.oid = b.entity_id
LEFT JOIN 
(
	SELECT mp.entity_id, array_agg(mp.p_name ORDER BY mp.p_name) as column_name
	FROM meta.property_extra mp
	WHERE mp.is_pkey = TRUE AND EXISTS (SELECT 1 FROM pg_attribute WHERE attrelid = mp.entity_id AND attname = mp.p_name)
	GROUP BY mp.entity_id
) as p ON p.entity_id = v.oid
WHERE (v.relkind = ANY (ARRAY['v'::"char"])) AND (pg_has_role(v.relowner, 'USAGE'::text) OR has_table_privilege(v.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
  OR has_any_column_privilege(v.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]))
UNION ALL
SELECT r.oid AS entity_id,
	n.nspname::text AS schema_name,
	r.relname::text AS table_name,
	COALESCE(obj_description(r.oid), r.relname::text) AS title,
	CASE
		WHEN array_length(a.pkeys, 1) = 1
		THEN a.pkeys[1]::text
		ELSE array_to_json(a.pkeys)::text
	END AS primarykey,
  r.relkind::text AS table_type,
  NULL::text AS view_definition,
  FALSE AS create_pkey
FROM pg_class r
LEFT JOIN pg_namespace n ON n.oid = r.relnamespace
LEFT JOIN 
(
	SELECT pc.conrelid, array_agg(at.attname ORDER BY at.attname)::text[] as pkeys
	FROM pg_constraint pc
	LEFT JOIN pg_attribute at ON (at.attnum = ANY (pc.conkey)) AND at.attrelid = pc.conrelid
	WHERE pc.contype = 'p'::"char"
	GROUP BY pc.conrelid
) a ON a.conrelid = r.oid
WHERE (r.relkind = ANY (ARRAY['r'::"char", 'f'::"char", 'p'::"char"])) AND r.relpartbound IS NULL
  AND (pg_has_role(r.relowner, 'USAGE'::text) OR has_table_privilege(r.oid, 'SELECT, INSERT, UPDATE, DELETE, TRUNCATE, REFERENCES, TRIGGER'::text)
  OR has_any_column_privilege(r.oid, 'SELECT, INSERT, UPDATE, REFERENCES'::text)) AND (n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]));
--
--
CREATE OR REPLACE VIEW meta.entity_edit AS
 SELECT
    entity_id
  , schema_name
  , table_name
  , title
  , primarykey
  , table_type
   FROM meta.entity
  WHERE entity.schema_name <> 'meta'::name;
--
--
CREATE OR REPLACE VIEW meta.property AS
  SELECT
    c.oid ||'.'|| a.attname                                         AS property_name,
    a.attname::TEXT                                                 AS column_name,
    c.oid                                                           AS entity_id,
    COALESCE(
      pe.type,
      COALESCE(
        CASE
          WHEN re.ref_key IS NOT NULL THEN 'ref'
          ELSE NULL
        END,
        COALESCE(
          CASE
              WHEN co.conkey[1] IS NOT NULL THEN 'ref'
              WHEN a.atttypid = ANY(ARRAY[2950, 142, 17]) THEN 'invisible'
              ELSE NULL::TEXT
          END, COALESCE(type_match.property_type, 'string')
        )
      )
    )                                                               AS type,
    CASE
      WHEN t.typelem <> 0 AND t.typlen = '-1' THEN 'ARRAY'
      ELSE format_type(a.atttypid, NULL)
    END::information_schema.character_data                          AS data_type,
    true                                                            AS visible,
    FALSE                                                           AS readonly,
    COALESCE(d.description, a.attname::TEXT)                        AS title,
    COALESCE(pe.ref_entity, COALESCE(re.entity_id, r.oid))          AS ref_entity,
    COALESCE(pe.ref_key, COALESCE(re.ref_key, at.attname::TEXT))::TEXT  AS ref_key,
    a.attnum * 10                                                   AS _order,
    co.conname::information_schema.sql_identifier                   AS constraint_name,
    NOT (a.attnotnull OR t.typtype = 'd'::"char" AND t.typnotnull)  AS is_nullable,
    pg_get_expr(ad.adbin, ad.adrelid)                               AS "default",
    CASE
      WHEN t.typtype = 'd' THEN NULL
      ELSE
        CASE
            WHEN a.atttypmod = '-1'                   THEN NULL
            WHEN a.atttypid = ANY (ARRAY[1042, 1043]) THEN a.atttypmod-4
            WHEN a.atttypid = ANY (ARRAY[1560, 1562]) THEN a.atttypmod
                                                      ELSE NULL
        END                                                             
    END::INTEGER                                                    AS data_type_len,
    COALESCE 
    (
      pe.is_pkey,
      CASE WHEN cp.conname IS NULL THEN FALSE ELSE TRUE END
    )                                                               AS is_pkey
  FROM pg_attribute a
  LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum
  JOIN 
  (
    pg_class c
    JOIN pg_namespace nc ON c.relnamespace = nc.oid
  ) ON a.attrelid = c.oid
  JOIN 
  (
    pg_type t
    JOIN pg_namespace nt ON t.typnamespace = nt.oid
  ) ON a.atttypid = t.oid
  LEFT JOIN meta.property_extra pe ON property_name  = c.oid ||'.'|| a.attname
  LEFT JOIN 
  (
    pg_constraint co
    JOIN
    (
      pg_class r
      LEFT JOIN pg_namespace nr ON r.relnamespace = nr.oid
      JOIN 
      (
        pg_constraint cr
        JOIN pg_attribute at ON cr.conkey[1] = at.attnum AND at.attrelid = cr.conrelid
      ) ON r.oid = cr.conrelid AND cr.contype = 'p'::"char"
    ) ON r.oid = co.confrelid
  ) ON c.oid = co.conrelid AND co.contype = 'f'::"char" AND a.attnum = co.conkey[1]
  LEFT JOIN pg_constraint cp ON c.oid = cp.conrelid AND cp.contype = 'p'::"char" AND a.attnum = ANY(cp.conkey)
  LEFT JOIN pg_description d ON a.attnum = d.objsubid AND a.attrelid = d.objoid
  LEFT JOIN meta.type_match ON a.atttypid = type_match.data_type_id
  LEFT JOIN meta.relation_extra re ON re.relation_entity = c.oid AND re.key = a.attname
  WHERE a.attnum > 0
    AND NOT a.attisdropped
    AND (c.relkind = ANY (ARRAY['r'::"char", 'v'::"char", 'f'::"char", 'p'::"char"]))
    AND (pg_has_role(c.relowner, 'USAGE'::TEXT)
      OR has_column_privilege(c.oid, a.attnum, 'SELECT, INSERT, UPDATE, REFERENCES'::TEXT))
    AND (nc.nspname <> ALL (ARRAY['information_schema'::name, 'pg_catalog'::name]))
  ORDER BY entity_id, _order;
--
--
CREATE OR REPLACE VIEW meta.relation AS
  SELECT
    format('%s.%s.%s.%s',
      r.oid,
      atf.attname,
      e.oid,
      at.attname
    )                                         AS relation_name,
    COALESCE(
      obj_description(c.oid, 'pg_constraint')
      , e.relname)                            AS title,
    r.oid                                     AS entity_id,
    atf.attname::text                         AS ref_key,
    e.oid                                     AS relation_entity,
    at.attname::text                          AS key,
    false                                     AS virtual
  FROM pg_class e
    JOIN      pg_constraint c   ON e.oid = c.conrelid AND c.contype = 'f'::"char"
    LEFT JOIN pg_class      r   ON r.oid = c.confrelid
    LEFT JOIN pg_attribute  at  ON c.conkey[1] = at.attnum AND at.attrelid = c.conrelid
    LEFT JOIN pg_attribute  atf ON c.confkey[1] = atf.attnum AND atf.attrelid = c.confrelid
UNION
SELECT
  re.relation_name,
  COALESCE (re.title, e.relname) AS title,
  re.entity_id,
  re.ref_key::text,
  re.relation_entity,
  re.key::text,
  true AS virtual
FROM meta.relation_extra re
LEFT JOIN pg_class e ON e.oid = re.relation_entity;
--
--
CREATE OR REPLACE VIEW meta.functions AS
  SELECT
    p.oid                                   AS function_id,
    n.nspname::text                         AS function_schema,
    p.proname::text                         AS function_name,
    format_type(t.oid, NULL::integer)       AS rval_type,
    pg_get_function_arguments(p.oid)        AS function_attributes,
    p.prosrc                                AS function_code
  FROM (pg_namespace n
    JOIN pg_proc p ON ((p.pronamespace = n.oid)))
    JOIN pg_type t ON ((p.prorettype = t.oid))
  WHERE n.nspname::text <> ALL (ARRAY['pg_catalog'::text, 'information_schema'::text]);
--
--
CREATE OR REPLACE VIEW meta.menu AS
WITH RECURSIVE temp2(menu_id, name, parent, title, projection, view_id, role, iconclass, path, style, key) AS
(
	WITH RECURSIVE temp1(menu_id, name, parent, title, projection, view_id, role, path, level, iconclass, style, key) AS
	(
		SELECT
			t1.menu_id,
			t1.name,
			t1.parent,
			t1.title,
			t1.projection,
			t1.view_id,
			t1.role,
			(to_char(t1._order, '000'::TEXT) || t1.name) AS path,
			1,
			t1.iconclass,
			t1.style,
			t1.key
		FROM meta.menu_item t1
		WHERE t1.parent IS NULL AND (t1.role IS NULL OR pg_has_role("current_user"(), (t1.role)::name, 'member'::TEXT))
		UNION
		SELECT
			t2.menu_id,
			t2.name,
			t2.parent,
			t2.title,
			t2.projection,
			t2.view_id,
			t2.role,
			(((temp1_1.path ||'->'::TEXT) || to_char(t2._order, '000'::TEXT)) || t2.name) AS "varchar",
			(temp1_1.level + 1),
			t2.iconclass,
			t2.style,
			t2.key
		FROM meta.menu_item t2
		JOIN temp1 temp1_1 ON temp1_1.menu_id = t2.parent
		WHERE t2.role IS NULL OR pg_has_role("current_user"(), (t2.role)::name, 'member'::TEXT)
	)
	SELECT temp1.menu_id,
		temp1.name,
		temp1.parent,
		temp1.title,
		temp1.projection,
		temp1.view_id,
		temp1.role,
		temp1.iconclass,
		temp1.path,
		temp1.style,
		temp1.key
	FROM temp1
	JOIN meta.entity ON temp1.projection = entity.table_name
	UNION
	SELECT temp1.menu_id,
		temp1.name,
		temp1.parent,
		temp1.title,
		temp1.projection,
		temp1.view_id,
		temp1.role,
		temp1.iconclass,
		temp1.path,
		temp1.style,
		temp1.key
	FROM temp1
	JOIN temp2 ON temp1.menu_id = temp2.parent
)
SELECT *
FROM temp2
LIMIT 1000;
--
--
CREATE VIEW meta.column_type AS
SELECT format_type(oid, NULL)::pg_catalog.name AS data_type
  FROM pg_catalog.pg_type
  WHERE typisdefined AND typtype ='b' AND typcategory <> 'A';
--
--
CREATE OR REPLACE VIEW meta.function_type AS
SELECT format_type(oid, NULL)::pg_catalog.name AS rval_type FROM pg_catalog.pg_type;
--
--
CREATE OR REPLACE VIEW meta.schema AS
SELECT
	n.oid 		AS schema_id,
	n.nspname	AS schema_name,
	COALESCE(obj_description(n.oid), n.nspname) AS title
    FROM pg_catalog.pg_namespace n
	WHERE  n.nspname <> ALL (ARRAY['pg_catalog', 'information_schema', 'pg_toast', 'pg_temp_1', 'pg_toast_temp_1']);
--
--
CREATE OR REPLACE VIEW meta.view_entity AS
  SELECT
    entity.entity_id,
    entity.title,
    entity.primarykey,
    entity.table_type
   FROM meta.entity
  WHERE entity.schema_name <> ALL (ARRAY['ems'::TEXT, 'meta'::TEXT]);
--
--
CREATE OR REPLACE VIEW meta.view_projection_entity AS
  SELECT entity.table_name AS projection_name,
    entity.title,
    entity.table_name AS jump,
    entity.primarykey,
    NULL AS additional,
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
  FROM meta.entity;
--
--
CREATE OR REPLACE VIEW meta.view_projection_property AS
  SELECT property.property_name AS projection_property_name,
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
    property."default",
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
  FROM meta.property
  LEFT JOIN pg_class v ON v.oid = property.entity_id
  LEFT JOIN pg_class r ON r.oid = property.ref_entity
  ORDER BY property._order;
--
--
CREATE OR REPLACE VIEW meta.view_projection_relation AS
  SELECT relation.relation_name AS projection_relation_name,
    relation.title,
    v.relname AS projection_name,
    relation.entity_id,
    relation.ref_key,
    r.relname AS related_projection_name,
    relation.relation_entity,
    relation.key,
    false AS readonly,
    CASE WHEN (relation.entity_id = 'meta.entity'::regclass::oid AND relation.ref_key = 'table_name' AND relation.relation_entity = 'meta.menu_item'::regclass::oid AND relation.key = 'projection')
        OR (relation.entity_id = 'meta.schema'::regclass::oid AND relation.ref_key = 'schema_name' AND relation.relation_entity = 'meta.entity_edit'::regclass::oid AND relation.key = 'schema_name')
        OR (relation.entity_id = 'meta.entity_type'::regclass::oid AND relation.ref_key = 'type' AND relation.relation_entity = 'meta.entity_edit'::regclass::oid AND relation.key = 'table_type')
        OR (relation.entity_id = 'meta.entity'::regclass::oid AND relation.ref_key = 'entity_id' AND relation.relation_entity = 'meta.property'::regclass::oid AND relation.key = 'ref_entity')
        OR (relation.entity_id = 'meta.entity'::regclass::oid AND relation.ref_key = 'entity_id' AND relation.relation_entity = 'meta.relation'::regclass::oid AND relation.key = 'relation_entity')
      THEN FALSE
      ELSE TRUE
    END AS visible,
    false AS opened,
    NULL AS _order,
    NULL AS view_id,
    NULL AS hint,
    NULL AS additional
  FROM meta.relation
  LEFT JOIN pg_class v ON v.oid = relation.entity_id
  LEFT JOIN pg_class r ON r.oid = relation.relation_entity
  ORDER BY _order;
--
--
--
--
CREATE OR REPLACE FUNCTION meta.clean()
  RETURNS TEXT
  LANGUAGE plpgsql
AS $$
BEGIN
  DELETE FROM meta.property_extra WHERE property_name NOT IN (SELECT property_name FROM meta.property);
  DELETE FROM meta.property_extra WHERE entity_id NOT IN (SELECT entity_id FROM meta.entity);
  UPDATE meta.property SET type = 'string', ref_entity = NULL, ref_key = NULL WHERE ref_entity NOT IN (SELECT entity_id FROM meta.entity);
  --
  DELETE FROM meta.relation_extra WHERE relation_name NOT IN (SELECT relation_name FROM meta.relation);
  DELETE FROM meta.relation_extra WHERE relation_entity NOT IN (SELECT entity_id FROM meta.entity) OR entity_id NOT IN (SELECT entity_id FROM meta.entity);
  --
  DELETE FROM meta.menu_item WHERE projection NOT IN (SELECT table_name FROM meta.entity);
  --
  RETURN 'OK';
END;$$;
--
--
CREATE OR REPLACE FUNCTION meta.update_view (_entity_id oid, new_view_definition TEXT)
  RETURNS TEXT
  LANGUAGE plpgsql
  SECURITY DEFINER
AS $$
DECLARE
  _exc_row    RECORD;
  depend_obj  TEXT;
  _entity     TEXT;
BEGIN
  SELECT DISTINCT r.ev_class::regclass::TEXT INTO depend_obj
  FROM pg_attribute    AS a
    JOIN pg_depend  AS d ON d.refobjid = a.attrelid AND d.refobjsubid = a.attnum
    JOIN pg_rewrite AS r ON d.objid = r.oid
  WHERE
      a.attrelid = _entity_id
  LIMIT 1;
  IF depend_obj IS NULL THEN
    SELECT relation_entity::regclass::TEXT INTO depend_obj FROM meta.relation
    WHERE entity_id = _entity_id AND virtual IS TRUE
    LIMIT 1;
    IF depend_obj IS NULL THEN
      SELECT format('%s.%s', quote_ident(schema_name), quote_ident(table_name)) INTO _entity FROM meta.entity WHERE entity_id = _entity_id;
      DELETE FROM pg_attribute WHERE attrelid = _entity_id;
      DELETE FROM pg_description WHERE objoid = _entity_id AND objsubid > 0;
      UPDATE pg_class SET relnatts = 0 WHERE oid = _entity_id;
      EXECUTE(format('CREATE OR REPLACE VIEW %s AS %s;', _entity, new_view_definition));
      RETURN 'OK';
    ELSE
      SELECT * FROM meta.exceptions WHERE errcode = 'PA002' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = format(_exc_row.message, depend_obj), DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
  ELSE
    SELECT * FROM meta.exceptions WHERE errcode = 'PA003' INTO _exc_row;
    RAISE EXCEPTION USING MESSAGE = format(_exc_row.message, depend_obj), DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
  END IF;
END;$$;
--
--
CREATE OR REPLACE FUNCTION meta.create_menu()
  RETURNS TEXT
  LANGUAGE plpgsql
AS $$
BEGIN
  truncate meta.menu_item;
  ALTER SEQUENCE meta.menu_seq RESTART WITH 1;
  INSERT INTO meta.menu_item (name, title, _order)
    SELECT
      schema_name,
      schema_name,
      (SELECT count(1)
        FROM meta.schema s1
        WHERE s1.schema_name <= s.schema_name
       ) AS _order
    FROM meta.schema s ORDER BY schema_name;
  INSERT INTO meta.menu_item (name, parent, title, projection, _order)
    SELECT
      schema_name||'.'||table_name,
      (SELECT menu_id
        FROM meta.menu_item
        WHERE name = e.schema_name) as parent,
      title,
      table_name,
      (SELECT count(1)
        FROM meta.entity e1
        WHERE e1.title <= e.title
      ) AS _order
    FROM meta.entity e
    WHERE schema_name = 'meta' OR (SELECT count(*) FROM meta.property WHERE is_pkey = TRUE AND entity_id = e.entity_id) < 2
    ORDER BY title;
  RETURN 'OK';
END;$$;
--
--
CREATE OR REPLACE FUNCTION meta.set_views_pkey()
RETURNS text
LANGUAGE 'plpgsql'
AS $BODY$
DECLARE
	_view RECORD;
	_column text;
	_pkeys text[];
BEGIN
	FOR _view IN SELECT * FROM meta.entity WHERE table_type = 'v' AND schema_name <> 'meta' AND primarykey IS NULL
	LOOP
		SELECT array_agg(column_name) INTO _pkeys FROM meta.property
		WHERE entity_id = ANY
		(
			SELECT DISTINCT a.attrelid::regclass::oid
			FROM pg_attribute    AS a
			JOIN pg_depend  AS d ON d.refobjid = a.attrelid AND d.refobjsubid = a.attnum
			JOIN pg_rewrite AS r ON d.objid = r.oid
			JOIN pg_class AS c ON c.oid = a.attrelid
			WHERE r.ev_class = _view.entity_id AND c.relkind = 'r'::"char"
		) AND is_pkey IS TRUE;
		FOR _column IN SELECT attname FROM pg_attribute WHERE attrelid = _view.entity_id ORDER BY attnum
		LOOP
			IF (_column = ANY(_pkeys))
			THEN
				UPDATE meta.property SET is_pkey = true WHERE entity_id = _view.entity_id AND column_name = _column;
				EXIT;
			END IF;
		END LOOP;
	END LOOP;
	RETURN 'OK';
END; $BODY$;
--
--
--  Создание триггеров
--
CREATE OR REPLACE FUNCTION meta.entity_ex_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _exc_row      RECORD;
BEGIN
    SELECT n.nspname, v.relname
      FROM pg_class v LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
      WHERE v.oid = new.entity_id
      INTO new.e_schema, new.e_table;
    IF new.e_schema IS NULL OR new.e_table IS NULL THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA006' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
  RETURN new;
END;$$;
--
CREATE TRIGGER entity_ex_trg BEFORE INSERT OR UPDATE
ON meta.entity_extra FOR EACH ROW EXECUTE PROCEDURE meta.entity_ex_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.property_ex_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _exc_row      RECORD;
BEGIN
    SELECT n.nspname, v.relname
      FROM pg_class v LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
      WHERE v.oid = new.entity_id::oid
      INTO new.e_schema, new.e_table;
    IF new.e_schema IS NULL OR new.e_table IS NULL THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA006' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;

    IF new.ref_entity IS NOT NULL THEN
	    SELECT n.nspname, v.relname
	      FROM pg_class v LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
	      WHERE v.oid = new.ref_entity
	        INTO new.r_schema, new.r_table;
	    IF new.r_schema IS NULL OR new.r_table IS NULL THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA007' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
	    END IF;
    ELSE
       new.r_schema = NULL;
       new.r_table = NULL;
    END IF;

    IF new.p_name IS NULL THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA008' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = format(_exc_row.message, new.p_name), DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;

    IF new.ref_entity IS NOT NULL AND new.ref_key IS NULL THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA009' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
  RETURN new;
END;$$;
--
CREATE TRIGGER property_ex_trg BEFORE INSERT OR UPDATE
ON meta.property_extra FOR EACH ROW EXECUTE PROCEDURE meta.property_ex_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.relation_ex_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _exc_row      RECORD;
BEGIN
    SELECT n.nspname, v.relname
      FROM pg_class v LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
      WHERE v.oid = new.entity_id
      INTO new.e_schema, new.e_table;

    SELECT n.nspname, v.relname
      FROM pg_class v LEFT JOIN pg_namespace n ON n.oid = v.relnamespace
      WHERE v.oid = new.relation_entity
      INTO new.r_schema, new.r_table;

    if new.e_schema is null or new.e_table is null then
      SELECT * FROM meta.exceptions WHERE errcode = 'PA007' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    end if;

    if new.r_schema is null or new.r_table is null then
      SELECT * FROM meta.exceptions WHERE errcode = 'PA010' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    end if;

  RETURN new;
END;$$;
--
CREATE TRIGGER relation_ex_trg BEFORE INSERT OR UPDATE
ON meta.relation_extra FOR EACH ROW EXECUTE PROCEDURE meta.relation_ex_trgf();
--
--
CREATE FUNCTION meta.menu_item_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF new.name is null THEN
    new.name = new.projection;
  END IF;
  IF new.title is null THEN
    new.title  = coalesce
    (
      (
        SELECT entity.title FROM meta.entity WHERE entity.table_name = new.projection
      ), new.projection
    );
  END IF;
  RETURN new;
END;$$;
--
CREATE TRIGGER menu_item_tr BEFORE INSERT ON meta.menu_item FOR EACH ROW
  EXECUTE PROCEDURE meta.menu_item_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.triggers_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _trg_create  TEXT;
  old_entity   TEXT;
  new_entity   TEXT;
  new_function TEXT;
  ev           INTEGER;
  events       TEXT[];
BEGIN
  IF TG_OP = 'DELETE'
  THEN
    SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) INTO old_entity FROM meta.entity WHERE entity_id = old.entity_id;
    EXECUTE(format('DROP TRIGGER %s ON %s;', quote_ident(old.trigger_name), old_entity));
    RETURN old;
  END IF;

  IF TG_OP = 'INSERT'
  THEN
    SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) INTO new_entity FROM meta.entity WHERE entity_id = new.entity_id;
    SELECT format('%s.%s(%s)', quote_ident(function_schema), quote_ident(function_name), function_attributes) INTO new_function FROM meta.functions WHERE function_id = new.function_id;
    FOREACH ev IN ARRAY (select array_agg(arr)::integer[] from (select json_array_elements_text(new.tg_event) as arr) as x)
    LOOP
      events = events || (CASE WHEN ev = 0 THEN 'INSERT' WHEN ev = 1 THEN 'UPDATE' WHEN ev = 2 THEN 'DELETE' ELSE 'TRUNCATE' END);
    END LOOP;
    EXECUTE
    (
      format
      (
         'CREATE TRIGGER %s %s %s ON %s %s EXECUTE PROCEDURE %s',
        quote_ident(new.trigger_name),
        CASE WHEN new.tg_type = 3 THEN 'INSTEAD OF'
          WHEN new.tg_type = 1 THEN 'BEFORE'
          ELSE 'AFTER'
        END,
        array_to_string(events, ' OR '),
        new_entity,
        CASE WHEN new.type_row THEN 'FOR EACH ROW' ELSE 'FOR EACH STATEMENT' END,
        new_function
      )
    );
    RETURN new;
  END IF;

  IF TG_OP = 'UPDATE'
  THEN
    SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) INTO old_entity FROM meta.entity WHERE entity_id = old.entity_id;
    IF (new.trigger_state <> old.trigger_state) THEN
      EXECUTE 
      (
        format
        (
          'ALTER %s %s %s TRIGGER %s;',
          CASE WHEN (SELECT table_type = 'r' FROM meta.entity WHERE entity_id = old.entity_id) THEN 'TABLE' ELSE 'VIEW' END,
          old_entity,
          CASE WHEN new.trigger_state THEN 'ENABLE' ELSE 'DISABLE' END,
          quote_ident(old.trigger_name)
        )
      );
    END IF;
    IF (new.trigger_name <> old.trigger_name)
    THEN
      EXECUTE(format('ALTER TRIGGER %s ON %s RENAME TO %s;', quote_ident(old.trigger_name), old_entity, quote_ident(new.trigger_name)));
    END IF;
    RETURN new;
  END IF;
END;$$;
--
CREATE TRIGGER triggers_trg INSTEAD OF INSERT OR UPDATE OR DELETE
  ON meta.triggers FOR EACH ROW EXECUTE PROCEDURE meta.triggers_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.entity_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
  DECLARE
    _exc_row            RECORD;
    new_pkey            TEXT;
    new_table_row       TEXT;    
    new_schema_name     TEXT;
    new_table_name      TEXT;
    old_entity          TEXT;
    new_entity          TEXT;
  BEGIN
    IF  TG_OP = 'DELETE' THEN
      EXECUTE(format('DROP %s %s.%s;', CASE WHEN old.table_type = 'v' THEN 'VIEW' ELSE 'TABLE' END, quote_ident(old.schema_name), quote_ident(old.table_name)));
      PERFORM  meta.clean();
      RETURN old;
    END IF;

    IF  TG_OP = 'INSERT' THEN
      IF new.schema_name IS NULL THEN
        new_schema_name = 'public';
      ELSE
        new_schema_name = lower(new.schema_name);
        IF new_schema_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
          IF NOT EXISTS(SELECT schema_name FROM information_schema.schemata WHERE schema_name = new_schema_name)
          THEN
            SELECT * FROM meta.exceptions WHERE errcode = 'PA011' INTO _exc_row;
            RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
          END IF;
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      END IF;
      new_table_name = lower(new.table_name);
      IF new_table_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
        new_entity = format('%s.%s', quote_ident(new_schema_name), quote_ident(new_table_name));
      ELSE
        SELECT * FROM meta.exceptions WHERE errcode = 'PA013' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      END IF;
      IF new.create_pkey IS TRUE
      THEN
        new_pkey = format('%s_key', new_table_name);
        new_table_row = format('%s uuid default public.uuid_generate_v4(), CONSTRAINT %s_pkey PRIMARY KEY (%s)', quote_ident(new_pkey), new_pkey, quote_ident(new_pkey));
      ELSE
        new_table_row = '';
      END IF;
      IF (new.view_definition is NOT null) THEN -- добавление представления
        EXECUTE(format('CREATE VIEW %s AS %s;', new_entity, new.view_definition));
      ELSE                                      -- добавление таблицы
        EXECUTE(
          format(
            'CREATE TABLE %s (%s);',
            new_entity,
            new_table_row
          )
        );
      END IF;
      EXECUTE(
        format(
          'COMMENT ON %s %s IS %L;',
          CASE WHEN new.view_definition IS NOT NULL THEN 'VIEW' ELSE 'TABLE' END,
          new_entity,
          COALESCE(new.title, new_table_name)
        )
      );
      EXECUTE(format('ALTER %s %s OWNER TO SESSION_USER;', CASE WHEN new.view_definition IS NOT NULL THEN 'VIEW' ELSE 'TABLE' END, new_entity));
      RETURN new;
    END IF;

    IF  TG_OP = 'UPDATE' 
    THEN
      old_entity = format('%s.%s', quote_ident(old.schema_name), quote_ident(old.table_name));
      IF new.schema_name IS NULL THEN
        new_schema_name = 'public';
      ELSE
        new_schema_name = lower(new.schema_name);
        IF new_schema_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
          IF NOT EXISTS(SELECT schema_name FROM information_schema.schemata WHERE schema_name = new_schema_name)
          THEN
            SELECT * FROM meta.exceptions WHERE errcode = 'PA011' INTO _exc_row;
            RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
          END IF;
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      END IF;
      IF new.table_name IS NULL
      THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA013' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      ELSE
        new_table_name = lower(new.table_name);
        IF new_table_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}'
        THEN
          new_entity = format('%s.%s', quote_ident(new_schema_name), quote_ident(new_table_name));
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA013' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      END IF;
      -- Обновление определения представления
      IF old.table_type = 'v' AND new.view_definition IS DISTINCT FROM old.view_definition
      THEN
        PERFORM meta.update_view(old.entity_id, new.view_definition);
      END IF;
      -- Обновление схемы
      IF new_schema_name <> old.schema_name
      THEN
        EXECUTE(format('ALTER %s %s SET SCHEMA %s;', CASE WHEN old.table_type = 'v' THEN 'VIEW' ELSE 'TABLE' END, old_entity, new_schema_name));
      END IF;
      -- Обновление наименования
      IF new_table_name <> old.table_name
      THEN
        EXECUTE(format('ALTER %s %s.%s RENAME TO %s;', CASE WHEN old.table_type = 'v' THEN 'VIEW' ELSE 'TABLE' END, quote_ident(new_schema_name), quote_ident(old.table_name), quote_ident(new_table_name)));
      END IF;
      -- Обновление комментария
      IF new.title IS DISTINCT FROM old.title
      THEN
        EXECUTE(format('COMMENT ON %s %s IS %L', CASE WHEN old.table_type = 'v' THEN 'VIEW' ELSE 'TABLE' END, new_entity, COALESCE(new.title, ''))); 
      END IF;
      -- Обновление связанных метаданных
      UPDATE meta.menu_item SET projection = new_table_name WHERE projection = old.table_name;
      UPDATE meta.property_extra pe SET entity_id = pe.entity_id WHERE entity_id = old.entity_id OR ref_entity = old.entity_id; -- значения схемы и имени таблицы обновятся внутренним триггером
      UPDATE meta.relation_extra re SET entity_id = re.entity_id WHERE entity_id = old.entity_id OR relation_entity = old.entity_id; -- значения схемы и имени таблицы обновятся внутренним триггером
      RETURN new;
    END IF;
END;$$;
--
CREATE TRIGGER entity_trg INSTEAD OF INSERT OR UPDATE OR DELETE
    ON meta.entity FOR EACH ROW EXECUTE PROCEDURE meta.entity_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.property_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS
$$
DECLARE
  _exc_row            RECORD;
  old_entity          TEXT;
  _key			  	      TEXT;
  _rel_entity	  	    TEXT;
  new_entity          TEXT;
  new_table 	        TEXT;
  new_ref_entity      TEXT;
  ref_name            TEXT;
  cont_name           TEXT;
  new_column_name     TEXT;
  new_ref_key         TEXT;
  old_property_name   TEXT;
  _data_type          TEXT;
BEGIN
  IF  TG_OP = 'DELETE' THEN
    IF EXISTS (SELECT * FROM meta.entity WHERE entity_id = old.entity_id AND table_type = 'r')
    THEN
		SELECT INTO _rel_entity, _key relation_entity::regclass::text, key FROM meta.relation WHERE entity_id = old.entity_id AND ref_key = old.column_name LIMIT 1;
		IF _rel_entity IS NOT NULL
		THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA016' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
		END IF;
		SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity INTO old_entity FROM meta.entity WHERE entity_id = old.entity_id;
		EXECUTE(format('ALTER TABLE %s DROP COLUMN %s', old_entity, quote_ident(old.column_name)));
		DELETE FROM meta.property_extra WHERE property_name = old.entity_id ||'.'|| old.column_name;
		DELETE FROM meta.relation_extra WHERE entity_id = old.ref_entity AND ref_key = old.ref_key AND relation_entity = old.entity_id AND key = old.column_name;
		RETURN old;
    ELSE
      SELECT * FROM meta.exceptions WHERE errcode = 'PA016' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
  END IF;

  IF  TG_OP = 'INSERT' THEN
    IF EXISTS (SELECT * FROM meta.entity WHERE entity_id = new.entity_id AND table_type = 'r')
    THEN
      SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity, table_name INTO new_entity, new_table FROM meta.entity WHERE entity_id = new.entity_id;
      IF new.ref_entity IS NOT NULL THEN -- добавление ссылочного поля
        SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity INTO new_ref_entity FROM meta.entity WHERE entity_id = new.ref_entity;
        new_ref_key = COALESCE(new.ref_key, (SELECT primarykey FROM meta.entity WHERE entity_id = new.ref_entity));
        IF EXISTS (SELECT * FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key)
        THEN
          new.column_name = COALESCE(new.column_name, new_ref_key);
          new_column_name = lower(new.column_name);
          IF new_column_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}'
          THENCREATE OR REPLACE FUNCTION meta.property_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS
$$
DECLARE
  _exc_row            RECORD;
  old_entity          TEXT;
  _key			  	      TEXT;
  _rel_entity	  	    TEXT;
  new_entity          TEXT;
  new_table 	        TEXT;
  new_ref_entity      TEXT;
  ref_name            TEXT;
  cont_name           TEXT;
  new_column_name     TEXT;
  new_ref_key         TEXT;
  old_property_name   TEXT;
  _data_type          TEXT;
  pkeys               TEXT[];
BEGIN
  IF  TG_OP = 'DELETE' THEN
    IF EXISTS (SELECT * FROM meta.entity WHERE entity_id = old.entity_id AND table_type = 'r')
    THEN
		SELECT INTO _rel_entity, _key relation_entity::regclass::text, key FROM meta.relation WHERE entity_id = old.entity_id AND ref_key = old.column_name LIMIT 1;
		IF _rel_entity IS NOT NULL
		THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA016' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
		END IF;
		SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity INTO old_entity FROM meta.entity WHERE entity_id = old.entity_id;
		EXECUTE(format('ALTER TABLE %s DROP COLUMN %s', old_entity, quote_ident(old.column_name)));
		DELETE FROM meta.property_extra WHERE property_name = old.entity_id ||'.'|| old.column_name;
		DELETE FROM meta.relation_extra WHERE entity_id = old.ref_entity AND ref_key = old.ref_key AND relation_entity = old.entity_id AND key = old.column_name;
		RETURN old;
    ELSE
      SELECT * FROM meta.exceptions WHERE errcode = 'PA016' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
  END IF;

  IF  TG_OP = 'INSERT' THEN
    IF EXISTS (SELECT * FROM meta.entity WHERE entity_id = new.entity_id AND table_type = 'r')
    THEN
      SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity, table_name INTO new_entity, new_table FROM meta.entity WHERE entity_id = new.entity_id;
      IF new.ref_entity IS NOT NULL THEN -- добавление ссылочного поля
        SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity INTO new_ref_entity FROM meta.entity WHERE entity_id = new.ref_entity;
        new_ref_key = COALESCE(new.ref_key, (SELECT primarykey FROM meta.entity WHERE entity_id = new.ref_entity));
        IF EXISTS (SELECT * FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key)
        THEN
          new.column_name = COALESCE(new.column_name, new_ref_key);
          new_column_name = lower(new.column_name);
          IF new_column_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}'
          THEN
            SELECT title FROM meta.entity INTO ref_name WHERE entity_id = new.ref_entity;
            SELECT data_type INTO new.data_type FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key;
            SELECT data_type_len INTO new.data_type_len FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key;
            _data_type = format('%s%s',
              new.data_type,
              CASE WHEN new.data_type_len IS NULL THEN '' ELSE format('(%s)', new.data_type_len) END
            );
            new.type = 'ref';
            EXECUTE(format('ALTER TABLE %s ADD COLUMN %s %s', new_entity, quote_ident(new_column_name), _data_type));
            EXECUTE(format('COMMENT ON COLUMN %s.%s IS %L', new_entity, quote_ident(new_column_name), COALESCE(new.title, ref_name)));
            SELECT title FROM meta.entity INTO cont_name WHERE entity_id = new.entity_id;
            IF EXISTS(SELECT * FROM meta.entity WHERE entity_id = new.ref_entity AND table_type = 'r')  -- ссылка на таблицу
            THEN
              EXECUTE(format('ALTER TABLE %s ADD CONSTRAINT %s_%s_fkey FOREIGN KEY (%s) REFERENCES %s (%s) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION',
                new_entity, new_table, new_column_name, quote_ident(new_column_name), new_ref_entity, quote_ident(new_ref_key)));
              EXECUTE(format('COMMENT ON CONSTRAINT %s_%s_fkey ON %s IS %L', new_table, new_column_name, new_entity, cont_name));
            ELSE  -- ссылка на представление
              INSERT INTO meta.relation (title, entity_id, ref_key, relation_entity, key, virtual)
	              VALUES (cont_name, new.ref_entity, new_ref_key, new.entity_id, new_column_name, TRUE);
            END IF;
          ELSE
            SELECT * FROM meta.exceptions WHERE errcode = 'PA008' INTO _exc_row;
            RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
          END IF;
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA017' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      ELSE -- добавление обычного поля
        new.data_type = COALESCE(new.data_type, 'text');
        _data_type = format('%s%s',
          new.data_type,
          CASE WHEN new.data_type_len IS NULL THEN '' ELSE format('(%s)', new.data_type_len) END
        );
        new_column_name = lower(new.column_name);
        IF new_column_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
          EXECUTE (format('ALTER TABLE %s ADD COLUMN %s %s', new_entity, quote_ident(new_column_name), _data_type));
          IF new."default" IS NOT NULL THEN
            EXECUTE (format('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT %s', new_entity, quote_ident(new_column_name), new."default"));
          END IF;
          IF new.title IS NOT NULL THEN
            EXECUTE (format('COMMENT ON COLUMN %s.%s IS %L', new_entity, quote_ident(new_column_name), new.title));
          END IF;
          IF new.is_nullable = FALSE THEN
            EXECUTE (format('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', new_entity, quote_ident(new_column_name)));
          END IF;
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA008' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;      
      END IF;
      IF new.is_pkey IS TRUE -- создание первичного ключа
      THEN
        IF (SELECT table_type FROM meta.entity WHERE entity_id = new.entity_id) = 'r'
        THEN
          SELECT array_append(array_agg(quote_ident(column_name)), quote_ident(new_column_name)) INTO pkeys FROM meta.property WHERE entity_id = new.entity_id AND is_pkey = TRUE;
          EXECUTE(format('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', new_entity, COALESCE((SELECT conname FROM pg_constraint WHERE contype = 'p' AND conrelid = new.entity_id), new_table||'_key')));
          EXECUTE(format('ALTER TABLE %s ADD CONSTRAINT %s_key PRIMARY KEY (%s)', new_entity, new_table, array_to_string(pkeys, ',')));
        END IF;
      END IF;
    ELSE
      SELECT * FROM meta.exceptions WHERE errcode = 'PA018' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
    INSERT INTO meta.property_extra(property_name, entity_id, type, ref_entity, ref_key, p_name, is_pkey)
      SELECT format('%s.%s', new.entity_id, new_column_name), new.entity_id, new.type, new.ref_entity, new_ref_key, new_column_name, new.is_pkey;
    RETURN new;
  END IF;

  IF  TG_OP = 'UPDATE' THEN
    SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity, table_name INTO new_entity, new_table FROM meta.entity WHERE entity_id = new.entity_id;
    new_column_name = old.column_name;
    new_ref_key = old.ref_key;
    old_property_name = format('%s.%s', old.entity_id, old.column_name);
    IF EXISTS (SELECT * FROM meta.entity WHERE entity_id = new.entity_id AND table_type = 'r') -- действия для таблиц
    THEN
      new_column_name = lower(new.column_name);
      IF new_column_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}'
      THEN
        new.property_name = format('%s.%s', new.entity_id, new_column_name);
        -- Перименование
        IF new_column_name <> old.column_name THEN
          EXECUTE (format('ALTER TABLE %s RENAME COLUMN %s TO %s', new_entity, quote_ident(old.column_name), quote_ident(new_column_name)));
        END IF;
        -- SET NOT NULL
        IF new.is_nullable <> old.is_nullable THEN
          IF new.is_nullable = FALSE THEN
            EXECUTE(format('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', new_entity, quote_ident(new_column_name)));
          ELSE
            EXECUTE(format('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', new_entity, quote_ident(new_column_name)));
          END IF;
        END IF;
        -- Удаление внешнего ключа
        IF old.ref_entity IS NOT NULL AND (old.ref_entity <> new.ref_entity OR new.ref_entity IS NULL)
        THEN
          new.type = CASE WHEN new.type = 'ref' THEN 'string' ELSE new.type END;
          new_ref_key = NULL;
          IF old.constraint_name IS NOT NULL THEN
            EXECUTE (format('ALTER TABLE %s DROP CONSTRAINT %s', new_entity, quote_ident(old.constraint_name)));
          ELSE
            DELETE FROM meta.relation_extra WHERE entity_id = old.ref_entity AND ref_key = old.ref_key AND relation_entity = old.entity_id AND key = old.column_name;
          END IF;
        END IF;
        -- Установка внешнего ключа
        IF new.ref_entity IS NOT NULL AND (old.ref_entity <> new.ref_entity OR old.ref_entity IS NULL)
        THEN
          SELECT format ('%s.%s', quote_ident(schema_name), quote_ident(table_name)) AS entity INTO new_ref_entity FROM meta.entity WHERE entity_id = new.ref_entity;
          new_ref_key = COALESCE(new.ref_key, (SELECT primarykey FROM meta.entity WHERE entity_id = new.ref_entity));
          IF NOT EXISTS (SELECT * FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key)
          THEN
            SELECT * FROM meta.exceptions WHERE errcode = 'PA017' INTO _exc_row;
            RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
          END IF;
          new.type = 'ref';
          SELECT data_type INTO new.data_type FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key;
          SELECT data_type_len INTO new.data_type_len FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key;
          SELECT title FROM meta.entity INTO cont_name WHERE entity_id = new.entity_id;
          IF EXISTS(SELECT * FROM meta.entity WHERE entity_id = new.ref_entity AND table_type = 'r')  -- ссылка на таблицу 
          THEN
              EXECUTE(format('ALTER TABLE %s ADD CONSTRAINT %s_%s_fkey FOREIGN KEY (%s) REFERENCES %s (%s) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION',
                new_entity, new_table, new_column_name, quote_ident(new_column_name), new_ref_entity, quote_ident(new_ref_key)));
              EXECUTE(format('COMMENT ON CONSTRAINT %s_%s_fkey ON %s IS %L', new_table, new_column_name, new_entity, cont_name));
          ELSE  -- ссылка на представлние
              INSERT INTO meta.relation (title, entity_id, ref_key, relation_entity, key, virtual)
	              VALUES (cont_name, new.ref_entity, new_ref_key, new.entity_id, new_column_name, TRUE);
          END IF;
        END IF;
        -- Изменение первичного ключа
        IF new.is_pkey IS DISTINCT FROM old.is_pkey
        THEN
          IF new.is_pkey = TRUE
          THEN
            SELECT array_append(array_agg(quote_ident(column_name)), quote_ident(new_column_name)) INTO pkeys FROM meta.property WHERE entity_id = new.entity_id AND is_pkey = TRUE;
          ELSE
            SELECT array_agg(quote_ident(column_name)) INTO pkeys FROM meta.property WHERE entity_id = new.entity_id AND is_pkey = TRUE AND column_name <> new_column_name;
          END IF;
          EXECUTE(format('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', new_entity, COALESCE((SELECT conname FROM pg_constraint WHERE contype = 'p' AND conrelid = new.entity_id), new_table||'_key')));
          EXECUTE(format('ALTER TABLE %s ADD CONSTRAINT %s_key PRIMARY KEY (%s)', new_entity, new_table, array_to_string(pkeys, ',')));
        END IF;
        -- Изменение типа данных
        IF new.data_type <> old.data_type OR new.data_type_len IS DISTINCT FROM old.data_type_len THEN
          _data_type = format('%s%s',
            new.data_type,
            CASE WHEN new.data_type_len IS NULL THEN '' ELSE format('(%s)', new.data_type_len) END
          );
          EXECUTE (format('ALTER TABLE %s ALTER COLUMN %s SET DATA TYPE %s USING (%s::%s)', new_entity, quote_ident(new_column_name), _data_type, quote_ident(new_column_name), _data_type));
        END IF;
        -- Изменение значения по умолчанию
        IF new."default" <> old."default" OR old."default" IS NULL OR new."default" IS NULL THEN
          IF new."default" IS NOT NULL THEN
            EXECUTE(format('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT %s', new_entity, quote_ident(new_column_name), new."default"));
          ELSE
            IF old."default" IS NOT NULL THEN
              EXECUTE(format('ALTER TABLE %s ALTER COLUMN %s DROP DEFAULT', new_entity, quote_ident(new_column_name)));
            END IF;
          END IF;
        END IF;
      ELSE
        SELECT * FROM meta.exceptions WHERE errcode = 'PA008' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      END IF;
    ELSEIF EXISTS (SELECT * FROM meta.entity WHERE entity_id = new.entity_id AND table_type = 'v') -- действия для представлений
    THEN
      new.property_name = old.property_name;
      new_column_name = old.column_name;
      -- Попытка смены наименования
      IF new.column_name <> old.column_name THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA019' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      END IF;
      -- Попытка смены типа данных
      IF new.data_type <> old.data_type OR new.data_type_len IS DISTINCT FROM old.data_type_len THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA019' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      END IF;
      -- Удаление ссылки
      IF old.ref_entity IS NOT NULL AND new.ref_entity IS NULL
      THEN
        new.type = CASE WHEN new.type = 'ref' THEN 'string' ELSE new.type END;
        new_ref_key = NULL;
        DELETE FROM meta.relation_extra WHERE entity_id = old.ref_entity AND ref_key = old.ref_key AND relation_entity = old.entity_id AND key = old.column_name;
      END IF;
      -- Установка ссылки
      IF new.ref_entity IS NOT NULL AND (old.ref_entity <> new.ref_entity OR old.ref_entity IS NULL)
      THEN
        new.type = 'ref';
        new_ref_key = COALESCE(new.ref_key, (SELECT primarykey FROM meta.entity WHERE entity_id = new.ref_entity));
        IF NOT EXISTS (SELECT * FROM meta.property WHERE entity_id = new.ref_entity AND column_name = new_ref_key)
        THEN
          SELECT * FROM meta.exceptions WHERE errcode = 'PA017' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
        SELECT title FROM meta.entity INTO cont_name WHERE entity_id = new.entity_id;
        INSERT INTO meta.relation (title, entity_id, ref_key, relation_entity, key, virtual)
          VALUES (cont_name, new.ref_entity, new_ref_key, new.entity_id, new_column_name, TRUE);
      END IF;
    ELSE
      SELECT * FROM meta.exceptions WHERE errcode = 'PA006' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;
    -- Изменение заголовка
    IF new.title <> old.title OR old.title IS NULL OR new.title IS NULL
    THEN
      EXECUTE(format('COMMENT ON COLUMN %s.%s IS %L', new_entity, quote_ident(new_column_name), COALESCE(new.title, '')));
    END IF;
    -- Обновление метаданных
    UPDATE meta.property_extra SET property_name = new.property_name, type = new.type, ref_entity = new.ref_entity, ref_key = new_ref_key, p_name = new_column_name, is_pkey = new.is_pkey
      WHERE property_extra.property_name = old_property_name;
    INSERT INTO meta.property_extra(property_name, entity_id, type, ref_entity, ref_key, p_name, is_pkey)
      SELECT new.property_name, new.entity_id, new.type, new.ref_entity, new_ref_key, new_column_name, new.is_pkey
      WHERE NOT EXISTS (SELECT * FROM  meta.property_extra WHERE property_extra.property_name = new.property_name);
    RETURN new;
  END IF;
END;$$;
--
CREATE TRIGGER property_trg INSTEAD OF INSERT OR UPDATE OR DELETE
  ON meta.property FOR EACH ROW EXECUTE PROCEDURE meta.property_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.relation_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _exc_row    RECORD;
BEGIN
  IF  TG_OP = 'DELETE' THEN
    IF old.virtual = FALSE THEN
      EXECUTE
      (
        'ALTER TABLE '||(select quote_ident(schema_name)||'.'||quote_ident(table_name) from meta.entity where entity_id = old.relation_entity)||
        ' DROP CONSTRAINT  '||(select conname from pg_constraint where conrelid = old.relation_entity and confrelid = old.entity_id)
      );
    ELSE
      DELETE FROM meta.relation_extra WHERE relation_name = old.relation_name;
    END IF;
    -- Удаление иформации из meta.property
    UPDATE meta.property SET ref_entity = NULL WHERE entity_id = old.relation_entity AND column_name = old.key;
    RETURN old;
  END IF;

  IF (select count(*) from meta.property where entity_id = new.relation_entity and column_name = new.key) <> 1
  THEN
    SELECT * FROM meta.exceptions WHERE errcode = 'PA020' INTO _exc_row;
    RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
  END IF;

  IF (select count(*) from meta.property where entity_id = new.entity_id and column_name = new.ref_key) <> 1
  THEN
    SELECT * FROM meta.exceptions WHERE errcode = 'PA009' INTO _exc_row;
    RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
  END IF;

  IF  TG_OP = 'UPDATE' THEN
    IF new.virtual <> old.virtual THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA021' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    END IF;

    IF new.title <> old.title AND new.virtual = false THEN
      EXECUTE
      (
        format('COMMENT ON CONSTRAINT %s ON %s IS %L',
          (select conname from pg_constraint where conrelid = new.relation_entity and confrelid = new.entity_id),
          (select quote_ident(schema_name)||'.'||quote_ident(table_name) from meta.entity where entity_id = new.relation_entity),
          new.title
        )
      );
	  END IF;

    IF new.virtual = true THEN
      UPDATE meta.relation_extra SET relation_name = format('%s.%s.%s.%s', new.entity_id, new.ref_key, new.relation_entity, new.key),
        relation_entity = new.relation_entity, entity_id = new.entity_id, title = new.title, ref_key = new.ref_key, key = new.key
      WHERE relation_extra.relation_name = format('%s.%s.%s.%s', old.entity_id, old.ref_key, old.relation_entity, old.key);
	  END IF;

    RETURN new;
  END IF;

  IF  TG_OP = 'INSERT' THEN
    IF new.virtual = FALSE THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA018' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    ELSE
      INSERT INTO meta.relation_extra(relation_name, relation_entity, entity_id, title, ref_key, key)
        SELECT
          format('%s.%s.%s.%s', new.entity_id, new.ref_key, new.relation_entity, new.key),
          new.relation_entity,
          new.entity_id,
          new.title,
          new.ref_key,
          new.key
        WHERE NOT exists
          (SELECT * FROM  meta.relation_extra WHERE relation_extra.relation_name = format('%s.%s.%s.%s', new.entity_id, new.ref_key, new.relation_entity, new.key));
    END IF;
    RETURN new;
  END IF;
  RETURN new;
END;$$;
--
CREATE TRIGGER relation_trg INSTEAD OF INSERT OR UPDATE OR DELETE ON meta.relation FOR EACH ROW
  EXECUTE PROCEDURE meta.relation_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.schema_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _exc_row            RECORD;
  new_schema_name     TEXT;
BEGIN
  IF TG_OP = 'DELETE' THEN
    EXECUTE('DROP SCHEMA '||quote_ident(old.schema_name));
    PERFORM  meta.clean();
    RETURN old;
  END IF;
  IF TG_OP = 'INSERT' THEN
    IF new.schema_name IS NULL
    THEN
      SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
      RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
    ELSE
      new_schema_name = lower(new.schema_name);
      IF new_schema_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
        IF EXISTS(SELECT schema_name FROM information_schema.schemata WHERE schema_name = new_schema_name)
        THEN
          SELECT * FROM meta.exceptions WHERE errcode = 'PA025' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      ELSE
        SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      END IF;
    END IF;
    EXECUTE ('CREATE SCHEMA ' || quote_ident(new_schema_name));
    EXECUTE ('COMMENT ON SCHEMA ' || quote_ident(new_schema_name) || ' IS '''|| new.title||'''');
    RETURN new;
  END IF;
  IF TG_OP = 'UPDATE' THEN
    IF new.title <> old.title THEN
      EXECUTE ('COMMENT ON SCHEMA ' || quote_ident(old.schema_name) || ' IS '''|| new.title||'''');
    END IF;
    IF new.schema_name <> old.schema_name THEN
      IF new.schema_name IS NULL 
      THEN
        SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
        RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
      ELSE
        new_schema_name = lower(new.schema_name);
        IF new_schema_name SIMILAR TO '[a-z_][a-z_0-9]{0,62}' THEN
          IF EXISTS(SELECT schema_name FROM information_schema.schemata WHERE schema_name = new_schema_name)
          THEN
            SELECT * FROM meta.exceptions WHERE errcode = 'PA025' INTO _exc_row;
            RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
          END IF;
        ELSE
          SELECT * FROM meta.exceptions WHERE errcode = 'PA012' INTO _exc_row;
          RAISE EXCEPTION USING MESSAGE = _exc_row.message, DETAIL = COALESCE(_exc_row.detail, ''), HINT = COALESCE(_exc_row.hint, ''), ERRCODE = _exc_row.errcode;
        END IF;
      END IF;
      EXECUTE ('ALTER SCHEMA ' || quote_ident(old.schema_name) || ' RENAME TO ' || quote_ident(new_schema_name));
      RETURN new;
    END IF;
    RETURN new;
  END IF;
END;$$;
--
CREATE TRIGGER schema_trg INSTEAD OF INSERT OR UPDATE OR DELETE
ON meta.schema FOR EACH ROW EXECUTE PROCEDURE meta.schema_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.function_DELETE_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  old_function_schema TEXT;
  old_function_name TEXT;
BEGIN
  --PERFORM udf_dropfunction(old.function_name);
  old_function_schema := quote_ident(old.function_schema);
  old_function_name := quote_ident(old.function_name);
  EXECUTE 'DROP FUNCTION ' || format('%s.%s(%s)', old_function_schema, old_function_name,old.function_attributes);
  RETURN old;
END;$$;
--
CREATE TRIGGER function_delete_trg INSTEAD OF DELETE ON meta.functions FOR EACH ROW EXECUTE PROCEDURE meta.function_delete_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.function_insert_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
  new_function_schema TEXT;
  new_function_name TEXT;

BEGIN
  new_function_schema := quote_ident(new.function_schema);
  new_function_name := quote_ident(new.function_name);

  EXECUTE 'CREATE OR REPLACE FUNCTION ' || new_function_schema || '.' || new_function_name || ' (' || COALESCE(new.function_attributes, '') || ') RETURNS ' || new.rval_type || ' AS $BODY$' || new.function_code || '$BODY$ LANGUAGE plpgsql VOLATILE NOT LEAKPROOF';
  RETURN new;
END;$_$;
--
CREATE TRIGGER function_insert_trg INSTEAD OF INSERT ON meta.functions FOR EACH ROW EXECUTE PROCEDURE meta.function_insert_trgf();
--
--
CREATE OR REPLACE FUNCTION meta.function_update_trgf() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
  old_function_schema TEXT;
  old_function_name TEXT;

BEGIN
  old_function_schema := quote_ident(old.function_schema);
  old_function_name := quote_ident(old.function_name);
  --PERFORM udf_dropfunction(old.function_name);
  EXECUTE 'CREATE OR REPLACE FUNCTION ' || old_function_schema || '.' || old_function_name || ' (' || COALESCE(old.function_attributes, '') || ') RETURNS ' || old.rval_type || ' AS $BODY$ ' || new.function_code || '$BODY$ LANGUAGE plpgsql VOLATILE NOT LEAKPROOF';
  RETURN new;
END;$_$;
--
CREATE TRIGGER function_update_trg INSTEAD OF UPDATE ON meta.functions FOR EACH ROW EXECUTE PROCEDURE meta.function_update_trgf();
--
--
--  Установка данных схемы meta (en)
--
INSERT INTO meta.type_match VALUES (16, 'bool');
INSERT INTO meta.type_match VALUES (21 , 'integer');
INSERT INTO meta.type_match VALUES (23 , 'integer');
INSERT INTO meta.type_match VALUES (1082 , 'date');
INSERT INTO meta.type_match VALUES (1184 , 'datetime');
INSERT INTO meta.type_match VALUES (1114 , 'datetime');
--
--
INSERT INTO meta.entity_type VALUES ('r', 'Table');
INSERT INTO meta.entity_type VALUES ('v', 'View');
--
--
INSERT INTO meta.property_type VALUES ('bool'      , 'True of false'                        );
INSERT INTO meta.property_type VALUES ('button'    , 'Button'                               );
INSERT INTO meta.property_type VALUES ('caption'   , 'Headline'                             );
INSERT INTO meta.property_type VALUES ('date'      , 'Date'                                 );
INSERT INTO meta.property_type VALUES ('datetime'  , 'Date and time'                        );
INSERT INTO meta.property_type VALUES ('file'      , 'File'                                 );
INSERT INTO meta.property_type VALUES ('integer'   , 'Integer'                              );
INSERT INTO meta.property_type VALUES ('address'   , 'Address'                              );
INSERT INTO meta.property_type VALUES ('plain'     , 'Text without formatting'              );
INSERT INTO meta.property_type VALUES ('ref'       , 'Lookup'                               );
INSERT INTO meta.property_type VALUES ('ref_link'  , 'Link'                                 );
INSERT INTO meta.property_type VALUES ('string'    , 'String'                               );
INSERT INTO meta.property_type VALUES ('text'      , 'Rich text'                            );
INSERT INTO meta.property_type VALUES ('time'      , 'Time'                                 );
INSERT INTO meta.property_type VALUES ('titleLink' , 'Link with a title (link || title)'    );
INSERT INTO meta.property_type VALUES ('money'     , 'Money'                                );
INSERT INTO meta.property_type VALUES ('ref_tree'  , 'Classifier link'                      );
INSERT INTO meta.property_type VALUES ('parent_id' , 'Parent reference'                     );
INSERT INTO meta.property_type VALUES ('row_color' , 'Row color'                            );
INSERT INTO meta.property_type VALUES ('filedb'    , 'File in the database'                 );
INSERT INTO meta.property_type VALUES ('progress'  , 'Horizontal indicator'                 );
INSERT INTO meta.property_type VALUES ('invisible' , 'Invisible'                            );
INSERT INTO meta.property_type VALUES ('image'     , 'Image in the file'                    );
INSERT INTO meta.property_type VALUES ('imagedb'   , 'Image in the database'                );
INSERT INTO meta.property_type VALUES ('duration'  , 'Duration (period) by ISO'             );
INSERT INTO meta.property_type VALUES ('complex'   , 'Complex type'                         );
INSERT INTO meta.property_type VALUES ('uint8h'    , 'Item Number'                          );
INSERT INTO meta.property_type VALUES ('mtext'     , 'Multitext'                            );
INSERT INTO meta.property_type VALUES ('code-sql'  , 'SQL format'                           );
INSERT INTO meta.property_type VALUES ('code-js'   , 'JavaScript format'                    );
INSERT INTO meta.property_type VALUES ('json'      , 'JSON format'                          );
INSERT INTO meta.property_type VALUES ('checkbox-group' , 'Checkbox Group'                  );
INSERT INTO meta.property_type VALUES ('radiobutton'    , 'Radiobutton Group'               );
--
--
INSERT INTO meta.exceptions(errcode, message, detail, hint) 
  VALUES ('PA001', 'Update of view definition is not available. %s uses a virtual field from this view', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint) 
  VALUES ('PA002', 'Update of view definition is not available. %s has virtual relation with this view', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint) 
  VALUES ('PA003', 'Update of view definition is not available. %s is depending from this view', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint) 
  VALUES ('PA004', 'The projection is already in the menu', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint) 
  VALUES ('PA005', 'Metadata uploads only for views', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA006', 'Entity does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA007', 'Parent entity does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA008', 'Incorrect column name', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA009', 'Invalid parent key name', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA010', 'Child entity does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA011', 'Schema does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA012', 'Incorrect schema name', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA013', 'incorrect table name', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA014', 'Incorrect primary key name', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA015', 'Property does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA016', 'Unable to delete', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA017', 'Reference entity key does not exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA018', 'Unable to add', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA019', 'Unable to update', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA020', 'Incorrect child entity key', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA021', 'Changing of relation type does not available', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA022', 'Such a virtual field already exists', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA023', 'Incorrect projection or column', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA024', 'Entity does not have the specified projection', NULL, NULL);
INSERT INTO meta.exceptions(errcode, message, detail, hint)
  VALUES ('PA025', 'Schema already exists', NULL, NULL);
--
--
-- entity_type
UPDATE meta.property SET type = 'invisible'      WHERE entity_id = 'meta.entity_type'::regclass::oid AND column_name = 'type';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity_type'::regclass::oid AND column_name = 'note';
-- menu_item
UPDATE meta.property SET type = 'invisible'      WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'menu_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'title';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.menu_item'::regclass::oid, ref_key = 'menu_id' WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'parent';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'table_name' WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'projection';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'view_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'role';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = '_order';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'iconclass';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'style';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.menu_item'::regclass::oid AND column_name = 'key';
-- options
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.options'::regclass::oid AND column_name = 'name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.options'::regclass::oid AND column_name = 'value';
-- property_type
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property_type'::regclass::oid AND column_name = 'type';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property_type'::regclass::oid AND column_name = 'note';
-- column_type
UPDATE meta.property SET type = 'string', is_pkey = TRUE         WHERE entity_id = 'meta.column_type'::regclass::oid AND column_name = 'data_type';
-- entity
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'entity_id';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.schema'::regclass::oid, ref_key = 'schema_name' WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'schema_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'table_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'title';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'primarykey';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity_type'::regclass::oid, ref_key = 'type' WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'table_type';
UPDATE meta.property SET type = 'code-sql'       WHERE entity_id = 'meta.entity'::regclass::oid AND column_name = 'view_definition';
-- entity_edit
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'entity_id';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.schema'::regclass::oid, ref_key = 'schema_name' WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'schema_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'table_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'title';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'primarykey';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity_type'::regclass::oid, ref_key = 'type' WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'table_type';
UPDATE meta.property SET type = 'code-sql'       WHERE entity_id = 'meta.entity_edit'::regclass::oid AND column_name = 'view_definition';
-- function_type
UPDATE meta.property SET type = 'string', is_pkey = TRUE         WHERE entity_id = 'meta.function_type'::regclass::oid AND column_name = 'rval_type';
-- functions
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'function_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'function_schema';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'function_name';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.function_type'::regclass::oid, ref_key = 'rval_type' WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'rval_type';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'function_attributes';
UPDATE meta.property SET type = 'code-sql'       WHERE entity_id = 'meta.functions'::regclass::oid AND column_name = 'function_code';
-- property
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'property_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'column_name';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'entity_id' WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'entity_id';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.property_type'::regclass::oid, ref_key = 'type' WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'type';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.column_type'::regclass::oid, ref_key = 'data_type' WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'data_type';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'visible';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'readonly';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'title';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'entity_id' WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'ref_entity';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'ref_key';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = '_order';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'constraint_name';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'is_nullable';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.property'::regclass::oid AND column_name = 'default';
-- relation
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'relation_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'title';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'entity_id' WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'entity_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'ref_key';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'entity_id' WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'relation_entity';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'key';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.relation'::regclass::oid AND column_name = 'virtual';
-- schema
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.schema'::regclass::oid AND column_name = 'schema_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.schema'::regclass::oid AND column_name = 'schema_name';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.schema'::regclass::oid AND column_name = 'title';
-- triggers
UPDATE meta.property SET type = 'invisible', is_pkey = TRUE      WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'trigger_id';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.entity'::regclass::oid, ref_key = 'entity_id' WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'entity_id';
UPDATE meta.property SET type = 'string'         WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'trigger_name';
UPDATE meta.property SET type = 'ref', ref_entity = 'meta.functions'::regclass::oid, ref_key = 'function_id' WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'function_id';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'trigger_state';
UPDATE meta.property SET type = 'bool'           WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'type_row';
UPDATE meta.property SET type = 'radiobutton'    WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'tg_type';
UPDATE meta.property SET type = 'checkbox-group' WHERE entity_id = 'meta.triggers'::regclass::oid AND column_name = 'tg_event';
--
--
UPDATE meta.relation_extra re SET (title) = 
(
  SELECT t.title
  FROM
  (
    SELECT 'meta', 'entity',            'table_name',      'meta', 'menu_item',           'projection',              'Used in the menu'                       UNION
    SELECT 'meta', 'schema',            'schema_name',     'meta', 'entity',              'schema_name',             'Entities'                               UNION
    SELECT 'meta', 'entity_type',       'type',            'meta', 'entity',              'table_type',              'Entities'                               UNION 
    SELECT 'meta', 'schema',            'schema_name',     'meta', 'entity_edit',         'schema_name',             'Entities witout meta'                   UNION
    SELECT 'meta', 'entity_type',       'type',            'meta', 'entity_edit',         'table_type',              'Entities witout meta'                   UNION
    SELECT 'meta', 'function_type',     'rval_type',       'meta', 'functions',           'rval_type',               'Functions'                              UNION
    SELECT 'meta', 'entity',            'entity_id',       'meta', 'property',            'entity_id',               'Properties'                             UNION
    SELECT 'meta', 'property_type',     'type',            'meta', 'property',            'type',                    'Properties'                             UNION
    SELECT 'meta', 'column_type',       'data_type',       'meta', 'property',            'data_type',               'Properties'                             UNION
    SELECT 'meta', 'entity',            'entity_id',       'meta', 'property',            'ref_entity',              'Refs in properties'                     UNION
    SELECT 'meta', 'entity',            'entity_id',       'meta', 'relation',            'entity_id',               'Relations'                              UNION
    SELECT 'meta', 'entity',            'entity_id',       'meta', 'relation',            'relation_entity',         'Child entity in'                        UNION
    SELECT 'meta', 'entity',            'entity_id',       'meta', 'triggers',            'entity_id',               'Triggers'                               UNION
    SELECT 'meta', 'functions',         'function_id',     'meta', 'triggers',            'function_id',             'Triggers'
  ) t (e_schema, e_table, ref_key, r_schema, r_table, key, title)
      WHERE re.e_schema = t.e_schema AND re.e_table = t.e_table AND re.ref_key = t.ref_key AND re.r_schema = t.r_schema AND re.r_table = t.r_table AND re.key = t.key
)
WHERE e_schema = 'meta';
--
--
INSERT INTO meta.menu_item(name, title, parent, projection, view_id, role, _order, iconclass, style, key) VALUES ('meta','Configuration',NULL,NULL,'list',NULL,1,NULL,NULL,NULL);
INSERT INTO meta.menu_item(name, title, parent, projection, view_id, role, _order, iconclass, style, key) VALUES ('meta.entity_edit','Entities',(SELECT menu_id FROM meta.menu_item WHERE name = 'meta'),'entity_edit','list',NULL,32,NULL,NULL,NULL);
INSERT INTO meta.menu_item(name, title, parent, projection, view_id, role, _order, iconclass, style, key) VALUES ('meta.menu_item','Menu items',(SELECT menu_id FROM meta.menu_item WHERE name = 'meta'),'menu_item','list',NULL,23,NULL,NULL,NULL);
INSERT INTO meta.menu_item(name, title, parent, projection, view_id, role, _order, iconclass, style, key) VALUES ('meta.schema','Schemas',(SELECT menu_id FROM meta.menu_item WHERE name = 'meta'),'schema','list',NULL,31,NULL,NULL,NULL);
INSERT INTO meta.menu_item(name, title, parent, projection, view_id, role, _order, iconclass, style, key) VALUES ('public','public',NULL,NULL,'list',NULL,2,NULL,NULL,NULL);
--
--
--  Установка комментариев (en)
--
COMMENT ON TABLE meta.options IS 'System options';
COMMENT ON COLUMN meta.options.name  IS 'Option name';
COMMENT ON COLUMN meta.options.value IS 'Option value';
--
--
COMMENT ON TYPE meta.file IS 'Structured file storage';
--
--
COMMENT ON TABLE  meta.type_match               IS 'Type matching';
COMMENT ON COLUMN meta.type_match.data_type_id  IS 'Data type in database';
COMMENT ON COLUMN meta.type_match.property_type IS 'Type on display';
--
--
COMMENT ON TABLE  meta.entity_extra        IS 'Extra parameters of entities';
COMMENT ON COLUMN meta.entity_extra.entity_id       IS 'Identifier';
COMMENT ON COLUMN meta.entity_extra.primarykey      IS 'Primary key';
COMMENT ON COLUMN meta.entity_extra.e_schema        IS 'Schema name';
COMMENT ON COLUMN meta.entity_extra.e_table         IS 'Table name';
--
--
COMMENT ON TABLE  meta.property_extra                 IS 'Extra parameters of properties';
COMMENT ON COLUMN meta.property_extra.property_name   IS 'Identifier';
COMMENT ON COLUMN meta.property_extra.type            IS 'Type on display';
COMMENT ON COLUMN meta.property_extra.ref_entity      IS 'Reference entity';
COMMENT ON COLUMN meta.property_extra.ref_key         IS 'Reference entity key';
COMMENT ON COLUMN meta.property_extra.entity_id       IS 'Entity';
COMMENT ON COLUMN meta.property_extra.e_schema        IS 'Schema name';
COMMENT ON COLUMN meta.property_extra.e_table         IS 'Table name';
COMMENT ON COLUMN meta.property_extra.p_name          IS 'Column name';
COMMENT ON COLUMN meta.property_extra.r_schema        IS 'Parent schema name';
COMMENT ON COLUMN meta.property_extra.r_table         IS 'Parent table name';
--
--
COMMENT ON TABLE  meta.relation_extra                 IS 'Extra parameters of relations';
COMMENT ON COLUMN meta.relation_extra.relation_name   IS 'Identifier';
COMMENT ON COLUMN meta.relation_extra.title           IS 'Title';
COMMENT ON COLUMN meta.relation_extra.entity_id       IS 'Parent entity';
COMMENT ON COLUMN meta.relation_extra.e_schema        IS 'Parent schema name';
COMMENT ON COLUMN meta.relation_extra.e_table         IS 'Parent table name';
COMMENT ON COLUMN meta.relation_extra.ref_key         IS 'Parent key';
COMMENT ON COLUMN meta.relation_extra.relation_entity IS 'Child entity';
COMMENT ON COLUMN meta.relation_extra.r_schema        IS 'Child entity schema name';
COMMENT ON COLUMN meta.relation_extra.r_table         IS 'Child entity table name';
COMMENT ON COLUMN meta.relation_extra.key             IS 'Child entity key';
--
--
COMMENT ON TABLE   meta.menu_item             IS 'Menu items';
COMMENT ON COLUMN  meta.menu_item.menu_id     IS 'Identifier';
COMMENT ON COLUMN  meta.menu_item.name        IS 'Name';
COMMENT ON COLUMN  meta.menu_item.title       IS 'Title';
COMMENT ON COLUMN  meta.menu_item.parent      IS 'Parent';
COMMENT ON COLUMN  meta.menu_item.projection  IS 'Entity';
COMMENT ON COLUMN  meta.menu_item.view_id     IS 'View template';
COMMENT ON COLUMN  meta.menu_item.role        IS 'Role';
COMMENT ON COLUMN  meta.menu_item._order      IS 'Orderr';
COMMENT ON COLUMN  meta.menu_item.iconclass   IS 'Menu icon';
COMMENT ON COLUMN  meta.menu_item.style       IS 'Style';
COMMENT ON COLUMN  meta.menu_item.key         IS 'Key';
--
--
COMMENT ON VIEW meta.triggers                   IS 'Triggers';
COMMENT ON COLUMN meta.triggers.trigger_id      IS 'Identifier';
COMMENT ON COLUMN meta.triggers.entity_id       IS 'Entity';
COMMENT ON COLUMN meta.triggers.trigger_name    IS 'Trigger name';
COMMENT ON COLUMN meta.triggers.function_id     IS 'Function';
COMMENT ON COLUMN meta.triggers.trigger_state   IS 'Trigger state';
COMMENT ON COLUMN meta.triggers.type_row        IS 'For each row mode';
COMMENT ON COLUMN meta.triggers.tg_type         IS 'Trigger type';
COMMENT ON COLUMN meta.triggers.tg_event        IS 'Events';
--
--
COMMENT ON VIEW meta.entity                   IS 'Entities';
COMMENT ON COLUMN meta.entity.entity_id       IS 'Identifier';
COMMENT ON COLUMN meta.entity.schema_name     IS 'Schema name';
COMMENT ON COLUMN meta.entity.table_name      IS 'Table name';
COMMENT ON COLUMN meta.entity.title           IS 'Title';
COMMENT ON COLUMN meta.entity.primarykey      IS 'Primary key';
COMMENT ON COLUMN meta.entity.table_type      IS 'Entity type';
COMMENT ON COLUMN meta.entity.view_definition IS 'View definition';
--
--
COMMENT ON VIEW meta.entity_edit                  IS 'Entities';
COMMENT ON COLUMN meta.entity_edit.entity_id      IS 'Identifier';
COMMENT ON COLUMN meta.entity_edit.schema_name    IS 'Schema name';
COMMENT ON COLUMN meta.entity_edit.table_name     IS 'Table name';
COMMENT ON COLUMN meta.entity_edit.title          IS 'Title';
COMMENT ON COLUMN meta.entity_edit.primarykey     IS 'Primary key';
COMMENT ON COLUMN meta.entity_edit.table_type     IS 'Entity type';
--
--
COMMENT ON VIEW meta.property                    IS 'Properties';
COMMENT ON COLUMN meta.property.property_name    IS 'Identifier';
COMMENT ON COLUMN meta.property.column_name      IS 'Column name';
COMMENT ON COLUMN meta.property.entity_id        IS 'Entity';
COMMENT ON COLUMN meta.property.type             IS 'Type on display';
COMMENT ON COLUMN meta.property.data_type        IS 'Data type in database';
COMMENT ON COLUMN meta.property.visible          IS 'Visibility';
COMMENT ON COLUMN meta.property.readonly         IS 'Read-only mode';
COMMENT ON COLUMN meta.property.title            IS 'Title';
COMMENT ON COLUMN meta.property.ref_entity       IS 'Reference entity';
COMMENT ON COLUMN meta.property.ref_key          IS 'Reference entity key';
COMMENT ON COLUMN meta.property._order           IS 'Order';
COMMENT ON COLUMN meta.property.constraint_name  IS 'Costraint name';
COMMENT ON COLUMN meta.property.is_nullable      IS 'Is nullable';
COMMENT ON COLUMN meta.property."default"        IS 'Default value';
COMMENT ON COLUMN meta.property.data_type_len    IS 'Data type length';
--
--
COMMENT ON VIEW meta.relation                   IS 'Relations';
COMMENT ON COLUMN meta.relation.relation_name   IS 'Identifier';
COMMENT ON COLUMN meta.relation.title           IS 'Title';
COMMENT ON COLUMN meta.relation.entity_id       IS 'Parent entity';
COMMENT ON COLUMN meta.relation.ref_key         IS 'Parent entity key';
COMMENT ON COLUMN meta.relation.relation_entity IS 'Child entity';
COMMENT ON COLUMN meta.relation.key             IS 'Child entity key';
COMMENT ON COLUMN meta.relation.virtual         IS 'Virtual relation';
--
--
COMMENT ON VIEW meta.functions                        IS 'User defined functions';
COMMENT ON COLUMN meta.functions.function_id          IS 'Identifier';
COMMENT ON COLUMN meta.functions.function_schema      IS 'Schema name';
COMMENT ON COLUMN meta.functions.function_name        IS 'Function name';
COMMENT ON COLUMN meta.functions.rval_type            IS 'Return value data type';
COMMENT ON COLUMN meta.functions.function_attributes  IS 'Function parameters';
COMMENT ON COLUMN meta.functions.function_code        IS 'Function code';
--
--
COMMENT ON TABLE  meta.entity_type      IS 'Entity types';
COMMENT ON COLUMN meta.entity_type.type IS 'Type name';
COMMENT ON COLUMN meta.entity_type.note IS 'Description';
--
--
COMMENT ON VIEW meta.column_type             IS 'Property data types';
COMMENT ON COLUMN meta.column_type.data_type IS 'Data type name';
--
--
COMMENT ON VIEW meta.function_type             IS 'Function return value types';
COMMENT ON COLUMN meta.function_type.rval_type IS 'Type name';
--
--
COMMENT ON TABLE  meta.property_type      IS 'Property types';
COMMENT ON COLUMN meta.property_type.type IS 'Type name';
COMMENT ON COLUMN meta.property_type.note IS 'Data type name';
--
--
COMMENT ON VIEW meta.schema               IS 'Schemas';
COMMENT ON COLUMN meta.schema.schema_id   IS 'Identifier';
COMMENT ON COLUMN meta.schema.schema_name IS 'Schema name';
COMMENT ON COLUMN meta.schema.title       IS 'Title';
--
--
COMMENT ON TABLE  meta.exceptions          IS 'Exceptions';
COMMENT ON COLUMN meta.exceptions.errcode  IS 'Exception code';
COMMENT ON COLUMN meta.exceptions.message  IS 'Message';
COMMENT ON COLUMN meta.exceptions.detail   IS 'Details';
COMMENT ON COLUMN meta.exceptions.hint     IS 'Hint';
--
--
COMMENT ON FUNCTION meta.update_view(oid, text) IS 'Update of view definition';
--
--
COMMENT ON FUNCTION meta.create_menu() IS 'Menu creation';
--
--
COMMENT ON FUNCTION meta.set_views_pkey IS 'Установка первичных ключей для представлений';
--
--
--  Создание ограничений, индексов, настройка расширения
--
ALTER TABLE meta.entity_extra ADD CONSTRAINT entity_extra_uniq UNIQUE (e_schema, e_table);
ALTER TABLE meta.property_extra ADD CONSTRAINT property_extra_uniq UNIQUE (e_schema, e_table, p_name);
ALTER TABLE meta.relation_extra ADD CONSTRAINT relation_extra_uniq UNIQUE (e_schema, e_table, key, r_schema, r_table, ref_key);
--
--
CREATE INDEX fki_menu_item_fk ON meta.menu_item USING btree (parent);
--
--
GRANT USAGE ON SCHEMA meta TO public;
GRANT SELECT ON TABLE meta.menu TO public;
GRANT SELECT ON TABLE meta.view_projection_entity TO public;
GRANT SELECT ON TABLE meta.view_projection_property TO public;
GRANT SELECT ON TABLE meta.view_projection_relation TO public;
--
--
CREATE ROLE guest WITH
  LOGIN
  NOSUPERUSER
  INHERIT
  NOCREATEDB
  NOCREATEROLE
  NOREPLICATION
  PASSWORD '123456';
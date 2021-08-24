--
-- PostgreSQL database dump
--

CREATE SCHEMA test_modules;


ALTER SCHEMA test_modules OWNER TO postgres;

--
-- Name: SCHEMA test_modules; Type: COMMENT; Schema: -; Owner: postgres
--

SET default_with_oids = false;

--
-- Name: test_schema; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA test_schema;


ALTER SCHEMA test_schema OWNER TO postgres;

--
-- Name: SCHEMA test_schema; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA test_schema IS 'Test schema';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: airport_element; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.airport_element (
                                              element_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                              name text,
                                              parent_key uuid,
                                              has_child boolean
);


ALTER TABLE test_modules.airport_element OWNER TO postgres;

--
-- Name: TABLE airport_element; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON TABLE test_modules.airport_element IS 'Элементы организационной структуры';


--
-- Name: COLUMN airport_element.name; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.airport_element.name IS 'Наименование';


--
-- Name: COLUMN airport_element.parent_key; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.airport_element.parent_key IS 'Родительский элемент';


--
-- Name: class_criteria; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.class_criteria (
                                             class_criteria_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                             element_class_key uuid,
                                             name text
);


ALTER TABLE test_modules.class_criteria OWNER TO postgres;

--
-- Name: TABLE class_criteria; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON TABLE test_modules.class_criteria IS 'Критерий классификации';


--
-- Name: COLUMN class_criteria.element_class_key; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.class_criteria.element_class_key IS 'Класс объект';


--
-- Name: COLUMN class_criteria.name; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.class_criteria.name IS 'Наименование критерия';


--
-- Name: cross_airport_element; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.cross_airport_element (
                                                    element_class_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                                    name text,
                                                    superobject_key uuid,
                                                    note text,
                                                    class_criteria_key uuid
);


ALTER TABLE test_modules.cross_airport_element OWNER TO postgres;

--
-- Name: TABLE cross_airport_element; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON TABLE test_modules.cross_airport_element IS 'Класс объекта';


--
-- Name: COLUMN cross_airport_element.name; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.cross_airport_element.name IS 'Наименование';


--
-- Name: COLUMN cross_airport_element.superobject_key; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.cross_airport_element.superobject_key IS 'Входит в структуру';


--
-- Name: COLUMN cross_airport_element.note; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.cross_airport_element.note IS 'Описание';


--
-- Name: COLUMN cross_airport_element.class_criteria_key; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.cross_airport_element.class_criteria_key IS 'Критерий классификации';


--
-- Name: reporting_forms; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.reporting_forms (
                                              element_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                              aircraft_code character(3),
                                              tm_departure time without time zone,
                                              dt_departure date,
                                              flight character(6)
);


ALTER TABLE test_modules.reporting_forms OWNER TO postgres;

--
-- Name: TABLE reporting_forms; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON TABLE test_modules.reporting_forms IS 'Тестирование отчётных форм';


--
-- Name: COLUMN reporting_forms.aircraft_code; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.reporting_forms.aircraft_code IS 'Код воздушного транспорта';


--
-- Name: COLUMN reporting_forms.tm_departure; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.reporting_forms.tm_departure IS 'Время';


--
-- Name: COLUMN reporting_forms.dt_departure; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.reporting_forms.dt_departure IS 'Дата';


--
-- Name: COLUMN reporting_forms.flight; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON COLUMN test_modules.reporting_forms.flight IS 'Код перелёта';

--
-- Name: text_types; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.text_types (
                                        text_types_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                        meta_plain text,
                                        meta_text text,
                                        detail_plain text,
                                        detail_text text
);


ALTER TABLE test_schema.text_types OWNER TO postgres;


CREATE OR REPLACE FUNCTION test_modules."check"(
    keys uuid[])
    RETURNS text
    LANGUAGE 'sql'

    COST 100
    VOLATILE
AS $BODY$select 'Ok'::text;$BODY$;


--
-- Name: TABLE text_types; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.text_types IS 'Текстовые типы';


--
-- Name: COLUMN text_types.meta_plain; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.text_types.meta_plain IS 'Активация режима текста без форматирования';


--
-- Name: COLUMN text_types.meta_text; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.text_types.meta_text IS 'Активация режима форматированного текста';


--
-- Name: COLUMN text_types.detail_plain; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.text_types.detail_plain IS 'Редактирование текста без форматирования';


--
-- Name: COLUMN text_types.detail_text; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.text_types.detail_text IS 'Редактирование форматированного текста';


--
-- Name: primary_table; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.primary_table (
    primary_table_key uuid DEFAULT uuid_generate_v4() NOT NULL
);


ALTER TABLE test_schema.primary_table OWNER TO postgres;

--
-- Name: TABLE primary_table; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.primary_table IS 'Родительская таблица';


--
-- Name: secondary_table; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.secondary_table (
    secondary_table_key uuid DEFAULT uuid_generate_v4() NOT NULL
);


ALTER TABLE test_schema.secondary_table OWNER TO postgres;

--
-- Name: TABLE secondary_table; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.secondary_table IS 'Дочерняя таблица';


--
-- Name: table_for_column; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.table_for_column (
                                              table_for_column_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                              edit_column_0 numeric,
                                              edit_column_1 numeric,
                                              remove_column_0 numeric,
                                              remove_column_1 numeric
);


ALTER TABLE test_schema.table_for_column OWNER TO postgres;

--
-- Name: TABLE table_for_column; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.table_for_column IS 'Таблица для тестирования столбцов';


--
-- Name: COLUMN table_for_column.edit_column_0; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.table_for_column.edit_column_0 IS 'Edit column from entity';


--
-- Name: COLUMN table_for_column.edit_column_1; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.table_for_column.edit_column_1 IS 'Edit column from property';


--
-- Name: COLUMN table_for_column.remove_column_0; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.table_for_column.remove_column_0 IS 'Remove column from entity';


--
-- Name: COLUMN table_for_column.remove_column_1; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON COLUMN test_schema.table_for_column.remove_column_1 IS 'Remove column from property';


--
-- Name: table_for_remove_0; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.table_for_remove_0 (
    table_for_remove_0_key uuid DEFAULT uuid_generate_v4() NOT NULL
);


ALTER TABLE test_schema.table_for_remove_0 OWNER TO postgres;

--
-- Name: TABLE table_for_remove_0; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.table_for_remove_0 IS 'Для тестирования удаления из schema';

--
-- Name: table_for_remove_1; Type: TABLE; Schema: test_schema; Owner: postgres
--

CREATE TABLE test_schema.table_for_remove_1 (
    table_for_remove_1_key uuid DEFAULT uuid_generate_v4() NOT NULL
);


ALTER TABLE test_schema.table_for_remove_1 OWNER TO postgres;

--
-- Name: TABLE table_for_remove_1; Type: COMMENT; Schema: test_schema; Owner: postgres
--

COMMENT ON TABLE test_schema.table_for_remove_1 IS 'Для тестирования удаления из entity';

--
-- Name: report_report_id_seq; Type: SEQUENCE; Schema: test_modules; Owner: postgres
--

CREATE SEQUENCE test_modules.report_report_id_seq
    INCREMENT 1
    START 1
    MINVALUE 1
    MAXVALUE 9223372036854775807
    CACHE 1;

ALTER SEQUENCE test_modules.report_report_id_seq OWNER TO postgres;

--
-- Name: report; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.report
(
    report_id integer NOT NULL DEFAULT nextval('test_modules.report_report_id_seq'::regclass),
    title text COLLATE pg_catalog."default",
    projection text COLLATE pg_catalog."default",
    params json,
    paper text,
    orient text,
    font text,
    type text,
    CONSTRAINT report_pkey PRIMARY KEY (report_id)
);


ALTER TABLE test_modules.report OWNER to postgres;

--
-- Data for Name: airport_element; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.airport_element VALUES ('1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', 'Airport', NULL, true);
INSERT INTO test_modules.airport_element VALUES ('7a9535c8-4297-407b-9c1a-34e06454b265', 'Ground handling and engineering support', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('304ca106-a5da-4ca8-b99b-36e117c29b4c', 'Economic', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('02f8c9ff-4624-4ddc-bf1e-1067b1cae644', 'Marketing', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('06ca6afd-2f9d-4309-94eb-71c4d6033a07', 'Airport life support', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('aff60b00-c08f-4b6a-999b-996a70a49cc5', 'Planning and development', '304ca106-a5da-4ca8-b99b-36e117c29b4c', false);
INSERT INTO test_modules.airport_element VALUES ('7e1b4f91-49b1-4c70-ab12-8df4570c9007', 'Financial and economic', '304ca106-a5da-4ca8-b99b-36e117c29b4c', false);
INSERT INTO test_modules.airport_element VALUES ('29f7c0db-0adc-4aea-a7d2-b1f82ce1ee89', 'Department of work and wages', '304ca106-a5da-4ca8-b99b-36e117c29b4c', false);
INSERT INTO test_modules.airport_element VALUES ('fe59b74f-7b4b-4eed-81ba-3f12955b99c2', 'Financial department
', '304ca106-a5da-4ca8-b99b-36e117c29b4c', false);
INSERT INTO test_modules.airport_element VALUES ('77d7e995-95ba-4720-8964-4a7675d96c1b', 'Planning department', '304ca106-a5da-4ca8-b99b-36e117c29b4c', false);
INSERT INTO test_modules.airport_element VALUES ('c7684723-ec8a-48ff-b83b-9e650f21a3ac', 'Marketing of aviation activity', '02f8c9ff-4624-4ddc-bf1e-1067b1cae644', false);
INSERT INTO test_modules.airport_element VALUES ('b0410048-0acc-4e5b-9911-919160a3958a', 'Departament of foreign economic relations', '02f8c9ff-4624-4ddc-bf1e-1067b1cae644', false);
INSERT INTO test_modules.airport_element VALUES ('4cb9a933-f1b2-4919-9829-c025a267e2fe', 'Departament of contract preparation and support', '02f8c9ff-4624-4ddc-bf1e-1067b1cae644', false);
INSERT INTO test_modules.airport_element VALUES ('2b60c507-8a89-492e-8a0d-ecb39aa12e4d', 'Aerodrome service', 'ac02c328-da39-4b99-ad2b-569df4bffc0f', false);
INSERT INTO test_modules.airport_element VALUES ('5c14f791-0e37-40b6-9b1a-94453ac59cbf', 'Special transport service', 'ac02c328-da39-4b99-ad2b-569df4bffc0f', false);
INSERT INTO test_modules.airport_element VALUES ('a02469da-eaa1-44d5-8b46-35a115b5fc4b', 'Aeronautical information', 'af16d469-072c-4c3e-ae01-36e6cb39609e', false);
INSERT INTO test_modules.airport_element VALUES ('4a81fbbc-bedc-4f19-b9bb-4002d1dc09ee', 'Radio technical services', 'af16d469-072c-4c3e-ae01-36e6cb39609e', false);
INSERT INTO test_modules.airport_element VALUES ('3b3fb593-cb6e-4a4b-9fba-897a4e464802', 'Emergency service', 'ac02c328-da39-4b99-ad2b-569df4bffc0f', false);
INSERT INTO test_modules.airport_element VALUES ('16977e84-14da-4cff-af57-d212bdb7ec95', 'Ground handing', '7a9535c8-4297-407b-9c1a-34e06454b265', false);
INSERT INTO test_modules.airport_element VALUES ('556f18ec-2043-4688-8592-43dd13df29ad', 'Aircraft maintenance', '7a9535c8-4297-407b-9c1a-34e06454b265', false);
INSERT INTO test_modules.airport_element VALUES ('8391234a-4380-4888-be5c-41d74fa65806', 'Engineering construction and repair work', '7a9535c8-4297-407b-9c1a-34e06454b265', false);
INSERT INTO test_modules.airport_element VALUES ('5c7cbbbe-b2bf-43fe-a1cf-5c35776d6271', 'Fuel service', 'ac02c328-da39-4b99-ad2b-569df4bffc0f', false);
INSERT INTO test_modules.airport_element VALUES ('ac02c328-da39-4b99-ad2b-569df4bffc0f', 'Operation of airport services', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('d0ab3b06-cf70-434f-b921-cf0d68c62920', 'Specialized services', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);
INSERT INTO test_modules.airport_element VALUES ('b858ef4f-6669-4e79-a59a-611399104638', 'Test departament', 'aff60b00-c08f-4b6a-999b-996a70a49cc5', NULL);
INSERT INTO test_modules.airport_element VALUES ('99c2957b-6537-4e4f-a746-5f94916b4394', 'Air traffic control', 'af16d469-072c-4c3e-ae01-36e6cb39609e', false);
INSERT INTO test_modules.airport_element VALUES ('af16d469-072c-4c3e-ae01-36e6cb39609e', 'Air traffic services', '1e8c0ada-d300-4390-a24f-a4a52cf1ac6a', true);


--
-- Data for Name: class_criteria; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.class_criteria VALUES ('abd96bd6-c53f-47ce-8224-164fe5c9909b', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Ground handling and engineering support');
INSERT INTO test_modules.class_criteria VALUES ('fad8e82f-1dfd-458e-98d1-bb9f327dda1f', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Economic');
INSERT INTO test_modules.class_criteria VALUES ('a36e469e-200b-49ff-9afa-3b0b0227a560', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Marketing');
INSERT INTO test_modules.class_criteria VALUES ('d0daeba0-b80a-4768-9bb1-66f4f799d307', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Operation of airport services');
INSERT INTO test_modules.class_criteria VALUES ('cd8e0ab8-5c4f-4a4f-a49f-5653dcf93e30', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Air traffic services');


--
-- Data for Name: cross_airport_element; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.cross_airport_element VALUES ('42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', 'Airport', NULL, NULL, NULL);
INSERT INTO test_modules.cross_airport_element VALUES ('ad662d4a-ace8-43e2-9e37-0330922ca495', 'Ground handing', NULL, NULL, 'abd96bd6-c53f-47ce-8224-164fe5c9909b');
INSERT INTO test_modules.cross_airport_element VALUES ('5b4391a4-03cb-42df-8ffa-b274fcbd2159', 'Aircraft maintenance', NULL, NULL, 'abd96bd6-c53f-47ce-8224-164fe5c9909b');
INSERT INTO test_modules.cross_airport_element VALUES ('cefe0f92-b419-4753-95ec-26d5350909d3', 'Engineering construction and repair work', NULL, NULL, 'abd96bd6-c53f-47ce-8224-164fe5c9909b');
INSERT INTO test_modules.cross_airport_element VALUES ('343fd803-e4bc-470a-99ed-c8d4a458cd85', 'Planning and development', NULL, NULL, 'fad8e82f-1dfd-458e-98d1-bb9f327dda1f');
INSERT INTO test_modules.cross_airport_element VALUES ('3b6f815a-79e6-4e84-ab40-de52e56a56f3', 'Financial and economic', NULL, NULL, 'fad8e82f-1dfd-458e-98d1-bb9f327dda1f');
INSERT INTO test_modules.cross_airport_element VALUES ('966a3ecd-e602-4777-bc6b-7e86b1f88736', 'Department of work and wages', NULL, NULL, 'fad8e82f-1dfd-458e-98d1-bb9f327dda1f');
INSERT INTO test_modules.cross_airport_element VALUES ('40c2619f-ce40-4e1e-8559-83d2f95ac327', 'Financial department', NULL, NULL, 'fad8e82f-1dfd-458e-98d1-bb9f327dda1f');
INSERT INTO test_modules.cross_airport_element VALUES ('23d86ae4-0610-4b1d-b08c-e405fea4baba', 'Planning department', NULL, NULL, 'fad8e82f-1dfd-458e-98d1-bb9f327dda1f');
INSERT INTO test_modules.cross_airport_element VALUES ('20f38105-cfac-48f5-93ea-e72e93f19731', 'Marketing of aviation activity', NULL, NULL, 'a36e469e-200b-49ff-9afa-3b0b0227a560');
INSERT INTO test_modules.cross_airport_element VALUES ('15d1ca98-13d9-4ded-a512-452b98113384', 'Departament of foreign economic relations', NULL, NULL, 'a36e469e-200b-49ff-9afa-3b0b0227a560');
INSERT INTO test_modules.cross_airport_element VALUES ('baa0544a-1a8c-44d2-a1a6-e9ba12668f44', 'Departament of contract preparation and support', NULL, NULL, 'a36e469e-200b-49ff-9afa-3b0b0227a560');
INSERT INTO test_modules.cross_airport_element VALUES ('e664ec35-d5eb-4346-a126-a4e30b5dc0ea', 'Aerodrome service', NULL, NULL, 'd0daeba0-b80a-4768-9bb1-66f4f799d307');
INSERT INTO test_modules.cross_airport_element VALUES ('30e8d6f0-3196-42c0-b87e-4a0a9a600217', 'Special transport service', NULL, NULL, 'd0daeba0-b80a-4768-9bb1-66f4f799d307');
INSERT INTO test_modules.cross_airport_element VALUES ('411f98c3-bcd8-41ec-99b6-2217d0294f57', 'Emergency service', NULL, NULL, 'd0daeba0-b80a-4768-9bb1-66f4f799d307');
INSERT INTO test_modules.cross_airport_element VALUES ('612733f7-b295-41a8-8011-e2861c78c38b', 'Fuel service', NULL, NULL, 'd0daeba0-b80a-4768-9bb1-66f4f799d307');
INSERT INTO test_modules.cross_airport_element VALUES ('c77400e5-706c-4c81-b0d2-739d1df812ae', 'Aeronautical information', NULL, NULL, 'cd8e0ab8-5c4f-4a4f-a49f-5653dcf93e30');
INSERT INTO test_modules.cross_airport_element VALUES ('c4e0ed71-3db8-4882-901d-8507a8d74309', 'Radio technical services', NULL, NULL, 'cd8e0ab8-5c4f-4a4f-a49f-5653dcf93e30');
INSERT INTO test_modules.cross_airport_element VALUES ('dca551e2-8b21-4543-86b9-f897cde247a6', 'Air traffic control', NULL, NULL, 'cd8e0ab8-5c4f-4a4f-a49f-5653dcf93e30');
INSERT INTO test_modules.cross_airport_element VALUES ('d7d7425a-4d7c-4e03-a5b5-9e7460452555', 'Airport life support', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', NULL, NULL);
INSERT INTO test_modules.cross_airport_element VALUES ('6480b89b-2104-4a86-9e30-7365ee0785a3', 'Specialized services', '42df7e6f-906f-4cb4-b43b-f97cf0ffca0e', NULL, NULL);

--
-- Data for Name: reporting_forms; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.reporting_forms VALUES ('2c34c395-a74f-4a7c-b4b2-48914059271d', 'CR2', '09:45:00', '2017-09-13', 'PG0004');
INSERT INTO test_modules.reporting_forms VALUES ('293dceff-e314-4e50-b603-280bd6fd1533', 'CN1', '09:40:00', '2017-07-25', 'PG0007');
INSERT INTO test_modules.reporting_forms VALUES ('e50602b1-af81-48c0-a920-d791fba53a60', 'CN1', '09:25:00', '2017-09-07', 'PG0010');
INSERT INTO test_modules.reporting_forms VALUES ('da3d074a-3440-4a64-9b68-a46fb8b1572b', '773', '15:00:00', '2017-07-16', 'PG0013');
INSERT INTO test_modules.reporting_forms VALUES ('64971ca2-28bb-40d9-94bd-254b3006cf30', 'SU9', '14:25:00', '2017-07-23', 'PG0039');
INSERT INTO test_modules.reporting_forms VALUES ('c2d46dbf-c88f-4438-96e8-24591c39c9b8', 'CR2', '12:15:00', '2017-07-22', 'PG0001');
INSERT INTO test_modules.reporting_forms VALUES ('000c0648-af2f-4628-b402-cdd783a22c47', '773', '02:40:00', '2018-02-17', 'PG0040');
INSERT INTO test_modules.reporting_forms VALUES ('ff319c90-d4ea-4cd9-844e-259b97ba1b6d', '319', '23:20:00', '2018-08-06', 'PG0069');
INSERT INTO test_modules.reporting_forms VALUES ('8a74813c-4068-447b-8c26-867562b3c6cb', 'CN1', '03:50:00', '2018-05-02', 'PG0047');
INSERT INTO test_modules.reporting_forms VALUES ('103053d5-6ceb-42d4-a339-10ecd9890326', 'SU9', '07:05:00', '2018-06-25', 'PG0055');
INSERT INTO test_modules.reporting_forms VALUES ('78d69a9a-62ed-44f7-9317-962c820a36c4', '733', '14:50:00', '2018-12-07', 'PG0073');



UPDATE meta.property SET is_pkey = true WHERE column_name = 'flight_id' AND entity_id = 'bookings.flights_v'::regclass::oid;
--
-- Data for Name: projection_property; Type: TABLE DATA; Schema: meta; Owner: postgres
--
--
-- Data for Name: text_types; Type: TABLE DATA; Schema: test_schema; Owner: postgres
--

INSERT INTO test_schema.text_types (meta_plain, meta_text, detail_plain, detail_text) VALUES ('For multistring text', 'For formated text', 'Europe/Moscow', 'ALINA VOLKOVA');

--
-- Name: class_criteria class_criteria_pkey; Type: CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.class_criteria
    ADD CONSTRAINT class_criteria_pkey PRIMARY KEY (class_criteria_key);


--
-- Name: cross_airport_element element_class_pkey; Type: CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.cross_airport_element
    ADD CONSTRAINT element_class_pkey PRIMARY KEY (element_class_key);


--
-- Name: airport_element element_pkey; Type: CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.airport_element
    ADD CONSTRAINT element_pkey PRIMARY KEY (element_key);


--
-- Name: reporting_forms reports_pkey; Type: CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.reporting_forms
    ADD CONSTRAINT reports_pkey PRIMARY KEY (element_key);


--
-- Name: class_criteria class_criteria_element_class_key_fkey; Type: FK CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.class_criteria
    ADD CONSTRAINT class_criteria_element_class_key_fkey FOREIGN KEY (element_class_key) REFERENCES test_modules.cross_airport_element(element_class_key);


--
-- Name: CONSTRAINT class_criteria_element_class_key_fkey ON class_criteria; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON CONSTRAINT class_criteria_element_class_key_fkey ON test_modules.class_criteria IS 'Критерий классификации';


--
-- Name: cross_airport_element element_class_class_criteria_key_fkey; Type: FK CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.cross_airport_element
    ADD CONSTRAINT element_class_class_criteria_key_fkey FOREIGN KEY (class_criteria_key) REFERENCES test_modules.class_criteria(class_criteria_key);


--
-- Name: CONSTRAINT element_class_class_criteria_key_fkey ON cross_airport_element; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON CONSTRAINT element_class_class_criteria_key_fkey ON test_modules.cross_airport_element IS 'Класс объекта';


--
-- Name: cross_airport_element element_class_superobject_key_fkey; Type: FK CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.cross_airport_element
    ADD CONSTRAINT element_class_superobject_key_fkey FOREIGN KEY (superobject_key) REFERENCES test_modules.cross_airport_element(element_class_key);


--
-- Name: CONSTRAINT element_class_superobject_key_fkey ON cross_airport_element; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON CONSTRAINT element_class_superobject_key_fkey ON test_modules.cross_airport_element IS 'Класс объекта';


--
-- Name: airport_element element_parent_key_fkey; Type: FK CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.airport_element
    ADD CONSTRAINT element_parent_key_fkey FOREIGN KEY (parent_key) REFERENCES test_modules.airport_element(element_key);


--
-- Name: CONSTRAINT element_parent_key_fkey ON airport_element; Type: COMMENT; Schema: test_modules; Owner: postgres
--

COMMENT ON CONSTRAINT element_parent_key_fkey ON test_modules.airport_element IS 'Элементы';


--
-- Name: reporting_forms reports_aircraft_key_fkey; Type: FK CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.reporting_forms
    ADD CONSTRAINT reports_aircraft_key_fkey FOREIGN KEY (aircraft_code) REFERENCES bookings.aircrafts_data(aircraft_code);


--
-- Name: text_types text_types_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.text_types
    ADD CONSTRAINT text_types_pkey PRIMARY KEY (text_types_key);

--
-- Name: primary_table primary_table_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.primary_table
    ADD CONSTRAINT primary_table_pkey PRIMARY KEY (primary_table_key);


--
-- Name: secondary_table secondary_table_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.secondary_table
    ADD CONSTRAINT secondary_table_pkey PRIMARY KEY (secondary_table_key);


--
-- Name: table_for_column table_for_column_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.table_for_column
    ADD CONSTRAINT table_for_column_pkey PRIMARY KEY (table_for_column_key);


--
-- Name: table_for_remove_0 table_for_remove_0_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.table_for_remove_0
    ADD CONSTRAINT table_for_remove_0_pkey PRIMARY KEY (table_for_remove_0_key);


--
-- Name: table_for_remove_1 table_for_remove_1_pkey; Type: CONSTRAINT; Schema: test_schema; Owner: postgres
--

ALTER TABLE ONLY test_schema.table_for_remove_1
    ADD CONSTRAINT table_for_remove_1_pkey PRIMARY KEY (table_for_remove_1_key);


-- СОЗДАТЬ МЕНЮ
select meta.create_menu();

-- Блок ручного добавления

-- INSERT INTO meta.entity (schema_name, table_name, title, create_pkey) VALUES ('test_schema', 'table_for_view', 'Для тестирования представлений', 1);
-- INSERT INTO meta.entity (schema_name, table_name, title, view_definition) VALUES ('test_schema', 'primary_view', 'Родительское представление', 'SELECT pt.primary_table_key'||chr(10)||chr(9)||'FROM test_schema.primary_table pt;');
-- INSERT INTO meta.entity (schema_name, table_name, title, view_definition) VALUES ('test_schema', 'secondary_view', 'Дочернее представление', 'SELECT st.secondary_table_key'||chr(10)||chr(9)||'FROM test_schema.secondary_table st;');

INSERT INTO bookings.aircrafts_data VALUES ('TST', '{"en": "Test model", "ru": "Тестовая модель"}', 98765);

UPDATE meta.property SET is_pkey = true WHERE column_name = 'aircraft_code' AND entity_id = 'bookings.aircrafts'::regclass::oid;
UPDATE meta.property SET is_pkey = true WHERE column_name = 'airport_code' AND entity_id = 'bookings.airports'::regclass::oid;
UPDATE meta.property SET is_pkey = true WHERE column_name = 'flight_no' AND entity_id = 'bookings.routes'::regclass::oid;

UPDATE meta.property SET is_fkey = true WHERE column_name = 'parent_key' AND entity_id = 'test_modules.airport_element'::regclass::oid;

UPDATE meta.menu_item SET view_id = 'treedetailed' WHERE name = 'test_modules.airport_element';
UPDATE meta.menu_item SET view_id = 'treecrossdetailed' WHERE name = 'test_modules.cross_airport_element';

UPDATE meta.property SET type = 'plain' WHERE column_name = 'detail_plain' AND entity_id = (SELECT 'test_schema.text_types'::regclass::oid);
UPDATE meta.property SET type = 'text' WHERE column_name = 'detail_text' AND entity_id = (SELECT 'test_schema.text_types'::regclass::oid);

UPDATE meta.property SET type = 'invisible' WHERE column_name = 'report_id' AND entity_id = (SELECT 'test_modules.report'::regclass::oid);
UPDATE meta.property SET title = 'Наименование отчета' WHERE column_name = 'title' AND entity_id = (SELECT 'test_modules.report'::regclass::oid);
UPDATE meta.property SET title = 'Правила формирования отчета (системные)' WHERE column_name = 'params' AND entity_id = (SELECT 'test_modules.report'::regclass::oid);

-- INSERT INTO test_modules.report (title, projection) VALUES ('Отчет XXX', 'routes');
-- INSERT INTO test_modules.report (title, projection) VALUES ('Отчет YYY', 'entity');

-- UPDATE meta.property SET type = 'caption' WHERE entity_id = (SELECT entity_id FROM meta.entity WHERE schema_name = 'test_modules' AND table_name = 'airport_element') AND column_name = 'name';
-- UPDATE meta.property SET type = 'caption' WHERE entity_id = (SELECT entity_id FROM meta.entity WHERE schema_name = 'test_modules' AND table_name = 'cross_airport_element') AND column_name = 'name';

-- INSERT INTO meta.projection_extra VALUES ('cross_airport_element', 'Класс объекта', 'cross_airport_element', '{ "cross_property": "class_criteria_key",   "cross_relation": "class_criteria",   "cross_relation_property": "element_class_key",   "parent_property": "superobject_key" }', false, (select 'test_modules.cross_airport_element'::regclass::oid), NULL, 'test_modules', 'cross_airport_element');
-- INSERT INTO meta.projection_extra VALUES ('reporting_forms', 'Тестирование отчётных форм', 'reporting_forms', '{ "report_properties":["dt_departure", "aircraft_code"] }', false, (select 'test_modules.reporting_forms'::regclass::oid), NULL, 'test_modules', 'reporting_forms');

-- FUNCTION: test_modules.long_load(reporting_forms)

-- DROP FUNCTION test_modules.long_load(reporting_forms);

CREATE OR REPLACE FUNCTION test_modules.long_load(
    data test_modules.reporting_forms)
    RETURNS text
    LANGUAGE 'plpgsql'

    COST 100
    VOLATILE
AS $BODY$
BEGIN
    SELECT pg_sleep(120);
    RETURN 'Завершено';
END;
$BODY$;

ALTER FUNCTION test_modules.long_load(test_modules.reporting_forms)
    OWNER TO postgres;

-- Правки отображения:
DELETE FROM meta.menu_item WHERE name = 'bookings.aircrafts_data' AND projection = 'aircrafts_data';
DELETE FROM meta.menu_item WHERE name = 'bookings.airports_data' AND projection = 'airports_data';
UPDATE meta.property SET type = 'invisible' WHERE column_name = 'flight_id' AND entity_id = (SELECT 'bookings.ticket_flights'::regclass::oid);
UPDATE meta.property SET type = 'duration' WHERE column_name = 'duration' AND entity_id = (SELECT 'bookings.routes'::regclass::oid);
-- Отображение заголовков основного меню:
UPDATE meta.menu_item SET title = 'Bookings' WHERE name = 'bookings';
UPDATE meta.menu_item SET title = 'Metadata' WHERE name = 'meta';
UPDATE meta.menu_item SET title = 'Public' WHERE name = 'public';
UPDATE meta.menu_item SET title = 'Test modules' WHERE name = 'test_modules';
UPDATE meta.menu_item SET title = 'Test schema' WHERE name = 'test_schema';
UPDATE meta.menu_item SET title = 'Admin' WHERE name = 'admin';
-- Виртуальные зависимости для view:
INSERT INTO meta.relation (entity_id, relation_entity, title, key, virtual, ref_key) VALUES ((SELECT 'bookings.airports'::regclass::oid), (SELECT 'bookings.flights'::regclass::oid), 'Flights', 'departure_airport', true, 'airport_code');
INSERT INTO meta.relation (entity_id, relation_entity, title, key, virtual, ref_key) VALUES ((SELECT 'bookings.airports'::regclass::oid), (SELECT 'bookings.flights'::regclass::oid), 'Flights', 'arrival_airport', true, 'airport_code');
INSERT INTO meta.relation (entity_id, relation_entity, title, key, virtual, ref_key) VALUES ((SELECT 'bookings.aircrafts'::regclass::oid), (SELECT 'bookings.flights'::regclass::oid), 'Flights', 'aircraft_code', true, 'aircraft_code');
INSERT INTO meta.relation (entity_id, relation_entity, title, key, virtual, ref_key) VALUES ((SELECT 'bookings.aircrafts'::regclass::oid), (SELECT 'bookings.seats'::regclass::oid), 'Seats', 'aircraft_code', true, 'aircraft_code');
INSERT INTO meta.relation (entity_id, relation_entity, title, key, virtual, ref_key) VALUES ((SELECT 'bookings.aircrafts'::regclass::oid), (SELECT 'test_modules.reporting_forms'::regclass::oid), 'Reporting forms', 'aircraft_code', true, 'aircraft_code');
-- В апдейтах ниже точно хватит одного условия? Возможно надо писать в projection_property.
-- UPDATE meta.property SET ref_entity = (SELECT 'bookings.aircrafts'::regclass::oid) WHERE ref_key = 'aircraft_code';
-- UPDATE meta.property SET ref_entity = (SELECT 'bookings.airports'::regclass::oid) WHERE ref_key = 'airport_code';
-- Настройка типа radiobutton:
UPDATE meta.property SET type = 'radiobutton' WHERE column_name = 'fare_conditions' AND entity_id = (SELECT 'bookings.seats'::regclass::oid);

-- Представление для тестирования создания карты
CREATE OR REPLACE VIEW test_schema.creating_map
AS
SELECT ml.airport_code,
       ml.airport_name ->> bookings.lang() AS airport_name,
       ml.city ->> bookings.lang() AS city,
       (ml.coordinates)[0] lon,
       (ml.coordinates)[1] lat,
       ml.timezone
FROM bookings.airports_data ml;

-- Представление для тестирования карты и её настроек
CREATE OR REPLACE VIEW bookings.airports_map
AS
SELECT ml.airport_code,
       ml.airport_name ->> bookings.lang() AS airport_name,
       ml.city ->> bookings.lang() AS city,
       (ml.coordinates)[0] lon,
       (ml.coordinates)[1] lat,
       ml.timezone
FROM bookings.airports_data ml;

UPDATE meta.entity SET title = 'Airports map' WHERE entity_id = 'bookings.airports_map'::regclass::oid;

UPDATE meta.property SET is_pkey = true WHERE column_name = 'airport_code' AND entity_id = 'bookings.airports_map'::regclass::oid;

INSERT INTO meta.menu_item (name, title, parent, projection, view_id, _order) VALUES ('bookings.airports_map', 'Airports map', (SELECT menu_id FROM meta.menu_item WHERE name = 'bookings'), 'airports_map', 'datamaplist', 85);

-- Представление для тестирования создания диаграмм
CREATE OR REPLACE VIEW bookings.creating_chart
AS
SELECT ml.aircraft_code,
       ml.model ->> bookings.lang() AS model,
       ml.range
FROM bookings.aircrafts_data ml;

-- Представление для тестирования диаграмм
CREATE OR REPLACE VIEW bookings.aircrafts_chart
AS
SELECT ml.aircraft_code,
       ml.model ->> bookings.lang() AS model,
       ml.range
FROM bookings.aircrafts_data ml;

UPDATE meta.entity SET title = 'Aircrafts chart' WHERE schema_name = 'bookings' AND table_name = 'aircrafts_chart';

INSERT INTO meta.menu_item (name, title, parent, projection, view_id, _order) VALUES ('bookings.aircrafts_chart', 'Aircrafts chart', 2, 'aircrafts_chart', 'chart', 95);

-- Правки для составных первичных ключей (Больше не требуется?)
-- ALTER TABLE bookings.boarding_passes ADD COLUMN composite_key text DEFAULT concat_ws('-', 'ticket', 'flight') NOT NULL;
-- UPDATE bookings.boarding_passes SET composite_key = concat_ws('-', ticket_no, flight_id);
-- UPDATE meta.property SET is_pkey = true, type = 'invisible' WHERE column_name = 'composite_key' AND entity_id = 'bookings.boarding_passes'::regclass::oid;

-- ALTER TABLE bookings.ticket_flights ADD COLUMN composite_key text DEFAULT concat_ws('-', 'ticket', 'flight') NOT NULL;
-- UPDATE bookings.ticket_flights SET composite_key = concat_ws('-', ticket_no, flight_id);
-- ALTER TABLE bookings.boarding_passes DROP CONSTRAINT boarding_passes_ticket_no_fkey;
-- UPDATE meta.property SET is_pkey = true, type = 'invisible' WHERE column_name = 'composite_key' AND entity_id = 'bookings.ticket_flights'::regclass::oid;

-- ALTER TABLE bookings.boarding_passes
--     ADD CONSTRAINT boarding_passes_ticket_no_fkey FOREIGN KEY (composite_key)
--     REFERENCES bookings.ticket_flights (composite_key) MATCH SIMPLE
--     ON UPDATE NO ACTION
--     ON DELETE NO ACTION;

-- ALTER TABLE bookings.seats ADD COLUMN composite_key character varying(8) DEFAULT concat_ws('-', 'cod', 'seat') NOT NULL;
-- UPDATE bookings.seats SET composite_key = concat_ws('-', aircraft_code, seat_no);
-- UPDATE meta.property SET is_pkey = true, type = 'invisible' WHERE column_name = 'composite_key' AND entity_id = 'bookings.seats'::regclass::oid;

-- Таблица для тестирования полнотекстового поиска
CREATE TABLE test_modules.aircraft_doc (
                                           aircraft_doc_key uuid DEFAULT uuid_generate_v4() NOT NULL,
                                           title text,
                                           content text,
                                           content_vec tsvector,
                                           label text
);

COMMENT ON TABLE test_modules.aircraft_doc IS 'Aircraft documentation';
COMMENT ON COLUMN test_modules.aircraft_doc.title IS 'Наименование';
COMMENT ON COLUMN test_modules.aircraft_doc.content IS 'Содержание';
COMMENT ON COLUMN test_modules.aircraft_doc.label IS 'Метка';

INSERT INTO test_modules.aircraft_doc VALUES ('12ed5979-0f6a-4aaa-a401-0c25912a183a', 'Boeing 777', 'Boeing 777 (Triple Seven или T7 — «Три семёрки») — семейство двухдвигательных широкофюзеляжных пассажирских самолётов для авиалиний большой протяжённости. Самолёт разработан в начале 1990-х, совершил первый полёт в 1994 году, в эксплуатации с 1995 года.

Самолёты этого типа способны вместить от 305 до 550 пассажиров, в зависимости от конфигурации салонов, и имеют дальность полёта от 9100 до 17 500 километров. На Boeing 777 установлен абсолютный рекорд дальности для пассажирских самолётов: 21 601 км.

Boeing 777 — самый крупный в мире двухмоторный турбовентиляторный пассажирский самолёт. Его двигатели General Electric GE90 — самые крупные и самые мощные в истории авиации турбовентиляторные двигатели. Отличительная особенность — шестиколёсные стойки шасси.', NULL, 'Boing');
INSERT INTO test_modules.aircraft_doc VALUES ('7475a238-f956-4092-b6c1-90b7fa8a656a', 'Boeing 767', 'Boeing 767 — двухдвигательный широкофюзеляжный авиалайнер, предназначенный для совершения полётов средней и большой протяжённости. Первый двухмоторный авиалайнер, получивший право выполнять регулярные пассажирские рейсы через Атлантический океан. Производится американской компанией «Boeing» с 1981 года.', NULL, 'Boing');
INSERT INTO test_modules.aircraft_doc VALUES ('3b1ecbfd-9bd0-427d-8b50-6cf29573edf4', 'Airbus A320', 'Airbus A320 — семейство узкофюзеляжных самолётов для авиалиний малой и средней протяжённости, разработанных европейским консорциумом «Airbus S.A.S». Выпущенный в 1988 году, он стал первым массовым пассажирским самолётом, на котором была применена электродистанционная система управления (ЭДСУ, англ. fly-by-wire).

Главным конкурентом семейства Airbus A320 является семейство Boeing 737.', NULL, 'Airbus');
INSERT INTO test_modules.aircraft_doc VALUES ('3a4228b4-e13a-44a1-b940-746d08ca5f6a', 'Sukhoi Superjet 100', 'Sukhoi Superjet 100 (рус. Сухой Суперджет 100) — российский ближнемагистральный узкофюзеляжный пассажирский самолёт, предназначенный для перевозки 98 пассажиров на дальность 3000 или 4600 км. Разработан компанией «Гражданские самолёты Сухого» при участии ряда иностранных компаний (см. ниже). Первый пассажирский самолёт, разработанный в России после распада СССР. Спроектирован только с применением цифровых технологий. Обозначение типовой конструкции самолёта при сертификации — RRJ95 (Russian Regional Jet 95). Обозначение ICAO — SU95 (Су-95).', NULL, 'Sukhoi');

UPDATE test_modules.aircraft_doc SET content_vec = to_tsvector('russian', content);

ALTER TABLE ONLY test_modules.aircraft_doc
    ADD CONSTRAINT aircraft_doc_pkey PRIMARY KEY (aircraft_doc_key);

-- View: meta.help
CREATE OR REPLACE VIEW meta.help
AS
WITH RECURSIVE temp1(help_id, selector, index, title, description, content, clickable, parent_help_id, "order", path, level) AS (
    SELECT t1.help_id,
           t1.selector,
           t1.index,
           t1.title,
           t1.description,
           t1.content,
           t1.clickable,
           t1.parent_help_id,
           t1."order",
           to_char(COALESCE(t1."order", 0), '00000'::text) || to_char(t1.help_id, '00000'::text) AS path,
           1
    FROM meta.help_item t1
    WHERE t1.parent_help_id IS NULL
    UNION
    SELECT t2.help_id,
           t2.selector,
           t2.index,
           t2.title,
           t2.description,
           t2.content,
           t2.clickable,
           t2.parent_help_id,
           t2."order",
           ((temp1_1.path || '->'::text) || to_char(COALESCE(t2."order", 0), '00000'::text)) || to_char(t2.help_id, '00000'::text) AS "varchar",
           temp1_1.level + 1
    FROM meta.help_item t2
             JOIN temp1 temp1_1 ON temp1_1.help_id = t2.parent_help_id
)
SELECT temp1.help_id,
       temp1.selector,
       temp1.index,
       temp1.title,
       temp1.description,
       temp1.content,
       temp1.clickable,
       temp1.parent_help_id,
       temp1."order",
       temp1.path,
       temp1.level
FROM temp1
LIMIT 1000;

ALTER TABLE meta.help
    OWNER TO postgres;

-- GRANT SELECT ON TABLE meta.help TO "gitlab-runner";
GRANT ALL ON TABLE meta.help TO postgres;

-- Замена составных первичных ключей на их конкатенацию.
ALTER TABLE bookings.ticket_flights ADD COLUMN ticket_flights_id text;
UPDATE bookings.ticket_flights SET ticket_flights_id = concat(ticket_no, '.', flight_id);
ALTER TABLE bookings.boarding_passes DROP CONSTRAINT boarding_passes_ticket_no_fkey;
ALTER TABLE bookings.ticket_flights DROP CONSTRAINT ticket_flights_pkey;
ALTER TABLE bookings.ticket_flights ADD CONSTRAINT ticket_flights_pkey PRIMARY KEY (ticket_flights_id);
ALTER TABLE bookings.ticket_flights ADD CONSTRAINT ticket_flights_key UNIQUE (flight_id, ticket_no);
ALTER TABLE bookings.boarding_passes ADD CONSTRAINT boarding_passes_ticket_no_fkey FOREIGN KEY (flight_id, ticket_no)
    REFERENCES bookings.ticket_flights (flight_id, ticket_no) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE NO ACTION;
UPDATE meta.property SET type = 'invisible' WHERE column_name = 'ticket_flights' AND entity_id = (SELECT 'bookings.ticket_flights'::regclass::oid);

ALTER TABLE bookings.seats ADD COLUMN seats_id text;
UPDATE bookings.seats SET seats_id = concat(aircraft_code, '.', seat_no);
ALTER TABLE bookings.seats DROP CONSTRAINT seats_pkey;
ALTER TABLE bookings.seats ADD CONSTRAINT seats_pkey PRIMARY KEY (seats_id);
UPDATE meta.property SET type = 'invisible' WHERE column_name = 'seats_id' AND entity_id = (SELECT 'bookings.seats'::regclass::oid);

-- Добавление инфраструктуры для конструктора отчётов

--
-- Name: report_builder; Type: TABLE; Schema: test_modules; Owner: postgres
--

CREATE TABLE test_modules.report_builder (
                                             report_id integer NOT NULL,
                                             title text,
                                             projection text,
                                             params json,
                                             paper text,
                                             orient text,
                                             font text,
                                             type text
);


ALTER TABLE test_modules.report_builder OWNER TO postgres;

--
-- Name: report_builder_report_id_seq; Type: SEQUENCE; Schema: test_modules; Owner: postgres
--

CREATE SEQUENCE test_modules.report_builder_report_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE test_modules.report_builder_report_id_seq OWNER TO postgres;

--
-- Name: report_builder_report_id_seq; Type: SEQUENCE OWNED BY; Schema: test_modules; Owner: postgres
--

ALTER SEQUENCE test_modules.report_builder_report_id_seq OWNED BY test_modules.report_builder.report_id;


--
-- Name: report_builder report_id; Type: DEFAULT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.report_builder ALTER COLUMN report_id SET DEFAULT nextval('test_modules.report_builder_report_id_seq'::regclass);


--
-- Data for Name: report_builder; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.report_builder VALUES (1, NULL, NULL, NULL, NULL, 'L', NULL, 'xls');
INSERT INTO test_modules.report_builder VALUES (2, NULL, NULL, NULL, NULL, 'P', NULL, 'pdf');
INSERT INTO test_modules.report_builder VALUES (3, NULL, NULL, '[{"displayPath":"title","propertyName":["title"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"description","propertyName":["description"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"release year","propertyName":["release_year"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"language","propertyName":["language_id"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"rental duration","propertyName":["rental_duration"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"rental rate","propertyName":["rental_rate"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"length","propertyName":["length"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"replacement cost","propertyName":["replacement_cost"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"rating","propertyName":["rating"],"mode":"column","order":"none","hasFilter":false},{"displayPath":"special features","propertyName":["special_features"],"mode":"column","order":"none","hasFilter":false}]', NULL, NULL, NULL, NULL);


--
-- Name: report_builder_report_id_seq; Type: SEQUENCE SET; Schema: test_modules; Owner: postgres
--

SELECT pg_catalog.setval('test_modules.report_builder_report_id_seq', 3, true);


--
-- Name: report_builder report_builder_pkey; Type: CONSTRAINT; Schema: test_modules; Owner: postgres
--

ALTER TABLE ONLY test_modules.report_builder
    ADD CONSTRAINT report_builder_pkey PRIMARY KEY (report_id);


-- SEQUENCE: test_modules.filterable_id_seq

-- DROP SEQUENCE test_modules.filterable_id_seq;

CREATE SEQUENCE test_modules.filterable_id_seq
    INCREMENT 1
    START 1
    MINVALUE 1
    MAXVALUE 9223372036854775807
    CACHE 1;

ALTER SEQUENCE test_modules.filterable_id_seq
    OWNER TO postgres;

-- Table: test_modules.filterable_small

-- DROP TABLE test_modules.filterable_small;

CREATE TABLE IF NOT EXISTS test_modules.filterable_small
(
    id_val integer NOT NULL DEFAULT nextval('test_modules.filterable_id_seq'::regclass),
    lim_char_val character(6) NOT NULL,
    text_val text NOT NULL DEFAULT 'text' || currval('test_modules.filterable_id_seq'::regclass),
    num_val numeric NOT NULL DEFAULT floor(random() * 100)::int,
    date_val date NOT NULL,
    time_val time NOT NULL,
    time_with_tz_val time with time zone NOT NULL,
    timestamp_val timestamp NOT NULL,
    timestamp_with_tz_val timestamp with time zone NOT NULL,
    departure_airport character(3) COLLATE pg_catalog."default" NOT NULL,
    status character varying(20) NOT NULL,
    aircraft_code character(3) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT filterable_small_pkey PRIMARY KEY (id_val),
    CONSTRAINT filterable_small_aircraft_code_fkey FOREIGN KEY (aircraft_code)
        REFERENCES bookings.aircrafts_data (aircraft_code) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT flights_departure_airport_fkey FOREIGN KEY (departure_airport)
        REFERENCES bookings.airports_data (airport_code) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
)
    WITH (
        OIDS = FALSE
    )
    TABLESPACE pg_default;

ALTER TABLE test_modules.filterable_small
    OWNER to postgres;

--
-- Data for Name: filterable_small; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0134', DEFAULT, DEFAULT, '2017-09-10', '06:05:00.000', '11:55:00.000+00', '2017-09-10 06:50:00', '2017-09-10 11:55:00+00', 'DME', 'Scheduled', '319');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0052', DEFAULT, DEFAULT, 'August 25, 2017', '115000', '153500+01', '2017-08-25 11:50:000', '2017-08-25 15:35:00+01', 'VKO', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0561', DEFAULT, DEFAULT, '2017-Sep-10', '09:30 AM', '13:55:00+02:00', '2017-09-05 09:30:00', '2017-09-05 13:15:00+02', 'VKO', 'Scheduled', '763');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0529', DEFAULT, DEFAULT, '12-Sep-2017', '06:50', '11:20:00 FET', '2017-09-12 06:50:00+00', '2017-09-12 11:20:00+03', 'SVO', 'Scheduled', '763');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0461', DEFAULT, DEFAULT, '20170904', '092500', '10:20:00 WET', '2017-09-04 09:25:00', '2017-09-04 10:20:00+00', 'SVO', 'Scheduled', 'SU9');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0667', DEFAULT, DEFAULT, 'Sep-10-2017', '12:00', '17:30:00+03', '2017-09-10 12:00:00', '2017-09-10 17:30:00+03', 'SVO', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0360', DEFAULT, DEFAULT, '28-Aug-17', '06:00:00', '08:35:00+00', '2017-08-28 06:00:00', '2017-08-28 08:35:00+00', 'LED', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0569', DEFAULT, DEFAULT, '2017-08-24', '12:05', '15:10 CEST', '2017-08-24 12:05:00', '2017-08-24 15:10:00+02', 'SVX', 'Scheduled', '733');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0498', DEFAULT, DEFAULT, 'September 12, 2017', '071500', '105500-01', '2017-09-12 07:15:00', '2017-09-12 10:55:00-01', 'KZN', 'Scheduled', '319');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0621', DEFAULT, DEFAULT, '26-Aug-2017', '13:05:00.000', '14:00:00.000+00', '2017-08-26 13:05:00', '2017-08-26 14:00:00+00', 'KZN', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_small VALUES (DEFAULT, 'PG0612', DEFAULT, DEFAULT, 'Aug-18-2017', '13:25:00', '15:05:00-02', '2017-08-18 13:25:00', '2017-08-18 15:05:00-02', 'ROV', 'Scheduled', 'CN1');

-- Table: test_modules.filterable_large

-- DROP TABLE test_modules.filterable_large;

CREATE TABLE IF NOT EXISTS test_modules.filterable_large
(
    id_val integer NOT NULL DEFAULT nextval('test_modules.filterable_id_seq'::regclass),
    lim_char_val character(6) NOT NULL,
    text_val text NOT NULL DEFAULT 'text' || currval('test_modules.filterable_id_seq'::regclass),
    num_val numeric NOT NULL DEFAULT floor(random() * 100)::int,
    date_val date NOT NULL,
    time_val time NOT NULL,
    time_with_tz_val time with time zone NOT NULL,
    timestamp_val timestamp NOT NULL,
    timestamp_with_tz_val timestamp with time zone NOT NULL,
    departure_airport character(3) COLLATE pg_catalog."default" NOT NULL,
    status character varying(20) NOT NULL,
    aircraft_code character(3) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT filterable_large_pkey PRIMARY KEY (id_val),
    CONSTRAINT filterable_large_aircraft_code_fkey FOREIGN KEY (aircraft_code)
        REFERENCES bookings.aircrafts_data (aircraft_code) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT filterable_large_departure_airport_fkey FOREIGN KEY (departure_airport)
        REFERENCES bookings.airports_data (airport_code) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
)
    WITH (
        OIDS = FALSE
    )
    TABLESPACE pg_default;

ALTER TABLE test_modules.filterable_large
    OWNER to postgres;

--
-- Data for Name: filterable_large; Type: TABLE DATA; Schema: test_modules; Owner: postgres
--

INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0134', DEFAULT, DEFAULT, '2017-09-10', '06:05:00.000', '11:55:00.000+00', '2017-09-10 06:50:00', '2017-09-10 11:55:00+00', 'DME', 'Scheduled', '319');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0052', DEFAULT, DEFAULT, 'August 25, 2017', '115000', '153500+01', '2017-08-25 11:50:000', '2017-08-25 15:35:00+01', 'VKO', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0561', DEFAULT, DEFAULT, '2017-Sep-10', '09:30 AM', '13:55:00+02:00', '2017-09-05 09:30:00', '2017-09-05 13:15:00+02', 'VKO', 'Scheduled', '763');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0529', DEFAULT, DEFAULT, '12-Sep-2017', '06:50', '11:20:00 FET', '2017-09-12 06:50:00', '2017-09-12 11:20:00+03', 'SVO', 'Scheduled', '763');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0461', DEFAULT, DEFAULT, '20170904', '092500', '10:20:00 WET', '2017-09-04 09:25:00', '2017-09-04 10:20:00+00', 'BTK', 'Scheduled', 'SU9');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0667', DEFAULT, DEFAULT, 'Sep-10-2017', '12:00', '17:30:00+03', '2017-09-10 12:00:00', '2017-09-10 17:30:00+03', 'SVO', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0360', DEFAULT, DEFAULT, '28-Aug-17', '06:00:00', '08:35:00+00', '2017-08-28 06:00:00', '2017-08-28 08:35:00+00', 'LED', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0569', DEFAULT, DEFAULT, '2017-08-24', '12:05', '15:10 CEST', '2017-08-24 12:05:00', '2017-08-24 15:10:00+02', 'SVX', 'Scheduled', '733');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0498', DEFAULT, DEFAULT, 'September 12, 2017', '071500', '105500-01', '2017-09-12 07:15:00', '2017-09-12 10:55:00-01', 'KZN', 'Scheduled', '319');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0621', DEFAULT, DEFAULT, '26-Aug-2017', '13:05:00.000', '14:00:00.000+00', '2017-08-26 13:05:00', '2017-08-26 14:00:00+00', 'KZN', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0612', DEFAULT, DEFAULT, 'Aug-18-2017', '13:25:00', '15:05:00-02', '2017-08-18 13:25:00', '2017-08-18 15:05:00-02', 'ROV', 'Scheduled', 'CN1');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0135', DEFAULT, DEFAULT, '2017-09-10', '115000', '153500+01', '2017-08-25 11:50:000', '2017-08-25 15:35:00+01', 'LED', 'Scheduled', '319');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0053', DEFAULT, DEFAULT, 'August 25, 2017', '09:30 AM', '13:55:00+02:00', '2017-09-05 09:30:00', '2017-09-05 13:15:00+02', 'VKO', 'Scheduled', 'CR2');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0562', DEFAULT, DEFAULT, '2017-Sep-10', '06:50', '11:20:00 FET', '2017-09-12 06:50:00', '2017-09-12 11:20:00+03', 'HMA', 'Scheduled', '763');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0530', DEFAULT, DEFAULT, '12-Sep-2017', '092500', '10:20:00 WET', '2017-09-04 09:25:00', '2017-09-04 10:20:00+00', 'AER', 'Scheduled', '321');
INSERT INTO test_modules.filterable_large VALUES (DEFAULT, 'PG0462', DEFAULT, DEFAULT, '20170904', '12:00', '17:30:00+03', '2017-09-10 12:00:00', '2017-09-10 17:30:00+03', 'UFA', 'Scheduled', '773');


CREATE ROLE admins WITH
    NOLOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE
    INHERIT
    NOREPLICATION
    CONNECTION LIMIT -1;
COMMENT ON ROLE admins IS 'Администратор';

--
--

CREATE OR REPLACE FUNCTION public.testint(int)
    RETURNS bool
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS
$BODY$
SELECT TRUE;
$BODY$;

ALTER FUNCTION public.testint(int)
    OWNER TO postgres;

--
--

CREATE OR REPLACE FUNCTION public.testint2(text, int)
    RETURNS bool
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS
$BODY$
SELECT TRUE;
$BODY$;

ALTER FUNCTION public.testint2(text,int)
    OWNER TO postgres;

--
--

CREATE OR REPLACE FUNCTION public.testint3()
    RETURNS int
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS
$BODY$
SELECT 1200;
$BODY$;

ALTER FUNCTION public.testint3()
    OWNER TO postgres;

--
--

CREATE OR REPLACE FUNCTION public.test4(int)
    RETURNS text
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS
$BODY$
SELECT $1::text;
$BODY$;

ALTER FUNCTION public.test4(int)
    OWNER TO postgres;
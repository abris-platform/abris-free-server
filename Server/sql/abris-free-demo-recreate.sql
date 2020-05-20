do
$$
declare
  project_entity_id integer;
  employee_entity_id integer;
  task_entity_id integer;
  task_to_emp_entity_id integer;
  project_to_emp_entity_id integer;
begin
insert into meta.entity (table_name, title) values('project', 'Projects'); 
select entity_id from meta.entity where table_name = 'project' into project_entity_id;
insert into meta.property(entity_id, column_name, type, data_type, title) values (project_entity_id, 'name',	 'caption',	'text',	    'Name');
insert into meta.property(entity_id, column_name, type, data_type, title) values (project_entity_id, 'info',	 'plain',	'text',	    'Information');
insert into meta.property(entity_id, column_name, type, data_type, title) values (project_entity_id, 'cost',	 'money',	'numeric',	'Total cost');
insert into meta.property(entity_id, column_name, data_type, title)       values (project_entity_id, 'deadline',         'date',	    'Deadline');

insert into meta.entity (table_name, title) values('employee',	'Employees'); 
select entity_id from meta.entity where table_name = 'employee' into employee_entity_id;
insert into meta.property(entity_id, column_name, title) values (employee_entity_id, 'name',	'Name');
insert into meta.property(entity_id, column_name, type, data_type, title) values (employee_entity_id, 'work_from',	'time', 'time with time zone', 'From');
insert into meta.property(entity_id, column_name, type, data_type, title) values (employee_entity_id, 'work_to',	'time', 'time with time zone', 'To');

insert into meta.entity (table_name, title) values('task',	'Tasks'); 
select entity_id from meta.entity where table_name = 'task' into task_entity_id;
insert into meta.property(entity_id, column_name,  type, data_type, title) values (task_entity_id, 'title',	'caption',	'text',	'Title');	
insert into meta.property(entity_id, column_name, type, data_type,  title) values (task_entity_id, 'due_time',	'datetime',	'timestamp with time zone',	'Due time');
insert into meta.property(entity_id, column_name, type, data_type,  title) values (task_entity_id, 'progress',	'progress',	'integer',	'Progress');	
insert into meta.property(entity_id, column_name, type, data_type,   title) values (task_entity_id, 'description',	'text',	'text',	'Description');	
insert into meta.property(entity_id, ref_entity) values (task_entity_id, project_entity_id);

insert into meta.entity (table_name, title) values('task_to_emp',	'Participants') ; 
select entity_id from meta.entity where table_name = 'task_to_emp' into task_to_emp_entity_id;
insert into meta.property(entity_id, ref_entity) values (task_to_emp_entity_id, task_entity_id);
insert into meta.property(entity_id, ref_entity) values (task_to_emp_entity_id, employee_entity_id);

insert into meta.entity (table_name, title, primarykey, view_definition) values('project_to_emp',	'Project participants', 'project_to_emp_key',
  'SELECT DISTINCT md5(task.project_key::text||task_to_emp.employee_key::text)::uuid as project_to_emp_key,
    task.project_key,
    task_to_emp.employee_key
   FROM (task_to_emp
     JOIN task USING (task_key));'); 
select entity_id from meta.entity where table_name = 'project_to_emp' into project_to_emp_entity_id;
update meta.property set type='ref', ref_entity = project_entity_id, title = 'Project' where entity_id = project_to_emp_entity_id and column_name = 'project_key';
update meta.property set type='ref', ref_entity = employee_entity_id, title = 'Employee' where entity_id = project_to_emp_entity_id and column_name = 'employee_key';

insert into meta.relation (entity_id, relation_entity, key, ref_key, virtual) values (project_entity_id, project_to_emp_entity_id, 'project_key', 'project_key', true);
insert into meta.menu_item (projection) values ('project');
insert into meta.menu_item (projection) values ('employee');

--
-- Data for Name: employee; Type: TABLE DATA; Schema: public; Owner: nav
--

INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('e7a265fd-37d0-4661-bcaa-cd267ac40b3e', 'John', '07:00:00+03', '16:00:00+03');
INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('91c3548d-fffc-4c00-a2d8-da9d062993c3', 'William', '08:00:00+03', '17:00:00+03');
INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('ee1cb60d-9e2e-4a6b-9f6f-519e53a020ce', 'Andrew', '08:30:00+03', '16:30:00+03');
INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('9bf734a2-f802-4b31-9fa7-6bb42541ddd4', 'Miranda', '09:00:00+03', '18:00:00+03');
INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('aafa080b-b13c-4392-9558-ca9ec57f027d', 'Kate', '09:30:00+03', '17:00:00+03');
INSERT INTO public.employee (employee_key, name, work_from, work_to) VALUES ('df920360-26f4-41d4-badb-20009bda0905', 'Hassan', '03:00:00+03', '12:00:00+03');

--
-- Data for Name: project; Type: TABLE DATA; Schema: public; Owner: nav
--

INSERT INTO public.project (project_key, name, info, cost, deadline) VALUES ('b0fb83e2-d78a-4322-99d5-0fe51a21df41', 'Abris Free Demo', 'Absolutely free version of Abris Platform for commercial and non-commercial use', 12345, '2020-04-10');
INSERT INTO public.project (project_key, name, info, cost, deadline) VALUES ('b1044996-c966-42b0-bbaf-ef6f8b11a198', 'Documentation', 'User manuals for free and full-functional versions of Abris Platform', 9876, '2020-03-23');

--
-- Data for Name: task; Type: TABLE DATA; Schema: public; Owner: nav
--

INSERT INTO public.task (task_key, title, due_time, progress, description, project_key) VALUES ('f5831618-88e7-43f1-b3f7-522e9db6274f', 'Build software realeases', '2020-03-11 17:12:45+03', 100, '<p><span style="white-space: initial; font-weight: lighter;">Make builds for:</span><br></p><p><ul><li>client</li><li>server</li><li>database extension</li></ul></p>', 'b0fb83e2-d78a-4322-99d5-0fe51a21df41');
INSERT INTO public.task (task_key, title, due_time, progress, description, project_key) VALUES ('0e6c2d35-502d-4b9b-985a-e308559898c5', 'Sample database', '2020-04-10 16:57:39+03', 34, '<p>Create database that is simple and <em>easy-to-understand</em> but shows all the features of <strong>Abris Platform Free</strong></p>', 'b0fb83e2-d78a-4322-99d5-0fe51a21df41');
INSERT INTO public.task (task_key, title, due_time, progress, description, project_key) VALUES ('2a75365b-d1fe-45c4-9f92-a6e925ddfa08', 'Describe database creation', '2020-04-08 17:16:44+03', 85, NULL, 'b1044996-c966-42b0-bbaf-ef6f8b11a198');
INSERT INTO public.task (task_key, title, due_time, progress, description, project_key) VALUES ('9ee539f8-0fec-4652-b820-f0be945b281b', 'Describe widgets', '2020-05-14 17:18:00+03', 46, '<p>And put them on&nbsp;<a href="https://abris.site/free/abrisplatform.com" style="white-space: initial; font-weight: lighter; background-color: rgb(248, 249, 253);">abrisplatform.com</a></p>', 'b1044996-c966-42b0-bbaf-ef6f8b11a198');

--
-- Data for Name: task_to_emp; Type: TABLE DATA; Schema: public; Owner: nav
--

INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('75c77228-0049-4015-bc13-2d8ddfbcd235', 'f5831618-88e7-43f1-b3f7-522e9db6274f', 'e7a265fd-37d0-4661-bcaa-cd267ac40b3e');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('eaeebd98-5cdf-46fa-a8c0-fd3465d8dd02', '0e6c2d35-502d-4b9b-985a-e308559898c5', 'e7a265fd-37d0-4661-bcaa-cd267ac40b3e');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('f9476d1f-4182-4fe6-9b79-84629bef5399', '0e6c2d35-502d-4b9b-985a-e308559898c5', '91c3548d-fffc-4c00-a2d8-da9d062993c3');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('3b714355-c019-4404-93db-e7b4faaeddc0', '2a75365b-d1fe-45c4-9f92-a6e925ddfa08', '91c3548d-fffc-4c00-a2d8-da9d062993c3');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('9e8082fb-643e-4eff-9397-df7bb9c5148a', '2a75365b-d1fe-45c4-9f92-a6e925ddfa08', 'ee1cb60d-9e2e-4a6b-9f6f-519e53a020ce');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('521d08ab-9d30-4bc1-9b9c-e627ac7cba5d', '9ee539f8-0fec-4652-b820-f0be945b281b', 'ee1cb60d-9e2e-4a6b-9f6f-519e53a020ce');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('d2dabd7c-4b45-4086-8a7e-7a2937e46757', 'f5831618-88e7-43f1-b3f7-522e9db6274f', '9bf734a2-f802-4b31-9fa7-6bb42541ddd4');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('2eed8665-0a30-4c0f-aff5-dcb348e35545', '2a75365b-d1fe-45c4-9f92-a6e925ddfa08', '9bf734a2-f802-4b31-9fa7-6bb42541ddd4');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('4780a646-41c6-4d3c-8315-74ad1297a266', '9ee539f8-0fec-4652-b820-f0be945b281b', 'aafa080b-b13c-4392-9558-ca9ec57f027d');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('829eb258-5833-4bb6-84c1-fc06e84d2356', '0e6c2d35-502d-4b9b-985a-e308559898c5', 'df920360-26f4-41d4-badb-20009bda0905');
INSERT INTO public.task_to_emp (task_to_emp_key, task_key, employee_key) VALUES ('6644aa45-d1b3-43dd-a206-0559fef0e171', '9ee539f8-0fec-4652-b820-f0be945b281b', 'df920360-26f4-41d4-badb-20009bda0905');

end
$$
DROP SCHEMA IF EXISTS bookings;
CREATE SCHEMA bookings;
--
--
DELIMITER $$
CREATE FUNCTION bookings.lang()
RETURNS TEXT
DETERMINISTIC
BEGIN
	RETURN 'ru';
END$$
DELIMITER ;
--
--
CREATE TABLE bookings.aircrafts_data (
    aircraft_code character(3) NOT NULL COMMENT 'Aircraft code, IATA',
    model JSON NOT NULL COMMENT 'Aircraft model',
    `range` integer NOT NULL COMMENT 'Maximal flying distance, km',
    CONSTRAINT aircrafts_range_check CHECK ((`range` > 0)),
    CONSTRAINT aircrafts_pkey PRIMARY KEY (aircraft_code)
) COMMENT = 'Aircrafts (internal data)';
--
--
CREATE VIEW bookings.aircrafts AS
	SELECT ml.aircraft_code,
		(ml.model ->> 'ru') AS model,
		ml.`range`
	FROM bookings.aircrafts_data ml;
--
--
CREATE TABLE bookings.airports_data (
    airport_code character(3) NOT NULL COMMENT 'Airport code',
    airport_name JSON NOT NULL COMMENT 'Airport name',
    city JSON NOT NULL COMMENT 'City',
    coordinates point NOT NULL COMMENT 'Airport coordinates (longitude and latitude)',
    timezone text NOT NULL COMMENT 'Airport time zone',
    CONSTRAINT airports_data_pkey PRIMARY KEY (airport_code)
) COMMENT = 'Airports (internal data)';
--
--
CREATE VIEW bookings.airports AS
	SELECT ml.airport_code,
		(ml.airport_name ->> 'ru') AS airport_name,
		(ml.city ->> 'ru') AS city,
		ml.coordinates,
		ml.timezone
	FROM bookings.airports_data ml;
--
--
CREATE TABLE bookings.boarding_passes (
    ticket_no character(13) NOT NULL COMMENT 'Ticket number',
    flight_id integer NOT NULL COMMENT 'Flight ID',
    boarding_no integer NOT NULL COMMENT 'Boarding pass number',
    seat_no character varying(4) NOT NULL COMMENT 'Seat number',
    CONSTRAINT boarding_passes_flight_id_boarding_no_key UNIQUE (flight_id, boarding_no),
    CONSTRAINT boarding_passes_flight_id_seat_no_key UNIQUE (flight_id, seat_no),
    CONSTRAINT boarding_passes_pkey PRIMARY KEY (ticket_no, flight_id)
) COMMENT = 'Boarding passes';
--
--
CREATE TABLE bookings.bookings (
    book_ref character(6) NOT NULL COMMENT 'Booking number',
    book_date timestamp NOT NULL COMMENT 'Booking date',
    total_amount numeric(10,2) NOT NULL COMMENT 'Total booking cost',
    CONSTRAINT bookings_pkey PRIMARY KEY (book_ref)
) COMMENT = 'Bookings';
--
--
CREATE TABLE bookings.flights (
    flight_id integer AUTO_INCREMENT NOT NULL COMMENT 'Flight ID',
    flight_no character(6) NOT NULL COMMENT 'Flight number',
    scheduled_departure timestamp  NOT NULL COMMENT 'Scheduled departure time',
    scheduled_arrival timestamp NOT NULL COMMENT 'Scheduled arrival time',
    departure_airport character(3) NOT NULL COMMENT 'Airport of departure',
    arrival_airport character(3) NOT NULL COMMENT 'Airport of arrival',
    status character varying(20) NOT NULL COMMENT 'Flight status',
    aircraft_code character(3) NOT NULL COMMENT 'Aircraft code, IATA',
    actual_departure timestamp COMMENT 'Actual departure time',
    actual_arrival timestamp COMMENT 'Actual arrival time',
    CONSTRAINT flights_check CHECK ((scheduled_arrival > scheduled_departure)),
    CONSTRAINT flights_check1 CHECK (((actual_arrival IS NULL) OR ((actual_departure IS NOT NULL) AND (actual_arrival IS NOT NULL) AND (actual_arrival > actual_departure)))),
    -- CONSTRAINT flights_status_check CHECK ((`status` = ANY (SELECT 'On Time' UNION SELECT 'Delayed' UNION SELECT 'Departed' UNION SELECT 'Arrived' UNION SELECT 'Scheduled' UNION SELECT 'Cancelled'))),
    CONSTRAINT flights_flight_no_scheduled_departure_key UNIQUE (flight_no, scheduled_departure),
    CONSTRAINT flights_pkey PRIMARY KEY (flight_id)
) COMMENT = 'Flights';
--
--
CREATE VIEW bookings.flights_v AS
	SELECT f.flight_id,
		f.flight_no,
		f.scheduled_departure,
		f.scheduled_departure AS scheduled_departure_local,
		f.scheduled_arrival,
		f.scheduled_arrival AS scheduled_arrival_local,
		(f.scheduled_arrival - f.scheduled_departure) AS scheduled_duration,
		f.departure_airport,
		dep.airport_name AS departure_airport_name,
		dep.city AS departure_city,
		f.arrival_airport,
		arr.airport_name AS arrival_airport_name,
		arr.city AS arrival_city,
		f.status,
		f.aircraft_code,
		f.actual_departure,
		f.actual_departure AS actual_departure_local,
		f.actual_arrival,
		f.actual_arrival AS actual_arrival_local,
		(f.actual_arrival - f.actual_departure) AS actual_duration
	FROM bookings.flights f, bookings.airports dep, bookings.airports arr
	WHERE ((f.departure_airport = dep.airport_code) AND (f.arrival_airport = arr.airport_code));
--
--
CREATE VIEW bookings.routes AS
 WITH f3 AS (
         SELECT f2.flight_no,
            f2.departure_airport,
            f2.arrival_airport,
            f2.aircraft_code,
            f2.duration,
            CONCAT('[\'', GROUP_CONCAT(f2.days_of_week SEPARATOR '\', \''), '\']') AS days_of_week
           FROM ( SELECT f1.flight_no,
                    f1.departure_airport,
                    f1.arrival_airport,
                    f1.aircraft_code,
                    f1.duration,
                    f1.days_of_week
                   FROM ( SELECT flights.flight_no,
                            flights.departure_airport,
                            flights.arrival_airport,
                            flights.aircraft_code,
                            (flights.scheduled_arrival - flights.scheduled_departure) AS duration,
                            (DAYOFWEEK(flights.scheduled_departure)) AS days_of_week
                           FROM bookings.flights) f1
                  GROUP BY f1.flight_no, f1.departure_airport, f1.arrival_airport, f1.aircraft_code, f1.duration, f1.days_of_week
                  ORDER BY f1.flight_no, f1.departure_airport, f1.arrival_airport, f1.aircraft_code, f1.duration, f1.days_of_week) f2
          GROUP BY f2.flight_no, f2.departure_airport, f2.arrival_airport, f2.aircraft_code, f2.duration
        )
 SELECT f3.flight_no,
    f3.departure_airport,
    dep.airport_name AS departure_airport_name,
    dep.city AS departure_city,
    f3.arrival_airport,
    arr.airport_name AS arrival_airport_name,
    arr.city AS arrival_city,
    f3.aircraft_code,
    f3.duration,
    f3.days_of_week
   FROM f3,
    bookings.airports dep,
    bookings.airports arr
  WHERE ((f3.departure_airport = dep.airport_code) AND (f3.arrival_airport = arr.airport_code));
--
--
CREATE TABLE bookings.seats (
    aircraft_code character(3) NOT NULL COMMENT 'Aircraft code, IATA',
    seat_no character varying(4) NOT NULL COMMENT 'Seat number',
    fare_conditions character varying(10) NOT NULL COMMENT 'Travel class',
    -- CONSTRAINT seats_fare_conditions_check CHECK (((fare_conditions) = ANY (SELECT 'Economy' UNION SELECT 'Comfort' UNION SELECT 'Business'))),
    CONSTRAINT seats_pkey PRIMARY KEY (aircraft_code, seat_no)
) COMMENT = 'Seats';
--
--
CREATE TABLE bookings.ticket_flights (
    ticket_no character(13) NOT NULL COMMENT 'Ticket number',
    flight_id integer NOT NULL COMMENT 'Flight ID',
    fare_conditions character varying(10) NOT NULL COMMENT 'Travel class',
    amount numeric(10,2) NOT NULL COMMENT 'Travel cost',
    CONSTRAINT ticket_flights_amount_check CHECK ((amount >= (0))),
    -- CONSTRAINT ticket_flights_fare_conditions_check CHECK (((fare_conditions) = ANY (SELECT 'Economy' UNION SELECT 'Comfort' UNION SELECT 'Business'))),
    CONSTRAINT ticket_flights_pkey PRIMARY KEY (ticket_no, flight_id)
) COMMENT = 'Flight segment';
--
--
CREATE TABLE bookings.tickets (
    ticket_no character(13) NOT NULL COMMENT 'Ticket number',
    book_ref character(6) NOT NULL COMMENT 'Booking number',
    passenger_id character varying(20) NOT NULL COMMENT 'Passenger ID',
    passenger_name text NOT NULL COMMENT 'Passenger name',
    contact_data JSON COMMENT 'Passenger contact information',
    CONSTRAINT tickets_pkey PRIMARY KEY (ticket_no)
) COMMENT = 'Tickets';
--
--
--
--
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('773', '{"en": "Boeing 777-300", "ru": "Боинг 777-300"}', 11100);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('763', '{"en": "Boeing 767-300", "ru": "Боинг 767-300"}', 7900);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('SU9', '{"en": "Sukhoi Superjet-100", "ru": "Сухой Суперджет-100"}', 3000);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('320', '{"en": "Airbus A320-200", "ru": "Аэробус A320-200"}', 5700);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('321', '{"en": "Airbus A321-200", "ru": "Аэробус A321-200"}', 5600);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('319', '{"en": "Airbus A319-100", "ru": "Аэробус A319-100"}', 6700);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('733', '{"en": "Boeing 737-300", "ru": "Боинг 737-300"}', 4200);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('CN1', '{"en": "Cessna 208 Caravan", "ru": "Сессна 208 Караван"}', 1200);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, `range`) VALUES ('CR2', '{"en": "Bombardier CRJ-200", "ru": "Бомбардье CRJ-200"}', 2700);
--
--
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('YKS', '{"en": "Yakutsk Airport", "ru": "Якутск"}', '{"en": "Yakutsk", "ru": "Якутск"}', POINT(129.77099609375,62.0932998657226562), 'Asia/Yakutsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('MJZ', '{"en": "Mirny Airport", "ru": "Мирный"}', '{"en": "Mirnyj", "ru": "Мирный"}', POINT(114.03900146484375,62.534698486328125), 'Asia/Yakutsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KHV', '{"en": "Khabarovsk-Novy Airport", "ru": "Хабаровск-Новый"}', '{"en": "Khabarovsk", "ru": "Хабаровск"}', POINT(135.18800354004,48.5279998779300001), 'Asia/Vladivostok');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('PKC', '{"en": "Yelizovo Airport", "ru": "Елизово"}', '{"en": "Petropavlovsk", "ru": "Петропавловск-Камчатский"}', POINT(158.453994750976562,53.1679000854492188), 'Asia/Kamchatka');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('UUS', '{"en": "Yuzhno-Sakhalinsk Airport", "ru": "Хомутово"}', '{"en": "Yuzhno-Sakhalinsk", "ru": "Южно-Сахалинск"}', POINT(142.718002319335938,46.8886985778808594), 'Asia/Sakhalin');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('VVO', '{"en": "Vladivostok International Airport", "ru": "Владивосток"}', '{"en": "Vladivostok", "ru": "Владивосток"}', POINT(132.147994995117188,43.3989982604980469), 'Asia/Vladivostok');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('LED', '{"en": "Pulkovo Airport", "ru": "Пулково"}', '{"en": "St. Petersburg", "ru": "Санкт-Петербург"}', POINT(30.2625007629394531,59.8003005981445312), 'Europe/Moscow');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KGD', '{"en": "Khrabrovo Airport", "ru": "Храброво"}', '{"en": "Kaliningrad", "ru": "Калининград"}', POINT(20.5925998687744141,54.8899993896484375), 'Europe/Kaliningrad');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KEJ', '{"en": "Kemerovo Airport", "ru": "Кемерово"}', '{"en": "Kemorovo", "ru": "Кемерово"}', POINT(86.1072006225585938,55.2700996398925781), 'Asia/Novokuznetsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('NOZ', '{"en": "Spichenkovo Airport", "ru": "Спиченково"}', '{"en": "Novokuznetsk", "ru": "Новокузнецк"}', POINT(86.877197265625,53.8114013671875), 'Asia/Novokuznetsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('DME', '{"en": "Domodedovo International Airport", "ru": "Домодедово"}', '{"en": "Moscow", "ru": "Москва"}', POINT(37.9062995910644531,55.4087982177734375), 'Europe/Moscow');
--
--
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('064589', '2017-07-22 12:33:00', 310100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('1DC435', '2017-07-20 05:36:00', 6700.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('2F2226', '2017-07-13 10:40:00', 326100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('302066', '2017-08-09 03:21:00', 48900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('32C1D7', '2017-07-17 10:39:00', 137100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('7F0E21', '2017-07-02 03:06:00', 70200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('7F5D7B', '2017-08-04 21:31:00', 7300.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('9567AF', '2017-07-24 09:48:00', 50100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('D9CF3C', '2017-07-22 21:07:00', 98400.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('DDCAEA', '2017-08-09 00:10:00', 63300.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('F313DD', '2017-07-03 01:37:00', 30900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('000068', '2020-03-12 15:18:00', 18100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('0002D8', '2017-08-07 18:40:00', 23600.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('000012', '2020-03-12 15:18:00', 37900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('00000F', '2017-07-05 00:12:00', 265700.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('31ADD5', '2017-07-11 05:19:00', 52000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('6C0FB3', '2017-08-06 08:12:00', 112100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('CD9472', '2017-07-30 23:31:00', 91000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('63A498', '2017-07-10 09:31:00', 35200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('579EFD', '2017-07-23 13:04:00', 21400.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('FDC2AF', '2017-06-30 03:56:00', 20000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('644C15', '2017-07-02 04:52:00', 96200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('3A458F', '2017-07-20 21:47:00', 203500.00);
--
--
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (1, 'PG0405', '2017-07-16 09:35:00', '2017-07-16 10:30:00', 'DME', 'LED', 'Arrived', '321', '2017-07-16 09:44:00', '2017-07-16 10:39:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (2, 'PG0404', '2017-08-05 19:05:00', '2017-08-05 20:00:00', 'DME', 'LED', 'Arrived', '321', '2017-08-05 19:06:00', '2017-08-05 20:01:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (3, 'PG0405', '2017-08-05 09:35:00', '2017-08-05 10:30:00', 'DME', 'LED', 'Arrived', '321', '2017-08-05 09:39:00', '2017-08-05 10:34:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (5, 'PG0405', '2017-08-16 09:35:00', '2017-08-16 10:30:00', 'DME', 'LED', 'On Time', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (6, 'PG0404', '2017-08-16 19:05:00', '2017-08-16 20:00:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (9, 'PG0405', '2017-08-25 09:35:00', '2017-08-25 10:30:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (12, 'PG0404', '2017-08-23 19:05:00', '2017-08-23 20:00:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (13, 'PG0405', '2017-08-23 09:35:00', '2017-08-23 10:30:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (17, 'PG0404', '2017-08-06 19:05:00', '2017-08-06 20:00:00', 'DME', 'LED', 'Arrived', '321', '2017-08-06 19:05:00', '2017-08-06 20:00:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (18, 'PG0405', '2017-08-06 09:35:00', '2017-08-06 10:30:00', 'DME', 'LED', 'Arrived', '321', '2017-08-06 09:39:00', '2017-08-06 10:35:00');
--
--
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432262099', '1DC435', '2902 380461', 'ZINAIDA KOLESNIKOVA', '{"email": "kolesnikovaz_22021966@postgrespro.ru", "phone": "+70165381701"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432262559', '7F5D7B', '8304 490111', 'IRINA SOKOLOVA', '{"phone": "+70409757838"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432391976', '2F2226', '0529 836694', 'NIKOLAY AFANASEV', '{"phone": "+70416117072"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432392021', '064589', '8060 473243', 'MARIYA PAVLOVA', '{"phone": "+70964218696"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432816945', '7F0E21', '8841 094140', 'EVGENIY MATVEEV', '{"email": "matveev-evgeniy12081962@postgrespro.ru", "phone": "+70499680033"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432817563', '9567AF', '2571 103533', 'IRINA KULIKOVA', '{"email": "kulikova_i30091963@postgrespro.ru", "phone": "+70249793260"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432901522', '32C1D7', '6629 360047', 'NIKITA SMIRNOV', '{"email": "smirnov_n.121972@postgrespro.ru", "phone": "+70023619999"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432920090', 'D9CF3C', '5288 858785', 'OLGA CHERNOVA', '{"email": "o.chernova.091965@postgrespro.ru", "phone": "+70427851224"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433477016', 'DDCAEA', '4994 536095', 'ELENA MAKAROVA', '{"email": "makarova-e1967@postgrespro.ru", "phone": "+70857713969"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433477058', '302066', '5130 647261', 'ROMAN SEMENOV', '{"email": "roman_semenov_18031968@postgrespro.ru", "phone": "+70549211446"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432000991', 'F313DD', '6615 976589', 'MAKSIM ZHUKOV', '{"email": "m-zhukov061972@postgrespro.ru", "phone": "+70149562185"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432000992', 'F313DD', '2021 652719', 'NIKOLAY EGOROV', '{"phone": "+70791452932"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432000993', 'F313DD', '0817 363231', 'TATYANA KUZNECOVA', '{"email": "kuznecova-t-011961@postgrespro.ru", "phone": "+70400736223"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432261149', 'FDC2AF', '6991 021312', 'NADEZHDA MIKHAYLOVA', '{"phone": "+70057157138"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005432530989', '6C0FB3', '3960 621312', 'REGINA KULIKOVA', '{"email": "rkulikova.1965@postgrespro.ru", "phone": "+70558361155"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433008470', '3A458F', '7437 921312', 'MIKHAIL AFANASEV', '{"phone": "+70798437076"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433450003', '579EFD', '5838 621312', 'EGOR NIKOLAEV', '{"phone": "+70630104123"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433451604', '63A498', '2846 021312', 'RAVIL ZAKHAROV', '{"phone": "+70006996217"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005433507416', 'CD9472', '6704 621312', 'ALINA BELOVA', '{"email": "a_belova1976@postgrespro.ru", "phone": "+70962645798"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005434374448', '31ADD5', '1405 221312', 'NADEZHDA ZHURAVLEVA', '{"phone": "+70628049699"}');
INSERT INTO bookings.tickets (ticket_no, book_ref, passenger_id, passenger_name, contact_data) VALUES ('0005434586243', '644C15', '4152 521312', 'EKATERINA SOROKINA', '{"email": "esorokina-30031971@postgrespro.ru", "phone": "+70878960119"}');
--
--
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432816945', 1, 'Business', 20000.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432901522', 2, 'Economy', 6700.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432817563', 3, 'Economy', 7300.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432391976', 17, 'Business', 20000.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432262099', 18, 'Economy', 6700.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432920090', 6, 'Economy', 6700.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432262559', 5, 'Economy', 7300.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005432392021', 12, 'Economy', 6700.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005433477016', 13, 'Economy', 6700.00);
INSERT INTO bookings.ticket_flights (ticket_no, flight_id, fare_conditions, amount) VALUES ('0005433477058', 9, 'Economy', 6700.00);
--
--
INSERT INTO bookings.boarding_passes (ticket_no, flight_id, boarding_no, seat_no) VALUES ('0005432816945', 1, 1, '2C');
INSERT INTO bookings.boarding_passes (ticket_no, flight_id, boarding_no, seat_no) VALUES ('0005432901522', 2, 27, '11C');
INSERT INTO bookings.boarding_passes (ticket_no, flight_id, boarding_no, seat_no) VALUES ('0005432817563', 3, 18, '8D');
INSERT INTO bookings.boarding_passes (ticket_no, flight_id, boarding_no, seat_no) VALUES ('0005432391976', 17, 11, '5A');
INSERT INTO bookings.boarding_passes (ticket_no, flight_id, boarding_no, seat_no) VALUES ('0005432262099', 18, 78, '26D');
--
-- 
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '2A', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '2C', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '2D', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '2F', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '3A', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '3C', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '3D', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '3F', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '4A', 'Business');
INSERT INTO bookings.seats (aircraft_code, seat_no, fare_conditions) VALUES ('319', '4C', 'Business');
--
--
ALTER TABLE bookings.boarding_passes
    ADD CONSTRAINT boarding_passes_ticket_no_fkey FOREIGN KEY (ticket_no, flight_id) REFERENCES bookings.ticket_flights(ticket_no, flight_id);
--
--
ALTER TABLE bookings.flights
    ADD CONSTRAINT flights_aircraft_code_fkey FOREIGN KEY (aircraft_code) REFERENCES bookings.aircrafts_data(aircraft_code);
--
--
ALTER TABLE bookings.flights
    ADD CONSTRAINT flights_arrival_airport_fkey FOREIGN KEY (arrival_airport) REFERENCES bookings.airports_data(airport_code);
--
--
ALTER TABLE bookings.flights
    ADD CONSTRAINT flights_departure_airport_fkey FOREIGN KEY (departure_airport) REFERENCES bookings.airports_data(airport_code);
--
--
ALTER TABLE bookings.seats
    ADD CONSTRAINT seats_aircraft_code_fkey FOREIGN KEY (aircraft_code) REFERENCES bookings.aircrafts_data(aircraft_code) ON DELETE CASCADE;
--
--
ALTER TABLE bookings.ticket_flights
    ADD CONSTRAINT ticket_flights_flight_id_fkey FOREIGN KEY (flight_id) REFERENCES bookings.flights(flight_id);
--
--
ALTER TABLE bookings.ticket_flights
    ADD CONSTRAINT ticket_flights_ticket_no_fkey FOREIGN KEY (ticket_no) REFERENCES bookings.tickets(ticket_no);
--
--
ALTER TABLE bookings.tickets
    ADD CONSTRAINT tickets_book_ref_fkey FOREIGN KEY (book_ref) REFERENCES bookings.bookings(book_ref);
--
--
--
--
DROP SCHEMA IF EXISTS test_schema;
CREATE SCHEMA test_schema;
--
--
DROP SCHEMA IF EXISTS public;
CREATE SCHEMA public;
--
--
CREATE TABLE test_schema.text_types
(
    text_types_key VARCHAR(36) NOT NULL COMMENT 'Идентификатор',
    meta_plain     text COMMENT 'Активация режима текста без форматирования',
    meta_text      text COMMENT 'Активация режима форматированного текста',
    detail_plain   text COMMENT 'Редактирование текста без форматирования',
    detail_text    text COMMENT 'Редактирование форматированного текста'
) COMMENT = 'Текстовые типы';
--
--
DELIMITER $$
CREATE FUNCTION public.testint(a1 int)
RETURNS bool
DETERMINISTIC
BEGIN
	RETURN TRUE;
END$$
DELIMITER ;
--
--
DELIMITER $$
CREATE FUNCTION public.testint2(a1 text, a2 integer)
RETURNS bool
DETERMINISTIC
BEGIN
	RETURN TRUE;
END$$
DELIMITER ;
--
--
DELIMITER $$
CREATE FUNCTION public.testint3()
RETURNS int
DETERMINISTIC
BEGIN
	RETURN 1200;
END$$
DELIMITER ;
--
--
DELIMITER $$
CREATE FUNCTION public.test4(a1 int)
RETURNS CHAR
DETERMINISTIC
BEGIN
	RETURN CAST(a1 as CHAR);
END$$
DELIMITER ;

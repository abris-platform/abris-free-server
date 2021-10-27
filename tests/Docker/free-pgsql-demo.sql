SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

DROP DATABASE IF EXISTS demo;
CREATE DATABASE demo;

\connect demo

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

--
--

CREATE SCHEMA bookings;
COMMENT ON SCHEMA bookings IS 'Airlines demo database schema';

--
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


SET search_path = bookings, pg_catalog;

--
--

CREATE FUNCTION lang() RETURNS text
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
  RETURN current_setting('bookings.lang');
EXCEPTION
  WHEN undefined_object THEN
    RETURN NULL;
END;
$$;

--
--

CREATE FUNCTION now() RETURNS timestamp with time zone
    LANGUAGE sql IMMUTABLE
    AS $$SELECT '2017-08-15 18:00:00'::TIMESTAMP AT TIME ZONE 'Europe/Moscow';$$;

--
--

COMMENT ON FUNCTION now() IS 'Point in time according to which the data are generated';

SET default_tablespace = '';
SET default_with_oids = false;

--
--

CREATE TABLE aircrafts_data (
    aircraft_code character(3) NOT NULL,
    model jsonb NOT NULL,
    range integer NOT NULL,
    CONSTRAINT aircrafts_range_check CHECK ((range > 0))
);

COMMENT ON TABLE aircrafts_data IS 'Aircrafts (internal data)';
COMMENT ON COLUMN aircrafts_data.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN aircrafts_data.model IS 'Aircraft model';
COMMENT ON COLUMN aircrafts_data.range IS 'Maximal flying distance, km';

--
--

CREATE VIEW aircrafts AS
 SELECT ml.aircraft_code,
    (ml.model ->> lang()) AS model,
    ml.range
   FROM aircrafts_data ml;

COMMENT ON VIEW aircrafts IS 'Aircrafts';
COMMENT ON COLUMN aircrafts.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN aircrafts.model IS 'Aircraft model';
COMMENT ON COLUMN aircrafts.range IS 'Maximal flying distance, km';

--
--

CREATE TABLE airports_data (
    airport_code character(3) NOT NULL,
    airport_name jsonb NOT NULL,
    city jsonb NOT NULL,
    coordinates point NOT NULL,
    timezone text NOT NULL
);

COMMENT ON TABLE airports_data IS 'Airports (internal data)';
COMMENT ON COLUMN airports_data.airport_code IS 'Airport code';
COMMENT ON COLUMN airports_data.airport_name IS 'Airport name';
COMMENT ON COLUMN airports_data.city IS 'City';
COMMENT ON COLUMN airports_data.coordinates IS 'Airport coordinates (longitude and latitude)';
COMMENT ON COLUMN airports_data.timezone IS 'Airport time zone';

--
--

CREATE VIEW airports AS
  SELECT ml.airport_code,
    ml.airport_name ->> COALESCE(bookings.lang(), 'ru') AS airport_name,
    ml.city ->> COALESCE(bookings.lang(), 'ru') AS city,
    ml.coordinates,
    ml.timezone
   FROM bookings.airports_data ml;

COMMENT ON VIEW airports IS 'Airports';
COMMENT ON COLUMN airports.airport_code IS 'Airport code';
COMMENT ON COLUMN airports.airport_name IS 'Airport name';
COMMENT ON COLUMN airports.city IS 'City';
COMMENT ON COLUMN airports.coordinates IS 'Airport coordinates (longitude and latitude)';
COMMENT ON COLUMN airports.timezone IS 'Airport time zone';

--
--

CREATE TABLE boarding_passes (
    ticket_no character(13) NOT NULL,
    flight_id integer NOT NULL,
    boarding_no integer NOT NULL,
    seat_no character varying(4) NOT NULL
);

COMMENT ON TABLE boarding_passes IS 'Boarding passes';
COMMENT ON COLUMN boarding_passes.ticket_no IS 'Ticket number';
COMMENT ON COLUMN boarding_passes.flight_id IS 'Flight ID';
COMMENT ON COLUMN boarding_passes.boarding_no IS 'Boarding pass number';
COMMENT ON COLUMN boarding_passes.seat_no IS 'Seat number';

--
--

CREATE TABLE bookings (
    book_ref character(6) NOT NULL,
    book_date timestamp NOT NULL,
    total_amount numeric(10,2) NOT NULL
);

COMMENT ON TABLE bookings IS 'Bookings';
COMMENT ON COLUMN bookings.book_ref IS 'Booking number';
COMMENT ON COLUMN bookings.book_date IS 'Booking date';
COMMENT ON COLUMN bookings.total_amount IS 'Total booking cost';

--
--

CREATE TABLE flights (
    flight_id integer NOT NULL,
    flight_no character(6) NOT NULL,
    scheduled_departure timestamp NOT NULL,
    scheduled_arrival timestamp NOT NULL,
    departure_airport character(3) NOT NULL,
    arrival_airport character(3) NOT NULL,
    status character varying(20) NOT NULL,
    aircraft_code character(3) NOT NULL,
    actual_departure timestamp,
    actual_arrival timestamp,
    CONSTRAINT flights_check CHECK ((scheduled_arrival > scheduled_departure)),
    CONSTRAINT flights_check1 CHECK (((actual_arrival IS NULL) OR ((actual_departure IS NOT NULL) AND (actual_arrival IS NOT NULL) AND (actual_arrival > actual_departure)))),
    CONSTRAINT flights_status_check CHECK (((status)::text = ANY (ARRAY[('On Time'::character varying)::text, ('Delayed'::character varying)::text, ('Departed'::character varying)::text, ('Arrived'::character varying)::text, ('Scheduled'::character varying)::text, ('Cancelled'::character varying)::text])))
);

COMMENT ON TABLE flights IS 'Flights';
COMMENT ON COLUMN flights.flight_id IS 'Flight ID';
COMMENT ON COLUMN flights.flight_no IS 'Flight number';
COMMENT ON COLUMN flights.scheduled_departure IS 'Scheduled departure time';
COMMENT ON COLUMN flights.scheduled_arrival IS 'Scheduled arrival time';
COMMENT ON COLUMN flights.departure_airport IS 'Airport of departure';
COMMENT ON COLUMN flights.arrival_airport IS 'Airport of arrival';
COMMENT ON COLUMN flights.status IS 'Flight status';
COMMENT ON COLUMN flights.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN flights.actual_departure IS 'Actual departure time';
COMMENT ON COLUMN flights.actual_arrival IS 'Actual arrival time';

--
--

CREATE SEQUENCE flights_flight_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE flights_flight_id_seq OWNED BY flights.flight_id;

--
--

CREATE VIEW flights_v AS
 SELECT f.flight_id,
    f.flight_no,
    f.scheduled_departure,
    timezone(dep.timezone, f.scheduled_departure) AS scheduled_departure_local,
    f.scheduled_arrival,
    timezone(arr.timezone, f.scheduled_arrival) AS scheduled_arrival_local,
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
    timezone(dep.timezone, f.actual_departure) AS actual_departure_local,
    f.actual_arrival,
    timezone(arr.timezone, f.actual_arrival) AS actual_arrival_local,
    (f.actual_arrival - f.actual_departure) AS actual_duration
   FROM flights f,
    airports dep,
    airports arr
  WHERE ((f.departure_airport = dep.airport_code) AND (f.arrival_airport = arr.airport_code));

COMMENT ON VIEW flights_v IS 'Flights (extended)';
COMMENT ON COLUMN flights_v.flight_id IS 'Flight ID';
COMMENT ON COLUMN flights_v.flight_no IS 'Flight number';
COMMENT ON COLUMN flights_v.scheduled_departure IS 'Scheduled departure time';
COMMENT ON COLUMN flights_v.scheduled_departure_local IS 'Scheduled departure time, local time at the point of departure';
COMMENT ON COLUMN flights_v.scheduled_arrival IS 'Scheduled arrival time';
COMMENT ON COLUMN flights_v.scheduled_arrival_local IS 'Scheduled arrival time, local time at the point of destination';
COMMENT ON COLUMN flights_v.scheduled_duration IS 'Scheduled flight duration';
COMMENT ON COLUMN flights_v.departure_airport IS 'Deprature airport code';
COMMENT ON COLUMN flights_v.departure_airport_name IS 'Departure airport name';
COMMENT ON COLUMN flights_v.departure_city IS 'City of departure';
COMMENT ON COLUMN flights_v.arrival_airport IS 'Arrival airport code';
COMMENT ON COLUMN flights_v.arrival_airport_name IS 'Arrival airport name';
COMMENT ON COLUMN flights_v.arrival_city IS 'City of arrival';
COMMENT ON COLUMN flights_v.status IS 'Flight status';
COMMENT ON COLUMN flights_v.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN flights_v.actual_departure IS 'Actual departure time';
COMMENT ON COLUMN flights_v.actual_departure_local IS 'Actual departure time, local time at the point of departure';
COMMENT ON COLUMN flights_v.actual_arrival IS 'Actual arrival time';
COMMENT ON COLUMN flights_v.actual_arrival_local IS 'Actual arrival time, local time at the point of destination';
COMMENT ON COLUMN flights_v.actual_duration IS 'Actual flight duration';

--
--

CREATE VIEW routes AS
 WITH f3 AS (
         SELECT f2.flight_no,
            f2.departure_airport,
            f2.arrival_airport,
            f2.aircraft_code,
            f2.duration,
            array_agg(f2.days_of_week) AS days_of_week
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
                            (to_char(flights.scheduled_departure, 'ID'::text))::integer AS days_of_week
                           FROM flights) f1
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
    airports dep,
    airports arr
  WHERE ((f3.departure_airport = dep.airport_code) AND (f3.arrival_airport = arr.airport_code));

COMMENT ON VIEW routes IS 'Routes';
COMMENT ON COLUMN routes.flight_no IS 'Flight number';
COMMENT ON COLUMN routes.departure_airport IS 'Code of airport of departure';
COMMENT ON COLUMN routes.departure_airport_name IS 'Name of airport of departure';
COMMENT ON COLUMN routes.departure_city IS 'City of departure';
COMMENT ON COLUMN routes.arrival_airport IS 'Code of airport of arrival';
COMMENT ON COLUMN routes.arrival_airport_name IS 'Name of airport of arrival';
COMMENT ON COLUMN routes.arrival_city IS 'City of arrival';
COMMENT ON COLUMN routes.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN routes.duration IS 'Scheduled duration of flight';
COMMENT ON COLUMN routes.days_of_week IS 'Days of week on which flights are scheduled';

--
--

CREATE TABLE seats (
    aircraft_code character(3) NOT NULL,
    seat_no character varying(4) NOT NULL,
    fare_conditions character varying(10) NOT NULL,
    CONSTRAINT seats_fare_conditions_check CHECK (((fare_conditions)::text = ANY (ARRAY[('Economy'::character varying)::text, ('Comfort'::character varying)::text, ('Business'::character varying)::text])))
);

COMMENT ON TABLE seats IS 'Seats';
COMMENT ON COLUMN seats.aircraft_code IS 'Aircraft code, IATA';
COMMENT ON COLUMN seats.seat_no IS 'Seat number';
COMMENT ON COLUMN seats.fare_conditions IS 'Travel class';

--
--

CREATE TABLE ticket_flights (
    ticket_no character(13) NOT NULL,
    flight_id integer NOT NULL,
    fare_conditions character varying(10) NOT NULL,
    amount numeric(10,2) NOT NULL,
    CONSTRAINT ticket_flights_amount_check CHECK ((amount >= (0)::numeric)),
    CONSTRAINT ticket_flights_fare_conditions_check CHECK (((fare_conditions)::text = ANY (ARRAY[('Economy'::character varying)::text, ('Comfort'::character varying)::text, ('Business'::character varying)::text])))
);

COMMENT ON TABLE ticket_flights IS 'Flight segment';
COMMENT ON COLUMN ticket_flights.ticket_no IS 'Ticket number';
COMMENT ON COLUMN ticket_flights.flight_id IS 'Flight ID';
COMMENT ON COLUMN ticket_flights.fare_conditions IS 'Travel class';
COMMENT ON COLUMN ticket_flights.amount IS 'Travel cost';

--
--

CREATE TABLE tickets (
    ticket_no character(13) NOT NULL,
    book_ref character(6) NOT NULL,
    passenger_id character varying(20) NOT NULL,
    passenger_name text NOT NULL,
    contact_data jsonb
);

COMMENT ON TABLE tickets IS 'Tickets';
COMMENT ON COLUMN tickets.ticket_no IS 'Ticket number';
COMMENT ON COLUMN tickets.book_ref IS 'Booking number';
COMMENT ON COLUMN tickets.passenger_id IS 'Passenger ID';
COMMENT ON COLUMN tickets.passenger_name IS 'Passenger name';
COMMENT ON COLUMN tickets.contact_data IS 'Passenger contact information';

--
--

ALTER TABLE ONLY flights ALTER COLUMN flight_id SET DEFAULT nextval('flights_flight_id_seq'::regclass);

--
--

INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('773', '{"en": "Boeing 777-300", "ru": "Боинг 777-300"}', 11100);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('763', '{"en": "Boeing 767-300", "ru": "Боинг 767-300"}', 7900);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('SU9', '{"en": "Sukhoi Superjet-100", "ru": "Сухой Суперджет-100"}', 3000);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('320', '{"en": "Airbus A320-200", "ru": "Аэробус A320-200"}', 5700);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('321', '{"en": "Airbus A321-200", "ru": "Аэробус A321-200"}', 5600);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('319', '{"en": "Airbus A319-100", "ru": "Аэробус A319-100"}', 6700);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('733', '{"en": "Boeing 737-300", "ru": "Боинг 737-300"}', 4200);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('CN1', '{"en": "Cessna 208 Caravan", "ru": "Сессна 208 Караван"}', 1200);
INSERT INTO bookings.aircrafts_data (aircraft_code, model, range) VALUES ('CR2', '{"en": "Bombardier CRJ-200", "ru": "Бомбардье CRJ-200"}', 2700);

--
--

INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('YKS', '{"en": "Yakutsk Airport", "ru": "Якутск"}', '{"en": "Yakutsk", "ru": "Якутск"}', '(129.77099609375,62.0932998657226562)', 'Asia/Yakutsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('MJZ', '{"en": "Mirny Airport", "ru": "Мирный"}', '{"en": "Mirnyj", "ru": "Мирный"}', '(114.03900146484375,62.534698486328125)', 'Asia/Yakutsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KHV', '{"en": "Khabarovsk-Novy Airport", "ru": "Хабаровск-Новый"}', '{"en": "Khabarovsk", "ru": "Хабаровск"}', '(135.18800354004,48.5279998779300001)', 'Asia/Vladivostok');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('PKC', '{"en": "Yelizovo Airport", "ru": "Елизово"}', '{"en": "Petropavlovsk", "ru": "Петропавловск-Камчатский"}', '(158.453994750976562,53.1679000854492188)', 'Asia/Kamchatka');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('UUS', '{"en": "Yuzhno-Sakhalinsk Airport", "ru": "Хомутово"}', '{"en": "Yuzhno-Sakhalinsk", "ru": "Южно-Сахалинск"}', '(142.718002319335938,46.8886985778808594)', 'Asia/Sakhalin');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('VVO', '{"en": "Vladivostok International Airport", "ru": "Владивосток"}', '{"en": "Vladivostok", "ru": "Владивосток"}', '(132.147994995117188,43.3989982604980469)', 'Asia/Vladivostok');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('LED', '{"en": "Pulkovo Airport", "ru": "Пулково"}', '{"en": "St. Petersburg", "ru": "Санкт-Петербург"}', '(30.2625007629394531,59.8003005981445312)', 'Europe/Moscow');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KGD', '{"en": "Khrabrovo Airport", "ru": "Храброво"}', '{"en": "Kaliningrad", "ru": "Калининград"}', '(20.5925998687744141,54.8899993896484375)', 'Europe/Kaliningrad');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('KEJ', '{"en": "Kemerovo Airport", "ru": "Кемерово"}', '{"en": "Kemorovo", "ru": "Кемерово"}', '(86.1072006225585938,55.2700996398925781)', 'Asia/Novokuznetsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('NOZ', '{"en": "Spichenkovo Airport", "ru": "Спиченково"}', '{"en": "Novokuznetsk", "ru": "Новокузнецк"}', '(86.877197265625,53.8114013671875)', 'Asia/Novokuznetsk');
INSERT INTO bookings.airports_data (airport_code, airport_name, city, coordinates, timezone) VALUES ('DME', '{"en": "Domodedovo International Airport", "ru": "Домодедово"}', '{"en": "Moscow", "ru": "Москва"}', '(37.9062995910644531,55.4087982177734375)', 'Europe/Moscow');

--
--

INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('064589', '2017-07-22 12:33:00+03', 310100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('1DC435', '2017-07-20 05:36:00+03', 6700.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('2F2226', '2017-07-13 10:40:00+03', 326100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('302066', '2017-08-09 03:21:00+03', 48900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('32C1D7', '2017-07-17 10:39:00+03', 137100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('7F0E21', '2017-07-02 03:06:00+03', 70200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('7F5D7B', '2017-08-04 21:31:00+03', 7300.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('9567AF', '2017-07-24 09:48:00+03', 50100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('D9CF3C', '2017-07-22 21:07:00+03', 98400.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('DDCAEA', '2017-08-09 00:10:00+03', 63300.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('F313DD', '2017-07-03 01:37:00.000000 +00:00', 30900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('000068', '2020-03-12 15:18:00.000000 +00:00', 18100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('0002D8', '2017-08-07 18:40:00.000000 +00:00', 23600.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('000012', '2020-03-12 15:18:00.000000 +00:00', 37900.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('00000F', '2017-07-05 00:12:00.000000 +00:00', 265700.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('31ADD5', '2017-07-11 05:19:00.000000 +00:00', 52000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('6C0FB3', '2017-08-06 08:12:00.000000 +00:00', 112100.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('CD9472', '2017-07-30 23:31:00.000000 +00:00', 91000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('63A498', '2017-07-10 09:31:00.000000 +00:00', 35200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('579EFD', '2017-07-23 13:04:00.000000 +00:00', 21400.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('FDC2AF', '2017-06-30 03:56:00.000000 +00:00', 20000.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('644C15', '2017-07-02 04:52:00.000000 +00:00', 96200.00);
INSERT INTO bookings.bookings (book_ref, book_date, total_amount) VALUES ('3A458F', '2017-07-20 21:47:00.000000 +00:00', 203500.00);

--
--

INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (1, 'PG0405', '2017-07-16 06:35:00', '2017-07-16 7:30:00', 'DME', 'LED', 'Arrived', '321', '2017-07-16 06:44:00', '2017-07-16 7:39:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (2, 'PG0404', '2017-08-05 16:05:00', '2017-08-05 17:00:00', 'DME', 'LED', 'Arrived', '321', '2017-08-05 16:06:00', '2017-08-05 17:01:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (3, 'PG0405', '2017-08-05 06:35:00', '2017-08-05 7:30:00', 'DME', 'LED', 'Arrived', '321', '2017-08-05 06:39:00', '2017-08-05 7:34:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (5, 'PG0405', '2017-08-16 06:35:00', '2017-08-16 7:30:00', 'DME', 'LED', 'On Time', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (6, 'PG0404', '2017-08-16 16:05:00', '2017-08-16 17:00:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (9, 'PG0405', '2017-08-25 06:35:00', '2017-08-25 7:30:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (12, 'PG0404', '2017-08-23 16:05:00', '2017-08-23 17:00:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (13, 'PG0405', '2017-08-23 06:35:00', '2017-08-23 7:30:00', 'DME', 'LED', 'Scheduled', '321', NULL, NULL);
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (17, 'PG0404', '2017-08-06 16:05:00', '2017-08-06 17:00:00', 'DME', 'LED', 'Arrived', '321', '2017-08-06 16:05:00', '2017-08-06 17:00:00');
INSERT INTO bookings.flights (flight_id, flight_no, scheduled_departure, scheduled_arrival, departure_airport, arrival_airport, status, aircraft_code, actual_departure, actual_arrival) VALUES (18, 'PG0405', '2017-08-06 06:35:00', '2017-08-06 17:30:00', 'DME', 'LED', 'Arrived', '321', '2017-08-06 06:39:00', '2017-08-06 7:35:00');

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

ALTER TABLE ONLY aircrafts_data
    ADD CONSTRAINT aircrafts_pkey PRIMARY KEY (aircraft_code);

ALTER TABLE ONLY airports_data
    ADD CONSTRAINT airports_data_pkey PRIMARY KEY (airport_code);

ALTER TABLE ONLY boarding_passes
    ADD CONSTRAINT boarding_passes_flight_id_boarding_no_key UNIQUE (flight_id, boarding_no);

ALTER TABLE ONLY boarding_passes
    ADD CONSTRAINT boarding_passes_flight_id_seat_no_key UNIQUE (flight_id, seat_no);

ALTER TABLE ONLY boarding_passes
    ADD CONSTRAINT boarding_passes_pkey PRIMARY KEY (ticket_no, flight_id);

ALTER TABLE ONLY bookings
    ADD CONSTRAINT bookings_pkey PRIMARY KEY (book_ref);

ALTER TABLE ONLY flights
    ADD CONSTRAINT flights_flight_no_scheduled_departure_key UNIQUE (flight_no, scheduled_departure);

ALTER TABLE ONLY flights
    ADD CONSTRAINT flights_pkey PRIMARY KEY (flight_id);

ALTER TABLE ONLY seats
    ADD CONSTRAINT seats_pkey PRIMARY KEY (aircraft_code, seat_no);

ALTER TABLE ONLY ticket_flights
    ADD CONSTRAINT ticket_flights_pkey PRIMARY KEY (ticket_no, flight_id);

ALTER TABLE ONLY tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (ticket_no);

ALTER TABLE ONLY boarding_passes
    ADD CONSTRAINT boarding_passes_ticket_no_fkey FOREIGN KEY (ticket_no, flight_id) REFERENCES ticket_flights(ticket_no, flight_id);

ALTER TABLE ONLY flights
    ADD CONSTRAINT flights_aircraft_code_fkey FOREIGN KEY (aircraft_code) REFERENCES aircrafts_data(aircraft_code);

ALTER TABLE ONLY flights
    ADD CONSTRAINT flights_arrival_airport_fkey FOREIGN KEY (arrival_airport) REFERENCES airports_data(airport_code);

ALTER TABLE ONLY flights
    ADD CONSTRAINT flights_departure_airport_fkey FOREIGN KEY (departure_airport) REFERENCES airports_data(airport_code);

ALTER TABLE ONLY seats
    ADD CONSTRAINT seats_aircraft_code_fkey FOREIGN KEY (aircraft_code) REFERENCES aircrafts_data(aircraft_code) ON DELETE CASCADE;

ALTER TABLE ONLY ticket_flights
    ADD CONSTRAINT ticket_flights_flight_id_fkey FOREIGN KEY (flight_id) REFERENCES flights(flight_id);

ALTER TABLE ONLY ticket_flights
    ADD CONSTRAINT ticket_flights_ticket_no_fkey FOREIGN KEY (ticket_no) REFERENCES tickets(ticket_no);

ALTER TABLE ONLY tickets
    ADD CONSTRAINT tickets_book_ref_fkey FOREIGN KEY (book_ref) REFERENCES bookings(book_ref);

--
--

ALTER DATABASE demo SET search_path = bookings, public;
ALTER DATABASE demo SET bookings.lang = ru;

--
--

CREATE SCHEMA test_modules;
ALTER SCHEMA test_modules OWNER TO postgres;

--
--

SET default_with_oids = FALSE;

--
--

CREATE SCHEMA test_schema;
ALTER SCHEMA test_schema OWNER TO postgres;
COMMENT ON SCHEMA test_schema IS 'Test schema';

SET default_tablespace = '';
SET default_with_oids = FALSE;

--
--

CREATE TABLE test_schema.text_types
(
    text_types_key uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    meta_plain     text,
    meta_text      text,
    detail_plain   text,
    detail_text    text
);


ALTER TABLE test_schema.text_types
    OWNER TO postgres;
COMMENT ON TABLE test_schema.text_types IS 'Текстовые типы';
COMMENT ON COLUMN test_schema.text_types.meta_plain IS 'Активация режима текста без форматирования';
COMMENT ON COLUMN test_schema.text_types.meta_text IS 'Активация режима форматированного текста';
COMMENT ON COLUMN test_schema.text_types.detail_plain IS 'Редактирование текста без форматирования';
COMMENT ON COLUMN test_schema.text_types.detail_text IS 'Редактирование форматированного текста';

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

CREATE OR REPLACE FUNCTION public.testint2(text, integer)
    RETURNS bool
    LANGUAGE 'sql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS
$BODY$
SELECT TRUE;
$BODY$;

ALTER FUNCTION public.testint2(text,integer)
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

--
--

CREATE ROLE admins WITH
    NOLOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE
    INHERIT
    NOREPLICATION
    CONNECTION LIMIT -1;


COMMENT ON ROLE postgres IS 'Администратор';
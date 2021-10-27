FROM ubuntu:20.04

RUN apt-get update -yqq \
    && DEBIAN_FRONTEND=noninteractive apt-get install -yqq \
    libpq-dev libmagickwand-dev libzip-dev \
    wget curl unzip nano sudo \
    postgresql-12 \
    mysql-server \
    locales

RUN sed -i '/ru_RU.UTF-8/s/^# //g' /etc/locale.gen && \
    locale-gen

RUN echo "host all  all   0.0.0.0/0   md5" >> /etc/postgresql/12/main/pg_hba.conf \
    && sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" /etc/postgresql/12/main/postgresql.conf \
    && sed -i 's/127.0.0.1/0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf

RUN curl -O https://abrisplatform.com/downloads/abris-free-databases.zip \
    && unzip abris-free-databases.zip -d abris-free

COPY free-pgsql-demo.sql /tmp/
COPY free-mysql-demo.sql /tmp/

CMD service postgresql start \
    && service mysql start \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -c \"ALTER USER ${PG_MAIN_LOGIN} WITH ENCRYPTED PASSWORD '${PG_ROOT_PASSWORD}';\"" \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -f /tmp/free-pgsql-demo.sql" \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -d demo -f /abris-free/pg_abris_free.sql" \
    && sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${MYSQL_ROOT_PASSWORD}'; FLUSH PRIVILEGES;" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "DELETE FROM mysql.user WHERE User='';" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER '${MYSQL_MAIN_LOGIN}'@'%' IDENTIFIED WITH mysql_native_password BY '${PG_ROOT_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_MAIN_LOGIN}'@'%'; FLUSH PRIVILEGES;" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} < /abris-free/mysql_abris_free.sql \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} < /tmp/free-mysql-demo.sql \
    && /etc/init.d/postgresql restart \
    && /etc/init.d/mysql restart \
    && tail -f /dev/null

ENV LANG ru_RU.UTF-8
ENV LANGUAGE ru_RU:ru
ENV LC_ALL ru_RU.UTF-8
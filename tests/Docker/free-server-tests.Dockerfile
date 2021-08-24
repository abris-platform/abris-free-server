FROM ubuntu:20.04

RUN apt-get update -yqq \
    && DEBIAN_FRONTEND=noninteractive apt-get install -yqq \
    php7.4 php7.4-pgsql php7.4-zip php7.4-mysql \
    wget libpq-dev libmagickwand-dev libzip-dev \
    curl unzip nano sudo \
    postgresql-12 \
    mysql-server

RUN sed -i 's/;extension=pgsql/extension=pgsql/' /etc/php/7.4/cli/php.ini \
    && sed -i 's/;extension=mysqli/extension=mysqli/' /etc/php/7.4/cli/php.ini

RUN echo "host all  all   0.0.0.0/0   md5" >> /etc/postgresql/12/main/pg_hba.conf \
    && sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" /etc/postgresql/12/main/postgresql.conf \
    && sed -i 's/127.0.0.1/0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf

RUN curl -O https://abrisplatform.com/downloads/abris-free.zip \
    && unzip abris-free.zip -d abris-free
RUN curl -O https://edu.postgrespro.ru/demo-small.zip \
    && unzip demo-small.zip -d demo-small

COPY pgsql_additional.sql /tmp/

CMD service postgresql start \
    && service mysql start \
    && su postgres -c "psql -U postgres -c \"ALTER USER ${PG_MAIN_LOGIN} WITH ENCRYPTED PASSWORD '${PG_ROOT_PASSWORD}';\"" \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -f /demo-small/demo-small-20170815.sql" \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -d demo -f /abris-free/Server/sql_install/pg_abris_free.sql" \
    && su postgres -c "psql -U ${PG_MAIN_LOGIN} -d demo -f /tmp/pgsql_additional.sql" \
    && sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${MYSQL_ROOT_PASSWORD}'; FLUSH PRIVILEGES;" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "DELETE FROM mysql.user WHERE User='';" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" \
    && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER '${MYSQL_MAIN_LOGIN}'@'%' IDENTIFIED WITH caching_sha2_password BY '${PG_ROOT_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_MAIN_LOGIN}'@'%'; FLUSH PRIVILEGES;" \
    # && sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} < /abris-free/Server/sql_install/mysql_abris_free.sql \
    && /etc/init.d/postgresql restart \
    && /etc/init.d/mysql restart \
    && tail -f /dev/null
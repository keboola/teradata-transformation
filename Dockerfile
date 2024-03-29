FROM quay.io/keboola/aws-cli
ARG AWS_ACCESS_KEY_ID
ARG AWS_SECRET_ACCESS_KEY
RUN /usr/bin/aws s3 cp s3://keboola-drivers/teradata/tdodbc1710-17.10.00.17-1.x86_64.deb /tmp/teradata/tdodbc.deb
RUN /usr/bin/aws s3 cp s3://keboola-drivers/teradata/utils/TeradataToolsAndUtilitiesBase__ubuntu_x8664.17.10.15.00.tar.gz  /tmp/teradata/tdutils.tar.gz

FROM php:7.4-cli

ARG COMPOSER_FLAGS="--prefer-dist --no-interaction"
ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 3600

WORKDIR /code/

COPY docker/php-prod.ini /usr/local/etc/php/php.ini
COPY docker/composer-install.sh /tmp/composer-install.sh

RUN apt-get update -q \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends\
        locales \
        unzip \
        unixodbc \
        unixodbc-dev \
	&& rm -r /var/lib/apt/lists/* \
	&& sed -i 's/^# *\(en_US.UTF-8\)/\1/' /etc/locale.gen \
	&& locale-gen \
	&& chmod +x /tmp/composer-install.sh \
	&& /tmp/composer-install.sh

ENV LANGUAGE=en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions

# Teradata ODBC
COPY --from=0 /tmp/teradata/tdodbc.deb /tmp/teradata/tdodbc.deb
COPY docker/teradata/odbc.ini /tmp/teradata/odbc_td.ini
COPY docker/teradata/odbcinst.ini /tmp/teradata/odbcinst_td.ini

RUN dpkg -i /tmp/teradata/tdodbc.deb \
    && cat /tmp/teradata/odbc_td.ini >> /etc/odbc.ini \
    && cat /tmp/teradata/odbcinst_td.ini >> /etc/odbcinst.ini \
    && rm -r /tmp/teradata \
    && docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
    && install-php-extensions pdo_odbc odbc \
    && docker-php-source delete

ENV ODBCHOME = /opt/teradata/client/ODBC_64/
ENV ODBCINI = /opt/teradata/client/ODBC_64/odbc.ini
ENV ODBCINST = /opt/teradata/client/ODBC_64/odbcinst.ini
ENV LD_LIBRARY_PATH = /opt/teradata/client/ODBC_64/lib

# Teradata Utils
COPY --from=0 /tmp/teradata/tdutils.tar.gz /tmp/teradata/tdutils.tar.gz
RUN cd /tmp/teradata \
    && tar -xvaf tdutils.tar.gz \
    && sh /tmp/teradata/TeradataToolsAndUtilitiesBase/.setup.sh tptbase s3axsmod \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/teradata

## Composer - deps always cached unless changed
# First copy only composer files
COPY composer.* /code/

# Download dependencies, but don't run scripts or init autoloaders as the app is missing
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader

# Copy rest of the app
COPY . /code/

# Run normal composer - all deps are cached already
RUN composer install $COMPOSER_FLAGS

CMD ["php", "/code/src/run.php"]

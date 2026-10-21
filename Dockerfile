# ==========================================================
# Elibrary Fatek UNSRAT - PHP 8.2 + Apache + gRPC (Firestore)
# Versi CEPAT: memakai image dengan ekstensi gRPC yang sudah dikompilasi,
# sehingga build hanya beberapa menit (tanpa kompilasi gRPC ~30-40 menit).
# Jika kebijakan TI hanya mengizinkan image resmi, pakai Dockerfile.compile.
# ==========================================================
FROM clegginabox/php-grpc:8.2-apache

# Alat bantu, Composer, dan ekstensi BCMath untuk Firestore
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip git \
    && docker-php-ext-install bcmath \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Pastikan ekstensi gRPC aktif (build berhenti lebih awal jika tidak)
RUN php -m | grep -i '^grpc$'

# Pengaturan PHP (mode produksi) dan Apache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN { \
      echo 'date.timezone=Asia/Makassar'; \
      echo 'upload_max_filesize=20M'; \
      echo 'post_max_size=25M'; \
      echo 'memory_limit=256M'; \
      echo 'grpc.enable_fork_support=1'; \
      echo 'grpc.poll_strategy=epoll1'; \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
    } > "$PHP_INI_DIR/conf.d/zz-elib.ini"
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && a2enmod rewrite headers

WORKDIR /var/www/html

# Salin seluruh isi repository (termasuk admin/vendor, admin/uploads, dan admin/credentials.json)
COPY . /var/www/html/

# Pasang dependensi Composer HANYA jika admin/vendor belum ada di repository
RUN cd admin && if [ ! -f vendor/autoload.php ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction --no-progress; \
    fi

# Folder unggahan harus bisa ditulis oleh Apache
RUN mkdir -p admin/uploads && chown -R www-data:www-data admin/uploads

EXPOSE 80

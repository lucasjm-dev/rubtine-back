FROM nginx:alpine

# Config del sitio
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# nginx necesita public/ para servir estáticos y resolver try_files.
# El SCRIPT_FILENAME que se le pasa a php-fpm debe coincidir con la ruta
# que tiene el contenedor "app", por eso lo dejamos en /var/www/html/public.
COPY public /var/www/html/public

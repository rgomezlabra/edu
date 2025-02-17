#!/bin/bash
WEBDIR=/var/www/html
cd $WEBDIR
if ! bin/console dbal:run-sql -q 'SELECT * FROM usuario' 2> /dev/null; then
    php bin/console make:migration --no-interaction && \
    php bin/console doctrine:migration:migrate --no-interaction && \
    php bin/console doctrine:fixtures:load --no-interaction
fi
echo "Finalizado."

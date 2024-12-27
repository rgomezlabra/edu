# Evaluación del Desempeño Universitario

La aplicación web para la **Evaluación del Desempeño Universitario (EDU)** es una adaptación de la aplicación
**Evaluación del Desempeño**, junto con un subconjunto de herramientas adaptadas del Sistema Integral de Recursos
Humanos de la Universidad de Sevilla (**SIRHUS**).

## Requisitos

EDU está desarrollada sobre el entorno de programación Symfony 6.4 y se necesitan los siguientes requisitos de
instalación:
* Servidor web (Apache, Nginx)
* Gestor de bases de datos (MySQL, MariaDB, PostgreSQL)
* Servidor Redis para registro de sesiones (¿?)
* PHP 8.3
* Composer (https://getcomposer.com)

## Instalación

*(redactar adecuadamente)*

    git clone https://github.com/rgomezlabra/edu.git
    cd edu
    composer install
    php bin/console asset-map:compile
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ...
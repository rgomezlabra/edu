<?php

namespace App\DataFixtures;

use App\Entity\Estado;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar estados básicos de recursos.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class EstadoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Estados de tipo sistema
        $estado = new Estado();
        $estado->setNombre(Estado::PUBLICADO)
            ->setTipo(Estado::SISTEMA)
            ->setDescripcion('Recurso accesible públicamente')
            ->setIcono('fa-square-check')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::BORRADOR)
            ->setTipo(Estado::SISTEMA)
            ->setDescripcion('Recurso accesible solo por administrador/editor')
            ->setIcono('fa-keyboard')
            ->setColor('gray')
        ;
        $manager->persist($estado);
        $this->addReference(Estado::BORRADOR, $estado);
        $estado = new Estado();
        $estado->setNombre(Estado::ARCHIVADO)
            ->setTipo(Estado::SISTEMA)
            ->setDescripcion('Recurso archivado o caducado')
            ->setIcono('fa-archive')
            ->setColor('cyan')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::ELIMINADO)
            ->setTipo(Estado::SISTEMA)
            ->setDescripcion('Recurso marcado para su eliminación, es recuperable')
            ->setIcono('fa-trash')
            ->setColor('red')
        ;
        $manager->persist($estado);
        // Estados de tipo incidencia
        $estado = new Estado();
        $estado->setNombre(Estado::INICIADO)
            ->setTipo(Estado::INCIDENCIA)
            ->setDescripcion('Nueva incidencia')
            ->setIcono('fa-play')
            ->setColor('red')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::PROCESANDO)
            ->setTipo(Estado::INCIDENCIA)
            ->setDescripcion('Incidencia en proceso')
            ->setIcono('fa-sync-alt')
            ->setColor('orange')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::INTERNO)
            ->setTipo(Estado::INCIDENCIA)
            ->setDescripcion('Incidencia notificada a servicio interno')
            ->setIcono('fa-sign-in-alt')
            ->setColor('purple')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::EXTERNO)
            ->setTipo(Estado::INCIDENCIA)
            ->setDescripcion('Incidencia notificada a servicio externo')
            ->setIcono('fa-sign-out-alt')
            ->setColor('navy')
        ;
        $manager->persist($estado);
        $estado = new Estado();
        $estado->setNombre(Estado::FINALIZADO)
            ->setTipo(Estado::INCIDENCIA)
            ->setDescripcion('Incidencia finalizada')
            ->setIcono('fa-flag-checkered')
            ->setColor('green')
        ;
        $manager->persist($estado);
        $manager->flush();
    }
}

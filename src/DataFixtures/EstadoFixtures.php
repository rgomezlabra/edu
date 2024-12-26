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
        // Estados
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
        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Unidad;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar unidades de ejemplo.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class UnidadFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $unidad = new Unidad();
        $unidad->setCodigo('U001')->setNombre('Unidad de Ejemplo 1');
        $manager->persist($unidad);
        $unidad = new Unidad();
        $unidad->setCodigo('U002')->setNombre('Unidad de Ejemplo 2');
        $manager->persist($unidad);
        $manager->flush();
    }
}

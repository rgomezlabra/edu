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
    public const string UNIDAD1 = 'U001';
    public const string UNIDAD2 = 'U002';

    public function load(ObjectManager $manager): void
    {
        $unidad = new Unidad();
        $unidad->setCodigo(self::UNIDAD1)->setNombre('Unidad de Ejemplo 1');
        $manager->persist($unidad);
        $this->addReference(self::UNIDAD1, $unidad);
        $unidad = new Unidad();
        $unidad->setCodigo(self::UNIDAD2)->setNombre('Unidad de Ejemplo 2');
        $manager->persist($unidad);
        $this->addReference(self::UNIDAD2, $unidad);
        $manager->flush();
    }
}

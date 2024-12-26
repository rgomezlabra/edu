<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Situacion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar situaciones administrativas básicas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class SituacionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $situacion = new Situacion();
        $situacion->setCodigo('AC')->setNombre('Servicio Activo');
        $manager->persist($situacion);
        $manager->flush();
    }
}

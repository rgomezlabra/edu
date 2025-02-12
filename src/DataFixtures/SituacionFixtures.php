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
    public const string SITUA = 'AC';

    public function load(ObjectManager $manager): void
    {
        $situacion = new Situacion();
        $situacion->setCodigo(self::SITUA)->setNombre('Servicio Activo');
        $manager->persist($situacion);
        $manager->flush();
        $this->addReference(self::SITUA, $situacion);
    }
}

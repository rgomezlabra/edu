<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Grupo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar grupos profesionales básicos.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class GrupoFixtures extends Fixture
{
    public const string GRUPO1 = 'A1';
    public const string GRUPO2 = 'C1';
    public const string GRUPO3 = 'L3';

    public function load(ObjectManager $manager): void
    {
        $grupo = new Grupo();
        $grupo->setNombre('A1')
            ->setAdscripcion('F')
            ->setNivelMinimo(22)
            ->setNivelMaximo(30)
        ;
        $manager->persist($grupo);
        $this->addReference(self::GRUPO1, $grupo);
        $grupo = new Grupo();
        $grupo->setNombre('A2')
            ->setAdscripcion('F')
            ->setNivelMinimo(18)
            ->setNivelMaximo(26)
        ;
        $manager->persist($grupo);
        $grupo = new Grupo();
        $grupo->setNombre('C1')
            ->setAdscripcion('F')
            ->setNivelMinimo(15)
            ->setNivelMaximo(22)
        ;
        $manager->persist($grupo);
        $this->addReference(self::GRUPO2, $grupo);
        $grupo = new Grupo();
        $grupo->setNombre('C2')
            ->setAdscripcion('F')
            ->setNivelMinimo(14)
            ->setNivelMaximo(18)
        ;
        $manager->persist($grupo);
        $grupo = new Grupo();
        $grupo->setNombre('L1')->setAdscripcion('L');
        $manager->persist($grupo);
        $grupo = new Grupo();
        $grupo->setNombre('L2')->setAdscripcion('L');
        $manager->persist($grupo);
        $grupo = new Grupo();
        $grupo->setNombre('L3')->setAdscripcion('L');
        $manager->persist($grupo);
        $this->addReference(self::GRUPO3, $grupo);
        $grupo = new Grupo();
        $grupo->setNombre('L4')->setAdscripcion('L');
        $manager->persist($grupo);
        $manager->flush();
    }
}

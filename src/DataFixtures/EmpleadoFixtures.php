<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Empleado;
use App\Entity\Plantilla\Grupo;
use App\Entity\Plantilla\Situacion;
use App\Entity\Plantilla\Unidad;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Clock\DatePoint;

/**
 * Cargar empleados de defecto.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class EmpleadoFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ADMIN = 'admin';
    public const string EMPLEADO = 'curry';
    public const string EVALUADOR = 'evalua';
    public const string COLABORADOR = 'colabora';

    public function load(ObjectManager $manager): void
    {
        $empleado = new Empleado();
        $empleado->setNombre('Administrador')
            ->setApellidos('de Ejemplo')
            ->setDocIdentidad('11111111H')
            ->setNrp('11111111H')
            ->setCesado(DatePoint::createFromFormat('Y-m-d', '2022-01-01'))
        ;
        $manager->persist($empleado);
        $manager->flush();
        $this->addReference(self::ADMIN, $empleado);

        for ($i = 0; $i < 3; $i++) {
            $empleado = new Empleado();
            $empleado->setNombre('Currante')
                ->setApellidos('Total ' . $i)
                ->setDocIdentidad(str_repeat($i + 2, 8) . chr(65 + $i))
                ->setNrp(str_repeat($i + 2, 8) . chr(65 + $i))
                ->setGrupo($this->getReference($i % 2 === 0 ? GrupoFixtures::GRUPO2 : GrupoFixtures::GRUPO3, Grupo::class))
                ->setNivel($i % 2 === 0 ? 20 : null)
                ->setUnidad($this->getReference($i < 2 ? UnidadFixtures::UNIDAD1 : UnidadFixtures::UNIDAD2, Unidad::class))
                ->setSituacion($this->getReference(SituacionFixtures::SITUA, Situacion::class))
            ;
            $manager->persist($empleado);
            $manager->flush();
            $this->addReference(self::EMPLEADO . $i, $empleado);
        }

        for ($i = 0; $i < 2; $i++) {
            $empleado = new Empleado();
            $empleado->setNombre('Evaluador')
                ->setApellidos('Eficiente ' . $i)
                ->setDocIdentidad(str_repeat($i + 5, 8) . chr(65 + $i))
                ->setNrp(str_repeat($i + 5, 8) . chr(65 + $i))
                ->setGrupo($this->getReference(GrupoFixtures::GRUPO1, Grupo::class))
                ->setNivel(27)
                ->setUnidad($this->getReference($i % 2 === 0 ? UnidadFixtures::UNIDAD1 : UnidadFixtures::UNIDAD2, Unidad::class))
                ->setSituacion($this->getReference(SituacionFixtures::SITUA, Situacion::class))
            ;
            $manager->persist($empleado);
            $manager->flush();
            $this->addReference(self::EVALUADOR . $i, $empleado);
            $empleado = new Empleado();
            $empleado->setNombre('Colaborador')
                ->setApellidos('Colaborativo ' . $i)
                ->setDocIdentidad(str_repeat($i + 7, 8) . chr(65 + $i))
                ->setNrp(str_repeat($i + 7, 8) . chr(65 + $i))
                ->setGrupo($this->getReference(GrupoFixtures::GRUPO1, Grupo::class))
                ->setNivel(25)
                ->setUnidad($this->getReference($i % 2 === 0 ? UnidadFixtures::UNIDAD1 : UnidadFixtures::UNIDAD2, Unidad::class))
                ->setSituacion($this->getReference(SituacionFixtures::SITUA, Situacion::class))
            ;
            $manager->persist($empleado);
            $manager->flush();
            $this->addReference(self::COLABORADOR . $i, $empleado);
        }
    }

    public function getDependencies(): array
    {
        return [
            GrupoFixtures::class,
            SituacionFixtures::class,
            UnidadFixtures::class,
        ];
    }
}

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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Cargar empleados de defecto.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class EmpleadoFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ADMIN_EJEMPLO = 'admin';
    public const string EMPLEADO_EJEMPLO = 'curry';
    public const string EVALUADOR_EJEMPLO = 'evalua';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

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
        $this->addReference(self::ADMIN_EJEMPLO, $empleado);

        $empleado = new Empleado();
        $empleado->setNombre('Currante')
            ->setApellidos('Total')
            ->setDocIdentidad('22222222R')
            ->setNrp('22222222R')
            ->setGrupo($this->getReference(GrupoFixtures::GRUPO_EJEMPLO, Grupo::class))
            ->setUnidad($this->getReference(UnidadFixtures::UNIDAD_EJEMPLO, Unidad::class))
            ->setSituacion($this->getReference(SituacionFixtures::SITUA_EJEMPLO, Situacion::class))
        ;
        $manager->persist($empleado);
        $manager->flush();
        $this->addReference(self::EMPLEADO_EJEMPLO, $empleado);

        $empleado = new Empleado();
        $empleado->setNombre('Evaluador')
            ->setApellidos('Eficiente')
            ->setDocIdentidad('33333333K')
            ->setNrp('33333333K')
            ->setGrupo($this->getReference(GrupoFixtures::GRUPO_EJEMPLO, Grupo::class))
            ->setUnidad($this->getReference(UnidadFixtures::UNIDAD_EJEMPLO, Unidad::class))
            ->setSituacion($this->getReference(SituacionFixtures::SITUA_EJEMPLO, Situacion::class))
        ;
        $manager->persist($empleado);
        $manager->flush();
        $this->addReference(self::EVALUADOR_EJEMPLO, $empleado);
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

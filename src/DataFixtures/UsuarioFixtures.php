<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Empleado;
use App\Entity\Plantilla\Grupo;
use App\Entity\Plantilla\Situacion;
use App\Entity\Plantilla\Unidad;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Cargar usuario administrador por defecto.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class UsuarioFixtures extends Fixture
{
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
        ;
        $manager->persist($empleado);
        $manager->flush();
        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-admin');
        $usuario->setLogin('admin')
            ->setPassword($clave)
            ->setCorreo('admin@localhost')
            ->setRoles(['ROLE_ADMIN'])
            ->setEmpleado($empleado)
        ;
        $manager->persist($usuario);

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
        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-curry');
        $usuario->setLogin('curry')
            ->setPassword($clave)
            ->setCorreo('curry@localhost')
            ->setEmpleado($empleado)
        ;
        $manager->persist($usuario);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GrupoFixtures::class,
            Situacion::class,
            UnidadFixtures::class,
        ];
    }
}

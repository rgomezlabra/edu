<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Empleado;
use App\Entity\Usuario;
use DateTime;
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
        $now = new DateTime();
        $empleado = new Empleado();
        $empleado->setNombre('Administrador')
            ->setApellido1('Ejemplo')
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
            ->setCreado($now)
            ->setModificado($now);
        ;
        $manager->persist($usuario);

        $empleado = new Empleado();
        $empleado->setNombre('Currante')
            ->setApellido1('Total')
            ->setDocIdentidad('22222222R')
            ->setNrp('22222222R')
        ;
        $manager->persist($empleado);
        $manager->flush();
        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'muy-curry');
        $usuario->setLogin('curry')
            ->setPassword($clave)
            ->setCorreo('curry@localhost')
            ->setEmpleado($empleado)
            ->setCreado($now)
            ->setModificado($now);
        ;
        $manager->persist($usuario);
        $manager->flush();
    }
}

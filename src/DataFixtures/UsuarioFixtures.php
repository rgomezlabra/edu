<?php

namespace App\DataFixtures;

use App\Entity\Plantilla\Empleado;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Cargar usuario administrador por defecto.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class UsuarioFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-' . EmpleadoFixtures::ADMIN_EJEMPLO);
        $usuario->setLogin(EmpleadoFixtures::ADMIN_EJEMPLO)
            ->setPassword($clave)
            ->setCorreo(EmpleadoFixtures::ADMIN_EJEMPLO . '@localhost')
            ->setRoles(['ROLE_ADMIN'])
            ->setEmpleado($this->getReference(EmpleadoFixtures::ADMIN_EJEMPLO, Empleado::class))
        ;
        $manager->persist($usuario);
        $manager->flush();

        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-' . EmpleadoFixtures::EMPLEADO_EJEMPLO);
        $usuario->setLogin(EmpleadoFixtures::EMPLEADO_EJEMPLO)
            ->setPassword($clave)
            ->setCorreo(EmpleadoFixtures::EMPLEADO_EJEMPLO . '@localhost')
            ->setEmpleado($this->getReference(EmpleadoFixtures::EMPLEADO_EJEMPLO, Empleado::class))
        ;
        $manager->persist($usuario);
        $manager->flush();

        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-' . EmpleadoFixtures::EVALUADOR_EJEMPLO);
        $usuario->setLogin(EmpleadoFixtures::EVALUADOR_EJEMPLO)
            ->setPassword($clave)
            ->setCorreo(EmpleadoFixtures::EVALUADOR_EJEMPLO . '@localhost')
            ->setEmpleado($this->getReference(EmpleadoFixtures::EVALUADOR_EJEMPLO, Empleado::class))
        ;
        $manager->persist($usuario);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EmpleadoFixtures::class,
        ];
    }
}

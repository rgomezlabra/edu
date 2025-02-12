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
        $this->crearUsuario($manager, EmpleadoFixtures::ADMIN, ['ROLE_ADMIN']);
        for ($i = 0; $i < 3; $i++) {
            $this->crearUsuario($manager, EmpleadoFixtures::EMPLEADO . $i);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->crearUsuario($manager, EmpleadoFixtures::EVALUADOR . $i);
            $this->crearUsuario($manager, EmpleadoFixtures::COLABORADOR . $i);
        }
    }

    public function getDependencies(): array
    {
        return [
            EmpleadoFixtures::class,
        ];
    }

    /** @param string[] $roles */
    private function crearUsuario(ObjectManager $manager, string $login, array $roles = []): void
    {
        $usuario = new Usuario();
        $clave = $this->hasher->hashPassword($usuario, 'edu-' . $login);
        $usuario->setLogin($login)
            ->setPassword($clave)
            ->setCorreo($login . '@localhost')
            ->setRoles($roles)
            ->setEmpleado($this->getReference($login, Empleado::class))
        ;
        $manager->persist($usuario);
        $manager->flush();
    }
}

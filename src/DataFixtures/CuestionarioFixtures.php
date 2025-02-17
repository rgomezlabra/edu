<?php

namespace App\DataFixtures;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Estado;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar cuestionario de ejemplo.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class CuestionarioFixtures extends Fixture implements DependentFixtureInterface
{
    public const string EDU25 = 'EDU25';

    public function load(ObjectManager $manager): void
    {
        $cuestionario = new Cuestionario();
        $cuestionario->setCodigo('EDU25')
            ->setTitulo('Evaluación de Competencias o Conductas Profesionales 2025')
            ->setDescripcion('<p>Esta es la página de descripción del cuestionario.</p>')
            ->setBienvenida('<p>Esta es la página de bienvenida con instrucciones para rellenar el cuestionario.</p>')
            ->setDespedida('<p>Esta es la página de bienvenida con instrucciones para enviar el cuestionario.</p>')
            ->setConfiguracion(['peso1' => 34, 'peso2' => 56, 'peso3' => 10, 'reducido' => false])
            ->setEstado($this->getReference(Estado::BORRADOR, Estado::class))
            ->setAutor($this->getReference('usuario' . EmpleadoFixtures::ADMIN, Usuario::class))
        ;
        $manager->persist($cuestionario);
        $manager->flush();
        $this->addReference(self::EDU25, $cuestionario);
    }

    public function getDependencies(): array
    {
        return [
            EstadoFixtures::class,
            UsuarioFixtures::class,
        ];
    }
}

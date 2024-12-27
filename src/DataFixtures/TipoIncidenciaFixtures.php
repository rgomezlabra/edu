<?php

namespace App\DataFixtures;

use App\Entity\Desempenyo\TipoIncidencia;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Cargar tipos básicos de incidencias.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class TipoIncidenciaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tipo = new TipoIncidencia();
        $tipo->setNombre('Acceso a la aplicación')
            ->setDescripcion('No se puede acceder a la aplicación Evaluación de Desempeño.')
        ;
        $manager->persist($tipo);
        $tipo = new TipoIncidencia();
        $tipo->setNombre('Acceso a cuestionario')
            ->setDescripcion('No se puede acceder al cuestionario para realizar la autoevaluación.')
        ;
        $manager->persist($tipo);
        $tipo = new TipoIncidencia();
        $tipo->setNombre('Sin evaluador asignado')
            ->setDescripcion('No hay un agente evaluador asignado para que pueda realizar la evaluación.')
        ;
        $manager->persist($tipo);
        $tipo = new TipoIncidencia();
        $tipo->setNombre('Evaluador incorrecto')
            ->setDescripcion('El agente evaluador asignado no es correcto.')
        ;
        $manager->persist($tipo);
        $tipo = new TipoIncidencia();
        $tipo->setNombre('Otro tipo de incidencia')
            ->setDescripcion('Otro tipo de incidencia no especificada anteriormente.')
        ;
        $manager->persist($tipo);
        $manager->flush();
    }
}

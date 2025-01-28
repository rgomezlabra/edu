<?php

namespace App\Form\Desempenyo;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Formulario para configurar fechas para obtener resultados de un cuestionario de evaluación del desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class CuestionarioFechasType extends AbstractType
{
    #[Override]
    public function getParent(): string
    {
        return FormType::class;
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('provisional', DateType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'help' => 'Fecha para obtener resultados provisionales, posterior a fin del periodo de validez.',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('definitiva', DateType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'help' => 'Fecha para obtener resultados definitivos, igual o posterior a la fecha provisional.',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }
}

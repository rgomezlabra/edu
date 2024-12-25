<?php

namespace App\Form\Cuestiona;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar las fechas del periodo de validez de un cuestionario de preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class PeriodoValidezType extends AbstractType
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
            ->add('fecha_alta', DateType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'help' => $options['con_fechas'] ? 'Dato obligatorio al publicar el cuestionario.' : 'Opcional.',
                'label' => 'Fecha de inicio',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('fecha_baja', DateType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'help' => $options['con_fechas'] ? 'Dato obligatorio al publicar el cuestionario.' : 'Opcional.',
                'label' => 'Fecha de fin',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'con_fechas' => false,
        ]);
        $resolver->setAllowedTypes('con_fechas', 'bool');
    }
}

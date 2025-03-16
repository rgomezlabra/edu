<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Evalua;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para corregir la puntuación de una evaluación de desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Evalua>
 */
class CorreccionType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Evalua $evaluacion */
        $evaluacion = $options['data'];
        $builder
            ->add('empleado', TextType::class, [
                'attr' => [
                    'class'=>'w-50',
                    'readonly' => true,
                ],
                'data' => (string) $evaluacion->getEmpleado(),
                'mapped' => false,
            ])
            ->add('correccion', null, [
                'attr' => ['class' => 'w-25'],
                'help' => 'Valor numérico entre 0 y 100 con hasta 2 decimales.',
                'label' => 'Puntuación global tribunal',
            ])
            ->add('comentario', null , [
                'required' => false,
            ])
            ->add('corrector', TextType::class, [
                'attr' => [
                    'class'=>'w-50',
                    'readonly' => true,
                ],
                'data' => (string) $evaluacion->getCorrector()?->getLogin(),
                'label' => 'Confirmada por',
                'mapped' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evalua::class,
        ]);
    }
}

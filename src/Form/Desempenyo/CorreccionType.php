<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Evalua;
use Override;
use Symfony\Component\Form\AbstractType;
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
        $builder
            ->add('correccion', null, [
                'attr' => ['class' => 'w-25'],
                'help' => 'Valor numérico entre 0 y 10 con hasta 2 decimales.',
                'label' => 'Puntuación corregida',
            ])
            ->add('comentario', null , [
                'required' => false,
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

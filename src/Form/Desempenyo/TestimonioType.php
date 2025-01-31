<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Evalua;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar el testimonio añadido a una evaluación.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Evalua>
 */
class TestimonioType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('testimonio', null, [
            'label' => false,
        ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evalua::class,
        ]);
    }
}

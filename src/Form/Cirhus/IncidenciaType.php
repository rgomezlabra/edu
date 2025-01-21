<?php

namespace App\Form\Cirhus;

use App\Entity\Cirhus\Incidencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncidenciaType extends AbstractType
{
    /** @var string[] */
    public const array TIPOS_VALIDOS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('descripcion', null, [
                'label' => 'Descripción',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Incidencia::class,
            'de_aplicacion' => false,
        ]);
        $resolver->setAllowedTypes('de_aplicacion', 'bool');
    }
}

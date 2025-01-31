<?php

namespace App\Form\Desempenyo;

use App\Entity\Cirhus\IncidenciaApunte;
use App\Form\Cirhus\IncidenciaApunteType as CirhusIncidenciaApunteType;
use Override;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncidenciaApunteType extends CirhusIncidenciaApunteType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        if ('' !== $options['rol']) {
            $builder
                ->add('servicio', null, [
                    'help' => 'Dato interno que no se muestra al usuario creador de la incidencia.',
                    'label' => 'Servicio asignado',
                    'required' => false,
                ])
                ->add('observaciones', null, [
                    'help' => 'Dato interno que no se muestra al usuario creador de la incidencia.',
                    'label' => 'Observaciones internas',
                    'required' => false,
                ])
            ;
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncidenciaApunte::class,
            'reabrir' => false,
            'rol' => '',
        ]);
        $resolver->setAllowedTypes('reabrir', 'bool');
        $resolver->setAllowedTypes('rol', 'string');
    }
}

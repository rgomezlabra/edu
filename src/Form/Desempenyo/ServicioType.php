<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Servicio;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicioType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', null, [
                'label' => 'Código',
            ])
            ->add('nombre')
            ->add('correo', EmailType::class, [
                'label' => 'Correo electrónico',
            ])
            ->add('telefono', TelType::class, [
                'label' => 'Teléfono',
            ])
            ->add('responsable')
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Servicio::class,
        ]);
    }
}

<?php

namespace App\Form\Cuestiona;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class OpcionesRangoType extends AbstractType
{
    /** @inheritDoc */
    #[Override]
    public function getParent(): string
    {
        return FormType::class;
    }

    /** @inheritDoc */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('min', NumberType::class, [
                'attr' => ['class' => 'w-auto'],
                'help' => 'Valor mínimo (obligatorio).',
                'label' => 'Mínimo',
            ])
            ->add('max', NumberType::class, [
                'attr' => ['class' => 'w-auto'],
                'help' => 'Valor máximo (obligatorio).',
                'label' => 'Máximo',
            ])
            ->add('observaciones', CheckboxType::class, [
                'help' => 'Marcar si debe mostrarse un cuadro de texto para escribir observaciones.',
                'required' => false,
            ])
        ;
    }
}

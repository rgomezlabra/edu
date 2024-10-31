<?php

namespace App\Form\Desempenyo;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;

class ConfiguraCuestionarioType extends AbstractType
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
            ->add('peso1', IntegerType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'constraints' => [
                    new Range(['min' => 0, 'max' => 100])
                ],
                'help' => 'Peso de la evaluación de la Fase 1: Autoevaluación (valor entre 0 y 100).',
                'label' => 'Peso Fase 1',
            ])
            ->add('peso2', IntegerType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'constraints' => [
                    new Range(['min' => 0, 'max' => 100]),
                ],
                'help' => 'Peso de la evaluación de la Fase 2 Evaluación por responsable (valor entre 0 y 100).',
                'label' => 'Peso Fase 2',
            ])
            ->add('peso3', IntegerType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'constraints' => [
                    new Range(['min' => 0, 'max' => 100]),
                ],
                'help' => 'Peso de la evaluación de la Fase 3 Evaluación por tercer agente (valor entre 0 y 100).',
                'label' => 'Peso Fase 3',
            ])
        ;
    }
}

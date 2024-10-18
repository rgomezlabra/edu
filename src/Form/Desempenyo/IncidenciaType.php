<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Incidencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar una incidencia para evaluación de desempeño.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Incidencia>
 */
class IncidenciaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cuestionario', TextType::class, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'data' => $options['data']->getCuestionario()->getTitulo(),
                'disabled' => true,
            ])
            ->add('tipo', null, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'help' => 'Elegir un tipo de incidencia de la lista desplegable.',
                'label' => 'Tipo de incidencia',
                'placeholder' => false,
            ])
            ->add('incidencia', \App\Form\Cirhus\IncidenciaType::class, [
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Incidencia::class,
        ]);
    }
}

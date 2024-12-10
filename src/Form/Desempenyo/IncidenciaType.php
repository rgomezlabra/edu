<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Incidencia;
use App\Form\Cirhus\IncidenciaType as CirhusIncidenciaType;
use Override;
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
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Incidencia $incidencia */
        $incidencia = $options['data'];
        $builder
            ->add('cuestionario', TextType::class, [
                'attr' => [
                    'class' => 'w-50',
                ],
                'data' => $incidencia->getCuestionario()?->getTitulo(),
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
            ->add('incidencia', CirhusIncidenciaType::class, [
                'label' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Incidencia::class,
        ]);
    }
}

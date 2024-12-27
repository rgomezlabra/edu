<?php

namespace App\Form\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar grupo preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Cuestionario>
 */
class GrupoType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cuestionario', null, [
                'attr' => [
                    'class' => 'w-auto',
                ],
                'placeholder' => false,
            ])
            ->add('orden', null, [
                'attr' => [
                    'class' => 'w-auto',
                ],
            ])
            ->add('codigo', null, [
                'attr' => [
                    'class' => 'w-25',
                ],
                'label' => 'Código',
            ])
            ->add('titulo', null, [
                'label' => 'Título',
            ])
            ->add('descripcion', CKEditorType::class, [
                'label' => 'Descripción',
            ])
            ->add('activa', null, [
                'required' => false,
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Grupo::class,
        ]);
    }
}

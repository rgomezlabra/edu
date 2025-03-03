<?php

namespace App\Form\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Form\Type\TextEditorType;
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
                'attr' => ['class' => 'w-auto'],
                'placeholder' => false,
            ])
            ->add('codigo', null, [
                'attr' => ['class' => 'w-25'],
                'label' => 'Código',
            ])
            ->add('titulo', null, [
                'label' => 'Título',
            ])
            ->add('descripcion', TextEditorType::class, [
                'label' => 'Descripción',
                'required' => false,
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

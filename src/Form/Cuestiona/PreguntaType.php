<?php

namespace App\Form\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Entity\Cuestiona\Grupo;
use App\Entity\Cuestiona\Pregunta;
use App\Form\DataTransformer\JsonTransformer;
use App\Form\Type\TextEditorType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Cuestionario>
 */
class PreguntaType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Pregunta $pregunta */
        $pregunta = $options['data'];
        /** @var array<array-key, string[]> $tipos */
        $tipos = $options['tipos'];
        if (0 === $pregunta->getTipo()) {
            $pregunta->setTipo(Pregunta::TEXTO);
        }

        $builder
            ->add('grupo', EntityType::class, [
                'attr' => ['class' => 'w-auto'],
                'class' => Grupo::class,
                'choice_label' => 'titulo',
                'choices' => [$pregunta->getGrupo()],
                'placeholder' => false,
                'required' => false,
            ])
            ->add('codigo', null, [
                'attr' => ['class' => 'w-25'],
                'label' => 'Código',
            ])
            ->add('titulo', null, [
                'label' => 'Pregunta',
            ])
            ->add('descripcion', TextEditorType::class, [
                'help' => 'Información adicional para describir la pregunta.',
                'label' => 'Descripción',
                'required' => false,
            ])
            ->add('ayuda', TextType::class, [
                'help' => 'Texto de ayuda.',
                'required' => false,
            ])
            ->add('activa')
            ->add('opcional', null, [
                'help' => 'Marcar si la pregunta puede dejarse sin responder.',
                'label' => 'Respuesta opcional',
            ])
            ->add('tipo', TextType::class, [
                'attr' => [
                    'class' => 'w-auto',
                    'readonly' => true,
                ],
                'data' => $tipos[$pregunta->getTipo()]['leyenda'] ?? $pregunta->getTipo(),
                'help' => 'Ejemplo',
                'label' => 'Tipo de pregunta',
                'mapped' => false,
            ])
            ->add('id_tipo', HiddenType::class, [
                'data' => $pregunta->getTipo(),
                'mapped' => false,
            ])
            ->add('opciones', HiddenType::class)
        ;
        // Añadir subformulario para definir las opciones para el tipo de preguntas
        $clase = $tipos[$pregunta->getTipo()]['clase'] ?? '';
        if (class_exists($clase)) {
            $builder
                ->add('opciones_tipo', $clase, [
                    'help' => 'Opciones específicas para el tipo de pregunta.',
                    'label' => 'Opciones',
                    'mapped' => false,
                ])
                ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($pregunta) {
                    $form = $event->getForm();
                    $form->get('opciones_tipo')->setData($pregunta->getOpciones());
                })
                ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                    /** @var array<array-key, mixed> $opciones */
                    $opciones = $event->getForm()->get('opciones_tipo')->getData();
                    /** @var Pregunta $pregunta */
                    $pregunta = $event->getData();
                    $pregunta->setOpciones($opciones);
                })
            ;
            $builder->get('opciones')->addModelTransformer(new JsonTransformer());
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pregunta::class,
            'tipos' => [],
        ]);
        $resolver->setAllowedTypes('tipos', 'array');
    }
}

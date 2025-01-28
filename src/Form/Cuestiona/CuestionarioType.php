<?php

namespace App\Form\Cuestiona;

use App\Entity\Cuestiona\Cuestionario;
use App\Form\DataTransformer\JsonTransformer;
use App\Form\Type\TextEditorType;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para editar los datos básicos de un cuestionario de preguntas.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Cuestionario>
 */
class CuestionarioType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Cuestionario $cuestionario */
        $cuestionario = $options['data'];
        /** @var string $subform */
        $subform = $options['form_configuracion'];
        /** @var bool $soloConfig */
        $soloConfig = $options['solo_configuracion'];
        if ($soloConfig) {
            // Mostrar periodo de validez como referencia para fechas de resultados
            $builder->add('periodo', TextType::class, [
                'attr' => [
                    'class' => 'w-25',
                    'readonly' => true,
                ],
                'data' => sprintf(
                    '%s - %s',
                    $cuestionario->getFechaAlta()?->format('d/m/Y') ?? '',
                    $cuestionario->getFechaBaja()?->format('d/m/Y') ?? ''
                ),
                'mapped' => false,
                'label' => 'Periodo de validez',
            ]);
        } else {
            // Editar datos completos del cuestionario
            $builder
                ->add('estado', null, [
                    'attr' => ['class' => 'w-25'],
                    'choices' => [$cuestionario->getEstado()],
                    'disabled' => true,
                    'help' => 'Podrá definir preguntas mientras el cuestionario siga en estado "borrador".',
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
                ])
                ->add('bienvenida', TextEditorType::class, [
                    'help' => 'Dejar vacío para no mostrar ningún mensaje.',
                    'label' => 'Mensaje de bienvenida',
                ])
                ->add('despedida', TextEditorType::class, [
                    'help' => 'Dejar vacío para no mostrar ningún mensaje.',
                    'label' => 'Mensaje de salida',
                ])
                ->add('privado', CheckboxType::class, [
                    'help' => 'Marcar si el formulario solo puede ser rellenado por usuarios autenticados.',
                ])
                ->add('editable', CheckboxType::class, [
                    'help' => 'Marcar si el formulario puede ser editado por un usuario autenticado antes de su envío.',
                ])
                ->add('periodo', PeriodoValidezType::class, [
                    'con_fechas' => $options['con_fechas'],
                    'label' => 'Periodo de validez',
                    'mapped' => false,
                ])
            ;
        }
        if (class_exists($subform)) {
            $builder
                ->add('configuracion', HiddenType::class)
                ->add('subform_configuracion', $subform, [
                    'label' => 'Parámetros de configuración',
                    'mapped' => false,
                ])
                ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($cuestionario) {
                    $form = $event->getForm();
                    $form->get('subform_configuracion')->setData($this->prepararConfig($cuestionario->getConfiguracion()));
                })
                ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                    /** @var array<array-key, mixed> $config */
                    $config = $event->getForm()->get('subform_configuracion')->getData();
                    /** @var Cuestionario $cuestionario */
                    $cuestionario = $event->getData();
                    $cuestionario->setConfiguracion($config);
                })
            ;
            $builder->get('configuracion')->addModelTransformer(new JsonTransformer());
        }
        if (!$soloConfig) {
            $builder
                ->add('autor', null, [
                    'attr' => ['class' => 'w-25'],
                    'choices' => [$cuestionario->getAutor()],
                    'disabled' => true,
                ])
            ;
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cuestionario::class,
            'de_aplicacion' => false,
            'con_fechas' => false,
            'form_configuracion' => '',
            'solo_configuracion' => false,
        ]);
        $resolver->setAllowedTypes('de_aplicacion', 'bool');
        $resolver->setAllowedTypes('con_fechas', 'bool');
        $resolver->setAllowedTypes('form_configuracion', 'string');
        $resolver->setAllowedTypes('solo_configuracion', 'bool');
    }

    /**
     * Adapta parámetros de configuración, convirtiendo array de fechas en objetos para evitar errores.
     * @param array<array-key, mixed>|null $configuracion
     * @return array<array-key, mixed>
     */
    private function prepararConfig(?array $configuracion = null): array
    {
        $config = [];
        /** @var mixed $valor */
        foreach ($configuracion ?? [] as $clave => $valor) {
            if (is_array($valor) && array_key_exists('date', $valor)) {
                try {
                    $config[$clave] = new DateTimeImmutable((string) $valor['date'], new DateTimeZone($valor['timezone'] ?? 'UTC'));
                } catch (Exception) {
                    $config[$clave] = $valor;
                }
            } else {
                $config[$clave] = $valor;
            }
        }

        return $config;
    }
}

<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Evalua;
use App\Form\DataTransformer\EmpleadoDniTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para reasignar un evaluador a un empleado.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Evalua>
 */
class EvaluadorType extends AbstractType
{
    public function __construct(private readonly EmpleadoDniTransformer $empleadoDniTransformer) {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tipos = [
            'Responsable/Principal' => Evalua::EVALUA_RESPONSABLE,
            'Tercer agente' => Evalua::EVALUA_OTRO,
        ];
        /** @var Evalua $evalua */
        $evalua = $options['data'];
        $builder
            ->add('empleado', TextType::class, [
                'attr' => ['readonly' => true],
                'data' => $evalua->getEmpleado()?->getPersona(),
                'mapped' => false,
            ])
            ->add('tipoEvaluador', ChoiceType::class, [
                'choices' => null === $evalua->getEmpleado() ? $tipos : array_filter($tipos, fn ($t) => $evalua->getTipoEvaluador() === $t),
                'label' => 'Tipo de Evaluador',
                'mapped' => false,
            ])
            ->add('evaluador', TextType::class, [
                'attr' => ['readonly' => true],
                'help' => 'Elegir un evaluador de la lista.',
                'required' => true,
            ])
        ;
        // Convertir empleado en UVUS para poder elegir usando una "datatable"
        $builder->get('evaluador')
            ->addModelTransformer($this->empleadoDniTransformer)
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evalua::class,
        ]);
    }
}

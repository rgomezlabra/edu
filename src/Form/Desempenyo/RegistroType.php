<?php

namespace App\Form\Desempenyo;

use App\Entity\Desempenyo\Evalua;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulario para marcar solicitudes de rechazo enviadas a Registro General.
 * @author Ramón M. Gómez <ramongomez@us.es>
 * @extends AbstractType<Evalua>
 */
class RegistroType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Evalua $evalua */
        $evalua = $options['data'];
        $builder
            ->add('Solicitante', TextType::class, [
                'attr' => ['readonly' => true],
                'data' => $evalua->getEmpleado()?->getPersona(),
                'mapped' => false,
            ])
            ->add('rechazo_texto', null, [
                'label' => 'Observaciones',
            ])
            ->add('registrado', null, [
                'attr' => ['class' => 'w-auto'],
                'help' => 'Opcional.',
                'help_attr' => ['class' => 'text-secondary'],
                'label' => 'Fecha de registro',
                'required' => false,
                'widget' => 'single_text',
            ])
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

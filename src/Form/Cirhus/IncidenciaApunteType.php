<?php

namespace App\Form\Cirhus;

use App\Entity\Cirhus\IncidenciaApunte;
use App\Entity\Estado;
use App\Repository\EstadoRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncidenciaApunteType extends AbstractType
{

    public function __construct(private readonly EstadoRepository $estadoRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Estado[] $estados */
        $estados = $this->estadoRepository->createQueryBuilder('estado')
            ->andWhere('estado.nombre <> :iniciado')
            ->andWhere('estado.tipo = :incidencia')
            ->setParameter('iniciado', Estado::INICIADO)
            ->setParameter('incidencia', Estado::INCIDENCIA)
            ->getQuery()
            ->getResult()
        ;
        $ayudaEstados = '';
        foreach ($estados as $estado) {
            $ayudaEstados .= sprintf('<span class="fas %s"></span> %s - %s<br>', $estado->getIcono() ?? '', $estado, strtolower($estado->getDescripcion() ?? ''));
        }
        $ayudaEstados = <<< EOT
<em id="ayudaEstados" data-bs-toggle="popover" data-bs-html="true" data-bs-title="Estados" data-bs-content='$ayudaEstados'>
    <em class="text-info fas fa-info-circle"></em> Pulsar para ver información de los estados.
</em>
EOT;
        if ($options['reabrir'] || '' === $options['rol']) {
            $builder->add('comentario');
        } else {
            $builder
                ->add('estado', EntityType::class, [
                    'choices' => $estados,
                    'class' => Estado::class,
                    'help' => $ayudaEstados,
                    'help_html' => true,
                    'placeholder' => false,
                ])
                ->add('comentario')
                ->add('servicio', null, [
                    'attr' => ['class' => 'w-25'],
                    'help' => 'Dato interno que no se muestra al usuario creador de la incidencia.',
                    'label' => 'Servicio asignado',
                    'required' => false,
                ])
                ->add('observaciones', null, [
                    'help' => 'Dato interno que no se muestra al usuario creador de la incidencia.',
                    'label' => 'Observaciones internas',
                    'required' => false,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncidenciaApunte::class,
            'reabrir' => false,
            'rol' => '',
        ]);
        $resolver->setAllowedTypes('reabrir', 'bool');
        $resolver->setAllowedTypes('rol', 'string');
    }
}

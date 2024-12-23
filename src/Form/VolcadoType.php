<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Formulario para cargar un fichero CSV con volcado de datos.
 * @author Ramón M. Gómez <ramongomez@us.es>
 */
class VolcadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var string $tipo */
        $tipo = $options['data']['fileType'] ?? $options['fileType'];
        $builder
            ->add('fichero_csv', FileType::class, [
                'constraints' => [
                    new File([
                        'maxSize' => $options['data']['maxSize'] ?? $options['maxSize'],
                    ])
                ],
                'help' => sprintf('Indicar el fichero %s con el volcado de los datos.', $tipo),
                'label' => 'Fichero de datos',
                'mapped' => true,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxSize' => '32k',
            'fileType' => 'CSV',
        ]);
        $resolver->setAllowedTypes('maxSize', 'string');
        $resolver->setAllowedTypes('fileType', 'string');
    }
}

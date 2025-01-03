<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextEditorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'attr' => [
//                'class' => 'ckeditor',
                'data-controller' => 'ckeditor',
                'novalidate' => 'novalidate',
            ],
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}

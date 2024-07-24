<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NewArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('chapeau', TextType::class, [
                'label' => 'Chapeau',
            ])
            ->add('texte', TextareaType::class, [
                'label' => 'Texte',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPEG, PNG)',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('publie', CheckboxType::class, [
                'label' => 'Le publier',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}

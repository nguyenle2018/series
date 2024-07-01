<?php

namespace App\Form;

use App\Entity\Campus;
use App\Form\models\SearchEvent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEventType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void
    {
        $builder
            ->add('campus', EntityType::class, [
               'class' => Campus::class,
                'choice_label' => 'nom'
            ])
            ->add('search')
            ->add('startDate', DateType::class , [
                'label' => 'Start Date :',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'End Date :',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('sortieOrganisateur', CheckboxType::class, [
                'label' => 'Sortie Orgnisateur ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('sortiesInscrits', CheckboxType::class, [
                'label' => 'Sortie Inscrits ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('sortiesNonInscrits', CheckboxType::class, [
                'label' => 'Sortie Non Inscrits ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('sortiesPassees', CheckboxType::class, [
                'label' => 'Sortie Passees ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Rechercher',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchEvent::class,
            'required' => false
        ]);
    }
}

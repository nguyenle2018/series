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
            ->add('startDate', DateType::class)
            ->add('endDate', DateType::class)
            ->add('sortieOrganisateur', CheckboxType::class)
            ->add('sortiesInscrits', CheckboxType::class)
            ->add('sortiesNonInscrits', CheckboxType::class)
            ->add('sortiesPassees', CheckboxType::class)
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

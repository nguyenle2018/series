<?php

namespace App\Form;

use App\Repository\CampusRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieAnnulationType extends AbstractType
{

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Motif :',
                'attr' => [
                    'class' => 'form-control',
                    'max' => 189
                ],
            ])
            ->add('Annuler', SubmitType::class, [
                'label' => 'Annuler la sortie',
                'attr' => ['class' => 'btn btn-success mt-2 mx-2']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}

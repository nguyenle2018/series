<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use phpDocumentor\Reflection\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', TextType:: class, [
                'label'=> 'Pseudo :'
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom :'
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom :'
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone'
            ])
            ->add('mail', EmailType::class, [
                'label' => 'Email :'
            ])
            ->add('motPasse', PasswordType::class, [
                'label' => 'mot de Passe'
            ])

            ->add('sortieRejointes', EntityType::class, [
                'class' => Sortie::class,
                'query_builder' => function (SortieRepository $sortieRepository){
                        return $sortieRepository
                                ->createQueryBuilder('s')
                                ->addOrderBy('c.name');
                }

            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\ListItem;
use App\Entity\TodoList;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('TodoList', EntityType::class, [
                'class' => TodoList::class,
                'choice_label' => 'id',
                'required' => false,
                'placeholder' => '— Sélectionner une liste —',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListItem::class,
        ]);
    }
}

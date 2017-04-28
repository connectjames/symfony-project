<?php

namespace AppBundle\Form;

use AppBundle\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutDeliveryForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('email', HiddenType::class, [
                'required' => true,
                'label' => false
            ])
            ->add('firstName', TextType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false
            ])
            ->add('lastName', TextType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false
            ])
            ->add('company', TextType::class, [
                'required'  => false,
                'label' => false,
                'mapped' => false
            ])
            ->add('address1', TextType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false,
                'attr' => array(
                    'placeholder' => 'First line of address*'
                )
            ])
            ->add('address2', TextType::class, [
                'required'  => false,
                'label' => false,
                'mapped' => false,
                'attr' => array(
                    'placeholder' => 'Second line of address'
                )
            ])
            ->add('city', TextType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false
            ])
            ->add('postcode', TextType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false,
                'attr' => array(
                    'class' => 'uppercase',
                    'minlength' => 6,
                    'maxlength' => 8
                )
            ])
            ->add('phone', IntegerType::class, [
                'required'  => true,
                'label' => false,
                'mapped' => false
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'validation_groups' => ['Default', 'Registration'],
        ]);
    }
}

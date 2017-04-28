<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('stripeToken', HiddenType::class, [
                'required'  => true
            ])
            ->add('firstName', TextType::class, [
                'required'  => true
            ])
            ->add('lastName', TextType::class, [
                'required'  => true
            ])
            ->add('email', EmailType::class, [
                'required'  => true
            ])
            ->add('company', TextType::class, [
                'required'  => false
            ])
            ->add('address1', TextType::class, [
                'required'  => true
            ])
            ->add('address2', TextType::class, [
                'required'  => false
            ])
            ->add('city', TextType::class, [
                'required'  => true
            ])
            ->add('postcode', TextType::class, [
                'required'  => true
            ])
            ->add('phone', IntegerType::class, [
                'required'  => true
            ])
            ->add('comment', TextareaType::class, [
                'required'  => false
            ])
            ->add('county', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryFirstName', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryLastName', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryCompany', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryAddress1', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryAddress2', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryCity', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryPostcode', TextType::class, [
                'required'  => false
            ])
            ->add('deliveryPhone', IntegerType::class, [
                'required'  => false
            ])
            ->add('deliveryCounty', TextType::class, [
                'required'  => false
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required'  => false,
                'invalid_message' => 'The password fields must match.'
            ])
        ;
    }
}

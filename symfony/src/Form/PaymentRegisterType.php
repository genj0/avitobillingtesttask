<?php

namespace App\Form;

use App\Entity\Payment;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

final class PaymentRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'purpose',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 5, 'max' => 200]),
                    ],
                ]
            )
            ->add(
                'amount',
                NumberType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'input' => 'string',
                    'scale' => 2,
                    'html5' => true,
                ]
            )
            ->add(
                'notification',
                TextType::class,
                [
                    'constraints' => [
                        new Url(),
                        new Length(['max' => 255]),
                    ],
                ]
            )
            ->add(
                'orderId',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 1, 'max' => 255]),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Payment::class,
                'csrf_protection' => false,
            ]
        );
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Luhn;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CardPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'number',
                TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Luhn(),
                        new Length(['min' => 16, 'max' => 16]),
                    ],
                ]
            )
            ->add(
                'cardholderName',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 5, 'max' => 200]),
                    ],
                ]
            )
            ->add(
                'expiryDate',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 6, 'max' => 9]),
                    ],
                ]
            )
            ->add(
                'securityNumber',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 3, 'max' => 3]),
                    ],
                ]
            );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_token_id' => 'division_item'
            ]
        );
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PaymentsFromPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fields', TextType::class, ['empty_data' => ''])
            ->add(
                'startsOn',
                DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'endsOn',
                DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add('page', IntegerType::class, ['required' => false, 'empty_data' => '1'])
            ->add(
                'resOnPage',
                IntegerType::class,
                [
                    'required' => false,
                    'empty_data' => strval($options['defaultResOnPage']),
                    'constraints' => [
                        new Length(['min' => 1, 'max' => 4]),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
                'defaultResOnPage' => '100',
            ]
        );
        $resolver->setAllowedTypes('defaultResOnPage', 'int');
    }
}

<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentsFromPeriodType;
use App\Form\PaymentRegisterType;
use App\Service\EntityServices\PaymentServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiController extends AbstractController
{
    const DEFAULT_RES_ON_PAGE = 100;

    /**
     * @Route("/api/payment/{orderId}", name="payment_get", methods={"GET"})
     * @param Request $request
     * @param PaymentServiceInterface $paymentService
     * @param $orderId
     * @return JsonResponse
     */
    public function getPayment(Request $request, PaymentServiceInterface $paymentService, $orderId)
    {
        if (is_null($orderId) or empty($orderId)) {
            return $this->json(['status' => 'Invalid Order ID', 'orderId' => $orderId], 400);
        } else {
            $optFields = $request->query->get('fields', '');
            $paymentData = $paymentService->getPaymentData($orderId, $optFields);
            if (!is_null($paymentData)) {
                return $this->json(['status' => 'Success', 'payment' => $paymentData]);
            } else {
                return $this->json(['status' => 'Payment not found', 'orderId' => $orderId,], 404);
            }
        }
    }

    /**
     * @Route("/api/payment/register", name="payment_register", methods={"POST"})
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param PaymentServiceInterface $paymentService
     * @return JsonResponse
     */
    public function register(
        Request $request,
        TranslatorInterface $translator,
        PaymentServiceInterface $paymentService
    ): JsonResponse {
        $payment = new Payment();
        $form = $this->createForm(PaymentRegisterType::class, $payment);
        $form->submit($request->request->all());
        if ($form->isSubmitted() and $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->persist($payment);
                $em->flush(); //should not be in service
            } catch (UniqueConstraintViolationException $e) {
                $form->get('orderId')->addError(
                    new FormError($translator->trans('This value is already used.', [], 'validators'))
                );

                return $this->getValidationErrorResp($form);
            }

            $sessionId = $paymentService->createPaymentSession($payment);

            return $this->json(
                [
                    'status' => 'Success',
                    'sessionId' => $sessionId,
                    'cardPaymentUrl' => $this->generateUrl('pay_card_form', ['sessionId' => $sessionId], 0),
                ]
            );
        } else {
            return $this->getValidationErrorResp($form);
        }
    }

    /**
     * @Route("/api/payments/period", name="payments_get_period", methods={"GET"})
     *
     * @param Request $request
     * @param PaymentServiceInterface $paymentService
     * @return JsonResponse
     */
    public function getPaymentsFromPeriod(
        Request $request,
        PaymentServiceInterface $paymentService
    ): JsonResponse {
        $form = $this->createForm(
            PaymentsFromPeriodType::class,
            null,
            ['defaultResOnPage' => self::DEFAULT_RES_ON_PAGE]
        );
        $form->submit($request->query->all());

        if ($form->isSubmitted() and $form->isValid()) {
            $formData = $form->getData();

            $paymentsCount = $paymentService->getPaymentsCountFromPeriod($formData['startsOn'], $formData['endsOn']);

            if ($paymentsCount > 0) {
                if (($formData['page'] - 1) * $formData['resOnPage'] >= $paymentsCount) {
                    $formData['page'] = 1;
                }

                $paymentsData = $paymentService->getPaymentsDataFromPeriod(
                    $formData['startsOn'],
                    $formData['endsOn'],
                    $formData['fields'],
                    $formData['page'],
                    $formData['resOnPage']
                );

                return $this->json(
                    [
                        'status' => 'Success',
                        'page' => $formData['page'],
                        'nextPageExists' => ($formData['page'] * $formData['resOnPage'] < $paymentsCount),
                        'payments' => $paymentsData,
                    ]
                );
            } else {
                return $this->json(['status' => 'No payments found for this period.'], 404);
            }
        } else {
            return $this->getValidationErrorResp($form);
        }
    }

    /**
     * @Route("/api/session/create/{orderId}", name="payment_session_create")
     *
     * @param PaymentServiceInterface $paymentService
     * @param $orderId
     * @return JsonResponse
     */
    public function createPaymentSession(
        PaymentServiceInterface $paymentService,
        $orderId
    ): JsonResponse {
        if (is_null($orderId) or empty($orderId)) {
            return $this->json(['status' => 'Invalid Order ID', 'orderId' => $orderId], 400);
        } else {
            $payment = $paymentService->getPaymentByOrderId($orderId);

            if (is_null($payment) or ($payment->getStatus() != null)) {
                return $this->json(
                    [
                        'status' => 'Incomplete payment with this orderId was not found.',
                        'orderId' => $orderId,
                    ],
                    404
                );
            } else {
                $sessionId = $paymentService->createPaymentSession($payment);

                return $this->json(
                    [
                        'status' => 'Success',
                        'sessionId' => $sessionId,
                        'cardPaymentUrl' => $this->generateUrl('pay_card_form', ['sessionId' => $sessionId], 0),
                    ]
                );
            }
        }
    }


    private function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }

    private function getValidationErrorResp(FormInterface $form): JsonResponse
    {
        return $this->json(
            [
                'status' => 'Validation error occurred',
                'errors' => $this->getErrorsFromForm($form),
            ],
            400
        );
    }
}

<?php

namespace App\Controller;

use App\Form\CardPaymentType;
use App\Service\EntityServices\PaymentServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PaymentController extends AbstractController
{
    /**
     * @Route("payment/card/form", name="pay_card_form", methods={"GET", "POST"})
     * @param Request $request
     * @param PaymentServiceInterface $paymentService
     * @return Response
     */
    public function payCardForm(
        Request $request,
        PaymentServiceInterface $paymentService
    ): Response {
        $sessionId = $request->get('sessionId', null);
        if (is_null($sessionId) or !uuid_is_valid($sessionId)) {
            return $this->render(
                'payment/error.twig',
                [
                    'controller_name' => 'PaymentController',
                    'message' => 'PaymentSession.Invalid',
                ],
                new Response(null, 400)
            );
        } else {
            $payment = $paymentService->getPaymentBySessionId($sessionId);

            //Check if the session exists and the payment has not yet been completed/canceled
            if (!is_null($payment) and ($payment->getStatus() === null)) {
                $form = $this->createForm(CardPaymentType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() and $form->isValid()) {
                    //
                    // successful payment imitated
                    //
                    $payment->setStatus('Success');
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($payment);
                    $em->flush();
                    $paymentService->sendNotification($payment);

                    return $this->render('payment/success.twig', ['message' => 'PaymentSession.Success']);
                } else {
                    return $this->render(
                        'payment/card.twig',
                        [
                            'cardForm' => $form->createView(),
                            'amount' => $payment->getAmount(),
                            'purpose' => $payment->getPurpose(),
                        ]
                    );
                }
            } else {
                return $this->render(
                    'payment/error.twig',
                    ['message' => 'PaymentSession.Expired'],
                    new Response(null, 404)
                );
            }
        }
    }
}

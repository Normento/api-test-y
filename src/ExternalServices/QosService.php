<?php

namespace Core\ExternalServices;

use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\User\Models\User;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Http;

class QosService
{
    private $qosMtnPaymentRequestEndpoint;
    private $qosMoovPaymentRequestEndpoint;
    private $qosMtnSendMoneyEndpoint;
    private $qosMoovSendMoneyEndpoint;
    private $qosGetTransactionOrPaymentStatusEndpoint;
    private $user;
    private $password;
    private $mtnClientId;
    private $moovClientId;
    private $qosCardPaymentRequestEndpoint;
    private $quosCardKey;
    private $qosGetCardTransactionOrPaymentStatusEndpoint;

    public function __construct()
    {

        $this->qosMtnPaymentRequestEndpoint = Constants::qosMtnPaymentRequestEndpoint;
        $this->qosMtnSendMoneyEndpoint = Constants::qosMtnSendMoneyEndpoint;

        $this->qosMoovPaymentRequestEndpoint = Constants::qosMoovPaymentRequestEndpoint;
        $this->qosMoovSendMoneyEndpoint = Constants::qosMoovSendMoneyEndpoint;

        $this->qosGetTransactionOrPaymentStatusEndpoint = Constants::qosGetTransactionOrPaymentStatusEndpoint;
        $this->user = Constants::user;
        $this->password = Constants::password;
        $this->mtnClientId = Constants::mtnClientId;
        $this->moovClientId = Constants::moovClientId;
        $this->quosCardKey = Constants::quosCardKey;
        $this->qosCardPaymentRequestEndpoint = Constants::qosCardPaymentRequestEndpoint;

        $this->qosGetCardTransactionOrPaymentStatusEndpoint = Constants::qosGetCardTransactionOrPaymentStatusEndpoint;
    }

    public function requestPayment($amount, $phoneNumber, $mobileNetwork, $transref)
    {
        $payload = array(
            'msisdn' => $phoneNumber,
            'amount' => $amount,
            'transref' => $transref,
            'clientid' => $mobileNetwork == 'MTN' ? $this->mtnClientId : $this->moovClientId,
            'narration' => 'YLOMI'
        );
        $response = Http::withoutVerifying()
            ->accept('application/json')
            ->withBasicAuth($this->user, $this->password)
            ->post($mobileNetwork == 'MTN' ? $this->qosMtnPaymentRequestEndpoint : $this->qosMoovPaymentRequestEndpoint, $payload);

        return $response->json();
    }

    public function cardRequestPayment($amount, $transref, User $user)
    {

        $amountPayload = array(
            'totalAmount' => $amount,
            "currency" => "XOF"
        );
        $saleDetails = [
            "firstName" => $user->first_name, // m
            "lastName" => $user->last_name, // m
            "middleName" => "",
            "nameSuffix" => "",
            "title" => "Mr",  // m
            "address1" => "Cotonou", // m
            "address2" => "",
            "address4" => "string",
            "locality" => "Godomey",   // m
            "administrativeArea" => "",
            "postalCode" => "229",  // m
            "country" => "Benin",  // m
            "district" => "Bj",   // m
            "buildingNumber" => "string",
            "email" => $user->email,  // m
            "emailDomain" => "",
            "phoneNumber" => substr($user->phone_number, 3),   // m
            "phoneType" => "cel"  // m
        ];
        $payload = array(
            'type' => "card",
            'transref' => $transref,
            'qosKey' => $this->quosCardKey,
            'amountDetails' => $amountPayload,
            'returnUrl' => "http://localhost:3000/cardPayment",
            "saleDetails" => $saleDetails
        );
        $response = Http::withoutVerifying()
            ->accept('application/json')->withBasicAuth($this->user, $this->password)
            ->post($this->qosCardPaymentRequestEndpoint, $payload);
        return $response->json();
    }

    public function sendMoney($amount, $phoneNumber, $mobileNetwork, $transref)
    {
        $payload = array(
            'msisdn' => $phoneNumber,
            'amount' => $amount,
            'transref' => $transref,
            'clientid' => $mobileNetwork == 'MTN' ? $this->mtnClientId : $this->moovClientId,
        );
        $response = Http::withoutVerifying()
            ->withOptions(['proxy' => 'https://fuajj0mye8a5i0:e9av0wnfb3bqmcvn61a2e3fno937tr@us-east-shield-03.quotaguard.com:9294'])
            ->accept('application/json')
            ->withBasicAuth($this->user, $this->password)
            ->post($mobileNetwork == 'MTN' ? $this->qosMtnSendMoneyEndpoint : $this->qosMoovSendMoneyEndpoint, $payload);

        return $response->json();
    }

    public function getTransactionStatus($transref, $mobileNetwork)
    {
        $payload = array(
            'transref' => $transref,
            'clientid' => $mobileNetwork == 'MTN' ? $this->mtnClientId : $this->moovClientId,
        );

        $response = Http::withoutVerifying()
            ->accept('application/json')
            ->withBasicAuth($this->user, $this->password)
            ->post($this->qosGetTransactionOrPaymentStatusEndpoint, $payload);

        return $response->json();
    }

    public function getAccountInfo($phoneNumber, $mobileNetwork)
    {
        $payload = array(
            'msisdn' => $phoneNumber,
            'clientid' => $mobileNetwork == 'MTN' ? $this->mtnClientId : $this->moovClientId,
        );

        $response = Http::withoutVerifying()
            //->withOptions(['proxy' => 'https://fuajj0mye8a5i0:e9av0wnfb3bqmcvn61a2e3fno937tr@us-east-shield-03.quotaguard.com:9294'])
            ->accept('application/json')
            ->withBasicAuth($this->user, $this->password)
            ->post($mobileNetwork == 'MTN' ? 'https://qosic.net:8443/QosicBridge/user/getaccountholderinfo' : ' https://qosic.net:8443/QosicBridge/user/getaccountholderinfomv', $payload);

        return $response->json();
    }

    public function getCardTransactionStatus($transref)
    {
        $payload = array(
            'transref' => $transref,
            'qosKey' => $this->quosCardKey,
        );

        $response = Http::withoutVerifying()
            ->accept('application/json')
            ->withBasicAuth($this->user, $this->password)
            ->post($this->qosGetCardTransactionOrPaymentStatusEndpoint, $payload);

        return $response->json();
    }

    public function makeTransaction($paymentMethod, $amount, $phoneNumber, $transactionData, $user, $trace)
    {
        switch ($paymentMethod) {
            case 1:
                $paymentResponse = $this->requestPayment($amount, $phoneNumber, "MTN", $transactionData->id);
                if ($paymentResponse['responsecode'] == '01') {
                    return true;
                } else {
                    $transaction = Transaction::make([
                        'status' => 'FAILED',
                        'type' => "$trace",
                        'payment_method' => 'MTN',
                        'author' => $user->full_name,
                        'amount' => $amount,
                        "phoneNumber" => $phoneNumber
                    ]);
                    $transaction->transactionData()->associate($transactionData);
                    $transaction->save();
                    return $paymentResponse['responsecode'] == '529' ?
                        "Fonds issuffudant sur votre compte MTN Mobile Money. Veuillez recharger et reprendre" :
                        "Paiement non effectué , réessayez";
                }

            case 2:
                $paymentResponse = $this->requestPayment($amount, $phoneNumber, "MOOV", $transactionData->id);
                //dd($paymentResponse);
                if ($paymentResponse['responsecode'] == 0) {
                    $transaction = Transaction::make([
                        'status' => 'SUCCESSFUL',
                        'type' => "$trace",
                        'payment_method' => 'MOOV',
                        'author' => $user->full_name,
                        'amount' => $amount,
                        "phoneNumber" => $phoneNumber
                    ]);
                    $transaction->transactionData()->associate($transactionData);
                    $transaction->save();

                    return true;
                } else {
                    $transaction = Transaction::make([
                        'status' => 'FAILED',
                        'type' => "$trace",
                        'payment_method' => 'MOOV',
                        'author' => $user->full_name,
                        'amount' => $amount,
                        "phoneNumber" => $phoneNumber
                    ]);
                    $transaction->transactionData()->associate($transactionData);
                    $transaction->save();
                    return "Transaction échouée.";
                }
            case 3:
                /*   if (!is_null($package->user->wallet)) {
                      $wallet = $package->user->wallet;
                      if ($wallet->balance >= $placement_fee) {
                          $oldBalance = $wallet->balance;
                          $this->walletRepository->update(['balance' => $oldBalance - $placement_fee], $wallet);

                          $data = [
                              "balance_before_operation" => $oldBalance,
                              "balance_after_operation" => $wallet->balance,
                              "amount" => $placement_fee,
                              "operation_type" => true,
                              "trace" => "Paiement des frais de placement des commandes du client {$package->user->first_name} {$package->user->last_name}"
                          ];
                          $this->walletLogRepository->createWalletLog($data, $wallet);
                          $request->merge(['transref' => $transref]);


                          $transactionData = [
                              'transref' => $transref,
                              'status' => "SUCCESSFUL",
                              'author' => "{$package->user->first_name}" . "    {$package->user->last_name}",
                              'type' => "Frais de placement",
                              'payment_method' => 'WALLET',
                              'amount' => $placement_fee,
                              "phoneNumber" => $package->user->phone_number
                          ];
                          $this->transactionsRepository->store($transactionData);
                          $this->afterPlacementFeePayment($request, $package, false);

                          $factureTab['transref'] = $transref;
                          $factureTab['mobile_money'] = "CARTE BANCAIRE";
                          Mail::to($package->user->email)->send(new FactureMail($package->user, $factureTab));
                          $response['message'] = "Frais de placement payé avec succès";
                          $response['data'] = $wallet;
                          return response($response, 201);
                      }
                      return response(['message' => "Vous ne disposez pas du fond suffisant dans votre  portefeuille YLOMI pour effectuer le paiement des frais de placement de {$placement_fee}"], 400);
                  } else {
                      return response(['message' => "Vous ne disposez pas d'un portefeuille YLOMI pour effectuer des transactions"], 400);
                  } */
                break;
            case 4:
                /*  $paymentResponse = $this->cardRequestPayment($amount, $transref,);
                 if ($paymentResponse['responseCode'] == "00") {
                     $response['message'] = "Paiement en cour";
                     $paymentResponse['transref'] = $transref;
                     $response['data'] = $paymentResponse;
                     AfterPlacementFeeJob::dispatch($package, $transref,  $this->paymentsRepository, $this->transactionsRepository, $request->month_salary, $request->year, $this->qosService, $this->packageRepository, $request->payment_method)->delay(Carbon::now()->addMinutes(10));
                     return response($response, 201);
                 } else {
                     $transactionData = [
                         'transref' => $transref,
                         'status' => "FAILED",
                         'author' => "{$package->user->first_name}" . "         {$package->user->last_name}",
                         'type' => "Frais de placement",
                         'payment_method' => 'CARD',
                         'amount' => round($placement_fee),
                         "phoneNumber" => Auth::user()->phone_number

                     ];
                     $this->transactionsRepository->store($transactionData);
                     $response['message'] = "Paiement non effectué , réessayez";
                     $response['data'] = $paymentResponse;
                     return response($response, 500);
                 } */
                break;
            default:
                # code...
                break;
        }
    }

    public function verifyTransaction($paymentMethod, $transRef)
    {
        switch ($paymentMethod) {
            case 1:
            case 2:
                $transactionStatus = $this->getTransactionStatus($transRef, $paymentMethod == 1 ? "MTN" : "MOOV");
                if ($transactionStatus['responsemsg'] === 'SUCCESSFUL' || $transactionStatus['responsecode'] === '00') {
                    return true;
                } else if ($transactionStatus['responsemsg'] === 'FAILED' || $transactionStatus['responsecode'] === -1) {
                    return false;
                } else {
                    return $transactionStatus['responsemsg'];
                }
            case 4:
                $transactionStatus = $this->getCardTransactionStatus($transRef);
                if ($transactionStatus['data']['status'] === 'SUCCESS') {
                    return true;
                } else {
                    return false;
                }
        }
    }
}

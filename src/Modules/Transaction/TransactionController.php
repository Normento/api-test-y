<?php

namespace Core\Modules\Transaction;


use Core\ExternalServices\QosService;
use Core\Utils\Controller;
use Core\Utils\Constants;
use Illuminate\Http\Request;
use SmashedEgg\LaravelRouteAnnotation\Route;



#[Route('/transactions', middleware: ['auth:sanctum'])]
class TransactionController extends Controller
{
    public TransactionRepository $transactionRepository;
    public QosService $qosService;

    public function __construct(TransactionRepository $transactionsRepository, QosService $qosService)
    {
        $this->transactionRepository = $transactionsRepository;
        $this->qosService = $qosService;
    }



    public function verifyQosTransaction($transref, Request $request)
    {
        $queys = $request->query();
        if (!array_key_exists('type', $queys)) {
            return response(['message' => "Sélectionnez le type de transaction que vous voulez vérifier, soit 1 pour les mobiles Network et 2 pour les cartes bancaires"], 400);
        } elseif ($request->query('type') == 1) {
            if (!array_key_exists('mobileNetwork', $queys)) {
                return response(['message' => "Sélectionnez le type de réseau mobile soit MTN ou MOOV"], 400);
            } elseif (!in_array($request->query('mobileNetwork'), Constants::mobileNetworkEnum)) {
                return response(['message' => "Sélectionnez le type de réseau mobile seulement entre MTN et MOOV"], 400);
            }
            $transactionOrPaymentStatus = $this->qosService->getTransactionStatus($transref, strtoupper($request->query('mobileNetwork')));
        } elseif ($request->query('type') == 2) {
            $transactionOrPaymentStatus = $this->qosService->getCardTransactionStatus($transref);
        }

        return response($transactionOrPaymentStatus, 200);
    }


    #[Route('/', methods:['GET'])]
    public function index(Request $request)
{
    $filters = $request->only(['phone_number', 'type', 'payment_method', 'start_date', 'end_date','status']);

    if (!empty($filters)) {

        $transactions = $this->transactionRepository->filterTransactions($filters);
    } else {
        $transactions = $this->transactionRepository->getTransactions();
    }

    return response($transactions);
}

}

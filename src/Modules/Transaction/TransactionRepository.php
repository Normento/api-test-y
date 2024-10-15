<?php

namespace Core\Modules\Transaction;

use Illuminate\Http\Request;
use Core\Utils\BaseRepository;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\Transaction\Models\TransactionData;

class TransactionRepository extends BaseRepository
{
    protected Transaction $transactionsModel;
    protected TransactionData $transactionDataModel;

    public function __construct(Transaction $transactionsModel, TransactionData $transactionDataModel)
    {
        $this->transactionsModel = $transactionsModel;
        $this->transactionDataModel = $transactionDataModel;
        parent::__construct($transactionsModel);
    }


    public function store($data)
    {
        $transaction = $this->transactionsModel->make($data);
        $transaction->transactionData()->associate($data['transref']);
        $transaction->save();
        return $transaction;
    }

    public function getTransactions()
{
    return $this->transactionsModel->paginate(10);
}



public function filterTransactions(array $filters)
{
    $query = $this->transactionsModel->newQuery();

    // Filtrer par numéro de téléphone
    if (isset($filters['phone_number'])) {
        $query->where('phoneNumber', $filters['phone_number']);
    }

    // Filtrer par type
    if (isset($filters['type'])) {
        $query->where('type', 'like', '%' .  $filters['type']);
    }

    // Filtrer par méthode de paiement
    if (isset($filters['payment_method'])) {
        $query->where('payment_method', $filters['payment_method']);
    }

    // Filtrer par date
    if (isset($filters['start_date']) && isset($filters['end_date'])) {
        $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
    }
    
    // Filtrer par statut
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // Retourner les résultats paginés avec 10 résultats par page
    return $query->paginate(10);
}



    public function storeTransactionData($data)
    {
        return  $this->transactionDataModel->create($data);
    }
    public function findTransactionDatasBy($transactionDataId)
    {
        return $this->transactionDataModel->find($transactionDataId);
    }
    public function updateTransactionData($data, $transactionData)
    {
        return $transactionData->update($data);
    }
}

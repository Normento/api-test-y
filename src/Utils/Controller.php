<?php

namespace Core\Utils;

use App\Helpers\Helper;
use PHPUnit\TextUI\Help;
use Illuminate\Support\Str;
use Core\Utils\Jobs\GeneratePDF;
use NumberToWords\NumberToWords;
use Illuminate\Http\UploadedFile;
use Core\Utils\Jobs\UploadFileToS3;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Core\Modules\RecurringOrder\Models\Proposition;
use Illuminate\Routing\Controller as BaseController;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function s3FileUrl($key): string
    {
        return !is_null($key) ?

            Storage::temporaryUrl($key, now()->addDays(7)) : "";
    }

    protected function uploadFile(UploadedFile $file): string
    {
        $name = $file->hashName();
        $storedPath = $file->storeAs('temp', $name, 'local');
        $s3FilePath = 'uploadedFile/' . $name;
        UploadFileToS3::dispatch(storage_path('app/' . $storedPath), $s3FilePath);
        return $s3FilePath;
    }

    public function getCustomerBudget(int $netSalary, bool $cnss): array
    {
        if ($cnss) {
            $finalNetSalary = 0;
            $itsAmount = 0;
            $cnssEmployee = 0;
            if ($netSalary <= 60000) {
                $brutSalary = $netSalary + 3000;
            } else if ($netSalary <= 150000) {
                $brutSalary = $netSalary + 20000;
            } else if ($netSalary <= 250000) {
                $brutSalary = $netSalary + 45000;
            } else {
                $brutSalary = $netSalary + 140000;
            }

            while ($finalNetSalary != $netSalary) {
                $cnssEmployee = round((($brutSalary * 3.6) / 100));
                if ($brutSalary <= 60000) {
                    $itsAmount = 0;
                }
                if (60000 < $brutSalary && $brutSalary <= 150000) {
                    $itsAmount = round((($brutSalary - 60000) * 10) / 100);
                } else if (150000 < $brutSalary && $brutSalary <= 250000) {
                    $itsAmount = round(((150000 - 60000) * 10) / 100) +
                        round((($brutSalary - 150000) * 15) / 100);
                } else if (250000 < $brutSalary && $brutSalary <= 500000) {
                    $itsAmount = round(((150000 - 60000) * 10) / 100) +
                        round(((250000 - 150000) * 15) / 100) +
                        round((($brutSalary - 250000) * 19) / 100);
                } else if ($brutSalary > 500000) {
                    $itsAmount = round(((150000 - 60000) * 10) / 100) +
                        round(((250000 - 150000) * 15) / 100) +
                        round(((500000 - 250000) * 19) / 100) +
                        round((($brutSalary - 500000) * 30) / 100);
                }

                $finalNetSalary = $brutSalary - ($itsAmount + $cnssEmployee);
                if ($finalNetSalary != $netSalary) {
                    $brutSalary -= ($finalNetSalary - $netSalary);
                }
            }

            return [
                'brutSalary' => $brutSalary,
                'itsAmount' => $itsAmount,
                'cnssEmployee' => $cnssEmployee,
                'vpsAmount' => round(($brutSalary * 0) / 100),
                'cnssCustomer' => round(($brutSalary * 19.4) / 100),
                'ylomiAmount' => round(($netSalary * 20) / 100),
                'total' => round(($netSalary * 20) / 100) +
                    $itsAmount +
                    round(($brutSalary * 0) / 100) +
                    $cnssEmployee +
                    round(($brutSalary * 19.4) / 100) +
                    $netSalary,
            ];
        } else {
            $assurance = round(($netSalary * 3) / 100);
            return [
                'ylomiAmount' => round(($netSalary * 20) / 100) + $assurance,
                'total' => round(($netSalary * 20) / 100) + $assurance + $netSalary,
            ];

        }

    }


    public function generateCustomerContract(int $total_budget, RecurringOrder $recurringOrder, $proposition, string $signature = null): string
    {
        $contractName = "uploadedFile/" . Str::random(10) . ".pdf";
        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('fr');
        $budgetInLetter = $numberTransformer->toWords($total_budget);
        $context = [
            'budgetInLetter' => $budgetInLetter, 'total_budget' => $total_budget,
            "recurringOrder" => $recurringOrder, "user" => $recurringOrder->user,
            "acceptedPropositions" => $proposition
        ];
        !is_null($signature) ? $context['signature'] = $signature : null;

        GeneratePDF::dispatch('customer_contract', $context, $contractName);
        return $contractName;
    }

    public function generateEmployeeContract(RecurringOrder $recurringOrder, Proposition $proposition): string
    {
        $employeeContractName = "uploadedFile/" . Str::random(10) . ".pdf";
        $proposition->load('employee');
        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('fr');
        $salaryInLetter = $numberTransformer->toWords($proposition->salary);
        $employeeContractContext = [
            'salaryInLetter' => $salaryInLetter,
            'employee' => $proposition->employee,
            'recurringOrder' => $recurringOrder,
            'proposition' => $proposition,
        ];
        !$recurringOrder->cnsss ?
            GeneratePDF::dispatch('prestataires_contract', $employeeContractContext, $employeeContractName) :
            GeneratePDF::dispatch('employees_contract', $employeeContractContext, $employeeContractName);
        return $employeeContractName;
    }


    public function saveBase64Upload($base64String): string
{

    $imageData = base64_decode($base64String);

        $name = Str::random(10) . '.' . 'png';

        $tempPath = 'temp/' . $name;
        Storage::disk('local')->put($tempPath, $imageData);

        $s3FilePath = 'uploadedFile/' . $name;

        UploadFileToS3::dispatch(storage_path('app/' . $tempPath), $s3FilePath);



    return $s3FilePath;
}

public function generateAndGetAvenantUrl($recurringOrder, $propositionsAccepted, $total_budget, $clientSignaturePath = null)
{
    $numberToWords = new NumberToWords();
    $numberTransformer = $numberToWords->getNumberTransformer('fr');

    $avenantContratFileName = Str::random(10) . "-contrat-avenant-ylomi-direct-de-" . $recurringOrder->user->first_name . "-" . $recurringOrder->user->last_name . ".pdf";
    $salarys = [];
    foreach ($propositionsAccepted as $proposition) {
        array_push($salarys, Helper::getBudgetPerEmployee($proposition->employee_salary, $proposition->recurringOrder->cnss)['customer_budget']);
    }
    $salarys = json_encode($salarys);
    $avenantContractContext = [
        'package' => $recurringOrder,
        'numberTransformer' => $numberTransformer,
        'total_budget' => $total_budget,
        'clientSignaturePath' => $clientSignaturePath,
        'propositionsAccepted' => $propositionsAccepted,
        'salarys' => $salarys,
    ];
    $pdf = is_null($clientSignaturePath) ?
        GeneratePDF::dispatch('ylomi-direct-customer-avenant-contract',$avenantContractContext, $avenantContratFileName) :
        GeneratePDF::dispatch('ylomi-direct-customer-avenant-contract',$avenantContractContext, $avenantContratFileName);


    $directPDFAvenantUrl = $this->s3FileUrl($avenantContratFileName);

    $recurringOrder->avenant_contrat_file_name = $avenantContratFileName;
    $recurringOrder->save();

    return $directPDFAvenantUrl;
}

}

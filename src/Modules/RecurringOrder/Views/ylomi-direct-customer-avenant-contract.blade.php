<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>YLOMI DIRECT - CONTRAT DE PRESTATION</title>
</head>

<body>

    <h2>
        AVENANT CONTRAT DE PRESTATION
    </h2>

    <hr style='color: blue;'>

    <h4>
        ENTRE LES SOUSSIGNÉS :
    </h4>

    <p>
        <strong>YLOMI,</strong> SAS au capital de 5000000 FCFA (Cinq Millions) ayant son siège social au Lot 2228
        Kouhounou Cotonou, immeuble CORIS BANK en face du Stade de l’amitié, et immatriculée au Registre du commerce
        RB/COT/18 B 21871 sous le numéro IFU: 3201810333403, représentée par DAH N’GBEKOUNOU Jean Vivien, son CEO,
    </p>

    <p>
        Ci-après dénommée <strong>"Le prestataire"</strong> ;
    </p>

    <p>
        <strong>D’UNE PART, Et</strong>
    </p>

    <p>
        <strong>Mr/Mde</strong> {{ $package->user->first_name }} {{ $package->user->last_name }} Domicilié(e) à
        {{ $propositionsAccepted[0]->recurringOrder->address }}, Tel:
        {{ $package->user->phone_number }}, Adresse email:
        {{ $package->user->email }}
    </p>
    <p>
        Ci-après dénommé <strong>"Le client (e)"</strong>
    </p>
    <p>
        <strong>D’AUTRE PART,</strong>
    </p>
    <p>
        Le présent avenant qui fait partie intégrant du précèdent contrat, met un accent sur l’article ayant connu de
        modification suite à la suspension provisoire du précédent contrat. Tous les autres articles demeurent
        inchangés.
    </p>
    <p>
        <strong>Article 3 :</strong> Rémunération
    </p>
    <p>
        En contrepartie, le client paiera par mois au prestataire la somme de
        <strong>{{ $numberTransformer->toWords($total_budget) }} (FCFA {{ $total_budget }}) tous frais compris.</strong>
    </p>
    <p>
        Cette rémunération est payable par <strong>Mobile money via l'application au plus tard à la fin de chaque
            mois</strong> pour garantir la bonne continuité de la prestation.
    </p>
    <br>
    <p>
        Il faut noter que des frais de placement de <strong>FCFA 10 000</strong> sont exigibles et payable via
        l’application avant toute démarche de recrutement, formation et mise à disposition. Ces frais
        deviennent <strong>non remboursables</strong> dès lors que des démarches de recrutement sont
        engagées suivies de propositions d’employés.
    </p>
    <br>
    <p>
        Des frais de déplacement seront facturés en sus pour les cas où le client souhaiterait que
        les séances d’entretien avec les employés se fassent chez lui ou pour des cas de
        redéploiement (remplacement d’employé(e) pour des cas d’imprévu).
    </p>
    <br>
    Ci-dessous la liste de vos employés en fonction:
    <table style='font-family: arial, sans-serif ;border-collapse: collapse;width: 100% '>
        <tr>
            <th style="border: 1px solid #dddddd;
          text-align: left;
          padding: 8px;">Nom et Prénoms de
                l'employé</th>
            <th style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                Téléphone</th>
            <th style="border: 1px solid #dddddd;
                 text-align: left;
                 padding: 8px;">
                Nationalité</th>
            <th style="border: 1px solid #dddddd;
                  text-align: left;
                  padding: 8px;">
                Poste occupé</th>
            <th style="border: 1px solid #dddddd;
          text-align: left;
          padding: 8px;">Frais de
                prestation</th>
        </tr>
        @foreach ($propositionsAccepted as $index => $proposition)
            <tr>
                <td style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                    {{ $proposition->employee->full_name }}</td>
                <td style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                    {{ $proposition->employee->phone_number }}</td>
                <td style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                    {{ $proposition->employee->nationality }}</td>
                <td style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                    {{ $proposition->recurringOrder->recurringService->name }}</td>
                <td style="border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;">
                    {{ json_decode($salarys)[$index] }}</td>
            </tr>
        @endforeach

    </table>
    <br>
    <p>
        Fait à Cotonou, le
        {{ \Carbon\Carbon::now()->locale('fr_FR')->isoFormat('OD MMMM YYYY') }}
        en deux (02) exemplaires originaux.
    </p>
    <br>
    <table style='width: 100vw;'>
        <tr>
            <th style='text-align: left; font-weight: 400'>Le Client:</th>
            <th style='text-align: center; font-weight: 400'>Le prestataire :</th>
        </tr>
        <tr>
            <td>
                <p>
                    @if (isset($clientSignaturePath))
                        <span style='font-size: 12px;'>
                            Lu et approuvé
                        </span>
                        <br>
                        <img src="{{ $clientSignaturePath }}" height="70" width="90" alt="Client signature">
                    @else
                        <br>
                        <br>
                        <br>
                        <br>
                        <br>
                        <br>
                    @endif
                </p>
                <p>
                    {{ $package->user->first_name }} {{ $package->user->last_name }}
                </p>
            </td>
            <td style='text-align: center;'>
                <br>
                <p>
                    <img src="{{ public_path('images/ylomi_signature-removebg-preview.png') }}" width="100"
                        alt="Ylomi signature">
                </p>
                <p>
                    Jean Vivien DAH N’GBEKOUNOU
                </p>
            </td>
        </tr>
    </table>
</body>

</html>

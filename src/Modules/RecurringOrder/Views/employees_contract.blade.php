<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONTRAT DE TRAVAIL A DUREE DETERMINEE
    </title>
</head>

<body>

    <h2>
        CONTRAT DE TRAVAIL A DUREE DETERMINEE

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
        Ci-après dénommée <strong>"Employeur"</strong>;
    </p>

    <p>
        <strong>D’UNE PART, Et</strong>
    </p>

    <p>
        <strong>Mr/Mme</strong> {{ strtoupper($employee->full_name) }} Domicilié à {{ $employee->address }} Tel:
        {{ $employee->phone_number }},
    </p>

    <p>
        Nationalité : {{ $employee->nationality }} Poste occupé: {{ $recurringOrder->recurringService->name }}
    </p>

    <p>
        Ci-après dénommé <strong>"Employé(e) sous gestion"</strong>
    </p>

    <p>
        <strong>D’AUTRE PART,</strong>
    </p>

    <p>
        <strong>
            Les rapports de collaboration sont entièrement régis par le droit béninois. En l’absence d’une
            réglementation contractuelle spécifique, les dispositions de la directive interne de prestation de YLOMI,
            font foi.
        </strong>
    </p>

    <p>
        <strong>Article 1 :</strong> Durée de la Prestation
    </p>
    <p>
        @if ($proposition->started_date)
            Le présent contrat qui prend effet à compter du
            <strong>{{ \Carbon\Carbon::parse($proposition->started_date)->locale('fr_FR')->isoFormat('OD MMMM YYYY') }}</strong>
            est conclu pour une durée <strong> d'un (01) an
                renouvelable par tacite reconduction</strong>. Une période d’essai d’un
            (01) mois est requise. Au cours de ladite période, l’employeur peut rompre le contrat sans
            indemnité pour faute grave ou insuffisance de travail.
        @else
            Le présent contrat qui prend effet à compter de la date du deploiement de l'employée, est conclu pour une
            durée
            <strong> d'un (01) an
                renouvelable par tacite reconduction</strong>. Une période d’essai d’un
            (01) mois est requise. Au cours de ladite période, l’employeur peut rompre le contrat sans
            indemnité pour faute grave ou insuffisance de travail.
        @endif
    </p>

    <p>
        <strong>Article 2 :</strong> Horaires de travail, absence et retard:
    </p>

    <p>
        L’horaire de travail est celui convenu avec le client et Ylomi soit
        {{ $recurringOrder->intervention_frequency }} jours par semaine avec des
        possibilités de congé de deux (02) jours par mois selon que L’employé(e) soit hébergé(e) ou
        non, et ne doit en aucun cas dépassé les quarante (40) heures de travail par semaine. Toute absence doit être
        justifiée par un certificat médical. Toute absence non justifiée et tout retard récurent pourra faire l’objet de
        sanction voire de licenciement.
    </p>

    <p>
        <strong>Article 3 :</strong> Résiliation du contrat de travail
    </p>

    <p>
        Le présent contrat peut être rompu avant l’échéance du terme par Ylomi sans préavis ni indemnité dès lors que le
        contrat entre Ylomi et son client est rompu. Tout avertissement : (inefficacité au travail, retard répété, vol,
        fautes lourdes etc..) verbal ou écrit à l’endroit du prestataire constitue un préavis et peut conduire à la
        rupture
        du contrat.</strong>
    </p>

    <p>
        <strong>Article 4 :</strong> Lieu d’activité
    </p>

    <p>
        Le lieu de la prestation est le domicile ou lieu de travail du client de Ylomi ou tout autre lieu retenu de
        commun
        accord. L’employé(e) est invité à y être présent exclusivement pour l’exercice de ses activités
        professionnelles.
        L’employé(e) n’est pas autorisé à quitter son poste et ou le lieu de prestation pendant toute la durée de ses
        activités professionnelles (sauf cas de force majeur).

    </p>

    <p>
        <strong>Article 5 :</strong> Secret professionnel/règles déontologiques/clause de non concurrence
    </p>

    <p>
    <ul>
        <li>
            <u>Secret professionnel:</u> L’employé(e) est lié(e) par le secret professionnel relatif à toute information
            ou document dont il (elle) pourrait avoir connaissance au cours de l’exécution du présent contrat et cela
            même après la cessation de ses rapports de service.
        </li>
        <li>
            <u>Règles déontologiques:</u> L’employé(e) est tenu(e) de se conformer aux principes et règles
            déontologiques applicables aux agents de sa catégorie. Toute négligence grave aux obligations de service
            entraînera un licenciement immédiat.
        </li>
        <li>
            <u>Clause de non concurrence:</u> L’employé(e) s’engage à consacrer tout son temps dans les limites des
            règlements en vigueur, au service de l’employeur et s’interdit sans autorisation écrite de celui-ci, même en
            période de repos, tout travail rémunéré ou non dans le même secteur d’activité.
        </li>
    </ul>
    </p>

    <p>
        <strong>Article 6 :</strong> Responsabilité
    </p>

    <p>
        L’employé(e) répond de tout dommage qu’il (elle) causera par négligence grave ou intentionnellement. Le
        matériel, le mobilier et tout objet du client de Ylomi doivent être gardés et entretenir comme il se doit.
        <br>
        Tout manquement aux règles mentionnées ci-dessus ainsi que dans l’intégralité de ce contrat pourra être
        sanctionné d’un avertissement.
        <br>
        Aucun abandon de poste n’est toléré sans en informer la direction <strong>une semaine</strong> à l’avance pour
        accord. Dans tous les cas, tant qu’un remplaçant n’est trouvé par la direction, aucun abandon de poste n’est
        toléré. En cas de défiance, l’employé supportera les dommages par rétention du salaire du mois concerné.
    </p>

    <p>
        <strong>Article 7 :</strong> Rémunération
    </p>

    <p>
        La rémunération mensuelle nette est de <strong>FCFA {{ $proposition->salary }}
            ({{ $salaryInLetter }}).</strong> Le paiement s’effectuera sur place par
        Mobile Money sur
        présentation d’une facture normalisée au plus tard le dix (10) de chaque mois. Dans tous les cas, le paiement
        s’effectuera dès réception du règlement de la facture de prestation du client de Ylomi.

    </p>


    <p>
        <strong>Article 8 :</strong> Règlement des différends

    </p>

    <p>
        En cas de désaccord, les deux parties s’engagent à rechercher d’abord des solutions à l’amiable. Le For est basé
        à
        Cotonou.

    </p>
    <p>
        <strong>Article 9 :</strong> État de santé/assurances sociales/frais médicaux :

    </p>

    <p>
        L’employé(e) est déclaré(e) à la Caisse Nationale de Sécurité Sociale (CNSS) via Ylomi après la période d’essai.
        Les
        parts ouvrières, soit 3,6 % et patronales 19,4 % sont à la charge de l’employeur.

    </p>
    <br>
    <p>
        Fait à Cotonou, le
        {{ is_null($proposition->started_date)
            ? \Carbon\Carbon::now()->locale('fr_FR')->isoFormat('OD MMMM
            YYYY')
            : \Carbon\Carbon::parse($proposition->started_date)->locale('fr_FR')->isoFormat('OD MMMM
            YYYY') }}
        en deux (02) exemplaires originaux.
    </p>

    <br>
    <table style='width: 100vw;'>
        <tr>
            <th style='text-align: left; font-weight: 400'>L'employé(e):</th>
            <th style='text-align: center; font-weight: 400'>L’employeur:</th>
        </tr>
        <tr>
            <td>
                <p>
                    @if (isset($signature))
                        <span style='font-size: 12px;'>
                            Lu et approuvé
                        </span>
                        <br>
                        <img src="{{ $signature }}" height="70" width="90" alt="Employee signature">
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
                    {{ strtoupper($employee->full_name) }}
                    <br>
                    <small>(Signature précédée de la mention «Lu et approuvé») </small>
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

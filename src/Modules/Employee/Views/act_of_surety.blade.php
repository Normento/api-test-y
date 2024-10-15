<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>CAUTION DE TRAVAIL - DEPARTEMENT RH YLOMI</title>

    <style>
        .div-conteneur p {
            word-wrap: break-word;
        }
    </style>
</head>

<body>

<h2>
    CAUTION DE TRAVAIL - DEPARTEMENT RH YLOMI
</h2>

<hr style='color: blue;'>

<div style="text-align: left" class="div-conteneur">
    <p>
        Je soussigné <strong>{{ $surety['full_name'] }}</strong> domicilié à
        <strong>{{ $surety['address'] }}</strong>
    </p>

    <p>
        titulaire de la pièce d'identité N° <strong>{{ $surety['piece_number'] }}</strong> délivré par <strong>{{ $surety['piece_delivered_by'] }}</strong>
    </p>
    <p>  et expire le
        <strong>{{ \Carbon\Carbon::parse($surety['piece_expired_at'])->locale('fr_FR')->isoFormat('OD MMMM YYYY') }}</strong>
        agissant en qualité de (Lien familial
        avec caution) <strong>{{ $surety['family_link'] }}
    </p>
    <p>
        déclare porter caution à Mr/Mme <strong>{{ $employee->full_name }} pour le compte de
            l'entreprise <strong>YLOMI</strong>
    </p>
</div>
<br>
<div>
    <p>
        En foi de quoi, le présent acte de cautionnement  est produit pour servir et valoir ce que de droit
    </p>
</div>
<br>
<p>
    Fait à Cotonou, le
    {{ \Carbon\Carbon::now()->locale('fr_FR')->isoFormat('OD MMMM YYYY') }}
</p>
<br>
<table style='width: 100vw;'>
    <tr>
        <th style='text-align: right; font-weight: 400'>{{ $surety['phone_number'] }}</th>
    </tr>
    <tr>

        <td style='text-align: right;'>

            <p>
                @if (isset($surety['signature']))
                <strong style='font-size: 12px;'>
                    Lu et approuvé
                </strong>
                <br>
                <img src="{{ $surety['signature'] }}" height="70" width="90" alt="signature">
                @else

                @endif
            </p>
            <p>
                {{ $surety['full_name'] }}
            </p>
        </td>
    </tr>
</table>
</table>
</body>

</html>

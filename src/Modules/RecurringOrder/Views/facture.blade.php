<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture</title>
</head>
<style>
    * {
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .container {
        width: 700px%;
        margin-left: auto;
        margin-right: auto;
        margin-top: 100px;
        border: solid 1px rgba(157, 154, 154, 0.288);
        padding: 30px;
    }

    img {
        width: 100px;
        margin-top: 20px;
    }

    .grandTableau {
        margin-top: 65px;
    }

    .tableau {
        display: flex;
        justify-content: space-between;
    }

    .sousTitre {
        margin-right: 97px;
    }

    .textStyle {
        font-size: 40px;
        color: rgb(66, 7, 217);
    }

    .titre2 {
        margin-top: 50px;
        text-align: center;
    }

    .titre {
        justify-content: center;
        display: flex;
    }

    p {
        font-size: 22px;
        color: rgb(79, 78, 78);
    }

    h2 {
        text-align: center;
    }
</style>

<body style="background-color: white">
    <div>
        <p>
            Bonjour Mr/Mme {{ $user->first_name }} {{ $user->last_name }}
        </p>
    </div>
    <div class="container">
        <div class="titre">
            <div>
                <img src="{{ asset('images/ic_launcher.png') }}" alt="">
            </div>

            <div>
                <h2>Votre reçu Ylomi</h2>
                <span class="sousTitre">{{ \Carbon\Carbon::now()->locale('fr_FR')->isoFormat('OD MMMM YYYY') }} </span>
            </div>
        </div>
        <div class="titre2">
            <span class="textStyle">{{ $data['amount'] }} FCFA</span>
            <p>Votre paiement a été effectué</p>
            <p>avec succès et a été reçu pour {{ $data['subject'] }}</p>
        </div>
        <div class="grandTableau">
            <div class="tableau">
                <div>
                    <p>Montant payé</p>
                </div>
                <div>
                    <p> {{ $data['amount'] }} Fcfa</p>
                </div>
            </div>
            <hr />
            <div class="tableau">
                <div>
                    <p>Frais prélevés</p>
                </div>
                <div>
                    <p>{{ $data['transfer_fee'] }} Fcfa</p>
                </div>
            </div>
            <hr />
            <div class="tableau">
                <div>
                    <p>Méthode de paiement</p>
                </div>
                <div>
                    <p>{{ $data['mobile_money'] }} </p>
                </div>
            </div>
            <hr />
            <div class="tableau">
                <div>
                    <p>Référence de la transaction</p>
                </div>
                <div>
                    <p> {{ $data['transref'] }} </p>
                </div>
            </div>
            <hr />
        </div>

    </div>
    <p>
        NB: En cas de besoin d'une facture normalisée, veuillez nous contacter au <strong>+229 67283838</strong>
    </p>
    <p style="font-size: 14px; line-height: 24px; margin-top: 6px; margin-bottom: 20px;">
        Cordialement
        <br>L'équipe Ylomi
    </p>
</body>

</html>

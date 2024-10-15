<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    </style>
    <title>Certificat de Formation</title>
</head>

<body
    style="border-style: double; border-color: #FFCC31; overflow-y: hidden; max-width: 100%; border-width: 8px; border-radius: 0.25rem; max-height: max-content; padding: 5rem; ">
<section>
    <div style="justify-content: center;">
        <img style="width: 150px; height:50px; margin-bottom: 1.50rem; margin-left: auto;
margin-right: auto;"
             src="{{ asset('images/logo.png') }}" alt="ylomi">
        <h1
            style="text-align: center; margin-left: 10rem; /* 320px */
margin-right: 10rem; font-size: 30px;
line-height: 2.5rem; font-weight: 700;">
            Académie Ylomi : Centre de formation et de recyclage du personnel de maisson</h1>
    </div>
</section>
<section class="mt-16 px-12 ">
    <p style="font-size: 18px; padding-left: 2rem;">
        Nous soussignés <strong>Ylomi, SAS</strong>  au capital de <strong> FCFA 5 000 000</strong> représentée par <strong>Mr Jean Vivien DAH N'GBEKOUNOU</strong>  CEO,
        attestons par la présente que <strong>Mr/Mme {{ $employee->full_name }}</strong>  à suivi(e) avec succès {{
        $training->is_recycling ? 'un recyclage' : 'une formation' }}
        dans le domaine de <strong>{{ $service->name }}</strong>  sur la période allant <strong>du  {{ $training->start_date }} au {{ $training->end_date }}</strong> .
        Mr/Mme {{ $employee->full_name }} à également suivi(e) des cours de <strong>déontologie (savoir vivre et savoir être)</strong> .
    </p>

    <p style="text-align: justify; font-size: 18px; padding-left: 2rem; margin-top: 1.25rem;">
        En foi de quoi, la présente attestation lui est délivrée pour servir ce que de droit.</p>
</section>
<section class="mt-16 px-20 py-1 h-full">
    <div
        style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; place-items: stretch;">
        <div style="float: left;">
            <div style="height: 6rem; margin-bottom: 0.5rem;"></div>
            <span style="font-size: 18px; /* 24px */
line-height: 2rem;">Fait à Cotonou, le
                    {{ \Carbon\Carbon::now()->locale('fr_FR')->isoFormat('OD MMMM YYYY') }}</span>
        </div>
        <div style="float: right;">
            <div style="height: 6rem; margin-bottom: 0.5rem;">
                <img src="{{ public_path('images/ylomi_signature-removebg-preview.png') }}" width="100"
                     alt="Ylomi signature">
            </div>
            <div style="font-size: 18px; 	text-align: right;">Jean Vivien DAH N'GBEKOUNOU</div>
        </div>
    </div>
</section>
</body>

</html>

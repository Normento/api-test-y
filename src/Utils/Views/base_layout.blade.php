<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Ylomi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins';
        }
    </style>
</head>
<body>
<section class="max-w-2xl px-6 py-8 mx-auto bg-white ">
    <header>
        <a href="#">
            <img class="w-auto h-7 sm:h-8" src="https://merakiui.com/images/full-logo.svg" alt="">
        </a>
    </header>

    <main class="mt-8">
        @yield('content')

        <p class="mt-8 text-gray-600 text-center ">
            Cordialement, <br>
            L'équipe l'Ylomi
        </p>
    </main>

    <footer class="mt-8">
        <p class="mt-3 text-gray-500 text-center ">©
            <script> document.write((new Date()).getFullYear())</script>
            tout droit réservé. <br>Souriez, On s'occupe de tout !.
        </p>
    </footer>
</section>
</body>
</html>

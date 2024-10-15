<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Erreur</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

    <div class="py-2 bg-gray-300  dark:bg-black">
        <div class="container ">
          <!-- BEGIN: Error Page -->
          <div
            class="flex flex-col items-center justify-center h-screen text-center error-page lg:flex-row lg:text-left"
          >
            <div class="-intro-x lg:mr-20">
              <img
                alt="error-404"
                class="w-[450px] h-48 lg:h-auto" src="{{asset('images/error-illustration.svg')}}"

              />
            </div>
            <div class="mt-10 text-black lg:mt-0">
              <div class="font-medium intro-x text-8xl">401</div>
              <div class="mt-5 text-xl font-medium intro-x lg:text-3xl">
               Non autorisé
              </div>
              <div class="mt-3 text-lg intro-x">
                Oops! 😖  Vous n'êtes pas autorisé à accéder à cette page.
              </div>

            </div>
          </div>
          <!-- END: Error Page -->
        </div>
      </div>

</body>
</html>


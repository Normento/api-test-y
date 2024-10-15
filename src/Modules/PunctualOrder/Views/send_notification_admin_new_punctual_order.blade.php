@extends('base_layout')
@section('content')
<div class="divide-muted-200 dark:divide-muted-700 grid divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0 ">
    <div>
        <div class="flex flex-col p-8 justify-center items-center">
            <div class="w-[80px] h-[80px] bg-gray-400 rounded-full">
                <img size="xl" class="w-[80px] h-[80px] object-cover rounded-full"
                     src="../../../public/images/ic_launcher.png"/>
            </div>
            @if (!is_null($user))
            <div class="mx-auto mb-4 max-w-xs text-center">
                <div as="h2" size="md" weight="medium" class="mt-4 text-sm">

                            <span class="font-bold text-sm text-[#003399]"> {{ $user->full_name }}
                            </span>
                    vient de lancer
                    <span class="font-bold text-sm text-gray-700"> une commande ponctuelle.</span>
                </div>
            </div>
            @endif
            <div class="mx-auto ">
                <div elevated class="w-full px-6 py-4 border border-gray-200">
                    <div as="h3" size="xs" weight="medium" class="text-md font-bold ">
                        Description
                    </div>
                    <p size="xs" class="mt-2 text-sm text-justify leading-relaxed">
                        {{ $order->description }}
                    </p>
                </div>
                <div class="text-center mt-4">
                    <a href="/"
                       class="w-full block bg-[#003399] p-3 rounded-lg text-sm text-white font-bold">Traiter
                        la
                        demande</a>
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="flex flex-col p-8">
            <div tag="h2" size="md" weight="medium" class="mt-4 text-md font-bold text-center">
                Autres informations
            </div>
            <span size="xs" class="mt-2 text-center text-sm">
                    Quelque informations primordiales sur la nouvelle commande.
                </span>

            <div class="mt-6">
                <ul class="space-y-6">
                    <li class="flex gap-3 px-5">
                        <div
                            class="border-muted-200  flex h-6 w-6 items-center justify-center rounded-full border bg-[#003399] shadow-xl">
                            <i class="fa-solid fa-check text-white"></i>
                        </div>
                        <div>
                            <div as="h3" size="sm" weight="medium" class="text-md text-slate-500">
                                Service
                            </div>
                            <span size="xs" class="text-sm max-w-[210px] ">
                                    {{ $order->service->name }}
                                </span>
                        </div>
                    </li>
                    <li class="flex gap-3 px-5">
                        <div
                            class="border-muted-200  flex h-6 w-6 items-center justify-center rounded-full border bg-[#003399] shadow-xl">
                            <i class="fa-solid fa-check text-white"></i>
                        </div>
                        <div>
                            <div as="h3" size="sm" weight="medium" class="text-md text-slate-500">
                                Budget
                            </div>
                            <span size="xs" class="text-sm max-w-[210px]">
                                    {{ $order->budget }} fcfa
                                </span>
                        </div>
                    </li>
                    <li class="flex gap-3 px-5">
                        <div
                            class="border-muted-200  flex h-6 w-6 items-center justify-center rounded-full border bg-[#003399] shadow-xl">
                            <i class="fa-solid fa-check text-white"></i>
                        </div>
                        <div>
                            <div as="h3" size="sm" weight="medium" class="text-md text-slate-500">
                                Date souhaité
                            </div>
                            <span size="xs" class="text-sm max-w-[210px]">
                                    14 février 2024
                                </span>
                        </div>
                    </li>
                    <li class="flex gap-3 px-5">
                        <div
                            class="border-muted-200  flex h-6 w-6 items-center justify-center rounded-full border bg-[#003399] shadow-xl">
                            <i class="fa-solid fa-check text-white"></i>
                        </div>
                        <div>
                            <div as="h3" size="sm" weight="medium" class="text-md text-slate-500">
                                Adresse
                            </div>
                            <span size="xs" class="max-w-[210px] text-sm">
                                    {{ $order->address }}
                                </span>
                        </div>
                    </li>
                </ul>
            </div>
            <p class="mt-8 text-gray-600 text-xs text-center ">
                Cordialement, <br>
                L'équipe l'Ylomi
            </p>
            <div class="mt-2 text-center">
                <p class="text-xs text-gray-600 "><span class="text-xs text-gray-600 ">Copyrights
                            {{ $year }} © ylomi</span></p>
            </div>
        </div>
    </div>
</div>


@endsection

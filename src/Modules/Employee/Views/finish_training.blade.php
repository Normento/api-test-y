@extends('base_layout')
@section('content')
<h2 class="text-gray-700">Hi {{$admin->full_name}},</h2>

<p class="mt-2 leading-loose text-gray-600">
    L'employé {{$employee->full_name}} vient de terminer sa formation
</p>


@endsection


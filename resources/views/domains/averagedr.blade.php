@extends('layouts.app')

@section('css')
<style>
    #stocklink button {
        margin-bottom: 5px;
    }
</style>
@endsection

@section('content')
<section class="container-fluid">
    <div class="row justify-content-center">
        <h1 class="mt-5">Price for DR</h1>
    </div>
    <div class="row justify-content-center">
            <table class="table table-bordered table-hover table-sm" style="width: 250px">
                    <thead class="thead-light">
                        <tr>
                            <th>DR</th>
                            <th>Цена (рубли)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dr as $dr => $value)
                        <tr>
                            <td>{{ $dr }}</td>
                            @if ($value != "0")
                                <td>{{ $value }}</td>
                            @else
                                <td></td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
    </div>>


</section>
@endsection
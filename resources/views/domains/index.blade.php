@extends('layouts.app')

+@section('css')
    <style>
        #stocklink button {
            margin-bottom: 5px;
        }
    </style>
    +@endsection

@section('content')
<section class="container-fluid">
    <div class="row">
        <h1>Seo Domains</h1>
        <div class="col-md-12">
            {!! Form::open(array('method' => 'GET', 'route' => ['domains'], 'class' => 'form form-row')) !!}
            <div class="col-md-6">
                <div class="row">
                    <div class="control-group col-md-3">
                        {!! Form::label('resource[miralinks]', 'Miralinks', [ 'class' => 'control-label' ]) !!}
                        <div class="controls">
                            {!! Form::checkbox('resource[miralinks]', 'value', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="control-group col-md-3">
                        {!! Form::label('resource[sape]', 'Sape', [ 'class' => 'control-label' ]) !!}
                        <div class="controls">
                            {!! Form::checkbox('resource[sape]', 'value', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="control-group col-md-3">
                        {!! Form::label('resource[rotapost]', 'Rotapost', [ 'class' => 'control-label' ]) !!}
                        <div class="controls">
                            {!! Form::checkbox('resource[rotapost]', 'value', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="control-group col-md-3">
                        {!! Form::label('resource[gogetlinks]', 'Gogetlinks', [ 'class' => 'control-label' ]) !!}
                        <div class="controls">
                            {!! Form::checkbox('resource[gogetlinks]', 'value', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1">{!! Form::text('price_from', null, ['class' => 'form-control', 'placeholder' => 'Цена от']); !!}</div>
            <div class="col-md-1">{!! Form::text('price_to', null, ['class' => 'form-control', 'placeholder' => 'Цена до']); !!}</div>
            <div class="col-md-2">{!! Form::text('theme', null, ['class' => 'form-control', 'placeholder' => 'Тематика']); !!}</div>
            <div class="col-md-2"> {!! Form::submit('Поиск', array('class'=>'btn btn-primary')) !!}</div>

            {!! Form::close() !!}
        </div>
        <table class="table table-hover">
             <thead class="thead-light">
                 <tr>
                    <th>№</th>
                    <th>Url</th>
                     <th style="width: 200px">Цены</th>
                     <th style="width: 200px">Цена размещения с написанием</th>
                    <th>Тематика</th>
                    <th>Описание</th>
                    <th>Регион</th>
                    <th>Индекс страниц Google</th>
                    <th>DR (ahrefs)</th>
                    <th>Вх/Исх домены (ahrefs)</th>
                    <th>DA (Moz)</th>
                    <th>MajesticTF</th>
                    <th>MajesticCF</th>
                    <th>Трафик LiveInternet</th>
                    <th>Трафик SimilarWeb</th>
                    <th>Язык</th>
                    <th>Кол-во размещаемых ссылок</th>
                    <th>Дата добавления в биржу</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $key => $value)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><a href="http://{{$key}}">{{$key}}</a></td>
                    <td id="stocklink">
                        @foreach ($value['placement_price'] as $source => $price)
                            @switch($source)
                                @case('miralinks')
                                <img src="https://www.miralinks.ru/favicon.ico">
                                @break

                                @case('gogetlinks')
                                <img src="https://www.gogetlinks.net/favicon.ico">

                                @break
                                @case('rotapost')
                                <img src="https://www.rotapost.ru/i/favicon.ico">
                                @break

                                @case('sape')
                                <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico">
                                @break

                                @default

                            @endswitch

                                @if(isset($value['site_id']) && $value['site_id'])
                                    <a href="https://www.miralinks.ru/catalog/profileView/{{ $value['site_id'] }}" target="_blank">{{$source}} - {{$price}}</a>
                                @else
                                    {{$source}} - {{$price}}
                                @endif

                            <br />
                        @endforeach
                    </td>
                    <td>
                        @foreach ($value['writing_price'] as $source => $price)
                            @switch($source)
                                @case('miralinks')
                                <img src="https://www.miralinks.ru/favicon.ico">
                                @break

                                @case('gogetlinks')
                                <img src="https://www.gogetlinks.net/favicon.ico">

                                @break
                                @case('rotapost')
                                <img src="https://www.rotapost.ru/i/favicon.ico">
                                @break

                                @case('sape')
                                <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico">
                                @break

                                @default

                            @endswitch

                            {{$source}} - {{$price}}<br />
                        @endforeach
                    </td>
                    <td>{{$value['theme']}}</td>
                    <td>{{$value['desc']}}</td>
                    <td>{{$value['region']}}</td>
                    <td>{{$value['google_index']}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center">{{$value['lang']}}</td>
                    <td class="text-center">{{$value['links']}}</td>
                    <td class="text-center">{{$value['created_at']}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="container">
        {{ $domains->appends(request()->input())->links("pagination::bootstrap-4") }}
    </div>

</section>
@endsection
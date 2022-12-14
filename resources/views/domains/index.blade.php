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
    <div class="row">
        <h1>Seo Domains</h1>
        <div class="col-md-12">
            {!! Form::open(array('method' => 'GET', 'route' => ['domains'], 'class' => 'form form-row')) !!}
            {{--
            <div class="col-md-2 mb-4">{!! Form::text('price_from', request()->get('price_from'), ['class' => 'form-control', 'placeholder' => 'Цена от']); !!}</div>
            <div class="col-md-2 mb-4">{!! Form::text('price_to', request()->get('price_to'), ['class' => 'form-control', 'placeholder' => 'Цена до']); !!}</div>
            <div class="col-md-2 mb-4">{!! Form::text('theme', request()->get('theme'), ['class' => 'form-control', 'placeholder' => 'Тематика']); !!}</div>
            <div class="col-md-2 mb-4"> {!! Form::submit('Поиск', ['class'=>'btn btn-primary']) !!}</div>
            --}}
            <div class="col-md-10">
                <div class="row justify-content-md-center">
                    @isset ($link_stocks)
                        <div class="col-md-3">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th scope="col">Биржа</th>
                                    <th scope="col">Доменов</th>
                                    <th scope="col">Обновлено</th>
                                </tr>
                                </thead>
                            <tbody>
                            @foreach ($link_stocks as $link_stock_name => $link_stock_data)
                                <tr>
                                    <th scope="row">{{$link_stock_name}}</th>
                                    <td>{{$link_stock_data['count']}}</td>
                                    <td>{{$link_stock_data['update_date']}}</td>
                                </tr>
                            @endforeach
                            </table>
                            <hr />
                            <div class="col-md-12"> {!! Form::submit('Скачать базу из '.$domains_count.' доменов в XLS ', ['class'=>'btn btn-primary', 'name' => 'export']) !!}</div>
                        </div>

                    @endisset
                </div>
            </div>

            {!! Form::close() !!}
        </div>
        {{--
        <table class="table table-hover">
            <thead class="thead-light">
                <tr>
                    <th>№</th>
                    <th>Url</th>
                    <th style="width: 200px">Цены</th>
                    <th style="width: 200px">Цена размещения с написанием</th>
                    <th>DR (ahrefs)</th>
                    <th>Вх/Исх домены (ahrefs)</th>
                    <th>Ahrefs positions top10</th>
                    <th>Ahrefs traffic top100</th>
                    <th>Тематика</th>
                    <th>Регион</th>
                    <th>Индекс страниц Google</th>
                    <th>Кол-во размещаемых ссылок</th>
                    <th>Язык</th>
                    <th>MajesticTF</th>
                    <th>MajesticCF</th>
                    <th>Описание</th>
                    <th>Дата добавления в биржу</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($domains as $key => $value)
                <tr>
                    <td>{{ $value->id }}</td>
                    <td><a href="http://{{ $value->url }}">{{ $value->url }}</a></td>
                    <td id="stocklink">
                        @if($value->miralinks && $value->miralinks->placement_price)
                        <img src="https://www.miralinks.ru/favicon.ico">
                        @if(isset($value->miralinks->site_id))
                        <a href="https://www.miralinks.ru/catalog/profileView/{{ $value->miralinks->site_id }}" target="_blank">Miralinks - {{$value->miralinks->placement_price}}</a><br>
                        @else
                        Miralinks - {{$value->miralinks->placement_price}}<br>
                        @endif
                        @endif
                        @if($value->rotapost && $value->rotapost->placement_price)
                        <img src="https://www.rotapost.ru/i/favicon.ico"> Rotapost - {{$value->rotapost->placement_price}}<br>
                        @endif
                        @if($value->sape && $value->sape->placement_price)
                        <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico"> Sape - {{$value->sape->placement_price}}<br>
                        @endif
                        @if($value->gogetlinks && $value->gogetlinks->placement_price)
                        <img src="https://www.gogetlinks.net/favicon.ico"> Gogetlinks - {{$value->gogetlinks->placement_price}}<br>
                        @endif
                        @if($value->prnews && $value->prnews->price)
                        <img style="width:16px" src="https://www.prnews.ru/favicon.ico"> PR News - {{$value->prnews->price}}
                        @endif
                        @if($value->collaborator && $value->collaborator->price)
                        <img src="https://collaborator.pro/favicon.ico">
                        <a href="https://collaborator.pro/creator/article/view?id={{ $value->collaborator->site_id }}" target="_blank">Collaborator - {{ $value->collaborator->price }}</a><br>
                        @endif
                    </td>
                    <td>
                        @if($value->miralinks && $value->miralinks->writing_price)
                        <img src="https://www.miralinks.ru/favicon.ico"> Miralinks - {{$value->miralinks->writing_price}}<br>
                        @endif
                        @if($value->rotapost && $value->rotapost->writing_price)
                        <img src="https://www.rotapost.ru/i/favicon.ico"> Rotapost - {{$value->rotapost->writing_price}}<br>
                        @endif
                        @if($value->sape && $value->sape->writing_price)
                        <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico"> Sape - {{$value->sape->writing_price}}<br>
                        @endif
                        @if($value->gogetlinks && $value->gogetlinks->writing_price)
                        <img src="https://www.gogetlinks.net/favicon.ico"> Gogetlinks - {{$value->gogetlinks->writing_price}}
                        @endif
                    </td>
                    <td>{{$value->ahrefs_dr ? $value->ahrefs_dr : ''}}</td>
                    <td>{{$value->ahrefs_inlinks ? $value->ahrefs_inlinks : ''}}</td>
                    <td class="ahrefs_positions_top10">{{$value->ahrefs_positions_top10 ? $value->ahrefs_positions_top10 : ''}}</td>
                    <td class="ahrefs_traffic_top10">{{$value->ahrefs_traffic_top100 ? $value->ahrefs_traffic_top100 : ''}}</td>
                    <td>
                    @if ($value->miralinks && $value->miralinks->theme)
                        {{ $value->miralinks->theme  }}
                    @elseif ($value->collaborator && $value->collaborator->theme)
                            {{ $value->collaborator->theme  }}
                    @endif
                    </td>
                    <td>{{$value->miralinks ? $value->miralinks->region : ''}}</td>
                    <td>{{$value->miralinks ? $value->miralinks->google_index : ''}}</td>
                    <td class="text-center">{{$value->miralinks ? $value->miralinks->links : ''}}</td>
                    <td class="text-center">{{$value->miralinks ? $value->miralinks->lang : ''}}</td>
                    <td class="majestic_cf">{{$value->majestic_cf ? $value->majestic_cf : ''}}</td>
                    <td class="majestic_tf">{{$value->majestic_tf ? $value->majestic_tf : ''}}</td>
                    <td>{{$value->miralinks ? $value->miralinks->desc : ''}}</td>
                    <td class="text-center">{{$value->created_at}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        --}}
    </div>
    {{--
    <div class="container">
        {{ $domains->appends(request()->input())->links("pagination::bootstrap-4") }}
    </div>
    --}}

</section>
@endsection